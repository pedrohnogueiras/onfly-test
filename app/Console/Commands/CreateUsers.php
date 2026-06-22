<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:create-users')]
#[Description('Cria usuario cliente e admin para verificar o teste')]
class CreateUsers extends Command
{
    // Mantenha as propriedades protegidas caso esteja em versões anteriores ao Laravel 11
    protected $signature = 'app:create-users';
    protected $description = 'Cria usuario cliente e admin para verificar o teste';

    /**
     * Executa comando para criar usuarios default.
     */
    public function handle()
    {
        $this->info('Criando usuários padrão...');

        $createdUsers = [];

        // 1. Criando o Usuário Cliente
        $user = User::create([
            'name' => 'Cliente Onfly',
            'email' => 'cliente@onfly.com.br',
            'is_admin' => 0,
            'password' => bcrypt('password'), // Lembre-se de definir uma senha se o campo for obrigatório
        ]);

        $user_api_key = $user->generateApiKey();

        // Armazena os dados simplificados em formato de array para a tabela
        $createdUsers[] = [
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => $user->is_admin ? 'Sim' : 'Não',
            'api-key' => $user_api_key,
        ];

        // 2. Criando o Usuário Admin
        $admin = User::create([
            'name' => 'Admin Onfly',
            'email' => 'admin@onfly.com.br',
            'is_admin' => 1,
            'password' => bcrypt('password'),
        ]);

        // Correção: Aqui você estava chamando do $user anterior em vez do $admin
        $admin_api_key = $admin->generateApiKey();

        $createdUsers[] = [
            'name' => $admin->name,
            'email' => $admin->email,
            'is_admin' => $admin->is_admin ? 'Sim' : 'Não',
            'api-key' => $admin_api_key,
        ];

        $this->info('Dados do usuário padrão:');

        // CORREÇÃO: Adicionada a vírgula que faltava entre 'Admin' e 'API-KEY'
        $this->table(
            ['Nome', 'E-mail', 'Admin', 'API-KEY'],
            $createdUsers,
        );
    }
}
