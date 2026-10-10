<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/../includes/errors.php';

final class ErrorPagesTest extends TestCase
{
    #[DataProvider('paginasDeErro')]
    public function test_entrada_de_erro_renderiza_status_titulo_e_acao(
        int $status,
        string $arquivo,
        string $titulo,
        string $acao
    ): void {
        $_SERVER['HTTP_X_REQUEST_ID'] = '<img src=x onerror=alert(1)>';
        http_response_code(200);

        ob_start();
        require __DIR__ . '/../errors/' . $arquivo;
        $html = (string) ob_get_clean();

        self::assertSame($status, http_response_code());
        self::assertSame(1, substr_count($html, '<h1'));
        self::assertStringContainsString($titulo, $html);
        self::assertStringContainsString($acao, $html);
        self::assertStringContainsString('/static/errors.css', $html);
        self::assertStringNotContainsString('bootstrap', strtolower($html));
        self::assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
    }

    /** @return array<string, array{int, string, string, string}> */
    public static function paginasDeErro(): array
    {
        return [
            'acesso reservado' => [403, '403.php', 'Este espaço é reservado.', 'Voltar ao início'],
            'pagina perdida' => [404, '404.php', 'Parece que este caminho se perdeu.', 'Ir para o início'],
            'erro interno' => [500, '500.php', 'Nossa casa fez uma pausa inesperada.', 'Tentar novamente'],
            'ponte indisponivel' => [
                502,
                '502.php',
                'A ponte até o nosso espaço falhou por um instante.',
                'Tentar novamente',
            ],
            'manutencao' => [
                503,
                '503.php',
                'Estamos preparando o espaço para receber você.',
                'Voltar ao início',
            ],
            'tempo esgotado' => [
                504,
                '504.php',
                'O atendimento digital demorou mais que o normal.',
                'Tentar novamente',
            ],
        ];
    }

    public function test_renderer_escapa_request_id_e_nao_depende_de_configuracao_ou_banco(): void
    {
        $arquivo = (string) file_get_contents(__DIR__ . '/../includes/errors.php');
        $template = (string) file_get_contents(__DIR__ . '/../errors/error-page.php');

        self::assertStringContainsString("htmlspecialchars(\$requestId", $arquivo);
        self::assertStringNotContainsString('config()', $arquivo);
        self::assertStringNotContainsString('db()', $arquivo);
        self::assertStringNotContainsString('config()', $template);
        self::assertStringNotContainsString('db()', $template);
    }

    public function test_css_de_erros_nao_importa_dependencias_remotas(): void
    {
        $css = (string) file_get_contents(__DIR__ . '/../static/errors.css');

        self::assertStringContainsString(':focus-visible', $css);
        self::assertStringContainsString('@media', $css);
        self::assertStringNotContainsString('@import', $css);
        self::assertDoesNotMatchRegularExpression('/https?:\\/\\//', $css);
    }

    public function test_apache_mapeia_todos_os_erros_e_protege_o_template_interno(): void
    {
        $htaccess = (string) file_get_contents(__DIR__ . '/../.htaccess');

        foreach ([403, 404, 500, 502, 503, 504] as $status) {
            self::assertStringContainsString(
                "ErrorDocument {$status} /errors/{$status}.php",
                $htaccess
            );
        }

        self::assertMatchesRegularExpression(
            '/RewriteRule\s+\^errors\/error-page\\\\\.php\$\s+-\s+\[NC,F,L\]/',
            $htaccess
        );

        foreach (['config\\.php', 'includes|vendor|sql|tests|bin|docs|\\.git', '\\.env'] as $regra) {
            self::assertStringContainsString($regra, $htaccess);
        }
    }

    public function test_renderer_de_503_declara_tempo_para_nova_tentativa(): void
    {
        $renderer = (string) file_get_contents(__DIR__ . '/../includes/errors.php');

        self::assertStringContainsString("header('Retry-After: 3600')", $renderer);
    }
}
