<?php

declare(strict_types=1);

require_once __DIR__ . '/repositories.php';
require_once __DIR__ . '/disponibilidade-data.php';

/**
 * Gera horários disponíveis para um profissional em uma data.
 *
 * @return list<string> Horários livres no formato "HH:MM"
 */
function obterSlotsDisponiveis(
    PDO $pdo,
    string $profissionalId,
    string $data,
    int $duracaoMin
): array {
    $grade = obterEstadosSlotsServico($pdo, $profissionalId, $data, $duracaoMin);

    return array_values(array_map(
        static fn (array $slot): string => $slot['inicio'],
        array_filter($grade, static fn (array $slot): bool => $slot['estado'] === 'disponivel')
    ));
}

/**
 * @param list<array{hora_inicio: string, hora_fim: string}> $intervalosProvisorios
 * @return list<array{inicio: string, fim: string, estado: string}>
 */
function obterEstadosSlotsServico(
    PDO $pdo,
    string $profissionalId,
    string $data,
    int $duracaoMin,
    array $intervalosProvisorios = []
): array {
    $diaSemana = (int) date('w', strtotime($data));
    $config = obterConfiguracaoDisponibilidadeData($pdo, $profissionalId, $data);
    $passo = $config['intervalo'] ?? $duracaoMin;
    $duracaoMin = duracaoReservaData($pdo, $profissionalId, $data, $duracaoMin);

    $faixas = obterFaixasDisponibilidadeData($pdo, $profissionalId, $data)
        ?? obterDisponibilidadeDia($pdo, $profissionalId, $diaSemana);
    if ($faixas === []) {
        return [];
    }

    $ocupados = obterIntervalosOcupadosDia($pdo, $profissionalId, $data);

    $slots = [];
    foreach ($faixas as $faixa) {
        $cursor = strtotime($faixa['hora_inicio']);
        $limFaixa = strtotime($faixa['hora_fim']);

        while ($cursor + $duracaoMin * 60 <= $limFaixa) {
            $fimSlot = $cursor + $duracaoMin * 60;
            $estado = 'disponivel';
            foreach ($ocupados as $ocupado) {
                if (intervalosColidem($cursor, $fimSlot, $ocupado['inicio'], $ocupado['fim'])) {
                    $estado = 'agendado';
                    break;
                }
            }
            if ($estado === 'disponivel') {
                foreach ($intervalosProvisorios as $provisorio) {
                    if (
                        intervalosColidem(
                            $cursor,
                            $fimSlot,
                            strtotime($provisorio['hora_inicio']),
                            strtotime($provisorio['hora_fim'])
                        )
                    ) {
                        $estado = 'indisponivel';
                        break;
                    }
                }
            }
            $slots[] = [
                'inicio' => date('H:i', $cursor),
                'fim' => date('H:i', $fimSlot),
                'estado' => $estado,
            ];
            $cursor += $passo * 60;
        }
    }

    return $slots;
}

/** @return list<array{inicio: int, fim: int}> */
function obterIntervalosOcupadosDia(PDO $pdo, string $profissionalId, string $data): array
{
    try {
        $stmt = $pdo->prepare(
            "SELECT COALESCE(i.hora_inicio, a.hora_inicio) AS hora_inicio,
                    COALESCE(i.hora_fim, a.hora_fim) AS hora_fim
             FROM agendamentos a
             LEFT JOIN agendamento_servicos i ON i.agendamento_id = a.id
             WHERE a.profissional_id = ? AND a.data = ? AND a.status != 'cancelado'
             ORDER BY hora_inicio ASC"
        );
        $stmt->execute([$profissionalId, $data]);
        $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $erro) {
        $agendamentos = obterAgendamentosDia($pdo, $profissionalId, $data);
    }

    return array_map(static fn (array $agendamento): array => [
        'inicio' => strtotime($agendamento['hora_inicio']),
        'fim' => strtotime($agendamento['hora_fim']),
    ], $agendamentos);
}

function intervalosColidem(int $inicioA, int $fimA, int $inicioB, int $fimB): bool
{
    return $inicioA < $fimB && $fimA > $inicioB;
}

/** @param list<array{id: string, duracao_min: int}> $servicos */
function existeCombinacaoHorarios(PDO $pdo, string $profissionalId, string $data, array $servicos): bool
{
    $candidatos = [];
    foreach ($servicos as $servico) {
        $grade = obterEstadosSlotsServico(
            $pdo,
            $profissionalId,
            $data,
            (int) $servico['duracao_min']
        );
        $disponiveis = array_values(array_filter(
            $grade,
            static fn (array $slot): bool => $slot['estado'] === 'disponivel'
        ));
        if ($disponiveis === []) {
            return false;
        }
        $candidatos[] = $disponiveis;
    }
    usort($candidatos, static fn (array $a, array $b): int => count($a) <=> count($b));

    return existeCombinacaoRecursiva($candidatos, 0, []);
}

/** @param list<array{id: string, duracao_min: int}> $servicos */
function obterEstadoDiaAgendamento(PDO $pdo, string $profissionalId, string $data, array $servicos): string
{
    if (existeCombinacaoHorarios($pdo, $profissionalId, $data, $servicos)) {
        return 'disponivel';
    }
    foreach ($servicos as $servico) {
        foreach (obterEstadosSlotsServico($pdo, $profissionalId, $data, (int) $servico['duracao_min']) as $slot) {
            if ($slot['estado'] === 'agendado') {
                return 'agendado';
            }
        }
    }

    return 'indisponivel';
}

/**
 * @param list<list<array{inicio: string, fim: string, estado: string}>> $candidatos
 * @param list<array{inicio: int, fim: int}> $escolhidos
 */
function existeCombinacaoRecursiva(array $candidatos, int $indice, array $escolhidos): bool
{
    if ($indice === count($candidatos)) {
        return true;
    }
    foreach ($candidatos[$indice] as $slot) {
        $inicio = strtotime($slot['inicio']);
        $fim = strtotime($slot['fim']);
        foreach ($escolhidos as $escolhido) {
            if (intervalosColidem($inicio, $fim, $escolhido['inicio'], $escolhido['fim'])) {
                continue 2;
            }
        }
        if (
            existeCombinacaoRecursiva($candidatos, $indice + 1, [...$escolhidos, [
            'inicio' => $inicio,
            'fim' => $fim,
            ]])
        ) {
            return true;
        }
    }

    return false;
}
