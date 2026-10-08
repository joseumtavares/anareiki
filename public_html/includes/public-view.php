<?php

declare(strict_types=1);

function htmlPublico(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function urlImagemServico(?string $url): ?string
{
    if ($url === null || $url === '') {
        return null;
    }

    if (caminhoImagemLocalValido($url)) {
        return $url;
    }

    if (preg_match('~\A/uploads/(servicos|profissionais)/[a-f0-9]{32}\.(?:jpg|png|webp)\z~D', $url) === 1) {
        return $url;
    }

    if (preg_match('~\Ahttps://www\.genspark\.ai/api/files/[A-Za-z0-9/_-]+\z~D', $url) === 1) {
        return $url;
    }

    return null;
}

function urlFotoProfissional(?string $url): ?string
{
    if ($url === null) {
        return null;
    }
    if (preg_match('~\A/uploads/profissionais/[a-f0-9]{32}\.(?:jpg|png|webp)\z~D', $url) === 1) {
        return $url;
    }
    if (!caminhoImagemLocalValido($url)) {
        return null;
    }

    return $url;
}

function caminhoImagemLocalValido(string $url): bool
{
    if (preg_match('~\A/static/[A-Za-z0-9._/-]+\z~D', $url) !== 1) {
        return false;
    }

    foreach (explode('/', substr($url, 1)) as $segmento) {
        if ($segmento === '' || $segmento === '.' || $segmento === '..') {
            return false;
        }
    }

    return true;
}
