<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout/admin.php';

final class AdminLayoutTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_save_path(sys_get_temp_dir());
            session_start();
        }
        $_SESSION = ['admin' => ['id' => 'admin-1', 'nome' => 'Ana <script>']];
    }

    public function test_login_nao_renderiza_menu(): void
    {
        ob_start();
        adminTopo('Entrar');
        $html = (string) ob_get_clean();

        self::assertStringNotContainsString('/admin/servicos.php', $html);
        self::assertStringNotContainsString('Ana &lt;script&gt;', $html);
    }

    public function test_menu_do_painel_exibe_modulos(): void
    {
        ob_start();
        adminTopo('Painel', true);
        $html = (string) ob_get_clean();

        self::assertStringContainsString('Ana &lt;script&gt;', $html);
        self::assertStringContainsString('/admin/', $html);
        self::assertStringContainsString('/admin/servicos.php', $html);
        self::assertStringContainsString('/admin/profissionais.php', $html);
        self::assertStringContainsString('/admin/disponibilidade.php', $html);
        self::assertStringContainsString('/admin/agendamentos.php', $html);
        self::assertStringContainsString('name="csrf_token"', $html);
    }

    public function test_layout_escapa_conteudo_dinamico(): void
    {
        ob_start();
        adminTopo('<script>alert(1)</script>', true);
        adminBreadcrumb([
            ['rotulo' => 'Painel', 'url' => '/admin/'],
            ['rotulo' => '<b>Atual</b>', 'url' => null],
        ]);
        adminAlerta('<img src=x onerror=alert(1)>', 'script');
        $html = (string) ob_get_clean();

        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        self::assertStringContainsString('&lt;b&gt;Atual&lt;/b&gt;', $html);
        self::assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
        self::assertStringNotContainsString('alert(1)>', $html);
        self::assertStringContainsString('alert alert-info', $html);
    }
}
