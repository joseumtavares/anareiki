<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

iniciarSessao();

// Só POST com CSRF: um link/imagem de outro site não consegue deslogar o admin.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfValido($_POST['csrf_token'] ?? null)) {
    redirecionar('/admin/');
}

logout();
redirecionar('/admin/login.php?saiu=1');
