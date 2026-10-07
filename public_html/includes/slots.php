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
    $diaSemana = (int) date('w', strtotime($data));
    $config = obterConfiguracaoDisponibilidadeData($pdo, $profissionalId, $data);
    $passo = $config['intervalo'] ?? $duracaoMin;
    $duracaoMin = duracaoReservaData($pdo, $profissionalId, $data, $duracaoMin);

    $faixas = obterFaixasDisponibilidadeData($pdo, $profissionalId, $data)
        ?? obterDisponibilidadeDia($pdo, $profissionalId, $diaSemana);
    if ($faixas === []) {
        return [];
    }

    $agendamentos = obterAgendamentosDia($pdo, $profissionalId, $data);

    $ocupados = [];
    foreach ($agendamentos as $ag) {
        $inicio = strtotime($ag['hora_inicio']);
        $fim = strtotime($ag['hora_fim']);
        $ocupados[] = [$inicio, $fim];
    }

    $slots = [];
    foreach ($faixas as $faixa) {
        $cursor = strtotime($faixa['hora_inicio']);
        $limFaixa = strtotime($faixa['hora_fim']);

        while ($cursor + $duracaoMin * 60 <= $limFaixa) {
            $fimSlot = $cursor + $duracaoMin * 60;
            $colide = false;
            foreach ($ocupados as [$oi, $of]) {
                if ($cursor < $of && $fimSlot > $oi) {
                    $colide = true;
                    break;
                }
            }
            if (!$colide) {
                $slots[] = date('H:i', $cursor);
            }
            $cursor += $passo * 60;
        }
    }

    return $slots;
}
