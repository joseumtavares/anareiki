<?php

declare(strict_types=1);

require_once __DIR__ . '/errors.php';

/** @return array<string, mixed> */
function config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require dirname(__DIR__) . '/config.php';
        date_default_timezone_set('America/Sao_Paulo');
        ini_set('display_errors', $config['debug'] ? '1' : '0');
    }
    return $config;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = config()['db'];
        try {
            $pdo = new PDO(
                "mysql:host={$c['host']};dbname={$c['nome']};charset=utf8mb4",
                $c['usuario'],
                $c['senha'],
                [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            registrarErroAplicacao($e, gerarRequestId());
            throw new RuntimeException('Serviço temporariamente indisponível.', 0, $e);
        }
        // Mesmo fuso do PHP: NOW()/CURRENT_TIMESTAMP batem com DateTime (Brasil sem horário de verão).
        $pdo->exec("SET time_zone = '-03:00'");
    }
    return $pdo;
}

/** UUID v7 (RFC 9562): 48 bits de timestamp em ms + aleatório — ordenado no tempo. */
function gerarUuid(): string
{
    $ms = (int) (microtime(true) * 1000);
    $bytes = substr(pack('J', $ms), 2) . random_bytes(10);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x70);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
}

function uuidValido(string $valor): bool
{
    return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $valor) === 1;
}
