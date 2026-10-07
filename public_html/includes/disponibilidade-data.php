<?php

declare(strict_types=1);

/** @param list<string> $horarios */
function salvarDisponibilidadeData(PDO $pdo, string $profissionalId, string $data, array $horarios): void
{
    $dia = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
    if (!$dia || $dia->format('Y-m-d') !== $data || $data < date('Y-m-d')) {
        throw new InvalidArgumentException('Selecione uma data atual ou futura.');
    }
    foreach ($horarios as $hora) {
        if (preg_match('/^(?:[01]\d|2[0-3]):(?:00|30)$/D', $hora) !== 1) {
            throw new InvalidArgumentException('Selecione horários válidos de meia em meia hora.');
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
            ->execute([$profissionalId, $data, json_encode($horarios, JSON_THROW_ON_ERROR)]);
        $pdo->commit();
    } catch (Throwable $erro) {
        $pdo->rollBack();
        throw $erro;
    }
}

/** @return list<array{hora_inicio: string, hora_fim: string}>|null */
function obterFaixasDisponibilidadeData(PDO $pdo, string $profissionalId, string $data): ?array
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
    $horarios = json_decode((string) $json, true, 512, JSON_THROW_ON_ERROR);
    $faixas = [];
    foreach ($horarios as $hora) {
        $fim = date('H:i', strtotime($hora) + 1800);
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
