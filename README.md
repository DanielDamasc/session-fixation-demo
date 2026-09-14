# Demo: Ataque de Fixação de Sessão (Session Fixation)

Aplicação PHP/HTML educacional que simula, na prática, um **ataque de
fixação de sessão**: o atacante escolhe de antemão o Session ID que a
vítima vai usar e, se a aplicação não gerar um novo ID após o login,
consegue reaproveitar esse mesmo ID para assumir a sessão autenticada
dela.

O projeto inclui as duas pontas lado a lado, alternáveis por um toggle:

- **Modo vulnerável** — o ID de sessão não muda após o login.
- **Modo seguro** — o login chama `session_regenerate_id(true)`,
  invalidando qualquer ID fixado antes da autenticação.

> Uso local e didático apenas. Credenciais fixas (`vitima` /
> `senha123`), sem banco de dados, sem validações de produção.

## Requisitos

- XAMPP (Apache + PHP) instalado, com este projeto em `htdocs/session`.
- PHP 7.3+ (usa `setcookie()` com array de opções).

## Como rodar

1. Inicie o Apache no painel do XAMPP.
2. Acesse `http://localhost/session/` no navegador.

## Estrutura do projeto

```
session/
├── index.php              # Página inicial com o passo a passo da demo
├── attacker.php           # Console do atacante
├── login.php              # Formulário de login (ponto vulnerável)
├── account.php            # Área autenticada ("Minha Conta")
├── logout.php             # Encerra a sessão
├── reset.php              # Reseta o estado da demo
├── includes/
│   ├── mode.php           # Lê/grava o modo (seguro | vulneravel)
│   └── layout.php         # Cabeçalho/rodapé HTML compartilhado
└── data/
    └── mode.txt            # Estado do toggle, criado em runtime
```

## Passo a passo do ataque

Como atacante e vítima precisam de cookies independentes, use **duas
janelas de navegador separadas** (ex.: uma normal e uma anônima):

1. Na janela normal, abra o **Console do Atacante**
   (`attacker.php`). Ele gera um Session ID fixo (ex.:
   `ATACANTE-abc123`) e monta o link malicioso:
   ```
   http://localhost/session/login.php?PHPSESSID=ATACANTE-abc123
   ```
2. Copie esse link e abra numa **janela anônima** — é você "no papel
   da vítima".
3. Faça login normalmente (`vitima` / `senha123`). A página de login
   avisa que o Session ID veio da URL.
4. Volte para a janela normal (atacante) e clique em **"Verificar se
   a vítima logou"**.
   - **Modo vulnerável** (padrão): o atacante cai em `account.php` já
     autenticado como a vítima — o Session ID nunca mudou.
   - **Modo seguro**: ligue o toggle "Modo seguro" no console do
     atacante *antes* do passo 2 e repita o fluxo. Como o login
     regenera o ID, o atacante é redirecionado para a tela de login —
     o ID que ele fixou não vale mais nada.

Use **"Gerar novo ataque"** para reiniciar a demo (novo ID fixo e modo
voltando para vulnerável).

## Onde está o problema e a defesa no código

- **Vulnerabilidade** ([login.php](login.php)): antes de `session_start()`,
  o código adota um `PHPSESSID` vindo da URL (`session_id($_GET['PHPSESSID'])`)
  sem questionar sua origem — é isso que permite a fixação.
- **Defesa** ([login.php](login.php)): logo após validar as credenciais,
  se o modo seguro estiver ativo, `session_regenerate_id(true)` troca o
  ID da sessão e descarta o antigo, tornando inútil qualquer ID fixado
  previamente pelo atacante.

## Por que isso importa em apps reais

Aplicações vulneráveis normalmente não pegam o Session ID direto da
URL como aqui (isso foi feito só para a demo funcionar de forma
previsível em qualquer ambiente) — o vetor real costuma ser
`session.use_trans_sid` habilitado, um cookie sem os atributos corretos,
ou reaproveitamento do mesmo ID entre páginas não autenticadas e
autenticadas. A correção é sempre a mesma: **regenerar o Session ID
sempre que o nível de privilégio do usuário mudar** (login, logout,
elevação de permissão).
