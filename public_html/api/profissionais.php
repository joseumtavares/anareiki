<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/repositories.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$servicoId = $_GET['servico'] ?? '';

if (!is_string($servicoId) || !uuidValido($servicoId)) {
    http_response_code(400);
    echo json_encode(['erro' => 'Serviço inválido']);
    exit;
}

$profissionais = obterProfissionaisPorServico(db(), $servicoId);

echo json_encode($profissionais, JSON_UNESCAPED_UNICODE);
