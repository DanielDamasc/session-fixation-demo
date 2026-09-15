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

- PHP 7.3+ (usa `setcookie()` com array de opções) com o servidor
  embutido (`php -S`), ou XAMPP/Apache se preferir.

## Como rodar

O projeto usa um front controller único (`api/index.php`), então rode o
servidor embutido do PHP apontando para ele como *router script*:

```
php -S localhost:8000 -t api api/index.php
```

Acesse `http://localhost:8000/` no navegador.

> Se preferir XAMPP/Apache, aponte o document root para `api/` e
> configure um `.htaccess` com `mod_rewrite` reescrevendo todas as
> requisições para `index.php` (mesmo padrão usado no `vercel.json`).

## Deploy na Vercel

A Vercel não tem runtime oficial de PHP, então o projeto usa o runtime
comunitário [`vercel-php`](https://github.com/vercel-community/php),
configurado em [vercel.json](vercel.json).

1. Instale a CLI (`npm i -g vercel`) e rode `vercel` na raiz do projeto,
   ou importe o repositório em vercel.com.
2. Nenhuma variável de ambiente é necessária.
3. A Vercel exige que os arquivos executados como Serverless Functions
   fiquem dentro de uma pasta `api/` — por isso todo o código PHP está
   em [api/](api/).
4. **Todas as rotas passam por uma única função** (`api/index.php`,
   veja `rewrites` no `vercel.json`), que despacha internamente para
   `api/pages/*.php` conforme o caminho da requisição. Isso é
   necessário porque, na Vercel, cada arquivo PHP diferente casado por
   `functions` vira uma Serverless Function **isolada, com seu próprio
   `/tmp`**. Se `login.php` e `account.php` fossem funções separadas
   (como numa primeira tentativa deste projeto), a sessão gravada por
   uma nunca seria lida pela outra — o login simplesmente não
   funcionava em produção. Consolidando tudo numa função só,
   requisições sequenciais do mesmo visitante tendem a reaproveitar o
   mesmo container "quente" e, portanto, compartilhar o `/tmp` onde o
   PHP guarda as sessões.

**Limitação importante:** mesmo com uma função só, containers
serverless são efêmeros e a Vercel pode escalar para múltiplas
instâncias sob carga concorrente — sem garantia de reaproveitar o
mesmo `/tmp` entre requisições. Por isso:
- [api/includes/mode.php](api/includes/mode.php) grava o toggle "modo
  seguro" em `sys_get_temp_dir()` (o filesystem do deploy em si é
  somente leitura).
- Em uso individual e sequencial (como a demo pretende), isso funciona
  bem na prática. Sob tráfego concorrente real, o estado (toggle e
  sessões PHP) pode ocasionalmente não ser compartilhado. Para uma
  aplicação real isso pediria um armazenamento externo (Redis, banco,
  Vercel KV); para esta demo didática, o trade-off foi mantido simples
  de propósito.

## Estrutura do projeto

```
session/
├── vercel.json            # Config do deploy na Vercel (runtime vercel-php)
└── api/                   # Serverless Functions (exigido pela Vercel)
    ├── index.php          # Front controller: roteia pelo REQUEST_URI
    ├── includes/
    │   ├── mode.php       # Lê/grava o modo (seguro | vulneravel)
    │   └── layout.php     # Cabeçalho/rodapé HTML compartilhado
    └── pages/
        ├── home.php       # Página inicial com o passo a passo da demo
        ├── attacker.php   # Console do atacante
        ├── login.php      # Formulário de login (ponto vulnerável)
        ├── account.php    # Área autenticada ("Minha Conta")
        ├── logout.php     # Encerra a sessão
        └── reset.php      # Reseta o estado da demo
```

O estado do toggle "modo seguro" é gravado em `sys_get_temp_dir()`
(fora do repositório), não em um arquivo versionado — veja
[Deploy na Vercel](#deploy-na-vercel).

## Passo a passo do ataque

Como atacante e vítima precisam de cookies independentes, use **duas
janelas de navegador separadas** (ex.: uma normal e uma anônima):

1. Na janela normal, abra o **Console do Atacante**
   (`attacker.php`). Ele gera um Session ID fixo (ex.:
   `ATACANTE-abc123`) e monta o link malicioso:
   ```
   http://localhost:8000/login.php?PHPSESSID=ATACANTE-abc123
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

- **Vulnerabilidade** ([api/pages/login.php](api/pages/login.php)): antes de
  `session_start()`, o código adota um `PHPSESSID` vindo da URL
  (`session_id($_GET['PHPSESSID'])`) sem questionar sua origem — é isso que
  permite a fixação.
- **Defesa** ([api/pages/login.php](api/pages/login.php)): logo após validar as credenciais,
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
