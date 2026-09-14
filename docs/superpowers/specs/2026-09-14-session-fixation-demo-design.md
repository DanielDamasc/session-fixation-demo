# Demo de Ataque de Fixação de Sessão — Design

Data: 2026-09-14

## Objetivo

Aplicação educacional em PHP + HTML, rodando localmente via XAMPP
(`http://localhost/session/`), que simula na prática um ataque de
**fixação de sessão** (session fixation): o atacante define/injeta um
ID de sessão na vítima antes do login; se a aplicação não regenerar o
ID após autenticar, o atacante consegue reutilizar esse mesmo ID para
assumir a sessão autenticada da vítima.

A demo inclui as duas pontas lado a lado — vulnerável e corrigida —
alternáveis por um toggle, para que o mesmo mecanismo sirva tanto para
mostrar o ataque quanto a defesa (`session_regenerate_id()`).

Escopo: uso local, educacional, sem banco de dados, sem dependências
externas além do PHP nativo do XAMPP.

## Arquitetura

Aplicação PHP simples, session handler nativo (arquivo, padrão do
PHP), servida por Apache/XAMPP. Sem framework, sem build step. Estado
de configuração da demo (modo seguro/vulnerável) persistido em um
arquivo de texto simples em `data/mode.txt`, porque atacante e vítima
usam navegadores/cookie-jars diferentes e não podem compartilhar esse
estado via cookie ou `$_SESSION`.

Estrutura de arquivos:

```
session/
├── index.php              # página inicial / explicação da demo
├── attacker.php           # console do atacante
├── login.php              # formulário de login (ponto vulnerável)
├── account.php            # área autenticada ("Minha Conta")
├── logout.php             # destrói a sessão
├── reset.php              # reseta o estado da demo
├── includes/
│   ├── mode.php           # ler/gravar o modo (seguro|vulneravel) em data/mode.txt
│   └── layout.php         # cabeçalho/rodapé HTML compartilhado, estilo simples
└── data/
    └── mode.txt            # estado persistido do toggle (criado em runtime)
```

## Componentes

### `index.php`
Landing page explicando o cenário em 2-3 parágrafos e linkando para
`attacker.php` como ponto de partida. Também explica que o usuário
deve abrir o link malicioso em uma janela anônima/outro navegador para
simular a vítima de verdade (cookie jar separado).

### `attacker.php`
Console do atacante. Responsabilidades:
- Gerar (ou reaproveitar, se já existir na sessão do atacante) um ID
  de sessão fixo, ex.: `ATACANTE-<8 chars aleatórios>`.
- Mostrar o link malicioso pronto: `login.php?PHPSESSID=<id fixo>`.
- Toggle "Modo seguro" (checkbox num form que faz POST para o próprio
  `attacker.php`), que grava `seguro` ou `vulneravel` em
  `data/mode.txt` via `includes/mode.php`.
- Botão/link "Verificar se a vítima logou": aponta para
  `attacker.php?verificar=1&sid=<id fixo>`, que quando acessado seta o
  cookie de sessão do navegador do atacante para esse ID
  (`setcookie(session_name(), $sid, ...)`) e redireciona para
  `account.php`.
- Exibir o PHPSESSID atual do próprio navegador do atacante, para
  fins didáticos.
- Botão "Gerar novo ataque": chama `reset.php` e recarrega a página
  com um novo ID fixo.

### `login.php`
Ponto vulnerável central.
- `GET`: renderiza formulário de usuário/senha. Antes de
  `session_start()`, se existir `$_GET['PHPSESSID']`, chama
  `session_id($_GET['PHPSESSID'])` — isso é o que simula a aceitação
  do ID de sessão vindo de fora (fixação), independente da config de
  `session.use_trans_sid` do PHP, para a demo funcionar de forma
  previsível em qualquer ambiente.
- Exibe o PHPSESSID atual e o modo vigente (lido de
  `includes/mode.php`), para transparência.
