# Configuração do ambiente de desenvolvimento

Este guia configura o site localmente no Windows com XAMPP. O projeto está na Fase 2 da migração para PHP: o acesso administrativo e a fundação PHP estão em desenvolvimento; as páginas públicas ainda usam o protótipo Hono/Vite. Portanto, há **dois modos locais**: o servidor Vite para a interface atual e o Apache/MySQL do XAMPP para validar a fundação PHP.

## 1. Ferramentas necessárias

- Git, para obter e atualizar o repositório.
- Node.js LTS e npm, para o protótipo atual e ferramentas de qualidade.
- PHP 8.2 ou superior, Composer e as extensões `pdo_mysql` e `openssl`. O XAMPP inclui PHP, Apache e MariaDB; Composer deve ser instalado separadamente.
- XAMPP para Windows. Baixe o instalador no [site oficial Apache Friends](https://www.apachefriends.org/download.html). Selecione uma versão compatível com PHP 8.2+; confira a versão do PHP empacotada antes de instalar.
- Editor de código (por exemplo, VS Code).

> O XAMPP empacota MariaDB, que é compatível com as migrações do projeto (MariaDB 10.4+). No painel, o serviço aparece normalmente como **MySQL**.

## 2. Obter o projeto e instalar dependências

Abra o PowerShell na pasta em que deseja manter o repositório:

```powershell
git clone <URL-DO-REPOSITORIO> anareiki
cd anareiki
npm ci
composer install
```

Se já tem o repositório, entre na pasta dele, atualize sua branch conforme o fluxo da equipe e execute `npm ci` e `composer install`.

O Composer instala dependências PHP em `vendor/`. Não versione essa pasta. Para verificar as ferramentas instaladas:

```powershell
node --version
npm --version
php --version
composer --version
```

PHP no terminal deve ser 8.2 ou superior. Se o comando `php` não for encontrado, adicione a pasta `php` do XAMPP ao `PATH` do Windows ou use o executável diretamente, por exemplo `C:\xampp\php\php.exe`.

## 3. Instalar e iniciar o XAMPP

1. Instale o XAMPP em um caminho simples, como `C:\xampp`.
2. Abra o **XAMPP Control Panel** como administrador, se o Windows solicitar.
3. Inicie **Apache** e **MySQL**. Ambos devem indicar que estão em execução.
4. Abra `http://localhost/phpmyadmin/` para confirmar que o servidor de banco está acessível.

Se Apache não iniciar, verifique se outro serviço está usando a porta 80/443. O projeto usa a porta 8080 para o VirtualHost local, mas o Apache também precisa iniciar e pode estar configurado para a porta 80. A FAQ oficial do [XAMPP para Windows](https://www.apachefriends.org/faq_windows.html) contém orientações para conflitos e configuração.

## 4. Criar o banco local

No phpMyAdmin (`http://localhost/phpmyadmin/`):

1. Crie um banco chamado `anareiki` com collation `utf8mb4_unicode_ci`.
2. Selecione o banco `anareiki`.
3. Importe, em ordem, os arquivos `sql/migrations/001_schema_inicial.sql` e `sql/migrations/002_limites_taxa.sql` pela aba **Importar**.
4. Confira que as tabelas foram criadas e que `migracoes` contém `001_schema_inicial` e `002_limites_taxa`.

As migrações são destinadas a um banco vazio. Não reaplique uma migração já registrada; consulte a equipe antes de alterar um banco que contenha dados.

## 5. Configurar a aplicação PHP

A aplicação lê `config.php` na raiz do repositório. Esse arquivo não é versionado porque contém credenciais; o modelo com a estrutura esperada está em `config.example.php`.

1. Se `config.php` ainda não existir, copie `config.example.php` para `config.php` na raiz do repositório (ao lado de `composer.json`). Se ele já existir, preserve o arquivo e revise seus valores sem o substituir.
2. Preencha `db` para o banco local: host `127.0.0.1`, nome `anareiki`, usuário `root` e a senha configurada no seu MySQL local (em uma instalação XAMPP padrão, normalmente vazia).
3. Ajuste `debug` para `true` apenas no seu ambiente local.
4. Mantenha `maintenance_mode` como `false`. Use `true` somente durante uma manutenção planejada e retorne imediatamente a `false` quando ela terminar.
5. SMTP é opcional para abrir o site, mas é necessário para validar o fluxo de login/2FA por e-mail. Use credenciais de desenvolvimento autorizadas pela equipe. Nunca coloque segredos em commits, issues, documentação ou mensagens.

O projeto **não carrega variáveis de ambiente `.env`** atualmente. Os nomes usados na configuração são as chaves do array PHP `debug`, `maintenance_mode`, `db.host`, `db.nome`, `db.usuario`, `db.senha`, `smtp.host`, `smtp.porta`, `smtp.usuario`, `smtp.senha` e `smtp.remetente_nome`. O arquivo `config.php` é a fonte desses valores; não crie `.env` esperando que a aplicação o leia.

## 6. Configurar o Apache para servir a raiz do repositório

O deploy Git da Hostinger publica a raiz do repositório diretamente em `/public_html`. Portanto, o document root local também deve ser a raiz do repositório. O `.htaccess` bloqueia `config.php`, `includes/`, `vendor/`, `sql/`, `tests/` e `bin/`. Configure um VirtualHost local na porta 8080.

1. No painel do XAMPP, em **Apache → Config**, abra `httpd.conf`.
2. Garanta que estas diretivas estejam ativas (sem `#` no início):

```apache
Listen 8080
LoadModule rewrite_module modules/mod_rewrite.so
Include conf/extra/httpd-vhosts.conf
```

   Se a instalação já tiver outras portas ou módulos configurados, evite duplicar diretivas; ajuste as existentes.
3. Abra `C:\xampp\apache\conf\extra\httpd-vhosts.conf` e adicione um bloco como este, trocando o caminho para a localização real do clone:

```apache
<VirtualHost *:8080>
    ServerName localhost
    DocumentRoot "C:/Users/SEU-USUARIO/anareiki"
    <Directory "C:/Users/SEU-USUARIO/anareiki">
        AllowOverride All
        Require local
    </Directory>
</VirtualHost>
```

4. Salve e reinicie o Apache pelo painel do XAMPP.
5. Abra `http://localhost:8080/`.

`AllowOverride All` permite que as regras de `.htaccess` do projeto sejam aplicadas. O Apache precisa ter o módulo `rewrite` habilitado. A sintaxe de VirtualHost segue a [documentação oficial do Apache](https://httpd.apache.org/docs/current/vhosts/examples.html).

### Páginas de erro e manutenção

O `.htaccess` entrega páginas próprias para `403`, `404`, `500`, `502`, `503` e `504`. Verifique-as pelo Apache, e não por `php -S`, porque o servidor embutido do PHP não processa `ErrorDocument` nem as regras de acesso.

Para uma manutenção planejada:

1. Edite somente o arquivo local/privado `config.php` e altere `maintenance_mode` para `true`.
2. Confira que a home retorna `503` com a página de manutenção e que uma API, como `/api/profissionais.php`, retorna `503` em JSON com o cabeçalho `Retry-After`.
3. Mantenha `/admin/login.php` acessível para concluir a manutenção.
4. Retorne `maintenance_mode` a `false` e confirme que a home volta a `200` antes de encerrar a janela.

Erros `502`, `503` e `504` emitidos por proxy, CDN ou infraestrutura antes de a requisição alcançar o Apache/PHP podem ser substituídos pela própria hospedagem. Para esses casos, cadastre conteúdo equivalente em **hPanel → Error Pages**; a Hostinger documenta esse recurso em [Customize your website's error pages](https://support.hostinger.com/en/articles/1583295-how-to-customize-your-website-s-error-pages-in-hostinger). O conteúdo versionado continua sendo a referência para todos os erros que chegam à aplicação.

| Cenário de aceite | Resultado esperado |
| --- | --- |
| URL pública inexistente | `404`, página “Parece que este caminho se perdeu.”, sem detalhe técnico. |
| Arquivo ou diretório protegido | `403`, página “Este espaço é reservado.”, sem conteúdo interno. |
| Template interno `/errors/error-page.php` | `403`, sem executar o template diretamente. |
| Exceção não tratada do PHP | `500`, identificador de atendimento escapado e sem stack trace. |
| Entrada local `/errors/502.php`, `/errors/503.php` ou `/errors/504.php` | Respectivamente `502`, `503` ou `504`, com texto humanizado e ações navegáveis. |
| `maintenance_mode=true` na home | `503` em HTML, `Retry-After: 3600` e cache desabilitado. |
| `maintenance_mode=true` em API | `503` em JSON, `Retry-After: 3600`, sem HTML. |

## 7. Rodar o projeto

### Interface atual (protótipo Vite/Hono)

Na raiz do repositório:

```powershell
npm run dev
```

Abra o endereço local impresso pelo Vite (normalmente `http://localhost:5173`). Esse comando serve a interface atual e **não** inicia Apache, MySQL ou o backend PHP.

### Fundação PHP em Apache/XAMPP

Com Apache e MySQL em execução, abra `http://localhost:8080/`. Para acessar o painel implementado, use `http://localhost:8080/admin/login.php`. Recursos PHP ainda não implementados não estarão disponíveis; consulte `docs/PLANO_MESTRE_ANAREIKI.md` e `docs/HANDOFF.md` para o estado e a fase autorizada.

Não use `php -S` como substituto para validar a configuração Apache: esse servidor embutido não reproduz as regras de `.htaccess` e o VirtualHost local.

## 8. Comandos úteis

```powershell
# Interface e ferramentas de frontend
npm run dev
npm run build
npm run lint
npm run check:lines
npm run quality

# Qualidade e testes PHP
composer test
composer stan
composer cs
```

`npm run preview` serve a saída por Wrangler/Cloudflare Pages e é voltado ao protótipo legado. `npm run deploy` publica e não deve ser usado como parte do setup local.

## 9. Problemas comuns

- **Apache não inicia:** confira conflito de portas e os logs em `C:\xampp\apache\logs\error.log`.
- **Erro de conexão PDO:** confirme que MySQL iniciou, que o banco `anareiki` existe e que os valores `db` em `config.php` correspondem ao seu XAMPP.
- **PDO MySQL não encontrado:** confira `php.ini` da versão de PHP usada pelo Apache e pelo terminal; habilite `extension=pdo_mysql` e reinicie o Apache.
- **Erro 404/403 ou regras ignoradas:** valide o caminho do `DocumentRoot`, `AllowOverride All` e `mod_rewrite`; reinicie o Apache após mudanças.
- **`composer` ou `php` não reconhecido:** reinstale/configure o `PATH` ou abra um terminal novo após atualizar o `PATH`.
- **Porta Vite ocupada:** o Vite indicará outra porta disponível no terminal; use o endereço informado.

## Referências do projeto

- [Arquitetura](ARCHITECTURE.md)
- [Regras de desenvolvimento](RULES.md)
- [Plano Mestre e fase autorizada](PLANO_MESTRE_ANAREIKI.md)
- [Handoff de desenvolvimento](HANDOFF.md)
