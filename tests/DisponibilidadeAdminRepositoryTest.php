<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../public_html/includes/db.php';
require_once __DIR__ . '/../public_html/includes/repositories.php';

final class DisponibilidadeAdminRepositoryTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('CREATE TABLE disponibilidade (id TEXT PRIMARY KEY, profissional_id TEXT, dia_semana INTEGER, hora_inicio TEXT, hora_fim TEXT)');
        $this->pdo->exec("INSERT INTO disponibilidade VALUES ('d1', 'p1', 1, '09:00', '12:00')");
    }

    public function test_validacao_rejeita_horarios_invalidos_e_sobreposicao(): void
    {
        self::assertArrayHasKey('hora_inicio', validarDadosDisponibilidadeAdmin($this->pdo, ['dia_semana' => '1', 'hora_inicio' => '12:00', 'hora_fim' => '12:00'], 'p1'));
        self::assertArrayHasKey('sobreposicao', validarDadosDisponibilidadeAdmin($this->pdo, ['dia_semana' => '1', 'hora_inicio' => '11:00', 'hora_fim' => '13:00'], 'p1'));
    }

    public function test_salva_e_remove_disponibilidade(): void
    {
        $id = salvarDisponibilidadeAdmin($this->pdo, ['profissional_id' => 'p1', 'dia_semana' => 2, 'hora_inicio' => '09:00', 'hora_fim' => '12:00']);
        self::assertNotNull(obterDisponibilidadeAdmin($this->pdo, $id));
        excluirDisponibilidadeAdmin($this->pdo, $id);
        self::assertNull(obterDisponibilidadeAdmin($this->pdo, $id));
    }
}
