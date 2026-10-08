<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}
require_once __DIR__ . '/../public_html/includes/db.php';

$pdo = db();
// Recusa explicitamente destinos remotos: este helper é apenas para desenvolvimento local.
$conexao = (string) $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);
if (preg_match('/^(localhost|127\.0\.0\.1|::1)\b/i', $conexao) !== 1) {
    throw new RuntimeException('Migration automática permitida somente em conexão local.');
}
$versao = '004_categorias_servicos';
$stmt = $pdo->prepare('SELECT versao FROM migracoes WHERE versao = ?');
$stmt->execute([$versao]);
if ($stmt->fetchColumn() === false) {
    $sql = file_get_contents(__DIR__ . '/../sql/migrations/004_categorias_servicos.sql');
    if ($sql === false) {
        throw new RuntimeException('Arquivo da migration não encontrado.');
    }
    $pdo->exec($sql);
    echo "Migration de categorias aplicada.\n";
} else {
    echo "Migration de categorias já aplicada.\n";
}
$pdo->query('SELECT nome FROM categorias_servicos LIMIT 0');
echo "Tabela de categorias confirmada.\n";
