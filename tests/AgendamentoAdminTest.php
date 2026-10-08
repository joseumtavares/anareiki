<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../public_html/includes/agendamentos-admin.php';

final class AgendamentoAdminTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE agendamentos (
            id TEXT PRIMARY KEY, data TEXT, hora_inicio TEXT, hora_fim TEXT,
            servico_id TEXT, profissional_id TEXT, status TEXT, cliente_nome TEXT,
            cliente_telefone TEXT, cliente_email TEXT)');
        $this->pdo->exec('CREATE TABLE servicos (id TEXT PRIMARY KEY, nome TEXT)');
        $this->pdo->exec('CREATE TABLE profissionais (id TEXT PRIMARY KEY, nome TEXT)');
        $this->pdo->exec("INSERT INTO servicos VALUES ('s', 'Reiki')");
        $this->pdo->exec("INSERT INTO profissionais VALUES ('p', 'Ana')");
        $this->pdo->exec("INSERT INTO agendamentos VALUES
            ('a', '2026-10-07', '09:00', '10:00', 's', 'p', 'pendente', 'Cliente',
             '(48) 99999-1234', 'cliente@example.test')");
    }

    public function test_transicoes_validas_e_dados_do_cliente_preservados(): void
    {
        alterarStatusAgendamentoAdmin($this->pdo, 'a', 'pendente', 'confirmado');
        alterarStatusAgendamentoAdmin($this->pdo, 'a', 'confirmado', 'concluido');
        self::assertSame('concluido', $this->pdo->query('SELECT status FROM agendamentos')->fetchColumn());
        self::assertSame('Cliente', $this->pdo->query('SELECT cliente_nome FROM agendamentos')->fetchColumn());
    }

    public function test_transicao_invalida_nao_altera_registro(): void
    {
        try {
            alterarStatusAgendamentoAdmin($this->pdo, 'a', 'pendente', 'concluido');
            self::fail('A transição deveria falhar.');
        } catch (DomainException) {
            self::assertSame('pendente', $this->pdo->query('SELECT status FROM agendamentos')->fetchColumn());
        }
    }

    public function test_acao_concorrente_e_bloqueada(): void
    {
        alterarStatusAgendamentoAdmin($this->pdo, 'a', 'pendente', 'confirmado');
        $this->expectException(DomainException::class);
        alterarStatusAgendamentoAdmin($this->pdo, 'a', 'pendente', 'cancelado');
    }

    public function test_listagem_filtra_e_minimiza_dados(): void
    {
        $rows = listarAgendamentosAdmin($this->pdo, ['inicio' => '2026-10-07', 'status' => 'pendente']);
        self::assertCount(1, $rows);
        self::assertSame('Cliente', $rows[0]['cliente_nome']);
        self::assertSame('(48) 99999-1234', $rows[0]['cliente_telefone']);
        self::assertArrayNotHasKey('cliente_email', $rows[0]);
        self::assertSame([], listarAgendamentosAdmin($this->pdo, ['status' => 'cancelado']));
    }

    public function test_filtro_data_invalida_e_rejeitado(): void
    {
        $this->expectException(InvalidArgumentException::class);
        listarAgendamentosAdmin($this->pdo, ['inicio' => '2026-02-30']);
    }

    public function test_link_whatsapp_normaliza_telefone_brasileiro(): void
    {
        self::assertSame('https://wa.me/5548999991234', linkWhatsAppCliente('(48) 99999-1234'));
        self::assertSame('https://wa.me/5548999991234', linkWhatsAppCliente('+55 (48) 99999-1234'));
        self::assertSame('https://wa.me/554833331234', linkWhatsAppCliente('48 3333-1234'));
        self::assertSame('https://wa.me/5555999991234', linkWhatsAppCliente('(55) 99999-1234'));
    }

    public function test_link_whatsapp_nao_gerado_para_telefone_invalido(): void
    {
        foreach (['', '123', 'javascript:alert(48999991234)', '00000000000'] as $telefone) {
            self::assertNull(linkWhatsAppCliente($telefone));
        }
    }
}
