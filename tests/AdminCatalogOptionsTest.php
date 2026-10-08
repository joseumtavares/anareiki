<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../public_html/includes/admin.php';

final class AdminCatalogOptionsTest extends TestCase
{
    public function test_opcoes_visuais_sao_listas_fechadas(): void
    {
        self::assertContains('fa-hands', opcoesIconeServico());
        self::assertContains('purple', opcoesCorServico());
        self::assertContains('Mais Pedida', exemplosTagServico());
    }

    public function test_categorias_sao_normalizadas_e_sem_duplicidade(): void
    {
        self::assertSame(['Massagens', 'Terapias'], normalizarCategoriasServico([' terapias ', 'Massagens',
        'Terapias', '']));
    }
}
