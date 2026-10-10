<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

$publicViewFile = __DIR__ . '/../includes/public-view.php';
if (is_file($publicViewFile)) {
    require_once $publicViewFile;
}

final class PublicViewTest extends TestCase
{
    public function test_escapa_texto_e_atributos_html_incluindo_utf8_invalido(): void
    {
        self::assertSame(
            '&lt;script&gt;alert(&#039;x&#039;)&lt;/script&gt; &amp; &#039;Ana&#039; &quot;Reiki&quot;',
            htmlPublico('<script>alert(\'x\')</script> & \'Ana\' "Reiki"')
        );
        self::assertSame('', htmlPublico(null));
        self::assertSame('a�b', htmlPublico("a\x80b"));
    }

    public function test_aceita_apenas_urls_locais_para_foto_de_profissional(): void
    {
        self::assertSame('/static/img/ana.webp', urlFotoProfissional('/static/img/ana.webp'));
        self::assertNull(urlFotoProfissional(null));
        self::assertNull(urlFotoProfissional(''));
        self::assertNull(urlFotoProfissional('//evil.example/ana.webp'));
        self::assertNull(urlFotoProfissional('https://www.genspark.ai/api/files/s/example'));
        self::assertNull(urlFotoProfissional('/static/../config.php'));
        self::assertNull(urlFotoProfissional('/static/img/ana.webp?x=1'));
    }

    public function test_aceita_imagem_de_servico_local_ou_do_host_atual(): void
    {
        self::assertSame('/static/img/servico.webp', urlImagemServico('/static/img/servico.webp'));
        self::assertSame(
            'https://www.genspark.ai/api/files/s/exemplo_123',
            urlImagemServico('https://www.genspark.ai/api/files/s/exemplo_123')
        );
        self::assertNull(urlImagemServico('javascript:alert(1)'));
        self::assertNull(urlImagemServico('//www.genspark.ai/api/files/s/exemplo'));
        self::assertNull(urlImagemServico('http://www.genspark.ai/api/files/s/exemplo'));
        self::assertNull(urlImagemServico('https://www.genspark.ai.evil.test/api/files/s/exemplo'));
        self::assertNull(urlImagemServico('https://www.genspark.ai@evil.test/api/files/s/exemplo'));
        self::assertNull(urlImagemServico('https://www.genspark.ai/outro/caminho'));
        self::assertNull(urlImagemServico('/static/../config.php'));
    }
}
