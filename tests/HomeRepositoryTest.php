<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

$repositoryFile = __DIR__ . '/../includes/repositories.php';
if (is_file($repositoryFile)) {
    require_once $repositoryFile;
}

final class HomeRepositoryTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec(
            'CREATE TABLE servicos (
                id TEXT PRIMARY KEY,
                nome TEXT NOT NULL,
                descricao TEXT NOT NULL,
                duracao_min INTEGER NOT NULL,
                preco NUMERIC NULL,
                categoria TEXT NOT NULL,
                imagem_url TEXT NULL,
                icone TEXT NULL,
                cor TEXT NULL,
                tag TEXT NULL,
                ativo INTEGER NOT NULL,
                ordem INTEGER NOT NULL,
                observacao_interna TEXT NULL
            )'
        );
        $this->pdo->exec(
            'CREATE TABLE profissionais (
                id TEXT PRIMARY KEY,
                nome TEXT NOT NULL,
                especialidade TEXT NULL,
                bio TEXT NULL,
                foto_url TEXT NULL,
                ativo INTEGER NOT NULL,
                email_interno TEXT NULL
            )'
        );
    }

    public function test_lista_apenas_servicos_ativos_em_ordem_estavel_e_com_campos_publicos(): void
    {
        $insert = $this->pdo->prepare(
            'INSERT INTO servicos
                (id, nome, descricao, duracao_min, preco, categoria, imagem_url, icone, cor, tag, ativo, ordem,
                 observacao_interna)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $insert->execute([
            'c', 'Zeta', 'Descrição Z', 60, 80, 'Terapias', null, 'fa-spa', 'rose', null, 1, 1, 'privado',
        ]);
        $insert->execute([
            'a', 'Alfa', 'Descrição A', 45, 75, 'Massagens', null, null, null, null, 1, 1, 'privado',
        ]);
        $insert->execute([
            'b', 'Zeta', 'Descrição B', 90, null, 'Terapias', null, null, null, null, 1, 1, 'privado',
        ]);
        $insert->execute([
            'd', 'Oculto', 'Inativo', 60, 10, 'Terapias', null, null, null, null, 0, 0, 'privado',
        ]);

        $servicos = listarServicosPublicos($this->pdo);

        self::assertSame(['Alfa', 'Zeta', 'Zeta'], array_column($servicos, 'nome'));
        self::assertSame(['a', 'b', 'c'], array_column($servicos, 'id'));
        self::assertSame(
            ['id', 'nome', 'descricao', 'duracao_min', 'preco', 'categoria', 'imagem_url', 'icone', 'cor', 'tag'],
            array_keys($servicos[0])
        );
        self::assertNull($servicos[1]['preco']);
    }

    public function test_lista_apenas_profissionais_ativos_sem_colunas_privadas(): void
    {
        $insert = $this->pdo->prepare(
            'INSERT INTO profissionais (id, nome, especialidade, bio, foto_url, ativo, email_interno)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $insert->execute(['p2', 'Bruna', null, null, null, 1, 'bruna@example.test']);
        $insert->execute([
            'p1', 'Ana', 'Massoterapeuta', 'Perfil ativo', '/static/img/ana.webp', 1, 'ana@example.test',
        ]);
        $insert->execute(['p3', 'Oculta', 'Inativa', 'Não mostrar', null, 0, 'oculta@example.test']);

        $profissionais = listarProfissionaisPublicos($this->pdo);

        self::assertSame(['Ana', 'Bruna'], array_column($profissionais, 'nome'));
        self::assertSame(
            ['id', 'nome', 'especialidade', 'bio', 'foto_url'],
            array_keys($profissionais[0])
        );
        self::assertNull($profissionais[1]['foto_url']);
    }
}
