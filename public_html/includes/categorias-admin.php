<?php

declare(strict_types=1);

function salvarCategoriaServico(PDO $pdo, string $nome): void
{
    $nomes = normalizarCategoriasServico([$nome]);
    if ($nomes === [] || mb_strlen($nomes[0]) > 50) {
        throw new InvalidArgumentException('Informe uma categoria com até 50 caracteres.');
    }
    $nomes[0] = mb_convert_case($nomes[0], MB_CASE_TITLE, 'UTF-8');
    $stmt = $pdo->prepare('SELECT nome FROM categorias_servicos WHERE nome = ?');
    $stmt->execute([$nomes[0]]);
    if ($stmt->fetchColumn() !== false) {
        return;
    }
    try {
        $stmt = $pdo->prepare('INSERT INTO categorias_servicos (nome) VALUES (?)');
        $stmt->execute([$nomes[0]]);
    } catch (PDOException $erro) {
        if ((string) $erro->getCode() !== '23000') {
            throw $erro;
        }
        $stmt = $pdo->prepare('SELECT nome FROM categorias_servicos WHERE nome = ?');
        $stmt->execute([$nomes[0]]);
        if ($stmt->fetchColumn() === false) {
            throw $erro;
        }
    }
}
