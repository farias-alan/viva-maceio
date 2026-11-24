# Relatório — Testes E2E com Laravel Dusk (Viva Maceió)

Atividade Prática Avaliativa — Testes End-to-End (Parte 1)
Disciplina de Qualidade de Software — IFAL / Campus Maceió

Como instalar e executar: veja o [E2E-README.md](E2E-README.md).

**Sistema-alvo:** [Viva Maceió](README.md), portal de eventos culturais de Maceió (aplicação própria, Laravel 11 + Blade), executado **localmente**.
**Ferramenta:** Laravel Dusk 8.7.2 (automação do Google Chrome via ChromeDriver).

---

## 1. Resumo dos cenários

| | Cenário 1 — Fluxo principal | Cenário 2 — Caso negativo |
|---|---|---|
| **Arquivo** | [tests/Browser/CadastroEConsultaEventoTest.php](tests/Browser/CadastroEConsultaEventoTest.php) | [tests/Browser/CadastroDataInvalidaTest.php](tests/Browser/CadastroDataInvalidaTest.php) |
| **Objetivo** | Cadastrar um evento e consultá-lo | Tentar cadastrar um evento com data de fim anterior à de início |
| **Ação inicial** | Visitante acessa `/cadastrarEvento` | Organizador logado acessa `/cadastrarEvento` |
| **Etapas** | 1. É redirecionado ao login e entra com a conta fictícia · 2. Volta ao formulário, preenche título, descrição, categoria "Música" (componente Select2), datas válidas e endereço · 3. Envia · 4. Em `/eventos`, filtra pelo título · 5. Clica em "Saiba mais" | 1. Preenche todos os campos obrigatórios, com fim (19/12) antes do início (20/12) · 2. Envia · 3. Procura o título em `/eventos` |
| **Resultado esperado** | Mensagem "Evento cadastrado com sucesso!"; o evento aparece na listagem filtrada; a página de detalhes mostra a descrição, as datas `20/12/2026 - 20/12/2026` e o endereço `Rua das Flores, 100. Maceió - AL` | Alerta de erro "The data hora fim field must be a date after or equal to data hora inicio."; nenhuma mensagem de sucesso; a listagem mostra "Nenhum evento encontrado para os filtros aplicados."; o evento não existe no banco |
| **Resultado obtido** | ✅ Passou | ✅ Passou |

Todos os dados são fictícios. Antes de cada teste, o banco `dusk.sqlite` é recriado (`migrate:fresh`) e populado pelo [DuskSeeder](database/seeders/DuskSeeder.php) com categorias, dois eventos de exemplo e a conta de teste `organizador.teste@vivamaceio.test`, cuja senha fictícia vem do `.env.dusk.local`.

## 2. Escopo: o que o teste cobre e o que não permite concluir

**Percurso exercitado:** Google Chrome (controlado pelo ChromeDriver) → páginas Blade servidas pelo `php artisan serve` → login e sessão do Laravel Breeze → validação do `EventoController` → Eloquent → banco **SQLite** → leitura de volta nas páginas de listagem e de detalhes.

**O que os resultados NÃO permitem concluir:**
- se o sistema funciona com o **MySQL** previsto para produção (testamos com SQLite);
- se funciona em **outros navegadores** ou no celular (o Dusk usa apenas o Chrome);
- nada sobre layout e visual, responsividade, desempenho ou acessibilidade;
- se as páginas funcionam **sem internet** (dependem de CDN);
- se os filtros por **categoria** e por **data**, a página "Hoje", o cadastro de usuário e o perfil funcionam (não foram exercitados);
- nada sobre segurança: o teste não revela, por exemplo, que `POST /eventos` aceita requisições **sem login**, porque o percurso sempre passa pelo formulário autenticado.

## 3. Evidências da execução

Ficam na pasta [evidencias-e2e/](evidencias-e2e/):

| Arquivo | O que é |
|---|---|
| `execucao-terminal.log` | Saída completa do `php artisan dusk` (2 testes, 27 asserções, cerca de 18 s) |
| `testdox.html` / `testdox.txt` | Relatório legível gerado pelo PHPUnit |
| `junit.xml` | Relatório no formato JUnit (tempo e asserções por teste) |
| `screenshots/cenario1-*.png` | Formulário preenchido → sucesso → listagem filtrada → detalhes |
| `screenshots/cenario2-*.png` | Formulário com data inválida → mensagem de erro → listagem vazia |
| `falha-proposital/` | Execução de um teste **propositalmente errado**, para mostrar o que o Dusk produz quando algo falha: o print automático `failure-*.png`, o log do console do navegador e a mensagem `Did not see expected text [...]` |

O teste da falha proposital foi apagado depois de gerar a evidência; ele não faz parte da suíte.

## 4. Interpretação dos resultados

