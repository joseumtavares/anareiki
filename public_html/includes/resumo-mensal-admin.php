<?php

declare(strict_types=1);

/** @return array{servicos: list<array<string, mixed>>, quantidade: int, total_centavos: int, sem_preco: int} */
function resumoMensalAdmin(PDO $pdo, string $mes): array
{
    $inicio = DateTimeImmutable::createFromFormat('!Y-m', $mes);
    if (!$inicio || $inicio->format('Y-m') !== $mes) {
        throw new InvalidArgumentException('Selecione um mês válido.');
    }
    $stmt = $pdo->prepare("SELECT s.id, s.nome, s.preco, COUNT(*) AS quantidade,
        SUM(CASE WHEN a.status = 'concluido' THEN 1 ELSE 0 END) AS concluidos
        FROM agendamentos a INNER JOIN servicos s ON s.id = a.servico_id
        WHERE a.data >= ? AND a.data < ? AND a.status IN ('pendente', 'confirmado', 'concluido')
        GROUP BY s.id, s.nome, s.preco ORDER BY s.nome, s.id");
    $stmt->execute([$inicio->format('Y-m-d'), $inicio->modify('+1 month')->format('Y-m-d')]);
    $resumo = ['servicos' => [], 'quantidade' => 0, 'total_centavos' => 0, 'sem_preco' => 0];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $servico) {
        $servico['quantidade'] = (int) $servico['quantidade'];
        $servico['concluidos'] = (int) $servico['concluidos'];
        $servico['total_centavos'] = $servico['preco'] === null ? 0
            : (int) round((float) $servico['preco'] * 100) * $servico['concluidos'];
        $resumo['servicos'][] = $servico;
        $resumo['quantidade'] += $servico['quantidade'];
        $resumo['total_centavos'] += $servico['total_centavos'];
        if ($servico['preco'] === null) {
            $resumo['sem_preco'] += $servico['concluidos'];
        }
    }
    return $resumo;
}
