<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../public_html/includes/db.php';
require_once __DIR__ . '/../public_html/includes/public-view.php';
require_once __DIR__ . '/../public_html/includes/repositories.php';

final class ProfissionalAdminRepositoryTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('CREATE TABLE profissionais (id TEXT PRIMARY KEY, nome TEXT NOT NULL, especialidade TEXT, bio TEXT, foto_url TEXT, ativo INTEGER DEFAULT 1)');
        $this->pdo->exec('CREATE TABLE servicos (id TEXT PRIMARY KEY, nome TEXT NOT NULL, ativo INTEGER DEFAULT 1)');
        $this->pdo->exec('CREATE TABLE profissional_servico (profissional_id TEXT, servico_id TEXT, PRIMARY KEY (profissional_id, servico_id))');
        $this->pdo->exec("INSERT INTO profissionais VALUES ('p1', 'Ana', 'Massoterapeuta', 'Bio', NULL, 1)");
        $this->pdo->exec("INSERT INTO servicos VALUES ('s1', 'Reiki', 1), ('s2', 'Massagem', 1)");
    }

    public function test_salva_profissional_e_vinculos_em_transacao(): void
    {
        $id = salvarProfissionalAdmin($this->pdo, [
            'nome' => 'Bia', 'especialidade' => 'Terapeuta', 'bio' => 'Bio', 'foto_url' => null,
            'servicos' => ['s1', 's2'],
        ]);
        self::assertSame(['s1', 's2'], listarServicosDoProfissional($this->pdo, $id));
    }

    public function test_validacao_rejeita_foto_externa_e_servico_inexistente(): void
    {
        $erros = validarDadosProfissionalAdmin($this->pdo, [
            'nome' => '', 'foto_url' => 'https://evil.example/foto.jpg', 'servicos' => ['inexistente'],
        ]);
        self::assertArrayHasKey('nome', $erros);
        self::assertArrayHasKey('foto_url', $erros);
        self::assertArrayHasKey('servicos', $erros);
    }

    public function test_exclui_profissional_sem_vinculos_ou_agendamentos(): void
    {
        $this->pdo->exec('CREATE TABLE agendamentos (id TEXT, profissional_id TEXT)');
        $this->pdo->exec("INSERT INTO profissionais VALUES ('p2', 'Bia', NULL, NULL, NULL, 1)");
        excluirProfissionalAdmin($this->pdo, 'p2');
        self::assertNull(obterProfissionalAdmin($this->pdo, 'p2'));
    }

    public function test_bloqueia_exclusao_de_profissional_vinculado(): void
    {
        $this->pdo->exec('CREATE TABLE agendamentos (id TEXT, profissional_id TEXT)');
        $this->pdo->exec("INSERT INTO profissional_servico VALUES ('p1', 's1')");
        $this->expectException(DomainException::class);
        excluirProfissionalAdmin($this->pdo, 'p1');
    }
}
