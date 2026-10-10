<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/errors.php';

$requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? null;
renderizarPaginaErro(500, is_string($requestId) ? $requestId : null);
