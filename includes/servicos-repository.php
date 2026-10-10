<?php

declare(strict_types=1);

/** @return list<array<string, mixed>> */
function listarServicosAdmin(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT id, nome, descricao, duracao_min, preco, categoria, imagem_url, icone, cor, tag, ativo, ordem
         FROM servicos ORDER BY ordem ASC, nome ASC, id ASC'
    );

    /** @var list<array<string, mixed>> $servicos */
    $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $servicos;
}

/** @return list<string> */
function listarCategoriasServicos(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT DISTINCT categoria FROM servicos WHERE categoria <> "" ORDER BY categoria ASC');
    $nomes = array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    try {
        $stmt = $pdo->query('SELECT nome FROM categorias_servicos ORDER BY nome');
        $nomes = array_merge($nomes, array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN)));
    } catch (PDOException $erro) {
        if (
            (string) $erro->getCode() !== '42S02'
            && !($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
                && str_contains($erro->getMessage(), 'no such table: categorias_servicos'))
        ) {
            throw $erro;
        }
    }
    return normalizarCategoriasServico($nomes);
}

/** @return array<string, mixed>|null */
function obterServicoAdmin(PDO $pdo, string $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, nome, descricao, duracao_min, preco, categoria, imagem_url, icone, cor, tag, ativo, ordem
         FROM servicos WHERE id = ?'
    );
    $stmt->execute([$id]);
    $servico = $stmt->fetch(PDO::FETCH_ASSOC);
    return $servico !== false ? $servico : null;
}

/** @param array<string, mixed> $dados @return array<string, string> */
function validarDadosServicoAdmin(array $dados): array
{
    $erros = [];
    $textoObrigatorio = ['nome' => 100, 'descricao' => 65535, 'categoria' => 50];
    foreach ($textoObrigatorio as $campo => $limite) {
        $valor = trim((string) ($dados[$campo] ?? ''));
        if ($valor === '') {
            $erros[$campo] = 'Preencha este campo.';
        } elseif (mb_strlen($valor) > $limite) {
            $erros[$campo] = 'O texto excede o limite permitido.';
        }
    }

    $duracao = filter_var($dados['duracao_min'] ?? null, FILTER_VALIDATE_INT);
    if ($duracao === false || $duracao < 1) {
        $erros['duracao_min'] = 'Informe uma duração inteira positiva.';
    }

    $preco = $dados['preco'] ?? null;
    if ($preco !== null && trim((string) $preco) !== '') {
        if (
            !is_numeric($preco) || (float) $preco < 0 || preg_match('/^\d{1,6}(?:\.\d{1,2})?$/', (string)
            $preco) !== 1
        ) {
            $erros['preco'] = 'Informe um preço decimal não negativo.';
        }
    }

    $imagem = trim((string) ($dados['imagem_url'] ?? ''));
    if ($imagem !== '' && urlImagemServico($imagem) === null) {
        $erros['imagem_url'] = 'Informe uma URL de imagem permitida.';
    }
    foreach (['icone' => 50, 'cor' => 20, 'tag' => 50] as $campo => $limite) {
        if (mb_strlen(trim((string) ($dados[$campo] ?? ''))) > $limite) {
            $erros[$campo] = 'O texto excede o limite permitido.';
        }
    }

    $ordem = filter_var($dados['ordem'] ?? null, FILTER_VALIDATE_INT);
    if ($ordem === false || $ordem < 0) {
        $erros['ordem'] = 'Informe uma ordem inteira não negativa.';
    }

    return $erros;
}

/** @param array<string, mixed> $dados */
function salvarServicoAdmin(PDO $pdo, array $dados): string
{
    $id = isset($dados['id']) && is_string($dados['id']) && $dados['id'] !== ''
        ? $dados['id']
        : gerarUuid();
    $valores = [
        trim((string) $dados['nome']), trim((string) $dados['descricao']), (int) $dados['duracao_min'],
        ($dados['preco'] ?? '') === '' ? null : number_format((float) $dados['preco'], 2, '.', ''),
        trim((string) $dados['categoria']), trim((string) ($dados['imagem_url'] ?? '')) ?: null,
        trim((string) ($dados['icone'] ?? '')) ?: null, trim((string) ($dados['cor'] ?? '')) ?: null,
        trim((string) ($dados['tag'] ?? '')) ?: null, (int) $dados['ordem'],
    ];

    if (obterServicoAdmin($pdo, $id) === null) {
        $stmt = $pdo->prepare(
            'INSERT INTO servicos
             (id, nome, descricao, duracao_min, preco, categoria, imagem_url, icone, cor, tag, ativo, ordem)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)'
        );
        $stmt->execute([$id, ...$valores]);
        return $id;
    }

    $stmt = $pdo->prepare(
        'UPDATE servicos SET nome = ?, descricao = ?, duracao_min = ?, preco = ?, categoria = ?,
         imagem_url = ?, icone = ?, cor = ?, tag = ?, ordem = ? WHERE id = ?'
    );
    $stmt->execute([...$valores, $id]);
    return $id;
}

function alterarAtivoServico(PDO $pdo, string $id, bool $ativo): void
{
    $stmt = $pdo->prepare('UPDATE servicos SET ativo = ? WHERE id = ?');
    $stmt->execute([$ativo ? 1 : 0, $id]);
}

function imagemServicoEmUso(PDO $pdo, string $url): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM servicos WHERE imagem_url = ? LIMIT 1');
    $stmt->execute([$url]);
    return $stmt->fetch() !== false;
}

function excluirServicoAdmin(PDO $pdo, string $id): void
{
    $pdo->beginTransaction();
    try {
        $vinculo = $pdo->prepare('SELECT 1 FROM profissional_servico WHERE servico_id = ? LIMIT 1');
        $vinculo->execute([$id]);
        $agendamento = $pdo->prepare('SELECT 1 FROM agendamentos WHERE servico_id = ? LIMIT 1');
        $agendamento->execute([$id]);
        if ($vinculo->fetch() !== false || $agendamento->fetch() !== false) {
            throw new
            DomainException('Não é possível excluir um serviço vinculado a profissionais ou agendamentos.');
        }
        $stmt = $pdo->prepare('DELETE FROM servicos WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() !== 1) {
            throw new DomainException('Serviço não encontrado.');
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