- `POST`: valida credenciais fixas (usuário `vitima`, senha
  `senha123`, hardcoded — sem cadastro, é só pra demo). Se válidas:
  - Se modo == `seguro`: chama `session_regenerate_id(true)` **antes**
    de gravar `$_SESSION['user']`. Isso invalida o ID fixado pelo
    atacante.
  - Se modo == `vulneravel`: não regenera; grava `$_SESSION['user']`
    direto no ID recebido.
  - Redireciona para `account.php`.
- Credenciais inválidas: mostra erro, sem alterar sessão.

### `account.php`
- Se `$_SESSION['user']` não estiver setado, redireciona para
  `login.php`.
- Se estiver, mostra dados fake da vítima (nome, "última atividade")
  e o PHPSESSID atual em destaque visual, para o usuário comparar com
  o ID mostrado em `attacker.php`.

### `logout.php`
- Destrói a sessão (`session_unset()`, `session_destroy()`, apaga o
  cookie) e redireciona para `index.php`.

### `reset.php`
- Restaura `data/mode.txt` para o padrão (`vulneravel`) e redireciona
  de volta para `attacker.php` (que então gera um novo ID fixo, já que
  o ID do atacante fica na própria sessão dele, não em arquivo).

### `includes/mode.php`
- Duas funções: `getModo(): string` (lê `data/mode.txt`, default
  `vulneravel` se arquivo não existir) e `setModo(string $modo): void`
  (grava `seguro` ou `vulneravel`).

### `includes/layout.php`
- Helpers `render_header($titulo)` / `render_footer()` para HTML
  consistente (CSS inline simples, sem framework), incluindo uma
  faixa fixa mostrando "PHPSESSID atual: X" e o modo vigente em todas
  as páginas, para reforço didático constante.

## Fluxo de dados (cenário vulnerável)

1. Atacante abre `attacker.php` → gera ID fixo `ATACANTE-abc123`,
   deixa modo em "vulnerável", copia o link malicioso.
2. Atacante manda o link pra "vítima" (na prática, o próprio usuário
   abre em janela anônima): `login.php?PHPSESSID=ATACANTE-abc123`.
3. `login.php` adota esse ID antes de `session_start()`. Vítima
   preenche `vitima` / `senha123` e envia o form.
4. Como modo é "vulnerável", `login.php` NÃO regenera o ID — grava
   `$_SESSION['user']` no ID `ATACANTE-abc123` mesmo.
5. Vítima é redirecionada para `account.php`, autenticada.
6. Atacante clica "Verificar se a vítima logou" em `attacker.php` →
   seu navegador adota o cookie `ATACANTE-abc123` e acessa
   `account.php` → cai autenticado como a vítima.

No cenário seguro, o passo 4 regenera o ID, então no passo 6 o
atacante cai sem sessão válida (`account.php` redireciona pra login).

## Tratamento de erros / casos-limite

- `account.php` sem `$_SESSION['user']`: redireciona para `login.php`
  (comportamento igual em ambos os modos).
- `login.php` sem `PHPSESSID` na URL: funciona como login normal (PHP
  gera seu próprio ID), sem afetar a demo.
- `data/mode.txt` ausente: tratado como modo padrão `vulneravel`
  (arquivo é criado na primeira gravação via toggle).
- Comentários no código apontando explicitamente a linha vulnerável
  (aceitar `PHPSESSID` da URL sem revalidar) e a linha de defesa
  (`session_regenerate_id(true)`), para reforço didático — não é
  código de produção.

## Teste

Validação manual (sem suíte automatizada, dado o caráter didático e
interativo):

1. Modo vulnerável: seguir o fluxo completo com duas janelas de
   navegador (normal = atacante, anônima = vítima) e confirmar que o
   atacante consegue ver a conta da vítima após o "Verificar".
2. Modo seguro: repetir o fluxo e confirmar que o atacante NÃO
   consegue mais acessar a conta após o login da vítima (é redirecionado
   para login).
3. Conferir que `logout.php` limpa a sessão corretamente e que
   `reset.php` restaura o estado da demo para começar de novo.
