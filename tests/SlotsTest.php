<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/repositories.php';

$slotsFile = __DIR__ . '/../includes/slots.php';
if (is_file($slotsFile)) {
    require_once $slotsFile;
}

final class SlotsTest extends TestCase
{
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
            hora_inicio TEXT NOT NULL, hora_fim TEXT NOT NULL
        )');

        $this->pdo->exec("INSERT INTO profissionais (id, nome, especialidade, ativo)
            VALUES ('" . self::ANA_ID . "', 'Ana', 'Massoterapeuta', 1)");
        $this->pdo->exec("INSERT INTO servicos
            (id, nome, descricao, duracao_min, preco, categoria, ativo, ordem)
            VALUES ('" . self::SERVICO_ID . "', 'Massagem Relaxante',
            'Descrição', 60, 75.00, 'Massagens', 1, 1)");
        $this->pdo->exec("INSERT INTO profissional_servico
            (profissional_id, servico_id)
            VALUES ('" . self::ANA_ID . "', '" . self::SERVICO_ID . "')");
        $this->pdo->exec("INSERT INTO servicos
            (id, nome, descricao, duracao_min, preco, categoria, ativo, ordem)
            VALUES ('" . self::SERVICO2_ID . "', 'Reiki',
            'DescriÃ§Ã£o', 30, 50.00, 'Massagens', 1, 2)");
        $this->pdo->exec("INSERT INTO profissional_servico
            (profissional_id, servico_id)
            VALUES ('" . self::ANA_ID . "', '" . self::SERVICO2_ID . "')");

        // Sábado (6) 09:00–18:00
        $this->pdo->exec("INSERT INTO disponibilidade
            (id, profissional_id, dia_semana, hora_inicio, hora_fim)
            VALUES ('d1', '" . self::ANA_ID . "', 6, '09:00', '18:00')");
        // Segunda (1) 18:00–22:00
        $this->pdo->exec("INSERT INTO disponibilidade
            (id, profissional_id, dia_semana, hora_inicio, hora_fim)
            VALUES ('d2', '" . self::ANA_ID . "', 1, '18:00', '22:00')");
    }

    private function inserirAgendamento(
        string $data,
        string $horaInicio,
        string $horaFim,
        string $status = 'pendente'
    ): void {
        $this->pdo->prepare(
            "INSERT INTO agendamentos
                (id, servico_id, profissional_id, cliente_nome,
                 cliente_telefone, data, hora_inicio, hora_fim, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            'ag-' . $horaInicio,
            self::SERVICO_ID,
            self::ANA_ID,
            'Teste',
            '48999999999',
            $data,
            $horaInicio,
            $horaFim,
            $status,
        ]);
    }

    // --- Testes de obterSlotsDisponiveis ---

    public function test_gerar_slots_sabado_sem_conflito(): void
    {
        // Sábado 09:00–18:00, duração 60 min, sem agendamentos
        // 2026-10-03 é sábado (date('w') = 6)
        $slots = obterSlotsDisponiveis(
            $this->pdo,
            self::ANA_ID,
            '2026-10-03',
            60
        );

        $esperado = [
            '09:00', '10:00', '11:00', '12:00',
            '13:00', '14:00', '15:00', '16:00', '17:00',
        ];
        self::assertSame($esperado, $slots);
    }

    public function test_gerar_slots_com_um_conflito(): void
    {
        $this->inserirAgendamento('2026-10-03', '10:00', '11:00');

        $slots = obterSlotsDisponiveis(
            $this->pdo,
            self::ANA_ID,
            '2026-10-03',
            60
        );

        self::assertNotContains('10:00', $slots);
        self::assertContains('09:00', $slots);
        self::assertContains('11:00', $slots);
    }

    public function test_gerar_slots_com_multiplos_conflitos(): void
    {
        $this->inserirAgendamento('2026-10-03', '09:00', '10:00');
        $this->inserirAgendamento('2026-10-03', '14:00', '15:00');

        $slots = obterSlotsDisponiveis(
            $this->pdo,
            self::ANA_ID,
            '2026-10-03',
            60
        );

        self::assertNotContains('09:00', $slots);
        self::assertNotContains('14:00', $slots);
        self::assertContains('10:00', $slots);
        self::assertContains('13:00', $slots);
        self::assertContains('15:00', $slots);
    }

    public function test_agendamento_cancelado_nao_bloqueia_slot(): void
    {
        $this->inserirAgendamento(
            '2026-10-03',
            '10:00',
            '11:00',
            'cancelado'
        );

        $slots = obterSlotsDisponiveis(
            $this->pdo,
            self::ANA_ID,
            '2026-10-03',
            60
        );

        self::assertContains('10:00', $slots);
    }

    public function test_sem_disponibilidade_retorna_vazio(): void
    {
        // Quinta (4) não tem disponibilidade no seed
        // 2026-10-01 é quinta
        $slots = obterSlotsDisponiveis(
            $this->pdo,
            self::ANA_ID,
            '2026-10-01',
            60
        );

        self::assertSame([], $slots);
    }

    public function test_duracao_30_min_gera_passos_corretos(): void
    {
        // Segunda 18:00–22:00, duração 30 min
        // 2026-10-05 é segunda
        $slots = obterSlotsDisponiveis(
            $this->pdo,
            self::ANA_ID,
            '2026-10-05',
            30
        );

        $esperado = [
            '18:00', '18:30', '19:00', '19:30',
            '20:00', '20:30', '21:00', '21:30',
        ];
        self::assertSame($esperado, $slots);
    }

    public function test_grade_indica_reserva_e_escolha_provisoria_sem_expor_cliente(): void
    {
        $this->inserirAgendamento('2026-10-03', '10:00', '11:00');

        $grade = obterEstadosSlotsServico(
            $this->pdo,
            self::ANA_ID,
            '2026-10-03',
            60,
            [['hora_inicio' => '11:00', 'hora_fim' => '12:00']]
        );

        self::assertSame(['inicio' => '09:00', 'fim' => '10:00', 'estado' => 'disponivel'], $grade[0]);
        self::assertSame('agendado', $grade[1]['estado']);
        self::assertSame('indisponivel', $grade[2]['estado']);
        self::assertArrayNotHasKey('cliente_nome', $grade[1]);
    }

    public function test_existe_combinacao_para_servicos_em_horarios_separados(): void
    {
        $this->inserirAgendamento('2026-10-03', '10:00', '11:00');

        self::assertTrue(existeCombinacaoHorarios(
            $this->pdo,
            self::ANA_ID,
            '2026-10-03',
            [
                ['id' => self::SERVICO_ID, 'duracao_min' => 60],
                ['id' => self::SERVICO2_ID, 'duracao_min' => 30],
            ]
        ));
    }

    public function test_estado_do_dia_indica_agendado_sem_disponibilidade_para_combinacao(): void
    {
        $this->inserirAgendamento('2026-10-03', '09:00', '18:00');

        self::assertSame('agendado', obterEstadoDiaAgendamento(
            $this->pdo,
            self::ANA_ID,
            '2026-10-03',
            [['id' => self::SERVICO_ID, 'duracao_min' => 60]]
        ));
    }

    // --- Testes de obterDisponibilidadeDia ---

    public function test_obter_disponibilidade_sabado(): void
    {
        $faixas = obterDisponibilidadeDia(
            $this->pdo,
            self::ANA_ID,
            6
        );

        self::assertCount(1, $faixas);
        self::assertSame('09:00', $faixas[0]['hora_inicio']);
        self::assertSame('18:00', $faixas[0]['hora_fim']);
    }

    public function test_obter_disponibilidade_dia_sem_faixa(): void
    {
        $faixas = obterDisponibilidadeDia($this->pdo, self::ANA_ID, 4);

        self::assertSame([], $faixas);
    }

    // --- Testes de obterAgendamentosDia ---

    public function test_obter_agendamentos_dia_com_registros(): void
    {
        $this->inserirAgendamento('2026-10-03', '10:00', '11:00');

        $agendamentos = obterAgendamentosDia(
            $this->pdo,
            self::ANA_ID,
            '2026-10-03'
        );

        self::assertCount(1, $agendamentos);
        self::assertSame('10:00', $agendamentos[0]['hora_inicio']);
        self::assertSame('11:00', $agendamentos[0]['hora_fim']);
    }

    public function test_obter_agendamentos_exclui_cancelados(): void
    {
        $this->inserirAgendamento(
            '2026-10-03',
            '10:00',
            '11:00',
            'cancelado'
        );
        $this->inserirAgendamento('2026-10-03', '14:00', '15:00');

        $agendamentos = obterAgendamentosDia(
            $this->pdo,
            self::ANA_ID,
            '2026-10-03'
        );

        self::assertCount(1, $agendamentos);
        self::assertSame('14:00', $agendamentos[0]['hora_inicio']);
    }
}
