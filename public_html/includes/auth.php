<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/limites.php';
require_once __DIR__ . '/mailer.php';

const CODIGO_2FA_VALIDADE_SEG = 60;
const CODIGO_2FA_MAX_TENTATIVAS = 5;
const CODIGO_2FA_REENVIO_SEG = 60;
const LOGIN_MAX_FALHAS = 5;
const LOGIN_JANELA_SEG = 900;
const VERIFICACAO_MAX_FALHAS = 10;

// Hash de uma senha aleatória descartada: login com e-mail inexistente gasta o mesmo tempo.
const HASH_FICTICIO = '$2y$10$wTdtKrCxkw5BD3qZDRB01uXQam1g9toJMBPK6lSvDHO7TNA6SeZ2K';

// ---------------------------------------------------------------
// Sessão
// ---------------------------------------------------------------

function conexaoHttps(): bool
{
    return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
}

function iniciarSessao(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    config();
    ini_set('session.use_strict_mode', '1');
    session_name('reikiana');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => conexaoHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function redirecionar(string $url): never
{
    header('Location: ' . $url, true, 303);
    exit;
}

function ipCliente(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? 'desconhecido');
}

// ---------------------------------------------------------------
// Login (etapa 1: senha)
// ---------------------------------------------------------------

/** @return array{id: string, nome: string, email: string}|null */
function login(string $email, string $senha): ?array
{
    $stmt = db()->prepare('SELECT id, nome, email, senha_hash FROM administradores WHERE email = ?');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    $senhaOk = password_verify($senha, $admin ? $admin['senha_hash'] : HASH_FICTICIO);
    if (!$admin || !$senhaOk) {
        return null;
    }
    return ['id' => $admin['id'], 'nome' => $admin['nome'], 'email' => $admin['email']];
}

/** Senha ok: sessão fica "pendente 2FA" — ainda sem acesso ao painel. */
function iniciarVerificacao2fa(array $admin): void
{
    session_regenerate_id(true);
    unset($_SESSION['admin']);
    $_SESSION['2fa_pendente'] = $admin;
    $_SESSION['2fa_auto_reenvio_feito'] = false;
}

// ---------------------------------------------------------------
// 2FA (etapa 2: código por e-mail) — regras puras, testadas em tests/Codigo2faTest.php
// ---------------------------------------------------------------

function gerarCodigo2fa(): string
{
    return sprintf('%06d', random_int(0, 999999));
}

/**
 * @param array{codigo_hash: string, expira_em: string, tentativas: int|string, usado_em: ?string}|null $registro
 * @return 'ok'|'incorreto'|'expirado'|'bloqueado'|'invalido'
 */
function avaliarCodigo2fa(?array $registro, string $codigo, DateTimeImmutable $agora): string
{
    if ($registro === null || $registro['usado_em'] !== null) {
        return 'invalido';
    }
    if ($agora >= new DateTimeImmutable($registro['expira_em'])) {
        return 'expirado';
    }
    if ((int) $registro['tentativas'] >= CODIGO_2FA_MAX_TENTATIVAS) {
        return 'bloqueado';
    }
    return password_verify($codigo, $registro['codigo_hash']) ? 'ok' : 'incorreto';
}

function podeReenviarCodigo2fa(?string $ultimoEnvio, DateTimeImmutable $agora): bool
{
    if ($ultimoEnvio === null) {
        return true;
    }
    $decorrido = $agora->getTimestamp() - (new DateTimeImmutable($ultimoEnvio))->getTimestamp();
    return $decorrido >= CODIGO_2FA_REENVIO_SEG;
}

function mascararEmail(string $email): string
{
    [$usuario, $dominio] = explode('@', $email, 2) + [1 => ''];
    return mb_substr($usuario, 0, 1) . '***@' . $dominio;
}

// ---------------------------------------------------------------
// 2FA — acesso ao banco
// ---------------------------------------------------------------

function ultimoEnvioCodigo2fa(string $adminId): ?string
{
    $stmt = db()->prepare('SELECT MAX(criado_em) FROM codigos_2fa WHERE administrador_id = ?');
    $stmt->execute([$adminId]);
    $valor = $stmt->fetchColumn();
    return is_string($valor) ? $valor : null;
}

