<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/maintenance.php';
interromperSeEmManutencao(true);
require_once __DIR__ . '/../includes/repositories.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$servicos = $_GET['servicos'] ?? ($_GET['servico'] ?? []);
$servicos = is_array($servicos) ? $servicos : [$servicos];
$servicos = array_values(array_unique(array_filter($servicos, 'is_string')));

if ($servicos === [] || array_filter($servicos, static fn (string $id): bool => !uuidValido($id)) !== []) {
    http_response_code(400);
    echo json_encode(['erro' => 'Serviço inválido']);
    exit;
}

$profissionais = obterProfissionaisPorServicos(db(), $servicos);

echo json_encode($profissionais, JSON_UNESCAPED_UNICODE);
