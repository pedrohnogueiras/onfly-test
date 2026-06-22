<?php

declare(strict_types=1);

namespace Tests\Unit\DTO;

use App\DTO\User\RegisterRequestDTO;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class RegisterRequestDTOTest extends TestCase
{
    public function test_mapeia_campos_de_entrada(): void
    {
        $dto = RegisterRequestDTO::from([
            'nome' => 'Beltrano',
            'email' => 'beltrano@onfly.com.br',
            'password' => 'senha-segura-123',
        ]);

        $this->assertSame('Beltrano', $dto->name);
        $this->assertSame('beltrano@onfly.com.br', $dto->email);
        $this->assertSame('senha-segura-123', $dto->password);
        $this->assertFalse($dto->isAdmin);
    }

    public function test_mapeia_is_admin_do_payload(): void
    {
        $dto = RegisterRequestDTO::from([
            'nome' => 'Beltrano',
            'email' => 'beltrano@onfly.com.br',
            'password' => 'senha-segura-123',
            'is_admin' => true,
        ]);

        $this->assertTrue($dto->isAdmin);
    }

    public function test_toarray_expoe_chaves_esperadas(): void
    {
        $dto = RegisterRequestDTO::from([
            'nome' => 'Beltrano',
            'email' => 'beltrano@onfly.com.br',
            'password' => 'senha-segura-123',
            'is_admin' => true,
        ]);

        $output = $dto->toArray();

        $this->assertSame(
            ['name', 'email', 'password', 'isAdmin'],
            array_keys($output),
        );

        $this->assertSame('Beltrano', $output['name']);
        $this->assertSame('beltrano@onfly.com.br', $output['email']);
        $this->assertSame('senha-segura-123', $output['password']);
        $this->assertTrue($output['isAdmin']);
    }

    public function test_is_admin_default_false_quando_omitido(): void
    {
        $dto = RegisterRequestDTO::from([
            'nome' => 'Beltrano',
            'email' => 'beltrano@onfly.com.br',
            'password' => 'senha-segura-123',
        ]);

        $output = $dto->toArray();

        $this->assertFalse($output['isAdmin']);
    }
}
