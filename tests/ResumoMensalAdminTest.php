<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../public_html/includes/resumo-mensal-admin.php';
require_once __DIR__ . '/../public_html/includes/layout/admin.php';
require_once __DIR__ . '/../public_html/includes/layout/admin-resumo-mensal.php';

final class ResumoMensalAdminTest extends TestCase
{
    public function test_resumo_exclui_cancelados_e_soma_somente_concluidos(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE servicos (id TEXT, nome TEXT, preco DECIMAL(8,2))');
        $pdo->exec('CREATE TABLE agendamentos (servico_id TEXT, data TEXT, status TEXT)');
        $pdo->exec("INSERT INTO servicos VALUES ('s', 'Reiki', 100.15), ('t', 'Consulta', NULL)");
        $pdo->exec("INSERT INTO agendamentos VALUES
            ('s', '2026-10-01', 'concluido'), ('s', '2026-10-31', 'concluido'),
            ('s', '2026-10-07', 'confirmado'), ('s', '2026-10-08', 'pendente'),
            ('s', '2026-10-09', 'cancelado'), ('s', '2026-11-01', 'concluido'),
            ('t', '2026-10-10', 'concluido')");
        $resumo = resumoMensalAdmin($pdo, '2026-10');
        self::assertSame(5, $resumo['quantidade']);
        self::assertSame(20030, $resumo['total_centavos']);
        self::assertSame(1, $resumo['sem_preco']);
        $reiki = array_column($resumo['servicos'], null, 'id')['s'];
        self::assertSame(4, $reiki['quantidade']);
        self::assertSame(2, $reiki['concluidos']);
        self::assertSame(20030, $reiki['total_centavos']);
        self::assertSame(0, resumoMensalAdmin($pdo, '2026-09')['quantidade']);
    }

    public function test_mes_invalido_e_rejeitado(): void
    {
        $this->expectException(InvalidArgumentException::class);
        resumoMensalAdmin(new PDO('sqlite::memory:'), '2026-13');
    }

    public function test_grafico_renderiza_valores_e_escapa_nome_do_servico(): void
    {
        ob_start();
        renderResumoMensalAdmin('2026-10', [
            'quantidade' => 1, 'total_centavos' => 10015, 'sem_preco' => 0,
            'servicos' => [[
                'nome' => '<script>alert(1)</script>', 'quantidade' => 1,
                'concluidos' => 1, 'preco' => '100.15', 'total_centavos' => 10015,
            ]],
        ], null);
        $html = (string) ob_get_clean();
        self::assertStringContainsString('R$ 100,15', $html);
        self::assertStringContainsString('stroke-dasharray=', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
        self::assertStringNotContainsString('<script>', $html);
    }
}
