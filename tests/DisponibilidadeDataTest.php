<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../public_html/includes/slots.php';

final class DisponibilidadeDataTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('CREATE TABLE profissionais (id TEXT PRIMARY KEY)');
        $this->pdo->exec("INSERT INTO profissionais VALUES ('p')");
        $this->pdo->exec('CREATE TABLE disponibilidade (
            profissional_id TEXT, dia_semana INTEGER, hora_inicio TEXT, hora_fim TEXT)');
        $this->pdo->exec('CREATE TABLE disponibilidade_datas (
            profissional_id TEXT, data TEXT, horarios TEXT,
            PRIMARY KEY (profissional_id, data))');
        $this->pdo->exec('CREATE TABLE agendamentos (
            profissional_id TEXT, data TEXT, hora_inicio TEXT, hora_fim TEXT, status TEXT)');
        $this->pdo->exec("INSERT INTO disponibilidade VALUES ('p', 3, '09:00', '12:00')");
    }

    public function test_data_bloqueada_substitui_regra_semanal(): void
    {
        salvarDisponibilidadeData($this->pdo, 'p', '2030-10-09', []);
        self::assertSame([], obterSlotsDisponiveis($this->pdo, 'p', '2030-10-09', 60));
    }

    public function test_horarios_contiguos_permitem_servico_e_preservam_reservas(): void
    {
        salvarDisponibilidadeData($this->pdo, 'p', '2030-10-09', ['09:00', '09:30', '10:00', '10:30']);
        $this->pdo->exec("INSERT INTO agendamentos VALUES
            ('p', '2030-10-09', '09:00', '10:00', 'confirmado')");
        self::assertSame(['10:00'], obterSlotsDisponiveis($this->pdo, 'p', '2030-10-09', 60));
        salvarDisponibilidadeData($this->pdo, 'p', '2030-10-09', []);
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM agendamentos')->fetchColumn());
    }

    public function test_lacunas_nao_permitem_servico_longo(): void
    {
        salvarDisponibilidadeData($this->pdo, 'p', '2030-10-09', ['09:00', '10:00']);
        self::assertSame([], obterSlotsDisponiveis($this->pdo, 'p', '2030-10-09', 60));
    }

    public function test_horario_invalido_e_rejeitado(): void
    {
        $this->expectException(InvalidArgumentException::class);
        salvarDisponibilidadeData($this->pdo, 'p', '2030-10-09', ['25:00']);
    }
}
