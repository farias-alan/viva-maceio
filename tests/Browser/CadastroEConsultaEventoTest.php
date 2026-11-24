<?php

namespace Tests\Browser;

use Database\Seeders\DuskSeeder;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * CENÁRIO 1 — Fluxo principal: cadastrar um evento e consultá-lo.
 *
 * Ação inicial: um visitante acessa /cadastrarEvento.
 * Resultado esperado: depois de logar e cadastrar, o evento aparece na
 * listagem filtrada por título e sua página de detalhes mostra os dados
 * informados (descrição, datas e endereço).
 */
class CadastroEConsultaEventoTest extends DuskTestCase
{
    public function test_organizador_cadastra_evento_e_o_encontra_na_listagem(): void
    {
        $titulo = 'Festival E2E Dusk '.now()->format('His');

        $this->browse(function (Browser $browser) use ($titulo) {
            // 1. Visitante tenta abrir o cadastro e é levado ao login.
            $browser->visit('/cadastrarEvento')
                ->assertPathIs('/login')
                ->type('email', DuskSeeder::EMAIL)
                ->type('password', DuskSeeder::password())
                ->press('.btn-entrar')
                // Após o login, o Laravel devolve o usuário à página pretendida.
                ->assertPathIs('/cadastrarEvento')
                ->assertSee('Cadastrar evento');

            // 2. Preenche e envia o formulário.
            $this->preencherFormularioEvento($browser, $titulo, '2026-12-20T19:00', '2026-12-20T23:00');
            $this->capturar($browser, 'cenario1-01-formulario-preenchido');

            $browser->press('.btn-enviar')
                ->assertPathIs('/cadastrarEvento')
                ->assertSee('Evento cadastrado com sucesso!');
            $this->capturar($browser, 'cenario1-02-cadastro-com-sucesso');

            // 3. Consulta o evento na listagem pública usando o filtro por título.
            $browser->visit('/eventos')
                ->type('titulo', $titulo)
                ->press('Filtrar')
                ->assertQueryStringHas('titulo', $titulo)
                ->assertSeeIn('.grid-eventos', $titulo);
            $this->capturar($browser, 'cenario1-03-listagem-filtrada');

            // 4. Abre os detalhes e confere os dados gravados.
            $browser->clickLink('Saiba mais')
                ->assertPathBeginsWith('/eventos/detalhar/')
                ->assertSee($titulo)
                ->assertSee('Evento fictício criado por um teste automatizado com Laravel Dusk.')
                ->assertSee('20/12/2026 - 20/12/2026')
                ->assertSee('Rua das Flores, 100. Maceió - AL');
            $this->capturar($browser, 'cenario1-04-detalhes-do-evento');
        });
    }
}
