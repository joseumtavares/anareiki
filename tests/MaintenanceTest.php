<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/maintenance.php';

final class MaintenanceTest extends TestCase
{
    public function test_modo_de_manutencao_exige_booleano_verdadeiro(): void
    {
        self::assertTrue(modoManutencaoAtivo(['maintenance_mode' => true]));
        self::assertFalse(modoManutencaoAtivo([]));
        self::assertFalse(modoManutencaoAtivo(['maintenance_mode' => false]));
        self::assertFalse(modoManutencaoAtivo(['maintenance_mode' => 'true']));
    }

    public function test_resposta_json_de_manutencao_tem_contrato_publico_seguro(): void
    {
        $resposta = dadosRespostaManutencao(true);

        self::assertSame(503, $resposta['status']);
        self::assertSame('application/json; charset=utf-8', $resposta['content_type']);
        self::assertSame(3600, $resposta['retry_after']);
        self::assertSame(
            ['erro' => 'O site está em manutenção. Tente novamente em breve.'],
            json_decode((string) $resposta['corpo'], true, 512, JSON_THROW_ON_ERROR)
        );
        self::assertStringNotContainsString('<html', (string) $resposta['corpo']);
    }

    public function test_resposta_html_de_manutencao_deixa_renderizacao_para_pagina_503(): void
    {
        $resposta = dadosRespostaManutencao(false);

        self::assertSame(503, $resposta['status']);
        self::assertSame('text/html; charset=utf-8', $resposta['content_type']);
        self::assertSame(3600, $resposta['retry_after']);
        self::assertNull($resposta['corpo']);
    }

    public function test_entradas_publicas_chamam_guard_antes_de_abrir_banco_e_admin_permanece_livre(): void
    {
        $entradasHtml = ['index.php', 'confirmacao-agendamento.php'];
        $entradasJson = [
            'agendar.php',
            'api/profissionais.php',
            'api/slots.php',
            'api/disponibilidade.php',
        ];

        foreach ($entradasHtml as $arquivo) {
            $fonte = (string) file_get_contents(__DIR__ . '/../' . $arquivo);
            $guard = strpos($fonte, 'interromperSeEmManutencao(false)');
            $banco = strpos($fonte, 'db()');

            self::assertNotFalse($guard, $arquivo);
            self::assertNotFalse($banco, $arquivo);
            self::assertLessThan(
                $banco,
                $guard,
                $arquivo
            );
        }
        foreach ($entradasJson as $arquivo) {
            $fonte = (string) file_get_contents(__DIR__ . '/../' . $arquivo);
            $guard = strpos($fonte, 'interromperSeEmManutencao(true)');
            $banco = strpos($fonte, 'db()');

            self::assertNotFalse($guard, $arquivo);
            self::assertNotFalse($banco, $arquivo);
            self::assertLessThan(
                $banco,
                $guard,
                $arquivo
            );
        }

        foreach (glob(__DIR__ . '/../admin/*.php') ?: [] as $arquivo) {
            self::assertStringNotContainsString('interromperSeEmManutencao', (string) file_get_contents($arquivo));
        }
    }

    public function test_modelo_de_configuracao_desliga_manutencao_por_padrao(): void
    {
        $modelo = (string) file_get_contents(__DIR__ . '/../config.example.php');

        self::assertStringContainsString("'maintenance_mode' => false", $modelo);
    }
}