- **Os dois cenários passaram.** No cenário 1, o percurso completo (login → cadastro → gravação → listagem → detalhes) funciona e os dados gravados são exibidos corretamente. No cenário 2, o teste **passa porque o sistema rejeita** a entrada inválida: a validação `after_or_equal` do servidor barrou o cadastro e nada foi gravado.
- O cenário 2 mostra que a regra está **só no servidor**: o campo "Data e Hora Fim" não tem validação no HTML, então o usuário só descobre o erro depois de enviar.
- A falha proposital mostra como o Dusk ajuda a investigar: indica a asserção que falhou, salva um print da tela no momento da falha e guarda o console do navegador.

### Problemas encontrados no sistema durante a automação

| Achado | Como apareceu |
|---|---|
| O `DatabaseSeeder` não cria categorias, e sem categorias **nenhum evento pode ser cadastrado** | Ao preparar os dados (foi criado o `DuskSeeder`) |
| A rota `/dashboard`, destino padrão após o login, renderiza a view sem a variável `$categorias` (erro 500) | Na leitura do código. O teste entra por `/cadastrarEvento` e o Laravel devolve o usuário para lá após o login |
| As migrations **não podem ser desfeitas** (`migrate:rollback` falha: `local_evento` é criada depois de `evento`, que tem chave estrangeira para ela) | Na primeira execução, o trait `DatabaseMigrations` do Laravel falhou ao final dos testes |
| Após um erro de validação, o **formulário volta vazio** (a view não usa `old()`) | No print `cenario2-02-erro-de-validacao.png` |
| `/js/app.js` responde **404** em todas as páginas | No log do console capturado pelo Dusk na falha proposital |
| O `composer.lock` estava desatualizado em relação ao `composer.json` (pacote `select2/select2`) | No `composer install` |
| Mensagens de validação em inglês e com nomes técnicos ("data hora fim") | No cenário 2 |

## 5. Reflexão

**Dificuldades e limitações encontradas:**
1. **Ambiente:** o PHP 8.1 já instalado (XAMPP) era incompatível com o Laravel 11. Foi preciso instalar o PHP 8.3, o Composer e configurar certificados SSL para o Dusk conseguir baixar o ChromeDriver. A versão do ChromeDriver precisa acompanhar a do Chrome: quando o Chrome atualiza, é preciso rodar `dusk:chrome-driver --detect` de novo.
2. **Componente Select2:** ele esconde o `<select>` original, e o WebDriver não interage com elementos ocultos. O teste precisou agir como o usuário: abrir o componente visual e clicar na opção.
3. **Campos de data (`datetime-local`):** o que se digita depende do idioma do Chrome (`dd/mm/aaaa` em pt-BR, `mm/dd/yyyy` em en-US). Para o teste não depender da máquina, o valor é definido via JavaScript. É uma concessão: essa etapa não reproduz a digitação real do usuário.
4. **Texto renderizado × texto no HTML:** o CSS deixa os botões em maiúsculas ("ENVIAR"), e o WebDriver lê o texto já transformado, então `press('Enviar')` não encontrava o botão. Usamos seletores CSS (`.btn-enviar`).
5. **Dois processos:** o navegador acessa o servidor, que é outro processo. Por isso não dá para isolar os testes com transações (`RefreshDatabase`). O banco é recriado do zero antes de cada teste, o que é mais lento, e o servidor precisa estar rodando à parte.

**Conclusão do grupo:** o Dusk se integra muito bem a uma aplicação Laravel. Os testes são escritos em PHP, no próprio projeto, e há atalhos como `loginAs()`, os seeders e as asserções de banco. Mesmo com apenas dois cenários, a automação revelou problemas reais que não aparecem olhando uma tela por vez: seed incompleto, rota quebrada após o login, migrations que não podem ser desfeitas, formulário que perde os dados e um arquivo JS inexistente. Em contrapartida, o teste E2E é mais lento (cerca de 18 s para 2 cenários), depende de muita infraestrutura (PHP, Chrome, ChromeDriver, CDN) e é sensível a detalhes de interface como componentes JavaScript, idioma e CSS. Ele confirma que o percurso funciona nas condições testadas, mas não substitui testes de unidade e de integração, nem garante o comportamento em outros bancos ou navegadores.

## 6. Arquivos criados ou alterados para a atividade

| Arquivo | Descrição |
|---|---|
| `tests/DuskTestCase.php` | Configuração do Chrome, trava de segurança do banco, `migrate:fresh` antes de cada teste e ações reutilizadas (preencher formulário, Select2, datas, screenshots) |
| `tests/Browser/*Test.php` | Os dois cenários |
| `database/seeders/DuskSeeder.php` | Dados fictícios dos testes |
| `.env.dusk.local.example` | Modelo do ambiente de teste (sem segredos) |
| `phpunit.dusk.xml` | Configuração do PHPUnit para os testes de navegador |
| `composer.json` / `composer.lock` | Inclusão do `laravel/dusk` (dev) e sincronização do `select2/select2` |
| `evidencias-e2e/` | Registros da execução |

O código da aplicação (controllers, views, rotas) **não foi alterado**: os problemas encontrados foram apenas documentados.
