<?php

declare(strict_types=1);

require_once __DIR__ . '/public-view.php';

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
    return normalizarCategoriasServico(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN)));
}

/** @return list<array<string, mixed>> */
function listarProfissionaisAdmin(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT id, nome, especialidade, bio, foto_url, ativo FROM profissionais ORDER BY nome ASC, id ASC');
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
        $params = [trim((string) $dados['nome']), trim((string) ($dados['especialidade'] ?? '')) ?: null, trim((string) ($dados['bio'] ?? '')) ?: null, trim((string) ($dados['foto_url'] ?? '')) ?: null];
        if (obterProfissionalAdmin($pdo, $id) === null) {
            $stmt = $pdo->prepare('INSERT INTO profissionais (id, nome, especialidade, bio, foto_url, ativo) VALUES (?, ?, ?, ?, ?, 1)');
            $stmt->execute([$id, ...$params]);
        } else {
            $stmt = $pdo->prepare('UPDATE profissionais SET nome = ?, especialidade = ?, bio = ?, foto_url = ? WHERE id = ?');
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
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
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
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $e;
    }
}

/** @param array<string, mixed> $dados @return array<string, string> */
function validarDadosDisponibilidadeAdmin(PDO $pdo, array $dados, string $profissionalId, ?string $id = null): array
{
    $erros = [];
    $dia = filter_var($dados['dia_semana'] ?? null, FILTER_VALIDATE_INT);
    if ($dia === false || $dia < 0 || $dia > 6) { $erros['dia_semana'] = 'Escolha um dia válido.'; }
    $inicio = (string) ($dados['hora_inicio'] ?? '');
    $fim = (string) ($dados['hora_fim'] ?? '');
    if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $inicio) !== 1) { $erros['hora_inicio'] = 'Informe um horário válido.'; }
    if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $fim) !== 1) { $erros['hora_fim'] = 'Informe um horário válido.'; }
    if (!isset($erros['hora_inicio'], $erros['hora_fim']) && $inicio >= $fim) { $erros['hora_inicio'] = 'O início deve ser anterior ao fim.'; }
    if ($erros === [] && disponibilidadeSobreposta($pdo, $profissionalId, (int) $dia, $inicio, $fim, $id)) { $erros['sobreposicao'] = 'Este intervalo se sobrepõe a outro já cadastrado.'; }
    return $erros;
}

function disponibilidadeSobreposta(PDO $pdo, string $profissionalId, int $dia, string $inicio, string $fim, ?string $id = null): bool
{
    $sql = 'SELECT 1 FROM disponibilidade WHERE profissional_id = ? AND dia_semana = ? AND hora_inicio < ? AND hora_fim > ?';
    $params = [$profissionalId, $dia, $fim, $inicio];
    if ($id !== null) { $sql .= ' AND id <> ?'; $params[] = $id; }
    $stmt = $pdo->prepare($sql . ' LIMIT 1');
    $stmt->execute($params);
    return $stmt->fetch() !== false;
}

