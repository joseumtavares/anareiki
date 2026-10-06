<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../public_html/includes/db.php';
require_once __DIR__ . '/../public_html/includes/public-view.php';
require_once __DIR__ . '/../public_html/includes/repositories.php';

final class ServicoAdminRepositoryTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('CREATE TABLE servicos (
            id TEXT PRIMARY KEY, nome TEXT NOT NULL, descricao TEXT NOT NULL,
            duracao_min INTEGER NOT NULL, preco NUMERIC, categoria TEXT NOT NULL,
            imagem_url TEXT, icone TEXT, cor TEXT, tag TEXT,
            ativo INTEGER NOT NULL DEFAULT 1, ordem INTEGER NOT NULL DEFAULT 0
        )');
        $this->pdo->exec('CREATE TABLE profissional_servico (profissional_id TEXT, servico_id TEXT)');
        $this->pdo->exec('CREATE TABLE agendamentos (id TEXT, servico_id TEXT)');
        $this->pdo->exec("INSERT INTO servicos
            (id, nome, descricao, duracao_min, preco, categoria, ativo, ordem)
            VALUES ('ativo', 'Relaxante', 'Descrição', 60, 75.00, 'Massagens', 1, 2),
                   ('inativo', 'Antigo', 'Descrição antiga', 90, NULL, 'Terapias', 0, 1)");
    }

    public function test_lista_admin_retorna_ativos_e_inativos_em_ordem(): void
    {
        $servicos = listarServicosAdmin($this->pdo);

        self::assertSame(['inativo', 'ativo'], array_column($servicos, 'id'));
        self::assertSame(0, (int) $servicos[0]['ativo']);
    }

    public function test_cria_e_atualiza_servico(): void
    {
        $dados = [
            'nome' => 'Reiki', 'descricao' => 'Equilíbrio', 'duracao_min' => 60,
            'preco' => null, 'categoria' => 'Terapias', 'imagem_url' => null,
            'icone' => 'fa-hands', 'cor' => 'purple', 'tag' => null, 'ordem' => 3,
        ];
        $id = salvarServicoAdmin($this->pdo, $dados);
        self::assertTrue(uuidValido($id));
        self::assertSame('Reiki', obterServicoAdmin($this->pdo, $id)['nome']);

        $dados['id'] = $id;
        $dados['nome'] = 'Reiki atualizado';
        salvarServicoAdmin($this->pdo, $dados);
        self::assertSame('Reiki atualizado', obterServicoAdmin($this->pdo, $id)['nome']);
    }

    public function test_altera_ativo_sem_remover_registro(): void
    {
        alterarAtivoServico($this->pdo, 'ativo', false);

        self::assertSame(0, (int) obterServicoAdmin($this->pdo, 'ativo')['ativo']);
    }

    public function test_validacao_rejeita_duracao_preco_e_url_invalidos(): void
    {
        $erros = validarDadosServicoAdmin([
            'nome' => '', 'descricao' => '', 'duracao_min' => '0', 'preco' => '-1',
            'categoria' => '', 'imagem_url' => 'javascript:alert(1)', 'ordem' => '-2',
        ]);

        self::assertArrayHasKey('nome', $erros);
        self::assertArrayHasKey('duracao_min', $erros);
        self::assertArrayHasKey('preco', $erros);
        self::assertArrayHasKey('imagem_url', $erros);
        self::assertArrayHasKey('ordem', $erros);
    }

    public function test_exclui_servico_sem_vinculos(): void
    {
        excluirServicoAdmin($this->pdo, 'inativo');

        self::assertNull(obterServicoAdmin($this->pdo, 'inativo'));
    }

    public function test_rejeita_exclusao_com_vinculo_ou_agendamento(): void
    {
        $this->pdo->exec("INSERT INTO profissional_servico VALUES ('p1', 'ativo')");

        $this->expectException(DomainException::class);
        excluirServicoAdmin($this->pdo, 'ativo');
    }

    public function test_detecta_imagem_em_uso(): void
    {
        $this->pdo->exec("UPDATE servicos SET imagem_url = '/uploads/servicos/" . str_repeat('a', 32) . ".jpg' WHERE id = 'ativo'");
        self::assertTrue(imagemServicoEmUso($this->pdo, '/uploads/servicos/' . str_repeat('a', 32) . '.jpg'));
    }
}
