<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/errors.php';

final class ErrorHandlingTest extends TestCase
{
    public function test_diagnostico_nao_inclui_mensagem_dados_pessoais_ou_caminho_absoluto(): void
    {
        $erro = new RuntimeException('SQL cliente: Maria, telefone 48999991234, senha=segredo');
        $registro = formatarErroAplicacao($erro, 'abcdef0123456789');
        self::assertStringContainsString('abcdef0123456789', $registro);
        self::assertStringContainsString('RuntimeException', $registro);
        self::assertStringNotContainsString('Maria', $registro);
        self::assertStringNotContainsString('48999991234', $registro);
        self::assertStringNotContainsString('segredo', $registro);
        self::assertStringNotContainsString(dirname(__DIR__), $registro);
    }
    public function test_id_de_requisicao_tem_formato_seguro(): void
    {
        $id = gerarRequestId();
        self::assertMatchesRegularExpression('/^[a-f0-9]{16}$/', $id);
    }

    public function test_catalogo_publico_tem_textos_humanizados_para_os_status_suportados(): void
    {
        self::assertSame('Este espaço é reservado.', dadosPaginaErro(403)['titulo']);
        self::assertSame('Parece que este caminho se perdeu.', dadosPaginaErro(404)['titulo']);
        self::assertSame('Nossa casa fez uma pausa inesperada.', dadosPaginaErro(500)['titulo']);
        self::assertSame('A ponte até o nosso espaço falhou por um instante.', dadosPaginaErro(502)['titulo']);
        self::assertSame('Estamos preparando o espaço para receber você.', dadosPaginaErro(503)['titulo']);
        self::assertSame('O atendimento digital demorou mais que o normal.', dadosPaginaErro(504)['titulo']);
        self::assertSame(
            'Nossa casa fez uma pausa inesperada.',
            dadosPaginaErro(418)['titulo']
        );
    }
}
