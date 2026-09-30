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

    public function test_renderiza_catalogo_de_servicos_com_dados_escapados_e_opcionais(): void
    {
        require_once __DIR__ . '/../public_html/includes/public-view.php';
        $servicos = [
            [
                'id' => 'servico-1',
                'nome' => '<script>alert("XSS")</script>',
                'descricao' => 'Relaxamento & cuidado',
                'duracao_min' => 90,
                'preco' => '75.00',
                'imagem_url' => 'https://www.genspark.ai/api/files/s/imagem',
                'icone' => null,
                'cor' => null,
                'tag' => 'Mais "pedida"',
            ],
            [
                'id' => 'servico-2',
                'nome' => 'Atendimento especial',
                'descricao' => 'Descrição sem imagem',
                'duracao_min' => 60,
                'preco' => null,
                'imagem_url' => 'https://exemplo.test/imagem.jpg',
                'icone' => 'fa-hands',
                'cor' => 'purple',
                'tag' => null,
            ],
        ];

        ob_start();
        try {
            require __DIR__ . '/../public_html/includes/layout/home/services.php';
        } finally {
            $html = (string) ob_get_clean();
        }

        self::assertStringContainsString('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', $html);
        self::assertStringContainsString('Relaxamento &amp; cuidado', $html);
        self::assertStringNotContainsString('<script>alert("XSS")</script>', $html);
        self::assertStringContainsString('alt="&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;"', $html);
        self::assertStringContainsString('src="https://www.genspark.ai/api/files/s/imagem"', $html);
        self::assertStringContainsString('servico-img-placeholder purple', $html);
        self::assertStringContainsString('fas fa-hands', $html);
        self::assertStringNotContainsString('exemplo.test', $html);
        self::assertStringContainsString('Mais &quot;pedida&quot;', $html);
        self::assertStringContainsString('R$ 75,00', $html);
        self::assertStringContainsString('Consultar valor', $html);
        self::assertStringContainsString('90 min', $html);
        self::assertStringContainsString('60 min', $html);
    }

    public function test_mostra_estado_neutro_quando_nao_ha_servicos(): void
    {
        require_once __DIR__ . '/../public_html/includes/public-view.php';
        $servicos = [];

        ob_start();
        try {
            require __DIR__ . '/../public_html/includes/layout/home/services.php';
        } finally {
            $html = (string) ob_get_clean();
        }

        self::assertStringContainsString('role="status"', $html);
        self::assertStringContainsString('estarão disponíveis em breve', $html);
        self::assertStringContainsString('<section id="servicos"', $html);
        self::assertStringNotContainsString('Warning', $html);
    }

    public function test_preserva_secoes_complementares_estaticas_da_home(): void
    {
        ob_start();
        require __DIR__ . '/../public_html/includes/layout/home/sessions-packages.php';
        require __DIR__ . '/../public_html/includes/layout/home/availability-gallery.php';
        require __DIR__ . '/../public_html/includes/layout/home/contact-footer.php';
        $html = (string) ob_get_clean();
        $html = (string) preg_replace('/\s+/', ' ', $html);

        self::assertStringContainsString('Tabela de <span class="script">Sessões</span>', $html);
        self::assertStringContainsString('Pacote Total Flex', $html);
        self::assertStringContainsString('Horários <span class="script">Disponíveis</span>', $html);
        self::assertStringContainsString('Galeria de', $html);
        self::assertStringContainsString('Tratamentos', $html);
        self::assertStringContainsString('id="contato"', $html);
        self::assertStringContainsString('<footer class="footer">', $html);
        self::assertStringContainsString('48 99613-7757', $html);
    }
}
