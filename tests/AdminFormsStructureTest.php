<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AdminFormsStructureTest extends TestCase
{
    public function test_servicos_nao_tem_formularios_aninhados_nem_confirmacao_inline(): void
    {
        $source = (string) file_get_contents(__DIR__ . '/../public_html/admin/servicos.php');
        preg_match_all('~</?form\b[^>]*>~', $source, $matches);
        $profundidade = 0;
        foreach ($matches[0] as $tag) {
            $profundidade += str_starts_with($tag, '</') ? -1 : 1;
            self::assertLessThanOrEqual(1, $profundidade);
            self::assertGreaterThanOrEqual(0, $profundidade);
        }
        self::assertSame(0, $profundidade);
        self::assertStringNotContainsString('onsubmit=', $source);
    }

    public function test_layout_usa_script_local_para_menu_e_confirmacoes(): void
    {
        $source = (string) file_get_contents(__DIR__ . '/../public_html/includes/layout/admin.php');
        self::assertStringNotContainsString('<script src="https://', $source);
        self::assertStringContainsString('/static/admin-ui.js', $source);
    }

    public function test_erro_de_upload_profissional_nao_e_sobrescrito(): void
    {
        $source = (string) file_get_contents(__DIR__ . '/../public_html/admin/profissionais.php');
        self::assertStringContainsString('array_merge($erros, validarDadosProfissionalAdmin', $source);
    }
}
