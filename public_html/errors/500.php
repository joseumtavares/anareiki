<?php

declare(strict_types=1);

$requestId = htmlspecialchars(
    (string) ($_SERVER['HTTP_X_REQUEST_ID'] ?? 'desconhecido'),
    ENT_QUOTES,
    'UTF-8'
);
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport"
    content="width=device-width, initial-scale=1"><title>Erro — Reiki Ana</title><link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head><body
    class="bg-light"><main class="container py-5"><div class="card p-4 mx-auto" style="max-width:36rem"><h1
    class="h3">Algo não saiu como esperado</h1><p>O erro foi registrado. Informe este código ao suporte:</p>
    <code><?= $requestId ?></code><a class="btn btn-primary mt-4" href="/">Voltar ao início</a></div></main>
    </body></html>
