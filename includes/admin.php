<?php

declare(strict_types=1);

const ADMIN_FLASH_TIPOS = ['success', 'info', 'warning', 'danger'];

function opcoesIconeServico(): array
{
    return ['fa-hands', 'fa-hands-praying', 'fa-spa', 'fa-droplet', 'fa-person', 'fa-fire', 'fa-brain'];
}

function opcoesCorServico(): array
{
    return ['purple', 'green', 'gold', 'rose', 'teal', 'pink'];
}

function exemplosTagServico(): array
{
    return ['Mais Pedida', 'Novidade', 'Destaque', 'Relaxamento', 'Terapêutico'];
}

/** @param list<string> $categorias @return list<string> */
function normalizarCategoriasServico(array $categorias): array
{
    $resultado = [];
    foreach ($categorias as $categoria) {
        $categoria = trim($categoria);
        if ($categoria !== '') {
            $resultado[mb_strtolower($categoria)] = $categoria;
        }
    }
    $resultado = array_values($resultado);
    sort($resultado, SORT_NATURAL | SORT_FLAG_CASE);
    return $resultado;
}

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
