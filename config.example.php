<?php

// Modelo de configuração. Copie para ../config.php, fora de public_html, e preencha.
// A configuração é ignorada pelo Git e nunca deve ficar acessível pela web.

declare(strict_types=1);

return [
    // true só no seu PC: mostra erros PHP na tela. Em produção, SEMPRE false.
    'debug' => false,

    // Ative temporariamente para mostrar a página 503 nas entradas públicas.
    'maintenance_mode' => false,

    'db' => [
        'host'    => '127.0.0.1',
        'nome'    => 'anareiki',
        'usuario' => 'root',
        'senha'   => '',
    ],

    // Hostinger: smtp.hostinger.com, 465 (SSL) ou 587 (STARTTLS).
    // usuario = e-mail completo da caixa; senha = senha da própria caixa.
    'smtp' => [
        'host'           => 'smtp.hostinger.com',
        'porta'          => 465,
        'usuario'        => 'agendamento@reikiana.com.br',
        'senha'          => '',
        'remetente_nome' => 'Reiki Ana',
    ],
];
