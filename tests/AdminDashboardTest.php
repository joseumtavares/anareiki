<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin.php';

final class AdminDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_save_path(sys_get_temp_dir());
            session_start();
        }
        $_SESSION = [
            'admin' => ['id' => 'admin-1', 'nome' => 'Ana <script>'],
        ];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    public function test_dashboard_exibe_modulos_nome_escapado_e_flash(): void
    {
        adminFlash('Serviço salvo.', 'success');

        ob_start();
        require __DIR__ . '/../admin/index.php';
        $html = (string) ob_get_clean();

        self::assertStringContainsString('Ana &lt;script&gt;', $html);
        self::assertStringContainsString('Serviço salvo.', $html);
        self::assertStringContainsString('/admin/servicos.php', $html);
        self::assertStringContainsString('/admin/profissionais.php', $html);
        self::assertStringContainsString('/admin/disponibilidade.php', $html);
        self::assertStringContainsString('/admin/agendamentos.php', $html);
        self::assertStringContainsString('name="csrf_token"', $html);
        self::assertArrayNotHasKey('admin_flash', $_SESSION);
    }
}
