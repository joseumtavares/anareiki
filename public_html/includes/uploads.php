<?php

declare(strict_types=1);

const ADMIN_UPLOAD_MAX_BYTES = 5242880;

/** @return list<string> */
function listarImagensUpload(string $categoria, ?string $baseDir = null): array
{
    if (!in_array($categoria, ['servicos', 'profissionais'], true)) {
        return [];
    }
    $diretorio = ($baseDir ?? dirname(__DIR__) . '/uploads') . DIRECTORY_SEPARATOR . $categoria;
    $arquivos = glob($diretorio . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];
    $resultado = [];
    foreach ($arquivos as $arquivo) {
        $nome = basename($arquivo);
        if (preg_match('/^[a-f0-9]{32}\.(?:jpg|jpeg|png|webp)$/', $nome) === 1 || ($baseDir !== null && @getimagesize($arquivo) !== false)) {
            $resultado[] = '/uploads/' . $categoria . '/' . $nome;
        }
    }
    sort($resultado);
    return $resultado;
}

/** @param array<string, mixed> $arquivo */
function salvarUploadImagem(array $arquivo, string $categoria, ?string $baseDir = null): string
{
    $categorias = ['servicos', 'profissionais'];
    if (!in_array($categoria, $categorias, true)) {
        throw new InvalidArgumentException('Categoria de imagem inválida.');
    }
    if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new InvalidArgumentException('Selecione uma imagem.');
    }
    if (($arquivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Não foi possível receber a imagem.');
    }
    $tmp = (string) ($arquivo['tmp_name'] ?? '');
    if (!is_uploaded_file($tmp) && $baseDir === null) {
        throw new InvalidArgumentException('Upload inválido.');
    }
    if (!is_file($tmp) || (int) ($arquivo['size'] ?? 0) > ADMIN_UPLOAD_MAX_BYTES) {
        throw new InvalidArgumentException('A imagem deve ter até 5 MB.');
    }

    $info = @getimagesize($tmp);
    $mime = is_array($info) ? (string) $info['mime'] : '';
    $extensoes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensoes[$mime])) {
        throw new InvalidArgumentException('Use uma imagem JPG, PNG ou WEBP válida.');
    }

    $raiz = $baseDir ?? dirname(__DIR__) . '/uploads';
    $destino = $raiz . DIRECTORY_SEPARATOR . $categoria;
    if (!is_dir($destino) && !mkdir($destino, 0755, true) && !is_dir($destino)) {
        throw new RuntimeException('Não foi possível preparar a pasta de imagens.');
    }
    $nome = bin2hex(random_bytes(16)) . '.' . $extensoes[$mime];
    $caminho = $destino . DIRECTORY_SEPARATOR . $nome;
    $movido = $baseDir === null ? move_uploaded_file($tmp, $caminho) : rename($tmp, $caminho);
    if (!$movido) {
        throw new RuntimeException('Não foi possível salvar a imagem.');
    }
    return '/uploads/' . $categoria . '/' . $nome;
}
