<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../public_html/includes/repositories.php';

final class CategoriaAdminTest extends TestCase
{
    public function test_categoria_independe_de_servico_e_nao_duplica(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE categorias_servicos (nome TEXT PRIMARY KEY)');
        $pdo->exec('CREATE TABLE servicos (categoria TEXT)');
        $pdo->exec("INSERT INTO servicos VALUES ('Massagens')");
        salvarCategoriaServico($pdo, ' terapias ');
        salvarCategoriaServico($pdo, 'Terapias');
        self::assertSame(['Massagens', 'Terapias'], listarCategoriasServicos($pdo));
        self::assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM categorias_servicos')->fetchColumn());
    }

    public function test_categoria_vazia_e_rejeitada(): void
    {
        $this->expectException(InvalidArgumentException::class);
        salvarCategoriaServico(new PDO('sqlite::memory:'), ' ');
    }
}
