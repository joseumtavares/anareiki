<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../public_html/includes/errors.php';

final class ErrorHandlingTest extends TestCase
{
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