/** @return list<array<string, mixed>> */
function listarDisponibilidadeAdmin(PDO $pdo, string $profissionalId): array
{
    $stmt = $pdo->prepare('SELECT id, profissional_id, dia_semana, hora_inicio, hora_fim FROM disponibilidade WHERE profissional_id = ? ORDER BY dia_semana, hora_inicio');
    $stmt->execute([$profissionalId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** @param array<string, mixed> $dados */
function salvarDisponibilidadeAdmin(PDO $pdo, array $dados): string
{
    $id = isset($dados['id']) && (string) $dados['id'] !== '' ? (string) $dados['id'] : gerarUuid();
    $stmt = $pdo->prepare('SELECT 1 FROM disponibilidade WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->fetch() === false) {
        $stmt = $pdo->prepare('INSERT INTO disponibilidade (id, profissional_id, dia_semana, hora_inicio, hora_fim) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$id, $dados['profissional_id'], $dados['dia_semana'], $dados['hora_inicio'], $dados['hora_fim']]);
    } else {
        $stmt = $pdo->prepare('UPDATE disponibilidade SET dia_semana = ?, hora_inicio = ?, hora_fim = ? WHERE id = ?');
        $stmt->execute([$dados['dia_semana'], $dados['hora_inicio'], $dados['hora_fim'], $id]);
    }
    return $id;
}

/** @return array<string, mixed>|null */
function obterDisponibilidadeAdmin(PDO $pdo, string $id): ?array
{
    $stmt = $pdo->prepare('SELECT id, profissional_id, dia_semana, hora_inicio, hora_fim FROM disponibilidade WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row !== false ? $row : null;
}

function excluirDisponibilidadeAdmin(PDO $pdo, string $id): void
{
    $stmt = $pdo->prepare('DELETE FROM disponibilidade WHERE id = ?');
    $stmt->execute([$id]);
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
        if (!is_numeric($preco) || (float) $preco < 0 || preg_match('/^\d{1,6}(?:\.\d{1,2})?$/', (string) $preco) !== 1) {
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
            throw new DomainException('Não é possível excluir um serviço vinculado a profissionais ou agendamentos.');
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

/** @return list<array{hora_inicio: string, hora_fim: string}> */
function obterDisponibilidadeDia(PDO $pdo, string $profissionalId, int $diaSemana): array
{
    $stmt = $pdo->prepare(
        'SELECT hora_inicio, hora_fim
         FROM disponibilidade
         WHERE profissional_id = ? AND dia_semana = ?
         ORDER BY hora_inicio ASC'
    );
    $stmt->execute([$profissionalId, $diaSemana]);

    /** @var list<array{hora_inicio: string, hora_fim: string}> */
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** @return list<array{hora_inicio: string, hora_fim: string}> */
function obterAgendamentosDia(PDO $pdo, string $profissionalId, string $data): array
{
    $stmt = $pdo->prepare(
        "SELECT hora_inicio, hora_fim
         FROM agendamentos
         WHERE profissional_id = ? AND data = ? AND status != 'cancelado'
         ORDER BY hora_inicio ASC"
    );
    $stmt->execute([$profissionalId, $data]);

    /** @var list<array{hora_inicio: string, hora_fim: string}> */
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** @return list<array{id: string, nome: string}> */
function obterProfissionaisPorServico(PDO $pdo, string $servicoId): array
{
    $stmt = $pdo->prepare(
        'SELECT p.id, p.nome
         FROM profissionais p
         INNER JOIN profissional_servico ps ON ps.profissional_id = p.id
         WHERE ps.servico_id = ? AND p.ativo = 1
         ORDER BY p.nome ASC, p.id ASC'
    );
    $stmt->execute([$servicoId]);

    /** @var list<array{id: string, nome: string}> */
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function criarAgendamento(
    PDO $pdo,
    string $servicoId,
    string $profissionalId,
    string $data,
    string $horaInicio,
    int $duracaoMin,
    string $clienteNome,
    string $clienteTelefone
): string {
    $id = gerarUuid();
    $horaFim = date('H:i', strtotime($horaInicio) + $duracaoMin * 60);

    $stmt = $pdo->prepare(
        "INSERT INTO agendamentos
            (id, servico_id, profissional_id, cliente_nome,
             cliente_telefone, cliente_email, data, hora_inicio,
             hora_fim, status, observacao)
         VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?, 'pendente', NULL)"
    );
    $stmt->execute([
        $id, $servicoId, $profissionalId,
        $clienteNome, $clienteTelefone,
        $data, $horaInicio, $horaFim,
    ]);

    return $id;
}

/** @return array<string, mixed>|null */
function obterAgendamento(PDO $pdo, string $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT a.id, a.servico_id, a.profissional_id,
                a.cliente_nome, a.cliente_telefone, a.data,
                a.hora_inicio, a.hora_fim, a.status, a.criado_em,
                s.nome AS servico_nome, s.duracao_min, s.preco,
                p.nome AS profissional_nome
         FROM agendamentos a
         INNER JOIN servicos s ON s.id = a.servico_id
         INNER JOIN profissionais p ON p.id = a.profissional_id
         WHERE a.id = ?'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row !== false ? $row : null;
}

function validarSlotDisponivel(
    PDO $pdo,
    string $servicoId,
    string $profissionalId,
    string $data,
    string $horaInicio
): ?string {
    $stmtSrv = $pdo->prepare(
        'SELECT duracao_min FROM servicos WHERE id = ? AND ativo = 1'
    );
    $stmtSrv->execute([$servicoId]);
    $servico = $stmtSrv->fetch();
    if (!$servico) {
        return 'Serviço não encontrado.';
    }

    $stmtProf = $pdo->prepare(
        'SELECT id FROM profissionais WHERE id = ? AND ativo = 1'
    );
    $stmtProf->execute([$profissionalId]);
    if (!$stmtProf->fetch()) {
        return 'Profissional não encontrado.';
    }

    $stmtPs = $pdo->prepare(
        'SELECT 1 FROM profissional_servico
         WHERE profissional_id = ? AND servico_id = ?'
    );
    $stmtPs->execute([$profissionalId, $servicoId]);
    if (!$stmtPs->fetch()) {
        return 'Profissional não realiza este serviço.';
    }

    $duracaoMin = (int) $servico['duracao_min'];
    $slots = obterSlotsDisponiveis($pdo, $profissionalId, $data, $duracaoMin);

    if (!in_array($horaInicio, $slots, true)) {
        $diaSemana = (int) date('w', strtotime($data));
        $faixas = obterDisponibilidadeDia($pdo, $profissionalId, $diaSemana);
        if ($faixas === []) {
            return 'Profissional não disponível nesta data.';
        }
        return 'Horário ocupado ou indisponível.';
    }

    return null;
}
