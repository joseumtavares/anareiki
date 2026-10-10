<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/whatsapp-agendamento.php';

final class WhatsappAgendamentoTest extends TestCase
{
    public function test_cria_link_com_resumo_completo_do_agendamento(): void
    {
        $link = criarLinkWhatsAppAgendamento([
            'cliente_nome' => 'Maria Silva',
            'profissional_nome' => 'Ana',
            'data' => '2026-11-10',
            'hora_inicio' => '14:00',
            'hora_fim' => '15:15',
            'itens' => [
                ['nome_servico' => 'Reiki', 'duracao_min' => 45, 'preco' => '50.00'],
                ['nome_servico' => 'Massagem', 'duracao_min' => 30, 'preco' => '35.00'],
            ],
        ], '5548996137757');

        self::assertStringStartsWith('https://wa.me/5548996137757?text=', $link);
        $query = [];
        parse_str((string) parse_url($link, PHP_URL_QUERY), $query);
        $mensagem = (string) ($query['text'] ?? '');
        self::assertStringContainsString('Cliente: Maria Silva', $mensagem);
        self::assertStringContainsString('Profissional: Ana', $mensagem);
        self::assertStringContainsString('10/11/2026', $mensagem);
        self::assertStringContainsString('14:00 às 15:15', $mensagem);
        self::assertStringContainsString('• Reiki — 45 min — R$ 50,00', $mensagem);
        self::assertStringContainsString('Duração total: 1h15min', $mensagem);
        self::assertStringContainsString('Total: R$ 85,00', $mensagem);
    }

    public function test_mensagem_inclui_intervalo_de_cada_servico(): void
    {
        $mensagem = mensagemWhatsAppAgendamento([
            'data' => '2026-11-10',
            'hora_inicio' => '09:00',
            'hora_fim' => '10:00',
            'itens' => [[
                'nome_servico' => 'Reiki',
                'duracao_min' => 45,
                'preco' => '50.00',
                'hora_inicio' => '16:00',
                'hora_fim' => '16:45',
            ]],
        ]);

        self::assertStringContainsString('16:00', $mensagem);
        self::assertStringContainsString('16:45', $mensagem);
    }
}
