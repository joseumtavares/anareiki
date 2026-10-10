<?php

declare(strict_types=1);

require_once __DIR__ . '/errors.php';

/** @param array<string, mixed> $config */
function modoManutencaoAtivo(array $config): bool
{
    return ($config['maintenance_mode'] ?? false) === true;
}

/** @return array{status: 503, content_type: string, retry_after: 3600, corpo: string|null} */
function dadosRespostaManutencao(bool $json): array
{
    return [
        'status' => 503,
        'content_type' => $json ? 'application/json; charset=utf-8' : 'text/html; charset=utf-8',
        'retry_after' => 3600,
        'corpo' => $json
            ? json_encode(
                ['erro' => 'O site está em manutenção. Tente novamente em breve.'],
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            )
            : null,
    ];
}

function interromperSeEmManutencao(bool $json = false): void
{
    if (!modoManutencaoAtivo(config())) {
        return;
    }

    $resposta = dadosRespostaManutencao($json);
    http_response_code($resposta['status']);
    header('Content-Type: ' . $resposta['content_type']);
    header('Retry-After: ' . $resposta['retry_after']);
    header('Cache-Control: no-store, max-age=0');

    if ($json) {
        echo $resposta['corpo'];
        exit;
    }

    renderizarPaginaErro(503);
    exit;
}
