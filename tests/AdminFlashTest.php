<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/admin.php';

final class AdminFlashTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_save_path(sys_get_temp_dir());
            session_start();
        }
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function test_flash_e_consumido_uma_unica_vez(): void
    {
        adminFlash('Serviço salvo.', 'success');

        self::assertSame(
            ['mensagem' => 'Serviço salvo.', 'tipo' => 'success'],
            consumirAdminFlash()
        );
        self::assertNull(consumirAdminFlash());
    }

    public function test_flash_normaliza_tipo_desconhecido(): void
    {
        adminFlash('Aviso', 'script');

        self::assertSame(
            ['mensagem' => 'Aviso', 'tipo' => 'info'],
            consumirAdminFlash()
        );
    }

    public function test_consumir_flash_sem_mensagem_retorna_null(): void
    {
        self::assertNull(consumirAdminFlash());
    }
}
