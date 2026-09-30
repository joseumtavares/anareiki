<?php

// Cria o administrador ou redefine a senha de um existente (mesmo e-mail).
// Uso (na raiz do projeto): php bin/criar-admin.php
// A senha é digitada no terminal: não fica no histórico, no repositório nem em log.

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../public_html/includes/db.php';

function perguntar(string $rotulo): string
{
    echo $rotulo;
    return trim((string) fgets(STDIN));
}

$email = mb_strtolower(perguntar('E-mail do administrador: '));
if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 190) {
    fwrite(STDERR, "E-mail inválido. Rode o script de novo e digite um e-mail completo, ex.: ana@reikiana.com.br\n");
    exit(1);
}

$stmt = db()->prepare('SELECT id, nome FROM administradores WHERE email = ?');
$stmt->execute([$email]);
$existente = $stmt->fetch();

$nome = $existente ? $existente['nome'] : perguntar('Nome: ');
if ($nome === '' || mb_strlen($nome) > 100) {
    fwrite(STDERR, "Nome obrigatório (até 100 caracteres). Rode o script de novo.\n");
    exit(1);
}

echo "Atenção: a senha aparece na tela enquanto você digita (limitação do terminal do Windows).\n";
$senha = perguntar('Senha (mínimo 12 caracteres): ');
if (mb_strlen($senha) < 12) {
    fwrite(STDERR, "Senha curta demais. Use pelo menos 12 caracteres e rode o script de novo.\n");
    exit(1);
}
if (perguntar('Repita a senha: ') !== $senha) {
    fwrite(STDERR, "As senhas não conferem. Rode o script de novo.\n");
    exit(1);
}

$hash = password_hash($senha, PASSWORD_DEFAULT);

if ($existente) {
    db()->prepare('UPDATE administradores SET senha_hash = ? WHERE id = ?')->execute([$hash, $existente['id']]);
    echo "Senha de {$existente['nome']} redefinida.\n";
} else {
    db()->prepare('INSERT INTO administradores (id, nome, email, senha_hash) VALUES (?, ?, ?, ?)')
        ->execute([gerarUuid(), $nome, $email, $hash]);
    echo "Administrador {$nome} criado.\n";
}

echo "Dica: rode `cls` para limpar a senha da tela.\n";
