<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}
require_once __DIR__ . '/../public_html/includes/db.php';

$pdo = db();
$versao = '003_disponibilidade_datas';
$stmt = $pdo->prepare('SELECT versao FROM migracoes WHERE versao = ?');
$stmt->execute([$versao]);
if ($stmt->fetchColumn() !== false) {
    echo "Migração já aplicada.\n";
} else {
    $sql = file_get_contents(__DIR__ . '/../sql/migrations/003_disponibilidade_datas.sql');
    if ($sql === false) {
        throw new RuntimeException('Arquivo da migração não encontrado.');
    }
    $pdo->exec($sql);
    echo "Migração aplicada.\n";
}
$pdo->query('SELECT profissional_id, data, horarios FROM disponibilidade_datas LIMIT 0');
$stmt->execute([$versao]);
if ($stmt->fetchColumn() !== $versao) {
    throw new RuntimeException('Registro da migração não confirmado.');
}
echo "Tabela e registro da migração confirmados.\n";
