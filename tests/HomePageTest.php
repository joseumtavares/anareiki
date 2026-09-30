<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HomePageTest extends TestCase
{
    public function test_mostra_estado_neutro_quando_nao_ha_profissionais(): void
    {
        require_once __DIR__ . '/../public_html/includes/public-view.php';
        $profissionais = [];

        ob_start();
        require __DIR__ . '/../public_html/includes/layout/home/about.php';
        $html = (string) ob_get_clean();

        self::assertStringContainsString('role="status"', $html);
        self::assertStringContainsString('estarão disponíveis em breve', $html);
        self::assertStringContainsString('Me chama para agendar!', $html);
    }

    public function test_renderiza_shell_navegacao_e_perfis_com_escape_e_sem_foto_invalida(): void
    {
        require_once __DIR__ . '/../public_html/includes/public-view.php';
        $profissionais = [
            [
                'nome' => '<script>alert("Ana")</script>',
                'especialidade' => 'Massagem & Reiki',
                'bio' => 'Cuida de "você"',
                'foto_url' => null,
            ],
            [
                'nome' => 'Bia',
                'especialidade' => '',
                'bio' => null,
                'foto_url' => '/static/img/bia.webp',
            ],
            [
                'nome' => 'Cris',
                'especialidade' => null,
                'bio' => '',
                'foto_url' => 'https://exemplo.test/cris.jpg',
            ],
        ];

        ob_start();
        require __DIR__ . '/../public_html/includes/layout/home/head.php';
        require __DIR__ . '/../public_html/includes/layout/home/top.php';
        require __DIR__ . '/../public_html/includes/layout/home/about.php';
        $html = (string) ob_get_clean();

        self::assertStringContainsString('<title>Reiki Ana — Massoterapeuta</title>', $html);
        self::assertStringContainsString('href="#sobre" class="nav-link">Sobre</a>', $html);
        self::assertStringContainsString('href="#servicos" class="nav-link">Serviços</a>', $html);
        self::assertStringContainsString('<section id="sobre" class="sobre-section">', $html);
        self::assertStringContainsString('Espaço de atendimento Reiki Ana', $html);
        self::assertStringContainsString('Dicas para antes da sua massagem', $html);
        self::assertStringContainsString('Me chama para agendar!', $html);
        self::assertStringContainsString('&lt;script&gt;alert(&quot;Ana&quot;)&lt;/script&gt;', $html);
        self::assertStringContainsString('Massagem &amp; Reiki', $html);
        self::assertStringNotContainsString('<script>alert("Ana")</script>', $html);
        self::assertSame(1, substr_count($html, 'Dicas para antes da sua massagem'));
        self::assertSame(1, substr_count($html, 'Me chama para agendar!'));
        self::assertStringContainsString('Cuida de &quot;você&quot;', $html);
        self::assertStringNotContainsString('alt="Cris"', $html);
        self::assertSame(2, substr_count($html, '<img'));
        self::assertStringContainsString('src="/static/img/bia.webp"', $html);
    }
}
