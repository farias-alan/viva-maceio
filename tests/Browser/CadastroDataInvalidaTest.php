<?php

namespace Tests\Browser;

use App\Models\User;
use Database\Seeders\DuskSeeder;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * CENÁRIO 2 — Caso negativo: data de fim anterior à data de início.
 *
 * Ação inicial: um organizador logado abre /cadastrarEvento.
 * Resultado esperado: o sistema recusa o cadastro, exibe o erro de validação
 * e o evento NÃO aparece na listagem.
 *
 * Observação: o campo "Data e Hora Fim" não tem validação no HTML; quem
 * barra a entrada é a validação do Laravel no servidor (after_or_equal).
 */
class CadastroDataInvalidaTest extends DuskTestCase
{
    public function test_cadastro_com_data_fim_anterior_ao_inicio_e_rejeitado(): void
    {
        $titulo = 'Evento Invalido E2E '.now()->format('His');
        $usuario = User::where('email', DuskSeeder::EMAIL)->firstOrFail();

        $this->browse(function (Browser $browser) use ($titulo, $usuario) {
            // 1. Login direto (atalho do Dusk): o foco deste cenário é a validação.
            $browser->loginAs($usuario)
                ->visit('/cadastrarEvento')
                ->assertSee('Cadastrar evento');

            // 2. Fim (19/12) ANTES do início (20/12).
            $this->preencherFormularioEvento($browser, $titulo, '2026-12-20T19:00', '2026-12-19T19:00');
            $this->capturar($browser, 'cenario2-01-formulario-com-data-invalida');

            // 3. Envia e confere a rejeição.
            $browser->press('.btn-enviar')
                ->assertPathIs('/cadastrarEvento')
                ->assertVisible('.alert-danger')
                ->assertSeeIn('.alert-danger', 'must be a date after or equal to')
                ->assertDontSee('Evento cadastrado com sucesso!');
            $this->capturar($browser, 'cenario2-02-erro-de-validacao');

            // 4. Confirma pela interface que nada foi gravado.
            $browser->visit('/eventos?titulo='.urlencode($titulo))
                ->assertSee('Nenhum evento encontrado para os filtros aplicados.');
            $this->capturar($browser, 'cenario2-03-listagem-sem-o-evento');
        });

        // Verificação complementar direto no banco de teste.
        $this->assertDatabaseMissing('evento', ['titulo' => $titulo]);
    }
}
