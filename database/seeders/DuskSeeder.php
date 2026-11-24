<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Dados fictícios usados pelos testes E2E (Laravel Dusk).
 *
 * O DatabaseSeeder original do projeto cria apenas um usuário e não chama
 * os seeders de categorias e eventos. Sem categorias o cadastro de evento
 * sempre falha (o campo "categorias" é obrigatório), por isso este seeder
 * reúne tudo o que os cenários precisam, sem alterar o seeder da aplicação.
 */
class DuskSeeder extends Seeder
{
    public const EMAIL = 'organizador.teste@vivamaceio.test';

    public function run(): void
    {
        $this->call([
            PapelUsuarioSeeder::class,
            StatusSolicitacaoEventoSeeder::class,
            CategoriaEventoSeeder::class,
            EventoSeeder::class,
        ]);

        // Conta fictícia de teste. A senha vem do .env.dusk.local (DUSK_USER_PASSWORD).
        User::create([
            'name' => 'Organizador Teste',
            'email' => self::EMAIL,
            'password' => Hash::make(self::password()),
            'email_verified_at' => now(),
        ]);
    }

    public static function password(): string
    {
        return env('DUSK_USER_PASSWORD', 'senha-ficticia-123');
    }
}
