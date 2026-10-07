<?php

declare(strict_types=1);

/** @param list<string> $horarios */
function salvarDisponibilidadeData(
    PDO $pdo,
    string $profissionalId,
    string $data,
    array $horarios,
    int $intervalo = 30
): void {
    if (!in_array($intervalo, [30, 60], true)) {
        throw new InvalidArgumentException('Escolha intervalos de 30 ou 60 minutos.');
    }
    $dia = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
    if (!$dia || $dia->format('Y-m-d') !== $data || $data < date('Y-m-d')) {
        throw new InvalidArgumentException('Selecione uma data atual ou futura.');
    }
    foreach ($horarios as $hora) {
        if (preg_match('/^(?:[01]\d|2[0-3]):(?:00|30)$/D', $hora) !== 1) {
            throw new InvalidArgumentException('Selecione horários válidos de meia em meia hora.');
        }
        if ($intervalo === 60 && substr($hora, 3) !== '00') {
            throw new InvalidArgumentException('Para intervalos de 60 minutos, selecione horas inteiras.');
        }
    }
    $horarios = array_values(array_unique($horarios));
    sort($horarios);
    $pdo->beginTransaction();
    try {
        // Serializa edições do mesmo profissional em MySQL; SQLite serializa escritas.
        $sql = 'SELECT id FROM profissionais WHERE id = ?';
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$profissionalId]);
        if ($stmt->fetch() === false) {
            throw new InvalidArgumentException('Profissional não encontrado.');
        }
        $pdo->prepare('DELETE FROM disponibilidade_datas WHERE profissional_id = ? AND data = ?')
            ->execute([$profissionalId, $data]);
        $pdo->prepare('INSERT INTO disponibilidade_datas (profissional_id, data, horarios) VALUES (?, ?, ?)')
            ->execute([$profissionalId, $data, json_encode(
                ['intervalo' => $intervalo, 'horarios' => $horarios],
                JSON_THROW_ON_ERROR
            )]);
        $pdo->commit();
    } catch (Throwable $erro) {
        $pdo->rollBack();
        throw $erro;
    }
}

/** @return array{intervalo: int, horarios: list<string>}|null */
function obterConfiguracaoDisponibilidadeData(PDO $pdo, string $profissionalId, string $data): ?array
{
    try {
        $stmt = $pdo->prepare('SELECT horarios FROM disponibilidade_datas WHERE profissional_id = ? AND data = ?');
        $stmt->execute([$profissionalId, $data]);
    } catch (PDOException $erro) {
        // Compatibilidade antes da aplicação da migração, apenas para tabela ausente.
        if (
            in_array((string) $erro->getCode(), ['42S02'], true)
            || ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
                && str_contains($erro->getMessage(), 'no such table: disponibilidade_datas'))
        ) {
            return null;
        }
        throw $erro;
    }
    $json = $stmt->fetchColumn();
    if ($json === false) {
        return null;
    }
    return decodificarDisponibilidadeData((string) $json);
}

/** @return array{intervalo: int, horarios: list<string>} */
function decodificarDisponibilidadeData(string $json): array
{
    $dados = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    return ['intervalo' => (int) ($dados['intervalo'] ?? 30), 'horarios' => $dados['horarios'] ?? $dados];
}

function duracaoReservaData(PDO $pdo, string $profissionalId, string $data, int $duracao): int
{
    $config = obterConfiguracaoDisponibilidadeData($pdo, $profissionalId, $data);
    return $config === null ? $duracao : (int) ceil($duracao / $config['intervalo']) * $config['intervalo'];
}

/** @return list<array{hora_inicio: string, hora_fim: string}>|null */
function obterFaixasDisponibilidadeData(PDO $pdo, string $profissionalId, string $data): ?array
{
    $config = obterConfiguracaoDisponibilidadeData($pdo, $profissionalId, $data);
    if ($config === null) {
        return null;
    }
    $horarios = $config['horarios'];
    $faixas = [];
    foreach ($horarios as $hora) {
        $fim = date('H:i', strtotime($hora) + $config['intervalo'] * 60);
        if ($fim === '00:00') {
            $fim = '24:00';
        }
        $ultimo = array_key_last($faixas);
        if ($ultimo !== null && $faixas[$ultimo]['hora_fim'] === $hora) {
            $faixas[$ultimo]['hora_fim'] = $fim;
        } else {
            $faixas[] = ['hora_inicio' => $hora, 'hora_fim' => $fim];
        }
    }
    return $faixas;
}
