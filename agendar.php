<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
configurarTratamentoErros();
require_once __DIR__ . '/includes/maintenance.php';
interromperSeEmManutencao(true);
require_once __DIR__ . '/includes/repositories.php';
require_once __DIR__ . '/includes/slots.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/whatsapp-agendamento.php';

session_start();

function responderAgendamentoJson(int $status, string $erro): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['erro' => $erro], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /', true, 303);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!csrfValido($token)) {
    responderAgendamentoJson(403, 'Sessão expirada. Recarregue a página e tente novamente.');
}

$servicoIds = $_POST['servico_ids'] ?? ($_POST['servico_id'] ?? []);
$servicoIds = is_array($servicoIds) ? $servicoIds : [$servicoIds];
$servicoIds = array_values(array_unique(array_filter(array_map('strval', $servicoIds))));
$profissionalId = trim((string) ($_POST['profissional_id'] ?? ''));
$data = trim((string) ($_POST['data'] ?? ''));
$horaInicio = trim((string) ($_POST['hora_inicio'] ?? ''));
$horariosPorServico = $_POST['horarios_por_servico'] ?? [];
$clienteNome = trim((string) ($_POST['cliente_nome'] ?? ''));
$clienteTelefone = trim((string) ($_POST['cliente_telefone'] ?? ''));

if (
    $servicoIds === []
    || array_filter($servicoIds, static fn (string $id): bool => !uuidValido($id)) !== []
    || !uuidValido($profissionalId)
) {
    responderAgendamentoJson(422, 'Selecione um serviço e profissional válidos.');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || $data < date('Y-m-d')) {
    responderAgendamentoJson(422, 'Selecione uma data válida (não pode ser no passado).');
}
if (!is_array($horariosPorServico)) {
    responderAgendamentoJson(422, 'Informe os horarios dos servicos.');
}
$horariosPorServico = array_filter(
    $horariosPorServico,
    static fn (mixed $horario, mixed $servicoId): bool =>
        is_string($servicoId) && uuidValido($servicoId)
        && is_string($horario) && preg_match('/^\d{2}:\d{2}$/', $horario) === 1,
    ARRAY_FILTER_USE_BOTH
);
if ($horariosPorServico !== [] && count($horariosPorServico) !== count($servicoIds)) {
    responderAgendamentoJson(422, 'Informe um horario valido para cada servico.');
}
if ($horariosPorServico === [] && !preg_match('/^\d{2}:\d{2}$/', $horaInicio)) {
    responderAgendamentoJson(422, 'Selecione um horário válido.');
}
if ($clienteNome === '' || mb_strlen($clienteNome) < 2 || mb_strlen($clienteNome) > 100) {
    responderAgendamentoJson(422, 'Informe seu nome (entre 2 e 100 caracteres).');
}

$telefoneNumeros = preg_replace('/\D/', '', $clienteTelefone);
if (!is_string($telefoneNumeros) || preg_match('/^\d{10,15}$/', $telefoneNumeros) !== 1) {
    responderAgendamentoJson(422, 'Informe um telefone válido (somente números, 10 a 15 dígitos).');
}

$whatsappPhone = (string) (config()['whatsapp_phone'] ?? '');
try {
    $numeroWhatsApp = preg_replace('/\D+/', '', $whatsappPhone);
    if (!is_string($numeroWhatsApp) || preg_match('/^\d{10,15}$/', $numeroWhatsApp) !== 1) {
        throw new InvalidArgumentException('Número de WhatsApp da empresa inválido.');
    }

    $pdo = db();
    $agendamentoId = criarAgendamentoMultiplo(
        $pdo,
        $servicoIds,
        $profissionalId,
        $data,
        $horariosPorServico !== [] ? $horariosPorServico : $horaInicio,
        $clienteNome,
        $telefoneNumeros
    );
    $agendamento = obterAgendamentoComItens($pdo, $agendamentoId);
    if ($agendamento === null) {
        throw new RuntimeException('Não foi possível recuperar o agendamento criado.');
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'whatsapp_url' => criarLinkWhatsAppAgendamento($agendamento, $numeroWhatsApp),
    ], JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $e) {
    responderAgendamentoJson(422, $e->getMessage());
} catch (DomainException $e) {
    responderAgendamentoJson(409, $e->getMessage());
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'uq_agend_slot') || str_contains($e->getMessage(), 'UNIQUE constraint')) {
        responderAgendamentoJson(409, 'Este horário acabou de ser reservado. Escolha outro horário.');
    }
    registrarErroAplicacao($e, gerarRequestId());
    responderAgendamentoJson(500, 'Erro interno. Tente novamente.');
} catch (Throwable $e) {
    registrarErroAplicacao($e, gerarRequestId());
    responderAgendamentoJson(500, 'Erro interno. Tente novamente.');
}
