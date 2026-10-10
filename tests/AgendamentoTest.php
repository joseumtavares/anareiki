<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/repositories.php';
$slotsFile = __DIR__ . '/../includes/slots.php';
if (is_file($slotsFile)) {
    require_once $slotsFile;
}
final class AgendamentoTest extends TestCase
{
    public function test_migration_de_itens_preserva_agendamentos_legados(): void
    {
        $migration = __DIR__ . '/../sql/migrations/005_agendamento_servicos.sql';
        self::assertFileExists($migration);
        $sql = (string) file_get_contents($migration);
        self::assertStringContainsString('CREATE TABLE agendamento_servicos', $sql);
        self::assertStringContainsString('UNIQUE KEY uq_agendamento_servico', $sql);
        self::assertStringContainsString('INSERT INTO agendamento_servicos', $sql);
        self::assertStringContainsString('FROM agendamentos a', $sql);
        self::assertStringContainsString("VALUES ('005_agendamento_servicos')", $sql);
    }
    public function test_migracao_de_horarios_por_servico_preserva_intervalos_legados(): void
    {
        $migration = __DIR__ . '/../sql/migrations/006_horarios_por_servico.sql';
        self::assertFileExists($migration);
        $sql = (string) file_get_contents($migration);
        self::assertStringContainsString('ADD COLUMN hora_inicio TIME NULL', $sql);
        self::assertStringContainsString('ADD COLUMN hora_fim TIME NULL', $sql);
        self::assertStringContainsString('UPDATE agendamento_servicos i', $sql);
        self::assertStringContainsString('a.hora_inicio', $sql);
        self::assertStringContainsString('a.hora_fim', $sql);
        self::assertStringContainsString('MODIFY hora_inicio TIME NOT NULL', $sql);
        self::assertStringContainsString('MODIFY hora_fim TIME NOT NULL', $sql);
        self::assertStringContainsString("VALUES ('006_horarios_por_servico')", $sql);
    }
    public function test_grava_fim_arredondado_conforme_intervalo_do_profissional(): void
    {
        $this->pdo->exec('CREATE TABLE disponibilidade_datas (
            profissional_id TEXT, data TEXT, horarios TEXT,
            PRIMARY KEY (profissional_id, data))');
        foreach ([30 => '10:30', 60 => '11:00'] as $intervalo => $fimEsperado) {
            $data = $intervalo === 30 ? '2030-10-09' : '2030-10-10';
            salvarDisponibilidadeData($this->pdo, self::ANA_ID, $data, ['09:00', '10:00'], $intervalo);
            $id = criarAgendamento(
                $this->pdo,
                self::SERVICO_ID,
                self::ANA_ID,
                $data,
                '09:00',
                75,
                'Cliente',
                '48999999999'
            );
            $stmt = $this->pdo->prepare('SELECT hora_fim FROM agendamentos WHERE id = ?');
            $stmt->execute([$id]);
            self::assertSame($fimEsperado, $stmt->fetchColumn());
        }
    }
    private PDO $pdo;
    private const ANA_ID = '01a0db02-f800-76df-a6eb-97a169b3083f';
    private const SERVICO_ID = '01a0db02-f801-752e-811c-921ba4595ebb';
    private const SERVICO2_ID = '01a0db02-f802-7ad2-ae14-3374b133abbe';
    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('CREATE TABLE profissionais (
            id TEXT PRIMARY KEY, nome TEXT NOT NULL, especialidade TEXT,
            bio TEXT, foto_url TEXT, ativo INTEGER NOT NULL DEFAULT 1,
            criado_em TEXT DEFAULT CURRENT_TIMESTAMP
        )');
        $this->pdo->exec('CREATE TABLE servicos (
            id TEXT PRIMARY KEY, nome TEXT NOT NULL, descricao TEXT NOT NULL,
            duracao_min INTEGER NOT NULL DEFAULT 60, preco NUMERIC,
            categoria TEXT NOT NULL, imagem_url TEXT, icone TEXT, cor TEXT,
            tag TEXT, ativo INTEGER NOT NULL DEFAULT 1, ordem INTEGER NOT NULL DEFAULT 0
        )');
        $this->pdo->exec('CREATE TABLE profissional_servico (
            profissional_id TEXT NOT NULL, servico_id TEXT NOT NULL,
            PRIMARY KEY (profissional_id, servico_id)
        )');
        $this->pdo->exec('CREATE TABLE disponibilidade (
            id TEXT PRIMARY KEY, profissional_id TEXT NOT NULL,
            dia_semana INTEGER NOT NULL, hora_inicio TEXT NOT NULL,
            hora_fim TEXT NOT NULL
        )');
        $this->pdo->exec('CREATE TABLE agendamentos (
            id TEXT PRIMARY KEY, servico_id TEXT NOT NULL,
            profissional_id TEXT NOT NULL, cliente_nome TEXT NOT NULL,
            cliente_telefone TEXT NOT NULL, cliente_email TEXT,
            data TEXT NOT NULL, hora_inicio TEXT NOT NULL, hora_fim TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT \'pendente\', observacao TEXT,
            criado_em TEXT DEFAULT CURRENT_TIMESTAMP
        )');
        $this->pdo->exec("CREATE UNIQUE INDEX uq_agend_slot
            ON agendamentos (profissional_id, data, hora_inicio)");
        $this->pdo->exec('CREATE TABLE agendamento_servicos (
            id TEXT PRIMARY KEY, agendamento_id TEXT NOT NULL,
            servico_id TEXT NOT NULL, nome_servico TEXT NOT NULL,
            duracao_min INTEGER NOT NULL, preco NUMERIC, ordem INTEGER NOT NULL,
            hora_inicio TEXT NOT NULL, hora_fim TEXT NOT NULL,
            UNIQUE (agendamento_id, servico_id)
        )');
        $this->pdo->exec("INSERT INTO profissionais (id, nome, ativo)
            VALUES ('" . self::ANA_ID . "', 'Ana', 1)");
        $this->pdo->exec("INSERT INTO profissionais (id, nome, ativo)
            VALUES ('prof-inativo', 'Inativa', 0)");
        $this->pdo->exec("INSERT INTO servicos
            (id, nome, descricao, duracao_min, preco, categoria, ativo, ordem)
            VALUES ('" . self::SERVICO_ID . "', 'Massagem Relaxante',
            'Desc', 60, 75.00, 'Massagens', 1, 1)");
        $this->pdo->exec("INSERT INTO servicos
            (id, nome, descricao, duracao_min, preco, categoria, ativo, ordem)
            VALUES ('" . self::SERVICO2_ID . "', 'Massagem Terapêutica',
            'Desc', 60, 80.00, 'Massagens', 1, 2)");
        $this->pdo->exec("INSERT INTO profissional_servico
            VALUES ('" . self::ANA_ID . "', '" . self::SERVICO_ID . "')");
        $this->pdo->exec("INSERT INTO profissional_servico
            VALUES ('" . self::ANA_ID . "', '" . self::SERVICO2_ID . "')");
        $this->pdo->exec("INSERT INTO disponibilidade
            (id, profissional_id, dia_semana, hora_inicio, hora_fim)
            VALUES ('d1', '" . self::ANA_ID . "', 6, '09:00', '18:00')");
    }
    public function test_profissionais_por_servico_retorna_ativos(): void
    {
        $result = obterProfissionaisPorServico($this->pdo, self::SERVICO_ID);
        self::assertCount(1, $result);
        self::assertSame('Ana', $result[0]['nome']);
        self::assertSame(self::ANA_ID, $result[0]['id']);
        self::assertSame(['id', 'nome'], array_keys($result[0]));
    }
    public function test_profissionais_por_servico_inexistente(): void
    {
        $result = obterProfissionaisPorServico(
            $this->pdo,
            '00000000-0000-7000-8000-000000000000'
        );
        self::assertSame([], $result);
    }
    public function test_profissionais_por_servicos_exige_todos_os_servicos(): void
    {
        $result = obterProfissionaisPorServicos($this->pdo, [
            self::SERVICO_ID,
            self::SERVICO2_ID,
        ]);
        self::assertCount(1, $result);
        self::assertSame(self::ANA_ID, $result[0]['id']);
        $this->pdo->exec("DELETE FROM profissional_servico WHERE servico_id = '" . self::SERVICO2_ID . "'");
        self::assertSame([], obterProfissionaisPorServicos($this->pdo, [
            self::SERVICO_ID,
            self::SERVICO2_ID,
        ]));
    }
    public function test_criar_agendamento_grava_com_status_pendente(): void
    {
        $id = criarAgendamento(
            $this->pdo,
            self::SERVICO_ID,
            self::ANA_ID,
            '2026-10-03',
            '10:00',
            60,
            'João Silva',
            '48996249817'
        );
        self::assertTrue(uuidValido($id));
        $stmt = $this->pdo->prepare(
            'SELECT * FROM agendamentos WHERE id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        self::assertSame('pendente', $row['status']);
        self::assertSame('João Silva', $row['cliente_nome']);
        self::assertSame('48996249817', $row['cliente_telefone']);
        self::assertNull($row['cliente_email']);
        self::assertNull($row['observacao']);
        self::assertSame('10:00', $row['hora_inicio']);
        self::assertSame('11:00', $row['hora_fim']);
    }
    public function test_criar_agendamento_duplicado_lanca_excecao(): void
    {
        criarAgendamento(
            $this->pdo,
            self::SERVICO_ID,
            self::ANA_ID,
            '2026-10-03',
            '10:00',
            60,
            'Cliente 1',
            '48999999999'
        );
        $this->expectException(PDOException::class);
        criarAgendamento(
            $this->pdo,
            self::SERVICO_ID,
            self::ANA_ID,
            '2026-10-03',
            '10:00',
            60,
            'Cliente 2',
            '48888888888'
        );
    }
    public function test_hora_fim_calculada_corretamente(): void
    {
        $id = criarAgendamento(
            $this->pdo,
            self::SERVICO_ID,
            self::ANA_ID,
            '2026-10-03',
            '09:00',
            90,
            'Teste',
            '48999999999'
        );
        $stmt = $this->pdo->prepare(
            'SELECT hora_fim FROM agendamentos WHERE id = ?'
        );
        $stmt->execute([$id]);
        self::assertSame('10:30', $stmt->fetchColumn());
    }
    public function test_grava_horarios_individuais_para_servicos_separados(): void
    {
        $horarios = [self::SERVICO_ID => '09:00', self::SERVICO2_ID => '11:00'];
        $id = criarAgendamentoMultiplo(
            $this->pdo,
            [self::SERVICO_ID, self::SERVICO2_ID],
            self::ANA_ID,
            '2026-10-03',
            $horarios,
            'Cliente',
            '48999999999'
        );
        $pai = $this->pdo->prepare('SELECT hora_inicio, hora_fim FROM agendamentos WHERE id = ?');
        $pai->execute([$id]);
        self::assertSame(['09:00', '12:00'], array_values($pai->fetch()));
        $itens = $this->pdo->prepare(
            'SELECT hora_inicio, hora_fim FROM agendamento_servicos WHERE agendamento_id = ? ORDER BY ordem'
        );
        $itens->execute([$id]);
        $esperado = [
            ['hora_inicio' => '09:00', 'hora_fim' => '10:00'],
            ['hora_inicio' => '11:00', 'hora_fim' => '12:00'],
        ];
        self::assertSame($esperado, $itens->fetchAll());
    }
    public function test_obter_agendamento_retorna_dados_completos(): void
    {
        $id = criarAgendamento(
            $this->pdo,
            self::SERVICO_ID,
            self::ANA_ID,
            '2026-10-03',
            '10:00',
            60,
            'Maria',
            '48999999999'
        );
        $ag = obterAgendamento($this->pdo, $id);
        self::assertNotNull($ag);
        self::assertSame('Maria', $ag['cliente_nome']);
        self::assertSame('Massagem Relaxante', $ag['servico_nome']);
        self::assertSame('Ana', $ag['profissional_nome']);
    }
    public function test_obter_agendamento_inexistente_retorna_null(): void
    {
        $ag = obterAgendamento(
            $this->pdo,
            '00000000-0000-7000-8000-000000000000'
        );
        self::assertNull($ag);
    }
    public function test_validar_slot_disponivel_aceita_horario_livre(): void
    {
        $erro = validarSlotDisponivel(
            $this->pdo,
            self::SERVICO_ID,
            self::ANA_ID,
            '2026-10-03',
            '10:00'
        );
        self::assertNull($erro);
    }
    public function test_validar_slot_servico_inexistente(): void
    {
        $erro = validarSlotDisponivel(
            $this->pdo,
            '00000000-0000-7000-8000-000000000000',
            self::ANA_ID,
            '2026-10-03',
            '10:00'
        );
        self::assertNotNull($erro);
        self::assertStringContainsString('erviço', $erro);
    }
    public function test_validar_slot_profissional_nao_faz_servico(): void
    {
        $this->pdo->exec("INSERT INTO profissionais (id, nome, ativo)
            VALUES ('outro-prof', 'Outra', 1)");
        $erro = validarSlotDisponivel(
            $this->pdo,
            self::SERVICO_ID,
            'outro-prof',
            '2026-10-03',
            '10:00'
        );
        self::assertNotNull($erro);
        self::assertStringContainsString('não realiza', $erro);
    }
    public function test_validar_slot_fora_da_disponibilidade(): void
    {
        // Quinta (4) não tem disponibilidade
        $erro = validarSlotDisponivel(
            $this->pdo,
            self::SERVICO_ID,
            self::ANA_ID,
            '2026-10-01',
            '10:00'
        );
        self::assertNotNull($erro);
        self::assertStringContainsString('disponível', $erro);
    }
    public function test_validar_slot_horario_ocupado(): void
    {
        criarAgendamento(
            $this->pdo,
            self::SERVICO_ID,
            self::ANA_ID,
            '2026-10-03',
            '10:00',
            60,
            'Outro',
            '48999999999'
        );
        $erro = validarSlotDisponivel(
            $this->pdo,
            self::SERVICO_ID,
            self::ANA_ID,
            '2026-10-03',
            '10:00'
        );
        self::assertNotNull($erro);
        self::assertStringContainsString('ocupado', $erro);
    }
}
