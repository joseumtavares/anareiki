<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../public_html/includes/errors.php';

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

    public function test_mensagem_publica_nao_expoe_detalhes_da_excecao(): void
    {
        self::assertSame('Ocorreu um erro inesperado. Informe o código de atendimento.', mensagemErroPublica(500));
        self::assertSame('A página solicitada não foi encontrada.', mensagemErroPublica(404));
        self::assertSame('Você não tem permissão para acessar este recurso.', mensagemErroPublica(403));
    }
}
