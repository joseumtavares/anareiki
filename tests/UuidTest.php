<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../public_html/includes/db.php';

final class UuidTest extends TestCase
{
    public function test_gera_uuid_v7_no_formato_canonico(): void
    {
        $uuid = gerarUuid();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid
        );
    }

    public function test_uuids_sao_unicos(): void
    {
        $uuids = [];
        for ($i = 0; $i < 1000; $i++) {
            $uuids[] = gerarUuid();
        }

        $this->assertCount(1000, array_unique($uuids));
    }

    public function test_uuids_gerados_depois_ordenam_depois(): void
    {
        $primeiro = gerarUuid();
        usleep(2000);
        $segundo = gerarUuid();

        $this->assertLessThan(0, strcmp($primeiro, $segundo));
    }

    public function test_valida_uuid_do_seed(): void
    {
        $this->assertTrue(uuidValido('01a0db02-f800-76df-a6eb-97a169b3083f'));
    }

    public function test_rejeita_entrada_que_nao_e_uuid(): void
    {
        $valoresInvalidos = [
            '',
            '1',
            'abc',
            "1' OR '1'='1",
            '01a0db02-f800-76df-a6eb-97a169b3083f ',
            '01a0db02f80076dfa6eb97a169b3083f',
        ];
        foreach ($valoresInvalidos as $valor) {
            $this->assertFalse(uuidValido($valor), "deveria rejeitar: {$valor}");
        }
    }
}
