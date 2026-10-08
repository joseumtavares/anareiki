<?php

declare(strict_types=1);

function gerarRequestId(): string
{
    return bin2hex(random_bytes(8));
}

function mensagemErroPublica(int $status): string
{
    return match ($status) {
        403 => 'Você não tem permissão para acessar este recurso.',
        404 => 'A página solicitada não foi encontrada.',
        default => 'Ocorreu um erro inesperado. Informe o código de atendimento.',
    };
}

function formatarErroAplicacao(Throwable $erro, string $requestId): string
{
    return sprintf(
        '[reiki:%s] tipo=%s origem=%s linha=%d',
        preg_replace('/[^a-f0-9]/', '', $requestId),
        get_class($erro),
        basename($erro->getFile()),
        $erro->getLine()
    );
}

function registrarErroAplicacao(Throwable $erro, string $requestId): void
{
    error_log(formatarErroAplicacao($erro, $requestId));
}

function configurarTratamentoErros(): void
{
    static $configurado = false;
    if ($configurado) {
        return;
    }
    $configurado = true;
    $requestId = gerarRequestId();
    $_SERVER['HTTP_X_REQUEST_ID'] = $requestId;

    set_exception_handler(static function (Throwable $erro) use ($requestId): void {
        registrarErroAplicacao($erro, $requestId);
        http_response_code(500);
        require dirname(__DIR__) . '/errors/500.php';
    });
    set_error_handler(static function (int $severity, string $message, string $file, int $line) use ($requestId): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        $erro = new ErrorException($message, 0, $severity, $file, $line);
        registrarErroAplicacao($erro, $requestId);
        return true;
    });
}
