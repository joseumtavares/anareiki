<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../public_html/includes/uploads.php';

final class AdminImageGalleryTest extends TestCase
{
    private string $baseDir;

    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'reiki-gallery-' .
        bin2hex(random_bytes(4));
        mkdir($this->baseDir, 0775, true);
        copy(__DIR__ . '/../public_html/favicon.svg', $this->baseDir . DIRECTORY_SEPARATOR .
        'nao-imagem.svg');
        file_put_contents(
            $this->baseDir . DIRECTORY_SEPARATOR . 'foto.jpg',
            base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP////////////////////////////////////' .
            '//////2wBDAf//////////////////////////////////////////wAARCAABAAEDASIA' .
            'AhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9' .
            'oADAMBAAIQAxAAAAH/AP/EABQQAQAAAAAAAAAAAAAAAAAAACD/2gAIAQEAAQUCf//EABQR' .
            'AQAAAAAAAAAAAAAAAAAAABD/2gAIAQMBAT8BP//EABQRAQAAAAAAAAAAAAAAAAAAABD/2g' .
            'AIAQIBAT8BP//EABQQAQAAAAAAAAAAAAAAAAAAABD/2gAIAQEABj8Cf//Z', true)
        );
    }

    protected function tearDown(): void
    {
        foreach (glob($this->baseDir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->baseDir);
    }

    public function test_lista_apenas_extensoes_de_imagem_permitidas(): void
    {
        $imagens = listarImagensUpload('servicos', $this->baseDir);

        self::assertSame([], $imagens);
    }
}
