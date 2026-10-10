<?php

declare(strict_types=1);

require_once __DIR__ . '/public-view.php';
require_once __DIR__ . '/disponibilidade-data.php';
require_once __DIR__ . '/admin.php';
require_once __DIR__ . '/categorias-admin.php';

require_once __DIR__ . '/servicos-repository.php';
require_once __DIR__ . '/profissionais-repository.php';
require_once __DIR__ . '/disponibilidade-repository.php';
require_once __DIR__ . '/agendamentos-repository.php';

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
    $duracaoReserva = duracaoReservaData($pdo, $profissionalId, $data, $duracaoMin);
    $horaFim = date('H:i', strtotime($horaInicio) + $duracaoReserva * 60);

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
