<?php

declare(strict_types=1);

require_once __DIR__ . '/public-view.php';

/** @return list<array<string, mixed>> */
function listarServicosAdmin(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT id, nome, descricao, duracao_min, preco, categoria, imagem_url, icone, cor, tag, ativo, ordem
         FROM servicos ORDER BY ordem ASC, nome ASC, id ASC'
    );

    /** @var list<array<string, mixed>> $servicos */
    $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $servicos;
}

/** @return array<string, mixed>|null */
function obterServicoAdmin(PDO $pdo, string $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, nome, descricao, duracao_min, preco, categoria, imagem_url, icone, cor, tag, ativo, ordem
         FROM servicos WHERE id = ?'
    );
    $stmt->execute([$id]);
    $servico = $stmt->fetch(PDO::FETCH_ASSOC);
    return $servico !== false ? $servico : null;
}

/** @param array<string, mixed> $dados @return array<string, string> */
function validarDadosServicoAdmin(array $dados): array
{
    $erros = [];
    $textoObrigatorio = ['nome' => 100, 'descricao' => 65535, 'categoria' => 50];
    foreach ($textoObrigatorio as $campo => $limite) {
        $valor = trim((string) ($dados[$campo] ?? ''));
        if ($valor === '') {
            $erros[$campo] = 'Preencha este campo.';
        } elseif (mb_strlen($valor) > $limite) {
            $erros[$campo] = 'O texto excede o limite permitido.';
        }
    }

    $duracao = filter_var($dados['duracao_min'] ?? null, FILTER_VALIDATE_INT);
    if ($duracao === false || $duracao < 1) {
        $erros['duracao_min'] = 'Informe uma duração inteira positiva.';
    }

    $preco = $dados['preco'] ?? null;
    if ($preco !== null && trim((string) $preco) !== '') {
        if (!is_numeric($preco) || (float) $preco < 0 || preg_match('/^\d{1,6}(?:\.\d{1,2})?$/', (string) $preco) !== 1) {
            $erros['preco'] = 'Informe um preço decimal não negativo.';
        }
    }

    $imagem = trim((string) ($dados['imagem_url'] ?? ''));
    if ($imagem !== '' && urlImagemServico($imagem) === null) {
        $erros['imagem_url'] = 'Informe uma URL de imagem permitida.';
    }
    foreach (['icone' => 50, 'cor' => 20, 'tag' => 50] as $campo => $limite) {
        if (mb_strlen(trim((string) ($dados[$campo] ?? ''))) > $limite) {
            $erros[$campo] = 'O texto excede o limite permitido.';
        }
    }

    $ordem = filter_var($dados['ordem'] ?? null, FILTER_VALIDATE_INT);
    if ($ordem === false || $ordem < 0) {
        $erros['ordem'] = 'Informe uma ordem inteira não negativa.';
    }

    return $erros;
}

/** @param array<string, mixed> $dados */
function salvarServicoAdmin(PDO $pdo, array $dados): string
{
    $id = isset($dados['id']) && is_string($dados['id']) && $dados['id'] !== ''
        ? $dados['id']
        : gerarUuid();
    $valores = [
        trim((string) $dados['nome']), trim((string) $dados['descricao']), (int) $dados['duracao_min'],
        ($dados['preco'] ?? '') === '' ? null : number_format((float) $dados['preco'], 2, '.', ''),
        trim((string) $dados['categoria']), trim((string) ($dados['imagem_url'] ?? '')) ?: null,
        trim((string) ($dados['icone'] ?? '')) ?: null, trim((string) ($dados['cor'] ?? '')) ?: null,
        trim((string) ($dados['tag'] ?? '')) ?: null, (int) $dados['ordem'],
    ];

    if (obterServicoAdmin($pdo, $id) === null) {
        $stmt = $pdo->prepare(
            'INSERT INTO servicos
             (id, nome, descricao, duracao_min, preco, categoria, imagem_url, icone, cor, tag, ativo, ordem)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)'
        );
        $stmt->execute([$id, ...$valores]);
        return $id;
    }

    $stmt = $pdo->prepare(
        'UPDATE servicos SET nome = ?, descricao = ?, duracao_min = ?, preco = ?, categoria = ?,
         imagem_url = ?, icone = ?, cor = ?, tag = ?, ordem = ? WHERE id = ?'
    );
    $stmt->execute([...$valores, $id]);
    return $id;
}

function alterarAtivoServico(PDO $pdo, string $id, bool $ativo): void
{
    $stmt = $pdo->prepare('UPDATE servicos SET ativo = ? WHERE id = ?');
    $stmt->execute([$ativo ? 1 : 0, $id]);
}

/** @return list<array<string, mixed>> */
function listarServicosPublicos(PDO $pdo): array
{
    $stmt = $pdo->prepare(
        'SELECT id, nome, descricao, duracao_min, preco, categoria, imagem_url, icone, cor, tag
         FROM servicos
         WHERE ativo = 1
         ORDER BY ordem ASC, nome ASC, id ASC'
    );
    $stmt->execute();

    /** @var list<array<string, mixed>> $servicos */
    $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $servicos;
}

