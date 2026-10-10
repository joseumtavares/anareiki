<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HomePageTest extends TestCase
{
    public function test_mostra_estado_neutro_sem_profissionais_e_sem_cta_antigo(): void
    {
        require_once __DIR__ . '/../includes/public-view.php';
        $profissionais = [];

        ob_start();
        require __DIR__ . '/../includes/layout/home/about.php';
        $html = (string) ob_get_clean();

        self::assertStringContainsString('role="status"', $html);
        self::assertStringContainsString('estarão disponíveis em breve', $html);
        self::assertStringNotContainsString('Me chama para agendar!', $html);
    }

    public function test_navegacao_mantem_apenas_secoes_existentes(): void
    {
        ob_start();
        require __DIR__ . '/../includes/layout/home/top.php';
        $html = (string) ob_get_clean();

        self::assertStringContainsString('href="#sobre"', $html);
        self::assertStringContainsString('href="#servicos"', $html);
        self::assertStringContainsString('href="#pacotes"', $html);
        self::assertStringNotContainsString('href="#sessoes"', $html);
        self::assertStringNotContainsString('href="#horarios"', $html);
        self::assertStringNotContainsString('data-booking-open', $html);
    }

    public function test_renderiza_profissionais_com_escape_e_sem_foto_invalida(): void
    {
        require_once __DIR__ . '/../includes/public-view.php';
        $profissionais = [[
            'nome' => '<script>alert("Ana")</script>',
            'especialidade' => 'Massagem & Reiki',
            'bio' => 'Cuida de "você"',
            'foto_url' => null,
        ]];

        ob_start();
        require __DIR__ . '/../includes/layout/home/about.php';
        $html = (string) ob_get_clean();

        self::assertStringContainsString('&lt;script&gt;alert(&quot;Ana&quot;)&lt;/script&gt;', $html);
        self::assertStringContainsString('Massagem &amp; Reiki', $html);
        self::assertStringNotContainsString('<script>alert("Ana")</script>', $html);
        self::assertStringNotContainsString('Me chama para agendar!', $html);
    }

    public function test_renderiza_catalogo_com_dados_escapados_e_cta_do_painel(): void
    {
        require_once __DIR__ . '/../includes/public-view.php';
        $servicos = [[
            'id' => 'servico-1',
            'nome' => '<script>alert("XSS")</script>',
            'descricao' => 'Relaxamento & cuidado',
            'duracao_min' => 90,
            'preco' => '75.00',
            'imagem_url' => 'https://www.genspark.ai/api/files/s/imagem',
            'icone' => null,
            'cor' => null,
            'tag' => 'Mais "pedida"',
        ]];

        ob_start();
        require __DIR__ . '/../includes/layout/home/services.php';
        $html = (string) ob_get_clean();

        self::assertStringContainsString('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', $html);
        self::assertStringContainsString('Relaxamento &amp; cuidado', $html);
        self::assertStringNotContainsString('<script>alert("XSS")</script>', $html);
        self::assertStringContainsString('data-booking-service="servico-1"', $html);
    }

    public function test_mantem_pacotes_e_reduz_rodape_ao_bloco_da_marca(): void
    {
        ob_start();
        require __DIR__ . '/../includes/layout/home/sessions-packages.php';
        require __DIR__ . '/../includes/layout/home/contact-footer.php';
        $html = (string) ob_get_clean();

        self::assertStringContainsString('Pacote Total Flex', $html);
        self::assertStringNotContainsString('id="sessoes"', $html);
        self::assertStringNotContainsString('id="horarios"', $html);
        self::assertStringNotContainsString('id="contato"', $html);
        self::assertStringContainsString('Cuidando do seu bem-estar com técnicas naturais e muito amor.', $html);
        self::assertStringContainsString('footer-social', $html);
        self::assertStringNotContainsString('footer-links', $html);
        self::assertStringNotContainsString('footer-horarios', $html);
    }

    public function test_grade_visual_alinha_colunas_e_distribui_cards(): void
    {
        $aboutCss = (string) file_get_contents(__DIR__ . '/../static/style-02-hero-about.css');
        $servicesCss = (string) file_get_contents(__DIR__ . '/../static/style-03-services-sessions.css');
        $packagesCss = (string) file_get_contents(__DIR__ . '/../static/style-04-packages-hours.css');
        $footerCss = (string) file_get_contents(__DIR__ . '/../static/style-06-footer-responsive.css');

        self::assertMatchesRegularExpression('/\.sobre-grid\s*\{[\s\S]*?align-items:\s*stretch;/', $aboutCss);
        self::assertStringContainsString('grid-auto-rows: 1fr;', $servicesCss);
        self::assertStringContainsString('grid-auto-rows: 1fr;', $packagesCss);
        self::assertMatchesRegularExpression('/\.footer-grid\s*\{[\s\S]*?justify-content:\s*center;/', $footerCss);
    }
}
