<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/maintenance.php';
interromperSeEmManutencao(true);
require_once __DIR__ . '/../includes/limites.php';
require_once __DIR__ . '/../includes/repositories.php';
require_once __DIR__ . '/../includes/slots.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

$chaveRate = 'slots:' . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
if (limiteExcedido($chaveRate, 10, 60)) {
    http_response_code(429);
    echo json_encode(['erro' => 'Muitas requisições. Tente novamente em 1 minuto.'], JSON_UNESCAPED_UNICODE);
    exit;
}
registrarFalha($chaveRate, 60);

$servicos = $_GET['servicos'] ?? ($_GET['servico'] ?? []);
$servicos = is_array($servicos) ? $servicos : [$servicos];
$servicos = array_values(array_unique(array_filter($servicos, 'is_string')));
$profissionalId = $_GET['profissional'] ?? '';
$data = $_GET['data'] ?? '';

if ($servicos === [] || array_filter($servicos, static fn (string $id): bool => !uuidValido($id)) !== []) {
    http_response_code(400);
    echo json_encode(['erro' => 'Serviço inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!is_string($profissionalId) || !uuidValido($profissionalId)) {
    http_response_code(400);
    echo json_encode(['erro' => 'Profissional inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!is_string($data) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || $data < date('Y-m-d')) {
    http_response_code(400);
    echo json_encode(['erro' => 'Data inválida'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = db();
    $servicosBanco = obterServicosAtivosPorIds($pdo, $servicos);
    $stmtProf = $pdo->prepare('SELECT id FROM profissionais WHERE id = ? AND ativo = 1');
    $stmtProf->execute([$profissionalId]);
    if (!$stmtProf->fetch()) {
        throw new InvalidArgumentException('Profissional não encontrado.');
    }
    if (!in_array($profissionalId, array_column(obterProfissionaisPorServicos($pdo, $servicos), 'id'), true)) {
        throw new InvalidArgumentException('Profissional não realiza este serviço.');
    }
    $duracaoMin = array_sum(array_map(static fn (array $s): int => (int) $s['duracao_min'], $servicosBanco));
    $gradesPorServico = [];
    foreach ($servicosBanco as $servico) {
        $gradesPorServico[] = [
            'id' => $servico['id'],
            'nome' => $servico['nome'],
            'duracao_min' => (int) $servico['duracao_min'],
            'slots' => obterEstadosSlotsServico(
                $pdo,
                $profissionalId,
                $data,
                (int) $servico['duracao_min']
            ),
        ];
    }
    echo json_encode([
        'servicos' => array_column($servicosBanco, 'id'),
        'profissional_id' => $profissionalId,
        'data' => $data,
        'duracao_min' => $duracaoMin,
        'horarios' => obterSlotsDisponiveis($pdo, $profissionalId, $data, $duracaoMin),
        'grades_por_servico' => $gradesPorServico,
        'combinacao_disponivel' => existeCombinacaoHorarios(
            $pdo,
            $profissionalId,
            $data,
            $servicosBanco
        ),
    ], JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['erro' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    registrarErroAplicacao($e, gerarRequestId());
    http_response_code(500);
    echo json_encode(['erro' => 'Não foi possível consultar os horários.'], JSON_UNESCAPED_UNICODE);
}
