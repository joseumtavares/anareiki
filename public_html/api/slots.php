<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/limites.php';
require_once __DIR__ . '/../includes/repositories.php';
require_once __DIR__ . '/../includes/slots.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$chaveRate = 'slots:' . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
if (limiteExcedido($chaveRate, 10, 60)) {
    http_response_code(429);
    echo json_encode(['erro' => 'Muitas requisições. Tente novamente em 1 minuto.']);
    exit;
}
registrarFalha($chaveRate, 60);

$servicoId = $_GET['servico'] ?? '';
$profissionalId = $_GET['profissional'] ?? '';
$data = $_GET['data'] ?? '';

if (!is_string($servicoId) || !uuidValido($servicoId)) {
    http_response_code(400);
    echo json_encode(['erro' => 'Serviço inválido']);
    exit;
}
if (!is_string($profissionalId) || !uuidValido($profissionalId)) {
    http_response_code(400);
    echo json_encode(['erro' => 'Profissional inválido']);
    exit;
}
if (!is_string($data) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    http_response_code(400);
    echo json_encode(['erro' => 'Data inválida']);
    exit;
}

$ts = strtotime($data);
if ($ts === false || $data < date('Y-m-d')) {
    http_response_code(400);
    echo json_encode(['erro' => 'Data no passado ou inválida']);
    exit;
}

$pdo = db();

$stmtSrv = $pdo->prepare(
    'SELECT duracao_min FROM servicos WHERE id = ? AND ativo = 1'
);
$stmtSrv->execute([$servicoId]);
$servico = $stmtSrv->fetch();
if (!$servico) {
    http_response_code(400);
    echo json_encode(['erro' => 'Serviço não encontrado']);
    exit;
}

$stmtProf = $pdo->prepare(
    'SELECT id FROM profissionais WHERE id = ? AND ativo = 1'
);
$stmtProf->execute([$profissionalId]);
if (!$stmtProf->fetch()) {
    http_response_code(400);
    echo json_encode(['erro' => 'Profissional não encontrado']);
    exit;
}

$stmtPs = $pdo->prepare(
    'SELECT 1 FROM profissional_servico
     WHERE profissional_id = ? AND servico_id = ?'
);
$stmtPs->execute([$profissionalId, $servicoId]);
if (!$stmtPs->fetch()) {
    http_response_code(400);
    echo json_encode(['erro' => 'Profissional não realiza este serviço']);
    exit;
}

$duracaoMin = (int) $servico['duracao_min'];
$horarios = obterSlotsDisponiveis($pdo, $profissionalId, $data, $duracaoMin);

echo json_encode([
    'servico_id' => $servicoId,
    'profissional_id' => $profissionalId,
    'data' => $data,
    'duracao_min' => $duracaoMin,
    'horarios' => $horarios,
], JSON_UNESCAPED_UNICODE);
