<?php

declare(strict_types=1);

/** @return list<array<string, mixed>> */
function listarServicosPublicos(PDO $pdo): array
{
    $stmt = $pdo->prepare(
        'SELECT id, nome, descricao, duracao_min, preco, categoria, imagem_url, icone, cor, tag
         FROM servicos
         WHERE ativo = 1
         ORDER BY ordem ASC, nome ASC, id ASC'
    );
    $stmt->execute();

    /** @var list<array<string, mixed>> $servicos */
    $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $servicos;
}

/** @return list<array<string, mixed>> */
function listarProfissionaisPublicos(PDO $pdo): array
{
    $stmt = $pdo->prepare(
        'SELECT id, nome, especialidade, bio, foto_url
         FROM profissionais
         WHERE ativo = 1
         ORDER BY nome ASC, id ASC'
    );
    $stmt->execute();

    /** @var list<array<string, mixed>> $profissionais */
    $profissionais = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $profissionais;
}
