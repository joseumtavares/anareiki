<?php

declare(strict_types=1);

const ADMIN_FLASH_TIPOS = ['success', 'info', 'warning', 'danger'];

function adminFlash(string $mensagem, string $tipo = 'success'): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('A sessão precisa estar ativa para gravar uma mensagem.');
    }

    $_SESSION['admin_flash'] = [
        'mensagem' => $mensagem,
        'tipo' => in_array($tipo, ADMIN_FLASH_TIPOS, true) ? $tipo : 'info',
    ];
}

/** @return array{mensagem: string, tipo: string}|null */
function consumirAdminFlash(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE || !isset($_SESSION['admin_flash'])) {
        return null;
    }

    $flash = $_SESSION['admin_flash'];
    unset($_SESSION['admin_flash']);

    if (!is_array($flash) || !isset($flash['mensagem'], $flash['tipo'])) {
        return null;
    }

    return [
        'mensagem' => (string) $flash['mensagem'],
        'tipo' => in_array($flash['tipo'], ADMIN_FLASH_TIPOS, true) ? $flash['tipo'] : 'info',
    ];
}

function adminValor(array $valores, string $campo, string $fallback = ''): string
{
    return isset($valores[$campo]) && is_scalar($valores[$campo])
        ? (string) $valores[$campo]
        : $fallback;
}

function adminErro(array $erros, string $campo): ?string
{
    return isset($erros[$campo]) && is_scalar($erros[$campo])
        ? (string) $erros[$campo]
        : null;
}
