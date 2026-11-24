# Testes E2E com Laravel Dusk — Viva Maceió

Dois cenários automatizados no navegador para o portal Viva Maceió, executado localmente:
**cadastrar e consultar um evento** e **rejeitar um cadastro com a data de fim anterior à de início**.

Resumo dos cenários, escopo, evidências e reflexão: [RELATORIO-E2E.md](RELATORIO-E2E.md).

## Ferramenta e versões

Laravel Dusk **8.7.2** · PHPUnit 11.4.3 · Laravel 11.33.2 · PHP 8.3.37 · Composer 2.11.3 · Google Chrome 157 + ChromeDriver 157 · Windows 11

## Pré-requisitos

- **PHP 8.2 ou superior** com as extensões `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `fileinfo`, `zip` e `curl`.
  No Windows, aponte `curl.cainfo` no `php.ini` para o [cacert.pem](https://curl.se/docs/caextract.html); sem isso, o download do ChromeDriver falha.
- **Composer 2** e **Google Chrome**.
- **Internet** (as páginas carregam jQuery, Select2 e Bootstrap por CDN).
- MySQL e Node.js **não** são necessários.

## Configuração (uma vez)

```bash
composer install

cp .env.example .env
#   no .env: DB_CONNECTION=sqlite, comente as demais linhas DB_* e use APP_URL=http://127.0.0.1:8000
php artisan key:generate

cp .env.dusk.local.example .env.dusk.local
#   no .env.dusk.local: copie a APP_KEY do .env
#                       e coloque em DB_DATABASE o caminho ABSOLUTO de database/dusk.sqlite

php artisan dusk:chrome-driver --detect
```

Os testes usam um banco próprio (`database/dusk.sqlite`), recriado antes de cada teste com dados fictícios. Uma trava no `DuskTestCase` impede a execução em qualquer outro banco.

## Comandos para executar

```bash
# Terminal 1 — servidor da aplicação
php artisan serve --host=127.0.0.1 --port=8000

# Terminal 2 — testes
php artisan config:clear
php artisan dusk              # Chrome sem janela
php artisan dusk --browse     # Chrome visível (demonstração)
```

Para gerar os relatórios usados como evidência (pasta [evidencias-e2e/](evidencias-e2e/)):

```bash
php artisan dusk --testdox --log-junit evidencias-e2e/junit.xml --testdox-html evidencias-e2e/testdox.html
```

**Atenção:** durante a execução, o Dusk troca o `.env` pelo `.env.dusk.local` e restaura o original no final. Não interrompa com Ctrl+C; se o `.env` ficar trocado, o original está em `.env.backup`.
