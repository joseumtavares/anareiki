<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../public_html/includes/uploads.php';

final class AdminUploadTest extends TestCase
{
    private string $baseDir;

    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'reiki-upload-' .
        bin2hex(random_bytes(4));
        mkdir($this->baseDir, 0775, true);
        mkdir($this->baseDir . DIRECTORY_SEPARATOR . 'servicos', 0775, true);
    }

    protected function tearDown(): void
    {
        $files = glob($this->baseDir . DIRECTORY_SEPARATOR . '**' . DIRECTORY_SEPARATOR . '*') ?: [];
        foreach (array_reverse($files) as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        @rmdir($this->baseDir . DIRECTORY_SEPARATOR . 'servicos');
        @rmdir($this->baseDir);
    }

    public function test_salva_imagem_valida_em_subpasta_com_nome_aleatorio(): void
    {
        $arquivo = tempnam($this->baseDir, 'src');
        file_put_contents(
            $arquivo,
            base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP////////////////////////////////////' .
            '//////2wBDAf//////////////////////////////////////////wAARCAABAAEDASIA' .
            'AhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9' .
            'oADAMBAAIQAxAAAAH/AP/EABQQAQAAAAAAAAAAAAAAAAAAACD/2gAIAQEAAQUCf//EABQR' .
            'AQAAAAAAAAAAAAAAAAAAABD/2gAIAQMBAT8BP//EABQRAQAAAAAAAAAAAAAAAAAAABD/2g' .
            'AIAQIBAT8BP//EABQQAQAAAAAAAAAAAAAAAAAAABD/2gAIAQEABj8Cf//Z', true)
        );
        $resultado = salvarUploadImagem([
            'name' => 'foto.jpg', 'type' => 'image/jpeg', 'tmp_name' => $arquivo,
            'error' => UPLOAD_ERR_OK, 'size' => filesize($arquivo),
        ], 'servicos', $this->baseDir);

        self::assertStringStartsWith('/uploads/servicos/', $resultado);
        self::assertCount(1, glob($this->baseDir . DIRECTORY_SEPARATOR . 'servicos' . DIRECTORY_SEPARATOR .
        '*'));
    }

    public function test_rejeita_extensao_e_conteudo_que_nao_sao_imagem(): void
    {
        $arquivo = tempnam($this->baseDir, 'src');
        file_put_contents($arquivo, '<?php echo "x";');

        $this->expectException(InvalidArgumentException::class);
        salvarUploadImagem([
            'name' => 'shell.php', 'type' => 'image/jpeg', 'tmp_name' => $arquivo,
            'error' => UPLOAD_ERR_OK, 'size' => filesize($arquivo),
        ], 'servicos', $this->baseDir);
    }

    public function test_exclui_apenas_imagem_do_diretorio_permitido(): void
    {
        $nome = str_repeat('a', 32) . '.jpg';
        $arquivo = $this->baseDir . DIRECTORY_SEPARATOR . 'servicos' . DIRECTORY_SEPARATOR . $nome;
        file_put_contents($arquivo, 'imagem');

        excluirUploadImagem('/uploads/servicos/' . $nome, $this->baseDir);

        self::assertFileDoesNotExist($arquivo);
    }

    public function test_rejeita_caminho_fora_da_pasta_de_upload(): void
    {
        $this->expectException(InvalidArgumentException::class);
        excluirUploadImagem('/config.php', $this->baseDir);
    }
}
