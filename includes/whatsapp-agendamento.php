<?php

declare(strict_types=1);

/** @param array<string, mixed> $agendamento */
function mensagemWhatsAppAgendamento(array $agendamento): string
{
    $itens = $agendamento['itens'] ?? [];
    if (!is_array($itens) || $itens === []) {
        $itens = [[
            'nome_servico' => $agendamento['servico_nome'] ?? '',
            'duracao_min' => $agendamento['duracao_min'] ?? 0,
            'preco' => $agendamento['preco'] ?? null,
        ]];
    }

    $duracaoTotal = 0;
    $valorTotal = 0.0;
    $valoresDefinidos = true;
    $linhasServicos = [];
    foreach ($itens as $item) {
        if (!is_array($item)) {
            continue;
        }
        $duracao = (int) ($item['duracao_min'] ?? 0);
        $preco = $item['preco'] ?? null;
        $duracaoTotal += $duracao;
        if ($preco === null) {
            $valoresDefinidos = false;
            $precoFormatado = 'Consultar valor';
        } else {
            $valorTotal += (float) $preco;
            $precoFormatado = 'R$ ' . number_format((float) $preco, 2, ',', '.');
        }
        $horarioServico = '';
        if (isset($item['hora_inicio'], $item['hora_fim'])) {
            $horarioServico = (string) $item['hora_inicio'] . ' às ' . (string) $item['hora_fim'] . ' — ';
        }
        $linhasServicos[] = '• ' . (string) ($item['nome_servico'] ?? '')
            . ' — ' . $horarioServico . $duracao . ' min — ' . $precoFormatado;
    }

    $duracaoFormatada = ($duracaoTotal >= 60 ? intdiv($duracaoTotal, 60) . 'h' : '')
        . ($duracaoTotal % 60 > 0
            ? str_pad((string) ($duracaoTotal % 60), 2, '0', STR_PAD_LEFT) . 'min'
            : '00min');
    $data = date('d/m/Y', strtotime((string) ($agendamento['data'] ?? '')));

    return "Olá! Gostaria de confirmar meu agendamento.\n\n"
        . 'Cliente: ' . (string) ($agendamento['cliente_nome'] ?? '') . "\n"
        . 'Profissional: ' . (string) ($agendamento['profissional_nome'] ?? '') . "\n\n"
        . 'Data: ' . $data . "\n"
        . 'Horário: ' . (string) ($agendamento['hora_inicio'] ?? '')
        . ' às ' . (string) ($agendamento['hora_fim'] ?? '') . "\n\n"
        . "Serviços:\n" . implode("\n", $linhasServicos) . "\n\n"
        . 'Duração total: ' . $duracaoFormatada . "\n"
        . 'Total: ' . ($valoresDefinidos
            ? 'R$ ' . number_format($valorTotal, 2, ',', '.')
            : 'Consultar valor');
}

/** @param array<string, mixed> $agendamento */
function criarLinkWhatsAppAgendamento(array $agendamento, string $whatsappPhone): string
{
    $numero = preg_replace('/\D+/', '', $whatsappPhone);
    if (!is_string($numero) || preg_match('/^\d{10,15}$/', $numero) !== 1) {
        throw new InvalidArgumentException('Número de WhatsApp da empresa inválido.');
    }

    return 'https://wa.me/' . $numero . '?text='
        . rawurlencode(mensagemWhatsAppAgendamento($agendamento));
}
