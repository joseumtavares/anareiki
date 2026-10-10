<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
configurarTratamentoErros();
require_once __DIR__ . '/../includes/maintenance.php';
interromperSeEmManutencao(true);
require_once __DIR__ . '/../includes/limites.php';
require_once __DIR__ . '/../includes/repositories.php';
require_once __DIR__ . '/../includes/slots.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$chaveRate = 'disponibilidade:' . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
if (limiteExcedido($chaveRate, 10, 60)) {
    http_response_code(429);
    echo json_encode(['erro' => 'Muitas requisições. Tente novamente em 1 minuto.']);
    exit;
}
registrarFalha($chaveRate, 60);

$servicos = $_GET['servicos'] ?? ($_GET['servico'] ?? []);
$servicos = is_array($servicos) ? $servicos : [$servicos];
$servicos = array_values(array_unique(array_filter($servicos, 'is_string')));
$profissional = $_GET['profissional'] ?? '';
$mes = $_GET['mes'] ?? '';

if (
    $servicos === [] || !is_string($profissional) || !uuidValido($profissional)
    || array_filter($servicos, static fn (string $id): bool => !uuidValido($id)) !== []
    || !is_string($mes) || preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes) !== 1
) {
    http_response_code(400);
    echo json_encode(['erro' => 'Parâmetros inválidos']);
    exit;
}

try {
    $pdo = db();
    $lista = obterServicosAtivosPorIds($pdo, $servicos);
    if (!in_array($profissional, array_column(obterProfissionaisPorServicos($pdo, $servicos), 'id'), true)) {
        throw new InvalidArgumentException('Profissional indisponível.');
    }
    $inicio = new DateTimeImmutable($mes . '-01');
    $fim = $inicio->modify('first day of next month');
    $hoje = new DateTimeImmutable('today');
    $dias = [];
    $estados = [];
    for ($data = $inicio; $data < $fim; $data = $data->modify('+1 day')) {
        if ($data < $hoje) {
            continue;
        }
        $valor = $data->format('Y-m-d');
        $estado = obterEstadoDiaAgendamento($pdo, $profissional, $valor, $lista);
        $estados[$valor] = $estado;
        if ($estado === 'disponivel') {
            $dias[] = $valor;
        }
    }
    echo json_encode(['mes' => $mes, 'dias' => $dias, 'estados' => $estados], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['erro' => 'Não foi possível consultar disponibilidade.']);
}
