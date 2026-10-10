<?php

declare(strict_types=1);

/** @param array<string, mixed> $dados @return array<string, string> */
function validarDadosDisponibilidadeAdmin(PDO $pdo, array $dados, string $profissionalId, ?string $id = null): array
{
    $erros = [];
    $dia = filter_var($dados['dia_semana'] ?? null, FILTER_VALIDATE_INT);
    if ($dia === false || $dia < 0 || $dia > 6) {
        $erros['dia_semana'] = 'Escolha um dia válido.';
    }
    $inicio = (string) ($dados['hora_inicio'] ?? '');
    $fim = (string) ($dados['hora_fim'] ?? '');
    if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $inicio) !== 1) {
        $erros['hora_inicio'] = 'Informe um horário válido.';
    }
    if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $fim) !== 1) {
        $erros['hora_fim'] = 'Informe um horário válido.';
    }
    if (!isset($erros['hora_inicio'], $erros['hora_fim']) && $inicio >= $fim) {
        $erros['hora_inicio'] = 'O início deve ser anterior ao fim.';
    }
    if ($erros === [] && disponibilidadeSobreposta($pdo, $profissionalId, (int) $dia, $inicio, $fim, $id)) {
        $erros['sobreposicao'] = 'Este intervalo se sobrepõe a outro já cadastrado.';
    }
    return $erros;
}

function disponibilidadeSobreposta(
    PDO $pdo,
    string $profissionalId,
    int $dia,
    string $inicio,
    string $fim,
    ?string $id = null
): bool {
    $sql = 'SELECT 1 FROM disponibilidade WHERE profissional_id = ? AND dia_semana = ? AND
            hora_inicio < ? AND hora_fim > ?';
    $params = [$profissionalId, $dia, $fim, $inicio];
    if ($id !== null) {
        $sql .= ' AND id <> ?';
        $params[] = $id;
    }
    $stmt = $pdo->prepare($sql . ' LIMIT 1');
    $stmt->execute($params);
    return $stmt->fetch() !== false;
}

/** @return list<array<string, mixed>> */
function listarDisponibilidadeAdmin(PDO $pdo, string $profissionalId): array
{
    $stmt = $pdo->prepare('SELECT id, profissional_id, dia_semana, hora_inicio, hora_fim FROM
            disponibilidade WHERE profissional_id = ? ORDER BY dia_semana, hora_inicio');
    $stmt->execute([$profissionalId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** @param array<string, mixed> $dados */
function salvarDisponibilidadeAdmin(PDO $pdo, array $dados): string
{
    $id = isset($dados['id']) && (string) $dados['id'] !== '' ? (string) $dados['id'] : gerarUuid();
    $stmt = $pdo->prepare('SELECT 1 FROM disponibilidade WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->fetch() === false) {
        $stmt = $pdo->prepare('INSERT INTO disponibilidade (id, profissional_id, dia_semana, hora_inicio,
            hora_fim) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$id, $dados['profissional_id'], $dados['dia_semana'], $dados['hora_inicio'],
        $dados['hora_fim']]);
    } else {
        $stmt = $pdo->prepare('UPDATE disponibilidade SET dia_semana = ?, hora_inicio = ?, hora_fim = ? WHERE id = ?');
        $stmt->execute([$dados['dia_semana'], $dados['hora_inicio'], $dados['hora_fim'], $id]);
    }
    return $id;
}

/** @return array<string, mixed>|null */
function obterDisponibilidadeAdmin(PDO $pdo, string $id): ?array
{
    $stmt = $pdo->prepare('SELECT id, profissional_id, dia_semana, hora_inicio, hora_fim FROM
            disponibilidade WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row !== false ? $row : null;
}

function excluirDisponibilidadeAdmin(PDO $pdo, string $id): void
{
    $stmt = $pdo->prepare('DELETE FROM disponibilidade WHERE id = ?');
    $stmt->execute([$id]);
}
