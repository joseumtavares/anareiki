<?php

declare(strict_types=1);

function gerarRequestId(): string
{
    return bin2hex(random_bytes(8));
}

/**
 * @return array{
 *     status: int,
 *     codigo: string,
 *     titulo: string,
 *     mensagem: string,
 *     acao_primaria: array{rotulo: string, url: string},
 *     acao_secundaria: array{rotulo: string, url: string}|null
 * }
 */
function dadosPaginaErro(int $status): array
{
    $paginas = [
        403 => [
            'titulo' => 'Este espaço é reservado.',
            'mensagem' => 'Você chegou a uma área que não está aberta para visitas. '
                . 'Volte para um espaço de cuidado ou entre pela área administrativa, se ela for sua.',
            'acao_primaria' => ['rotulo' => 'Voltar ao início', 'url' => '/'],
            'acao_secundaria' => ['rotulo' => 'Acesso administrativo', 'url' => '/admin/login.php'],
        ],
        404 => [
            'titulo' => 'Parece que este caminho se perdeu.',
            'mensagem' => 'Talvez esta página tenha mudado de lugar; seu momento de pausa continua por aqui.',
            'acao_primaria' => ['rotulo' => 'Ir para o início', 'url' => '/'],
            'acao_secundaria' => ['rotulo' => 'Conhecer serviços', 'url' => '/#servicos'],
        ],
        500 => [
            'titulo' => 'Nossa casa fez uma pausa inesperada.',
            'mensagem' => 'Já registramos o ocorrido. Respire fundo e tente novamente em alguns instantes.',
            'acao_primaria' => ['rotulo' => 'Tentar novamente', 'url' => '/'],
            'acao_secundaria' => ['rotulo' => 'Voltar ao início', 'url' => '/'],
        ],
        502 => [
            'titulo' => 'A ponte até o nosso espaço falhou por um instante.',
            'mensagem' => 'O site recebeu uma resposta incompleta. Uma nova tentativa costuma resolver.',
            'acao_primaria' => ['rotulo' => 'Tentar novamente', 'url' => '/'],
            'acao_secundaria' => ['rotulo' => 'Voltar ao início', 'url' => '/'],
        ],
        503 => [
            'titulo' => 'Estamos preparando o espaço para receber você.',
            'mensagem' => 'O site está em uma breve pausa de manutenção. Volte em alguns instantes.',
            'acao_primaria' => ['rotulo' => 'Voltar ao início', 'url' => '/'],
            'acao_secundaria' => ['rotulo' => 'Falar pelo WhatsApp', 'url' => 'https://wa.me/5548996137757'],
        ],
        504 => [
            'titulo' => 'O atendimento digital demorou mais que o normal.',
            'mensagem' => 'A resposta levou mais tempo do que esperávamos. Tente novamente daqui a pouco.',
            'acao_primaria' => ['rotulo' => 'Tentar novamente', 'url' => '/'],
            'acao_secundaria' => ['rotulo' => 'Voltar ao início', 'url' => '/'],
        ],
    ];

    $pagina = $paginas[$status] ?? $paginas[500];
    $status = isset($paginas[$status]) ? $status : 500;

    return ['status' => $status, 'codigo' => 'Erro ' . $status, ...$pagina];
}

function mensagemErroPublica(int $status): string
{
    return dadosPaginaErro($status)['mensagem'];
}

function renderizarPaginaErro(int $status, ?string $requestId = null): void
{
    $pagina = dadosPaginaErro($status);
    $requestId = $requestId === null ? null : htmlspecialchars($requestId, ENT_QUOTES, 'UTF-8');

    http_response_code($pagina['status']);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store, max-age=0');
    if ($pagina['status'] === 503) {
        header('Retry-After: 3600');
    }

    require_once dirname(__DIR__) . '/errors/error-page.php';
    imprimirPaginaErro($pagina, $requestId);
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
        renderizarPaginaErro(500, $requestId);
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
