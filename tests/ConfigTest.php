<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/db.php';

final class ConfigTest extends TestCase
{
    public function test_caminho_da_configuracao_fica_fora_da_raiz_publica(): void
    {
        $caminho = caminhoConfiguracao();

        self::assertSame('config.php', basename($caminho));
        self::assertSame(dirname(__DIR__, 2), dirname($caminho));
        self::assertNotSame(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php', $caminho);
    }

    public function test_carregamento_informa_ausencia_sem_deixar_o_require_gerar_fatal(): void
    {
        $caminhoInexistente = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'anareiki-config-ausente.php';

        self::assertFileDoesNotExist($caminhoInexistente);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Configuração da aplicação indisponível.');

        carregarConfiguracao($caminhoInexistente);
    }

    public function test_entradas_publicas_configuram_o_handler_antes_da_manutencao(): void
    {
        $entradas = [
            'index.php',
            'agendar.php',
            'confirmacao-agendamento.php',
            'api/profissionais.php',
            'api/slots.php',
            'api/disponibilidade.php',
        ];

        foreach ($entradas as $entrada) {
            $fonte = (string) file_get_contents(__DIR__ . '/../' . $entrada);
            $handler = strpos($fonte, 'configurarTratamentoErros()');
            $manutencao = strpos($fonte, 'interromperSeEmManutencao');

            self::assertNotFalse($handler, $entrada);
            self::assertNotFalse($manutencao, $entrada);
            self::assertLessThan($manutencao, $handler, $entrada);
        }
    }
}