/** @return list<array<string, mixed>> */
function listarProfissionaisPublicos(PDO $pdo): array
{
    $stmt = $pdo->prepare(
        'SELECT id, nome, especialidade, bio, foto_url
         FROM profissionais
         WHERE ativo = 1
         ORDER BY nome ASC, id ASC'
    );
    $stmt->execute();

    /** @var list<array<string, mixed>> $profissionais */
    $profissionais = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $profissionais;
}

/** @return list<array{hora_inicio: string, hora_fim: string}> */
function obterDisponibilidadeDia(PDO $pdo, string $profissionalId, int $diaSemana): array
{
    $stmt = $pdo->prepare(
        'SELECT hora_inicio, hora_fim
         FROM disponibilidade
         WHERE profissional_id = ? AND dia_semana = ?
         ORDER BY hora_inicio ASC'
    );
    $stmt->execute([$profissionalId, $diaSemana]);

    /** @var list<array{hora_inicio: string, hora_fim: string}> */
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** @return list<array{hora_inicio: string, hora_fim: string}> */
function obterAgendamentosDia(PDO $pdo, string $profissionalId, string $data): array
{
    $stmt = $pdo->prepare(
        "SELECT hora_inicio, hora_fim
         FROM agendamentos
         WHERE profissional_id = ? AND data = ? AND status != 'cancelado'
         ORDER BY hora_inicio ASC"
    );
    $stmt->execute([$profissionalId, $data]);

    /** @var list<array{hora_inicio: string, hora_fim: string}> */
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** @return list<array{id: string, nome: string}> */
function obterProfissionaisPorServico(PDO $pdo, string $servicoId): array
{
    $stmt = $pdo->prepare(
        'SELECT p.id, p.nome
         FROM profissionais p
         INNER JOIN profissional_servico ps ON ps.profissional_id = p.id
         WHERE ps.servico_id = ? AND p.ativo = 1
         ORDER BY p.nome ASC, p.id ASC'
    );
    $stmt->execute([$servicoId]);

    /** @var list<array{id: string, nome: string}> */
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function criarAgendamento(
    PDO $pdo,
    string $servicoId,
    string $profissionalId,
    string $data,
    string $horaInicio,
    int $duracaoMin,
    string $clienteNome,
    string $clienteTelefone
): string {
    $id = gerarUuid();
    $horaFim = date('H:i', strtotime($horaInicio) + $duracaoMin * 60);

    $stmt = $pdo->prepare(
        "INSERT INTO agendamentos
            (id, servico_id, profissional_id, cliente_nome,
             cliente_telefone, cliente_email, data, hora_inicio,
             hora_fim, status, observacao)
         VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?, 'pendente', NULL)"
    );
    $stmt->execute([
        $id, $servicoId, $profissionalId,
        $clienteNome, $clienteTelefone,
        $data, $horaInicio, $horaFim,
    ]);

    return $id;
}

/** @return array<string, mixed>|null */
function obterAgendamento(PDO $pdo, string $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT a.id, a.servico_id, a.profissional_id,
                a.cliente_nome, a.cliente_telefone, a.data,
                a.hora_inicio, a.hora_fim, a.status, a.criado_em,
                s.nome AS servico_nome, s.duracao_min, s.preco,
                p.nome AS profissional_nome
         FROM agendamentos a
         INNER JOIN servicos s ON s.id = a.servico_id
         INNER JOIN profissionais p ON p.id = a.profissional_id
         WHERE a.id = ?'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row !== false ? $row : null;
}

function validarSlotDisponivel(
    PDO $pdo,
    string $servicoId,
    string $profissionalId,
    string $data,
    string $horaInicio
): ?string {
    $stmtSrv = $pdo->prepare(
        'SELECT duracao_min FROM servicos WHERE id = ? AND ativo = 1'
    );
    $stmtSrv->execute([$servicoId]);
    $servico = $stmtSrv->fetch();
    if (!$servico) {
        return 'Serviço não encontrado.';
    }

    $stmtProf = $pdo->prepare(
        'SELECT id FROM profissionais WHERE id = ? AND ativo = 1'
    );
    $stmtProf->execute([$profissionalId]);
    if (!$stmtProf->fetch()) {
        return 'Profissional não encontrado.';
    }

    $stmtPs = $pdo->prepare(
        'SELECT 1 FROM profissional_servico
         WHERE profissional_id = ? AND servico_id = ?'
    );
    $stmtPs->execute([$profissionalId, $servicoId]);
    if (!$stmtPs->fetch()) {
        return 'Profissional não realiza este serviço.';
    }

    $duracaoMin = (int) $servico['duracao_min'];
    $slots = obterSlotsDisponiveis($pdo, $profissionalId, $data, $duracaoMin);

    if (!in_array($horaInicio, $slots, true)) {
        $diaSemana = (int) date('w', strtotime($data));
        $faixas = obterDisponibilidadeDia($pdo, $profissionalId, $diaSemana);
        if ($faixas === []) {
            return 'Profissional não disponível nesta data.';
        }
        return 'Horário ocupado ou indisponível.';
    }

    return null;
}
