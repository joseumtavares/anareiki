<?php

declare(strict_types=1);

function linkWhatsAppCliente(string $telefone): ?string
{
    if (preg_match('/^\+?[0-9\s().-]+$/D', trim($telefone)) !== 1) {
        return null;
    }
    $numero = preg_replace('/[^0-9]/', '', $telefone) ?? '';
    if (strlen($numero) === 10 || strlen($numero) === 11) {
        $numero = '55' . $numero;
    }
    if (preg_match('/^55[1-9][0-9][2-9][0-9]{7,8}$/D', $numero) !== 1) {
        return null;
    }
    return 'https://wa.me/' . $numero;
}

/** @return list<string> */
function destinosStatusAgendamento(string $status): array
{
    return match ($status) {
        'pendente' => ['confirmado', 'cancelado'],
        'confirmado' => ['concluido', 'cancelado'],
        default => [],
    };
}

function alterarStatusAgendamentoAdmin(PDO $pdo, string $id, string $origem, string $destino): void
{
    if (!in_array($destino, destinosStatusAgendamento($origem), true)) {
        throw new DomainException('Esta alteração de status não é permitida.');
    }
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('UPDATE agendamentos SET status = ? WHERE id = ? AND status = ?');
        $stmt->execute([$destino, $id, $origem]);
        if ($stmt->rowCount() !== 1) {
            throw new DomainException('O agendamento mudou ou não existe. Atualize a página e tente novamente.');
        }
        $pdo->commit();
    } catch (Throwable $erro) {
        $pdo->rollBack();
        throw $erro;
    }
}

/** @param array<string, string> $filtros @return list<array<string, mixed>> */
function listarAgendamentosAdmin(PDO $pdo, array $filtros = []): array
{
    $condicoes = [];
    $params = [];
    foreach (['inicio' => '>=', 'fim' => '<='] as $campo => $operador) {
        $valor = $filtros[$campo] ?? '';
        if ($valor === '') {
            continue;
        }
        $data = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
        if (!$data || $data->format('Y-m-d') !== $valor) {
            throw new InvalidArgumentException('Informe um período válido.');
        }
        $condicoes[] = "a.data $operador ?";
        $params[] = $valor;
    }
    if (
        ($filtros['inicio'] ?? '') !== '' && ($filtros['fim'] ?? '') !== ''
        && $filtros['inicio'] > $filtros['fim']
    ) {
        throw new InvalidArgumentException('A data inicial deve ser anterior à final.');
    }
    $status = $filtros['status'] ?? '';
    if ($status !== '') {
        if (!in_array($status, ['pendente', 'confirmado', 'cancelado', 'concluido'], true)) {
            throw new InvalidArgumentException('Selecione um status válido.');
        }
        $condicoes[] = 'a.status = ?';
        $params[] = $status;
    }
    if (($filtros['profissional'] ?? '') !== '') {
        $condicoes[] = 'a.profissional_id = ?';
        $params[] = $filtros['profissional'];
    }
    $sql = 'SELECT a.id, a.data, a.hora_inicio, a.hora_fim, a.status,
                   a.cliente_nome, a.cliente_telefone,
                   s.nome AS servico_nome, p.nome AS profissional_nome
            FROM agendamentos a
            INNER JOIN servicos s ON s.id = a.servico_id
            INNER JOIN profissionais p ON p.id = a.profissional_id';
    if ($condicoes !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $condicoes);
    }
    $stmt = $pdo->prepare($sql . ' ORDER BY a.data DESC, a.hora_inicio DESC, a.id');
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
