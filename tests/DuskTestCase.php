<?php

namespace Tests;

use Database\Seeders\DuskSeeder;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\Browser;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    /**
     * Antes de CADA teste: banco recriado do zero + dados fictícios.
     *
     * O navegador conversa com outro processo (php artisan serve), por isso
     * não dá para usar RefreshDatabase (transações). O trait DatabaseMigrations
     * também não serve: ele executa migrate:rollback ao final, e as migrations
     * deste projeto não podem ser desfeitas (local_evento é criada depois de
     * evento, que tem chave estrangeira para ela). Usamos só migrate:fresh.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->garantirBancoDeTeste();

        $this->artisan('migrate:fresh', ['--seeder' => DuskSeeder::class])->run();
    }

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    /**
     * Trava de segurança: migrate:fresh APAGA todas as tabelas. Só deixa
     * continuar se o banco for o SQLite exclusivo dos testes (dusk.sqlite).
     */
    protected function garantirBancoDeTeste(): void
    {
        $conexao = config('database.default');
        $banco = (string) config("database.connections.{$conexao}.database");

        if ($conexao !== 'sqlite' || ! str_ends_with(str_replace('\\', '/', $banco), 'dusk.sqlite')) {
            $this->fail(
                "Trava de segurança: os testes Dusk só rodam no banco database/dusk.sqlite. ".
                "Conexão atual: {$conexao} ({$banco}). Confira o .env.dusk.local e rode 'php artisan config:clear'."
            );
        }

        if (! file_exists($banco)) {
            touch($banco);
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            '--window-size=1366,900',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    /* ------------------------------------------------------------------
     | Ações reutilizadas pelos cenários
     * ------------------------------------------------------------------ */

    /**
     * Preenche o formulário de cadastro de evento (tela /cadastrarEvento).
     * As datas usam o formato do <input type="datetime-local">: AAAA-MM-DDTHH:MM.
     */
    protected function preencherFormularioEvento(Browser $browser, string $titulo, string $inicio, string $fim): void
    {
        $browser->type('titulo', $titulo)
            ->type('nome_organizador', 'Coletivo Fictício de Testes')
            ->type('descricao', 'Evento fictício criado por um teste automatizado com Laravel Dusk.');

        $this->selecionarCategoria($browser, 'Música');

        $this->preencherDataHora($browser, 'data_hora_inicio', $inicio);
        $this->preencherDataHora($browser, 'data_hora_fim', $fim);

        $browser->check('fl_gratis')
            ->type('local_nome', 'Espaço Cultural Fictício')
            ->type('local_rua', 'Rua das Flores')
            ->type('local_numero', '100')
            ->type('local_cidade', 'Maceió')
            ->type('local_estado', 'AL');
    }

    /**
     * O campo "Categorias" usa Select2: o <select> original fica escondido e
     * o WebDriver não consegue interagir com ele. Aqui agimos como o usuário:
     * abre o componente visual e clica na opção pelo texto.
     */
    protected function selecionarCategoria(Browser $browser, string $nome): void
    {
        $browser->click('#categorias + .select2-container')
            ->waitFor('.select2-results__option')
            ->clickAtXPath("//li[contains(@class, 'select2-results__option') and normalize-space(.)='{$nome}']")
            ->assertSeeIn('#categorias + .select2-container', $nome);
    }

    /**
     * Digitar em <input type="datetime-local"> depende do idioma do Chrome
     * (dd/mm/aaaa no pt-BR, mm/dd/yyyy no en-US). Para o teste não quebrar
     * conforme a máquina, o valor é definido no formato ISO via JavaScript.
     */
    protected function preencherDataHora(Browser $browser, string $id, string $valorIso): void
    {
        $browser->script("document.getElementById('{$id}').value = '{$valorIso}';");
        $browser->assertInputValue("#{$id}", $valorIso);
    }

    /**
     * Captura a página inteira em tests/Browser/screenshots/{nome}.png.
     */
    protected function capturar(Browser $browser, string $nome): void
    {
        $browser->fitContent()->screenshot($nome)->resize(1366, 900);
    }
}
