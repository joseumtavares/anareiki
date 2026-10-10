<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/auth.php';

final class Codigo2faTest extends TestCase
{
    private DateTimeImmutable $agora;

    protected function setUp(): void
    {
        $this->agora = new DateTimeImmutable('2026-10-02 18:00:00');
    }

    /** @return array{codigo_hash: string, expira_em: string, tentativas: int, usado_em: ?string} */
    private function registro(
        string $codigo = '123456',
        string $expiraEm = '2026-10-02 18:10:00',
        int $tentativas = 0,
        ?string $usadoEm = null
    ): array {
        return [
            'codigo_hash' => password_hash($codigo, PASSWORD_DEFAULT),
            'expira_em'   => $expiraEm,
            'tentativas'  => $tentativas,
            'usado_em'    => $usadoEm,
        ];
    }

    public function test_codigo_gerado_tem_seis_digitos(): void
    {
        for ($i = 0; $i < 200; $i++) {
            $this->assertMatchesRegularExpression('/^\d{6}$/', gerarCodigo2fa());
        }
    }

    public function test_codigo_correto_dentro_da_validade_e_aceito(): void
    {
        $this->assertSame('ok', avaliarCodigo2fa($this->registro(), '123456', $this->agora));
    }

    public function test_codigo_errado_e_recusado(): void
    {
        $this->assertSame('incorreto', avaliarCodigo2fa($this->registro(), '654321', $this->agora));
    }

    public function test_codigo_expirado_e_recusado_mesmo_se_correto(): void
    {
        $registro = $this->registro(expiraEm: '2026-10-02 17:59:59');

        $this->assertSame('expirado', avaliarCodigo2fa($registro, '123456', $this->agora));
    }

    public function test_codigo_no_instante_exato_da_expiracao_ja_expirou(): void
    {
        $registro = $this->registro(expiraEm: '2026-10-02 18:00:00');

        $this->assertSame('expirado', avaliarCodigo2fa($registro, '123456', $this->agora));
    }

    public function test_apos_cinco_tentativas_bloqueia_mesmo_o_codigo_correto(): void
    {
        $registro = $this->registro(tentativas: CODIGO_2FA_MAX_TENTATIVAS);

        $this->assertSame('bloqueado', avaliarCodigo2fa($registro, '123456', $this->agora));
    }

    public function test_quarta_tentativa_errada_ainda_permite_tentar(): void
    {
        $registro = $this->registro(tentativas: CODIGO_2FA_MAX_TENTATIVAS - 1);

        $this->assertSame('ok', avaliarCodigo2fa($registro, '123456', $this->agora));
    }

    public function test_codigo_ja_usado_nao_vale_de_novo(): void
    {
        $registro = $this->registro(usadoEm: '2026-10-02 17:58:00');

        $this->assertSame('invalido', avaliarCodigo2fa($registro, '123456', $this->agora));
    }

    public function test_sem_codigo_pendente_e_invalido(): void
    {
        $this->assertSame('invalido', avaliarCodigo2fa(null, '123456', $this->agora));
    }

    public function test_reenvio_liberado_quando_nunca_enviou(): void
    {
        $this->assertTrue(podeReenviarCodigo2fa(null, $this->agora));
    }

    public function test_reenvio_bloqueado_antes_de_sessenta_segundos(): void
    {
        $this->assertFalse(podeReenviarCodigo2fa('2026-10-02 17:59:01', $this->agora));
    }

    public function test_reenvio_liberado_com_sessenta_segundos(): void
    {
        $this->assertTrue(podeReenviarCodigo2fa('2026-10-02 17:59:00', $this->agora));
    }

    public function test_mascara_email_para_exibir_na_tela(): void
    {
        $this->assertSame('a***@reikiana.com.br', mascararEmail('ana@reikiana.com.br'));
    }
}
