<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../public_html/includes/admin.php';

final class AdminFormTest extends TestCase
{
    public function test_helper_de_valor_usa_fallback(): void
    {
        self::assertSame('padrão', adminValor([], 'nome', 'padrão'));
        self::assertSame('Ana', adminValor(['nome' => 'Ana'], 'nome'));
    }

    public function test_helper_de_erro_nao_emite_markup_sem_erro(): void
    {
        self::assertNull(adminErro([], 'nome'));
        self::assertSame('Nome obrigatório.', adminErro(['nome' => 'Nome obrigatório.'], 'nome'));
    }
}
