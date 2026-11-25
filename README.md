# Testes E2E com Laravel Dusk — Viva Maceió

Atividade Prática Avaliativa — Testes End-to-End (Parte 1)
Disciplina de Qualidade de Software — IFAL / Campus Maceió  

Alunos: Antonio Alan, Adenilson Silva, Ingrid Mônica e Karla Cristina

Testes automatizados no navegador para o **Viva Maceió**, um portal de eventos culturais feito em Laravel e executado **localmente** (descrição original do projeto em [SOBRE-O-PROJETO.md](SOBRE-O-PROJETO.md)). A ferramenta é o **Laravel Dusk**, que controla um Google Chrome de verdade e age como um usuário: abre páginas, preenche formulários, clica e confere o que aparece na tela.

| Cenário | O que o teste faz | Resultado esperado |
|---|---|---|
| **1. Fluxo principal** | Visitante acessa o cadastro de eventos → faz login → cadastra um evento → busca o evento na listagem → abre os detalhes | O evento aparece na listagem e os detalhes mostram os dados cadastrados |
| **2. Caso negativo** | Organizador tenta cadastrar um evento com a data de fim **anterior** à de início | O sistema mostra uma mensagem de erro e o evento **não** é gravado |

---

## 1. Onde está cada entrega

| # | Entrega pedida | Onde encontrar |
|---|---|---|
| 1 | **Código-fonte dos cenários** | [tests/Browser/CadastroEConsultaEventoTest.php](tests/Browser/CadastroEConsultaEventoTest.php) (cenário 1)<br>[tests/Browser/CadastroDataInvalidaTest.php](tests/Browser/CadastroDataInvalidaTest.php) (cenário 2)<br>[tests/DuskTestCase.php](tests/DuskTestCase.php) (configuração e ações comuns aos dois) |
| 2 | **README** (ferramenta e versão, pré-requisitos, configuração, comandos) | Este arquivo, seções 3 a 6 |
| 3 | **Registro da execução** | Pasta [evidencias-e2e/](evidencias-e2e/) (detalhes na seção 7) |
| 4 | **Resumo dos cenários** (sistema-alvo, ação inicial, resultado esperado) | [RELATORIO-E2E.md, seção 1](RELATORIO-E2E.md#1-resumo-dos-cenários) |
| 5 | **Reflexão** (dificuldade ou limitação e conclusão) | [RELATORIO-E2E.md, seção 5](RELATORIO-E2E.md#5-reflexão) |
| — | Escopo: o que o teste cobre e o que não permite concluir | [RELATORIO-E2E.md, seção 2](RELATORIO-E2E.md#2-escopo-o-que-o-teste-cobre-e-o-que-não-permite-concluir) |
| — | Interpretação dos resultados e problemas encontrados no sistema | [RELATORIO-E2E.md, seção 4](RELATORIO-E2E.md#4-interpretação-dos-resultados) |

## 2. Arquivos criados para os testes

```
viva-maceio/
├── README.md                      ← este guia
├── SOBRE-O-PROJETO.md             ← descrição original do Viva Maceió
├── RELATORIO-E2E.md               ← cenários, escopo, resultados e reflexão
├── tests/
│   ├── DuskTestCase.php           ← base: Chrome, banco de teste, ações reutilizadas
│   └── Browser/
│       ├── CadastroEConsultaEventoTest.php   ← cenário 1
│       ├── CadastroDataInvalidaTest.php      ← cenário 2
│       └── screenshots/           ← prints gerados a cada execução
├── database/seeders/DuskSeeder.php ← dados fictícios usados nos testes
├── .env.dusk.local.example        ← modelo de configuração do ambiente de teste
├── phpunit.dusk.xml               ← configuração do executor de testes
└── evidencias-e2e/                ← registros da execução entregue
```

O código da aplicação (controllers, views, rotas) **não foi alterado**.

## 3. Ferramenta e versões

| Item | Versão |
|---|---|
| **Laravel Dusk** | **8.7.2** |
| PHPUnit (executor dos testes) | 11.4.3 |
| Laravel Framework | 11.33.2 |
| PHP | 8.3.37 (o projeto exige 8.2 ou superior) |
| Composer | 2.11.3 |
| Google Chrome / ChromeDriver | 157 / 157.0.8103.78 |
| Sistema operacional | Windows 11 |

## 4. Pré-requisitos

- **PHP 8.2 ou superior** com as extensões `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `fileinfo`, `zip` e `curl` habilitadas no `php.ini`.
  - **No Windows:** aponte `curl.cainfo` e `openssl.cafile` no `php.ini` para o arquivo [cacert.pem](https://curl.se/docs/caextract.html). Sem isso, o download do ChromeDriver falha com `cURL error 60`.
- **Composer 2**.
- **Google Chrome** instalado.
- **Internet**: as páginas carregam jQuery, Select2 e Bootstrap por CDN.
- **Não** é preciso MySQL nem Node.js: os testes usam SQLite.

## 5. Configuração (só na primeira vez)

Execute na pasta do projeto:

```bash
# 1. Baixar as dependências (inclui o Laravel Dusk)
composer install

# 2. Ambiente de desenvolvimento
cp .env.example .env
php artisan key:generate
```

Edite o `.env`:
- troque `DB_CONNECTION=mysql` por `DB_CONNECTION=sqlite`;
- comente as linhas `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD`;
- use `APP_URL=http://127.0.0.1:8000`.

```bash
# 3. Ambiente dos testes
cp .env.dusk.local.example .env.dusk.local
```

Edite o `.env.dusk.local`:
- em `APP_KEY`, cole a mesma chave que está no `.env`;
- em `DB_DATABASE`, coloque o **caminho absoluto** do arquivo `database/dusk.sqlite` (exemplo: `C:/projetos/viva-maceio/database/dusk.sqlite`). O arquivo é criado automaticamente.

```bash
# 4. Baixar o ChromeDriver na mesma versão do Chrome instalado
php artisan dusk:chrome-driver --detect
```

**Sobre o banco de teste:** antes de cada teste, o banco `dusk.sqlite` é apagado e recriado com dados fictícios (categorias, eventos de exemplo e a conta `organizador.teste@vivamaceio.test`). Uma trava no `DuskTestCase` impede que isso aconteça em qualquer outro banco.

## 6. Como executar os testes

São necessários **dois terminais** abertos na pasta do projeto, porque o Dusk não sobe a aplicação sozinho.

**Terminal 1 — subir a aplicação** (deixe aberto):
```bash
php artisan serve --host=127.0.0.1 --port=8000
```

**Terminal 2 — rodar os testes:**
```bash
php artisan config:clear

php artisan dusk --browse     # abre o Chrome na tela para acompanhar (recomendado)
php artisan dusk              # roda sem abrir janela
```

**Rodar só um cenário:**
```bash
php artisan dusk --browse --filter=CadastroEConsultaEventoTest   # cenário 1
php artisan dusk --browse --filter=CadastroDataInvalidaTest      # cenário 2
```

**Gerar os relatórios usados como evidência:**
```bash
php artisan dusk --testdox --log-junit evidencias-e2e/junit.xml --testdox-html evidencias-e2e/testdox.html
```

## 7. Como conferir os resultados

**No terminal**, ao final, deve aparecer:
```
✓ cadastro com data fim anterior ao inicio e rejeitado
✓ organizador cadastra evento e o encontra na listagem

Tests:    2 passed (27 assertions)
```
`2 passed` significa que os dois cenários passaram; `27 assertions` é o total de verificações feitas.

**Prints da execução:** a cada execução, os testes salvam capturas de tela em `tests/Browser/screenshots/`.

**Evidências da execução entregue**, na pasta [evidencias-e2e/](evidencias-e2e/):

| Arquivo | O que mostra |
|---|---|
| [execucao-terminal.log](evidencias-e2e/execucao-terminal.log) | Saída completa do terminal: 2 testes, 27 verificações, cerca de 18 s |
| [testdox.html](evidencias-e2e/testdox.html) / [testdox.txt](evidencias-e2e/testdox.txt) | Relatório legível com o resultado de cada cenário |
| [junit.xml](evidencias-e2e/junit.xml) | Relatório em formato JUnit, com tempo e verificações por teste |
| [screenshots/cenario1-*.png](evidencias-e2e/screenshots/) | Cenário 1: formulário preenchido → cadastro com sucesso → listagem filtrada → detalhes do evento |
| [screenshots/cenario2-*.png](evidencias-e2e/screenshots/) | Cenário 2: formulário com data inválida → mensagem de erro → listagem sem o evento |
| [falha-proposital/](evidencias-e2e/falha-proposital/) | O que o Dusk gera quando um teste **falha**: print automático da tela, log do console do navegador e mensagem de erro (teste temporário, já removido) |

## 8. Como ver um teste falhar

Serve para comprovar que o teste realmente verifica algo:

1. Em [CadastroEConsultaEventoTest.php](tests/Browser/CadastroEConsultaEventoTest.php), troque o texto `'Rua das Flores, 100. Maceió - AL'` por `'Rua Errada, 999'`.
2. Rode `php artisan dusk`.
3. O terminal mostra **FAILED** com a mensagem `Did not see expected text [Rua Errada, 999]`, e o Dusk salva automaticamente um print da tela em `tests/Browser/screenshots/failure-*.png`.
4. **Desfaça a alteração.**

## 9. Problemas comuns

| Sintoma | Solução |
|---|---|
| `php` não é reconhecido como comando | Reabra o terminal ou o editor depois de instalar o PHP |
| `cURL error 60` ao baixar o ChromeDriver | Configure o `cacert.pem` no `php.ini` (seção 4) |
| "This version of ChromeDriver only supports Chrome version …" | O Chrome atualizou. Rode `php artisan dusk:chrome-driver --detect` |
| "Trava de segurança: os testes Dusk só rodam no banco database/dusk.sqlite" | Confira o `DB_DATABASE` do `.env.dusk.local` e rode `php artisan config:clear` |
| Conexão recusada em `127.0.0.1:8000` | O Terminal 1 (`php artisan serve`) não está rodando |
| O `.env` ficou trocado depois de interromper com Ctrl+C | Durante a execução, o Dusk troca o `.env` pelo `.env.dusk.local`. O original fica em `.env.backup`: copie-o de volta para `.env` |
| A mensagem `forking is not supported on this platform` | É só um aviso do Windows; pode ignorar |
