<?php

declare(strict_types=1);

/** @return list<array<string, mixed>> */
function listarProfissionaisAdmin(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT id, nome, especialidade, bio, foto_url, ativo FROM profissionais ORDER BY
            nome ASC, id ASC');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** @return list<string> */
function listarServicosDoProfissional(PDO $pdo, string $id): array
{
    $stmt = $pdo->prepare('SELECT servico_id FROM profissional_servico WHERE profissional_id = ? ORDER BY servico_id');
    $stmt->execute([$id]);
    return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/** @param array<string, mixed> $dados @return array<string, string> */
function validarDadosProfissionalAdmin(PDO $pdo, array $dados): array
{
    $erros = [];
    $nome = trim((string) ($dados['nome'] ?? ''));
    if ($nome === '' || mb_strlen($nome) > 100) {
        $erros['nome'] = 'Informe um nome de até 100 caracteres.';
    }
    if (mb_strlen(trim((string) ($dados['especialidade'] ?? ''))) > 150) {
        $erros['especialidade'] = 'A especialidade excede o limite permitido.';
    }
    $foto = trim((string) ($dados['foto_url'] ?? ''));
    if ($foto !== '' && urlFotoProfissional($foto) === null) {
        $erros['foto_url'] = 'Use um caminho local válido para a foto.';
    }
    $ids = array_values(array_filter(array_map('strval', (array) ($dados['servicos'] ?? []))));
    if ($ids !== []) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id FROM servicos WHERE ativo = 1 AND id IN ($placeholders)");
        $stmt->execute($ids);
        if (count($stmt->fetchAll(PDO::FETCH_COLUMN)) !== count(array_unique($ids))) {
            $erros['servicos'] = 'Selecione apenas serviços ativos existentes.';
        }
    }
    return $erros;
}

/** @param array<string, mixed> $dados */
function salvarProfissionalAdmin(PDO $pdo, array $dados): string
{
    $id = isset($dados['id']) && is_string($dados['id']) && $dados['id'] !== '' ? $dados['id'] : gerarUuid();
    $pdo->beginTransaction();
    try {
        $params = [trim((string) $dados['nome']), trim((string) ($dados['especialidade'] ?? '')) ?: null,
        trim((string) ($dados['bio'] ?? '')) ?: null, trim((string) ($dados['foto_url'] ?? '')) ?: null];
        if (obterProfissionalAdmin($pdo, $id) === null) {
            $stmt = $pdo->prepare('INSERT INTO profissionais (id, nome, especialidade, bio, foto_url, ativo) VALUES
            (?, ?, ?, ?, ?, 1)');
            $stmt->execute([$id, ...$params]);
        } else {
            $stmt = $pdo->prepare('UPDATE profissionais SET nome = ?, especialidade = ?, bio = ?, foto_url = ?
            WHERE id = ?');
            $stmt->execute([...$params, $id]);
        }
        $pdo->prepare('DELETE FROM profissional_servico WHERE profissional_id = ?')->execute([$id]);
        $stmt = $pdo->prepare('INSERT INTO profissional_servico (profissional_id, servico_id) VALUES (?, ?)');
        foreach (array_unique(array_map('strval', (array) ($dados['servicos'] ?? []))) as $servicoId) {
            $stmt->execute([$id, $servicoId]);
        }
        $pdo->commit();
        return $id;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/** @return array<string, mixed>|null */
function obterProfissionalAdmin(PDO $pdo, string $id): ?array
{
    $stmt = $pdo->prepare('SELECT id, nome, especialidade, bio, foto_url, ativo FROM profissionais WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row !== false ? $row : null;
}

function excluirProfissionalAdmin(PDO $pdo, string $id): void
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT 1 FROM profissional_servico WHERE profissional_id = ? LIMIT 1');
        $stmt->execute([$id]);
        $vinculado = $stmt->fetch() !== false;
        $stmt = $pdo->prepare('SELECT 1 FROM agendamentos WHERE profissional_id = ? LIMIT 1');
        $stmt->execute([$id]);
        if ($vinculado || $stmt->fetch() !== false) {
            throw new DomainException('Não é possível excluir um profissional com vínculos ou agendamentos.');
        }
        $stmt = $pdo->prepare('DELETE FROM profissionais WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() !== 1) {
            throw new DomainException('Profissional não encontrado.');
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
