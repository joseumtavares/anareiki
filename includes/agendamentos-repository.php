<?php

declare(strict_types=1);

/** @param list<string> $servicoIds
 * @return list<array{id: string, nome: string, especialidade: string|null, foto_url: string|null}> */
function obterProfissionaisPorServicos(PDO $pdo, array $servicoIds): array
{
    $servicoIds = array_values(array_unique($servicoIds));
    if ($servicoIds === []) {
        return [];
    }

    $placeholders = implode(', ', array_fill(0, count($servicoIds), '?'));
    $stmt = $pdo->prepare(
        'SELECT p.id, p.nome, p.especialidade, p.foto_url
         FROM profissionais p
         INNER JOIN profissional_servico ps ON ps.profissional_id = p.id
         WHERE p.ativo = 1 AND ps.servico_id IN (' . $placeholders . ')
         GROUP BY p.id, p.nome, p.especialidade, p.foto_url
         HAVING COUNT(DISTINCT ps.servico_id) = (? + 0)
         ORDER BY p.nome ASC, p.id ASC'
    );
    $stmt->execute([...$servicoIds, count($servicoIds)]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** @param list<string> $servicoIds
 * @return list<array{id: string, nome: string, duracao_min: int, preco: string|null}> */
function obterServicosAtivosPorIds(PDO $pdo, array $servicoIds): array
{
    $ids = array_values(array_unique(array_filter($servicoIds, 'is_string')));
    if ($ids === []) {
        return [];
    }

    $placeholders = implode(', ', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        'SELECT id, nome, duracao_min, preco FROM servicos
         WHERE ativo = 1 AND id IN (' . $placeholders . ')'
    );
    $stmt->execute($ids);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $byId = [];
    foreach ($rows as $row) {
        $byId[$row['id']] = $row;
    }

    $servicos = [];
    foreach ($ids as $id) {
        if (!isset($byId[$id])) {
            throw new InvalidArgumentException('Serviço inválido ou inativo.');
        }
        $servicos[] = $byId[$id];
    }
    return $servicos;
}

/**
 * @param list<array{id: string, nome: string, duracao_min: int, preco: string|null}> $servicos
 * @param array<string, string> $horariosPorServico
 * @return array{
 *   servicos: list<array{id: string, nome: string, duracao_min: int, preco: string|null}>,
 *   intervalos: array<string, array{hora_inicio: string, hora_fim: string}>,
 *   hora_inicio: string,
 *   hora_fim: string
 * }
 */
function validarHorariosIndividuais(
    PDO $pdo,
    array $servicos,
    string $profissionalId,
    string $data,
    array $horariosPorServico
): array {
    $idsEsperados = array_column($servicos, 'id');
    $idsRecebidos = array_keys($horariosPorServico);
    sort($idsEsperados);
    sort($idsRecebidos);
    if ($idsEsperados !== $idsRecebidos) {
        throw new InvalidArgumentException('Informe um horario para cada servico selecionado.');
    }

    $intervalos = [];
    $provisorios = [];
    foreach ($servicos as $servico) {
        $horaInicio = $horariosPorServico[$servico['id']];
        if (preg_match('/^\d{2}:\d{2}$/', $horaInicio) !== 1) {
            throw new InvalidArgumentException('Horario de servico invalido.');
        }
        $grade = obterEstadosSlotsServico(
            $pdo,
            $profissionalId,
            $data,
            (int) $servico['duracao_min'],
            $provisorios
        );
        $slot = null;
        foreach ($grade as $item) {
            if ($item['inicio'] === $horaInicio && $item['estado'] === 'disponivel') {
                $slot = $item;
                break;
            }
        }
        if ($slot === null) {
            throw new DomainException('Um dos horarios selecionados esta ocupado ou indisponivel.');
        }
        $intervalos[$servico['id']] = [
            'hora_inicio' => $slot['inicio'],
            'hora_fim' => $slot['fim'],
        ];
        $provisorios[] = $intervalos[$servico['id']];
    }

    $inicios = array_column($intervalos, 'hora_inicio');
    $fins = array_column($intervalos, 'hora_fim');
    sort($inicios);
    rsort($fins);

    return [
        'servicos' => $servicos,
        'intervalos' => $intervalos,
        'hora_inicio' => $inicios[0],
        'hora_fim' => $fins[0],
    ];
}

/** @param list<string> $servicoIds */
function validarReservaMultiplaDisponivel(
    PDO $pdo,
    array $servicoIds,
    string $profissionalId,
    string $data,
    array|string $horaInicio
): array {
    $servicos = obterServicosAtivosPorIds($pdo, $servicoIds);
    $profissionais = obterProfissionaisPorServicos($pdo, array_column($servicos, 'id'));
    if (!in_array($profissionalId, array_column($profissionais, 'id'), true)) {
        throw new InvalidArgumentException('Profissional não realiza todos os serviços selecionados.');
    }
    if (is_array($horaInicio)) {
        return validarHorariosIndividuais(
            $pdo,
            $servicos,
            $profissionalId,
            $data,
            $horaInicio
        );
    }
    $duracao = array_sum(array_map(
        static fn (array $servico): int => (int) $servico['duracao_min'],
        $servicos
    ));
    if (!in_array($horaInicio, obterSlotsDisponiveis($pdo, $profissionalId, $data, $duracao), true)) {
        throw new DomainException('Horário ocupado ou indisponível.');
    }
    return $servicos;
}

/**
 * @param list<array{id: string, duracao_min: int}> $servicos
 * @return array<string, array{hora_inicio: string, hora_fim: string}>
 */
function intervalosLegadosPorServico(array $servicos, string $inicio, string $fim): array
{
    $cursor = strtotime($inicio);
    $intervalos = [];
    foreach ($servicos as $ordem => $servico) {
        $fimItem = $ordem === array_key_last($servicos)
            ? strtotime($fim)
            : $cursor + ((int) $servico['duracao_min'] * 60);
        $intervalos[$servico['id']] = [
            'hora_inicio' => date('H:i', $cursor),
            'hora_fim' => date('H:i', $fimItem),
        ];
        $cursor = $fimItem;
    }

    return $intervalos;
}

/** @param list<string> $servicoIds */
function criarAgendamentoMultiplo(
    PDO $pdo,
    array $servicoIds,
    string $profissionalId,
    string $data,
    array|string $horaInicio,
    string $clienteNome,
    string $clienteTelefone
): string {
    $pdo->beginTransaction();
    try {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $lock = $pdo->prepare('SELECT id FROM profissionais WHERE id = ? FOR UPDATE');
            $lock->execute([$profissionalId]);
            if (!$lock->fetch()) {
                throw new InvalidArgumentException('Profissional não encontrado.');
            }
        }
        $reserva = validarReservaMultiplaDisponivel(
            $pdo,
            $servicoIds,
            $profissionalId,
            $data,
            $horaInicio
        );
        if (isset($reserva['servicos'])) {
            $servicos = $reserva['servicos'];
            $intervalos = $reserva['intervalos'];
            $inicioReserva = $reserva['hora_inicio'];
            $fim = $reserva['hora_fim'];
        } else {
            $servicos = $reserva;
            $duracao = array_sum(array_map(
                static fn (array $servico): int => (int) $servico['duracao_min'],
                $servicos
            ));
            $inicioReserva = $horaInicio;
            $fim = date(
                'H:i',
                strtotime($inicioReserva) + (duracaoReservaData($pdo, $profissionalId, $data, $duracao) * 60)
            );
            $intervalos = intervalosLegadosPorServico($servicos, $inicioReserva, $fim);
        }
        $id = gerarUuid();
        $stmt = $pdo->prepare(
            'INSERT INTO agendamentos '
            . '(id, servico_id, profissional_id, cliente_nome, cliente_telefone, cliente_email, '
            . 'data, hora_inicio, hora_fim, status, observacao) '
            . "VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?, 'pendente', NULL)"
        );
        $stmt->execute([
            $id, $servicos[0]['id'], $profissionalId, $clienteNome,
            $clienteTelefone, $data, $inicioReserva, $fim,
        ]);
        $item = $pdo->prepare(
            'INSERT INTO agendamento_servicos '
            . '(id, agendamento_id, servico_id, nome_servico, duracao_min, preco, ordem, hora_inicio, hora_fim) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($servicos as $ordem => $servico) {
            $intervalo = $intervalos[$servico['id']];
            $item->execute([
                gerarUuid(), $id, $servico['id'], $servico['nome'],
                $servico['duracao_min'], $servico['preco'], $ordem + 1,
                $intervalo['hora_inicio'], $intervalo['hora_fim'],
            ]);
        }
        $pdo->commit();
        return $id;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

/** @return array<string, mixed>|null */
function obterAgendamentoComItens(PDO $pdo, string $id): ?array
{
    $agendamento = obterAgendamento($pdo, $id);
    if ($agendamento === null) {
        return null;
    }
    $stmt = $pdo->prepare(
        'SELECT nome_servico, duracao_min, preco, ordem, hora_inicio, hora_fim '
        . 'FROM agendamento_servicos WHERE agendamento_id = ? ORDER BY ordem'
    );
    $stmt->execute([$id]);
    $agendamento['itens'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $agendamento;
}
