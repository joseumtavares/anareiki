<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

// Limite de taxa por chave em janela fixa (tabela limites_taxa, migração 002).

function limiteExcedido(string $chave, int $maximo, int $janelaSeg): bool
{
    $stmt = db()->prepare(
        'SELECT contador FROM limites_taxa
         WHERE chave = ? AND janela_inicio > NOW() - INTERVAL ? SECOND'
    );
    $stmt->execute([$chave, $janelaSeg]);
    return (int) $stmt->fetchColumn() >= $maximo;
}

function registrarFalha(string $chave, int $janelaSeg): void
{
    // Janela vencida recomeça a contagem. MySQL avalia as atribuições da esquerda
    // para a direita: contador usa o janela_inicio antigo antes de ele ser trocado.
    $stmt = db()->prepare(
        'INSERT INTO limites_taxa (chave, contador, janela_inicio) VALUES (?, 1, NOW())
         ON DUPLICATE KEY UPDATE
           contador = IF(janela_inicio <= NOW() - INTERVAL ? SECOND, 1, contador + 1),
           janela_inicio = IF(janela_inicio <= NOW() - INTERVAL ? SECOND, NOW(), janela_inicio)'
    );
    $stmt->execute([$chave, $janelaSeg, $janelaSeg]);
}

function limparLimite(string $chave): void
{
    db()->prepare('DELETE FROM limites_taxa WHERE chave = ?')->execute([$chave]);
}