/** @param array{id: string, nome: string, email: string} $admin */
function enviarCodigo2fa(array $admin): bool
{
    $codigo = gerarCodigo2fa();
    $agora = new DateTimeImmutable();
    $pdo = db();

    // Código novo invalida os anteriores ainda não usados.
    $pdo->prepare('UPDATE codigos_2fa SET usado_em = NOW() WHERE administrador_id = ? AND usado_em IS NULL')
        ->execute([$admin['id']]);
    $pdo->prepare(
        'INSERT INTO codigos_2fa (id, administrador_id, codigo_hash, expira_em, criado_em) VALUES (?, ?, ?, ?, ?)'
    )->execute([
        gerarUuid(),
        $admin['id'],
        password_hash($codigo, PASSWORD_DEFAULT),
        $agora->modify('+' . CODIGO_2FA_VALIDADE_SEG . ' seconds')->format('Y-m-d H:i:s'),
        $agora->format('Y-m-d H:i:s'),
    ]);

    $texto = "Olá, {$admin['nome']}.\n\n"
        . "Seu código de acesso ao painel Reiki Ana é: {$codigo}\n\n"
        . "Ele vale por 1 minuto e só pode ser usado uma vez.\n"
        . "Se não foi você que tentou entrar, ignore este e-mail e troque sua senha.";
    return enviarEmail($admin['email'], 'Seu código de acesso — Reiki Ana', $texto);
}

/** @return 'ok'|'incorreto'|'expirado'|'bloqueado'|'invalido' */
function validarCodigo2fa(string $adminId, string $codigo): string
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT id, codigo_hash, expira_em, tentativas, usado_em FROM codigos_2fa
         WHERE administrador_id = ? AND usado_em IS NULL ORDER BY criado_em DESC LIMIT 1'
    );
    $stmt->execute([$adminId]);
    $registro = $stmt->fetch() ?: null;

    $resultado = avaliarCodigo2fa($registro, $codigo, new DateTimeImmutable());
    if ($registro === null || !in_array($resultado, ['ok', 'incorreto'], true)) {
        return $resultado;
    }

    // Reserva a tentativa de forma atômica: requisições em paralelo não furam o limite de 5.
    $reserva = $pdo->prepare(
        'UPDATE codigos_2fa SET tentativas = tentativas + 1
         WHERE id = ? AND tentativas < ? AND usado_em IS NULL AND expira_em > NOW()'
    );
    $reserva->execute([$registro['id'], CODIGO_2FA_MAX_TENTATIVAS]);
    if ($reserva->rowCount() !== 1) {
        if (new DateTimeImmutable() >= new DateTimeImmutable($registro['expira_em'])) {
            return 'expirado';
        }
        return 'bloqueado';
    }
    if ($resultado === 'incorreto') {
        return 'incorreto';
    }

    // Uso único, também atômico.
    $uso = $pdo->prepare(
        'UPDATE codigos_2fa SET usado_em = NOW()
         WHERE id = ? AND usado_em IS NULL AND expira_em > NOW()'
    );
    $uso->execute([$registro['id']]);
    if ($uso->rowCount() === 1) {
        return 'ok';
    }
    return new DateTimeImmutable() >= new DateTimeImmutable($registro['expira_em'])
        ? 'expirado'
        : 'invalido';
}

/** Código ok: sessão completa de admin. */
function concluirLogin(array $admin): void
{
    session_regenerate_id(true);
    unset($_SESSION['2fa_pendente']);
    $_SESSION['admin'] = ['id' => $admin['id'], 'nome' => $admin['nome']];
}

// ---------------------------------------------------------------
// Acesso ao painel
// ---------------------------------------------------------------

/** @return array{id: string, nome: string}|null */
function adminLogado(): ?array
{
    return $_SESSION['admin'] ?? null;
}

/** @return array{id: string, nome: string} */
function requireAdmin(): array
{
    iniciarSessao();
    $admin = adminLogado();
    if ($admin === null) {
        redirecionar('/admin/login.php');
    }
    return $admin;
}

function logout(): void
{
    $_SESSION = [];
    $p = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => $p['path'],
        'secure'   => $p['secure'],
        'httponly' => $p['httponly'],
        'samesite' => $p['samesite'],
    ]);
    session_destroy();
}
