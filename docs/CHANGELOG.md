# Histórico de alterações

## [2026-10-09]

### Adicionado

- Painel de agendamento integrado à página inicial, com seleção de vários serviços, profissional, calendário, horários por serviço, dados do cliente e confirmação pelo WhatsApp configurado.
- Migrações `005_agendamento_servicos.sql` e `006_horarios_por_servico.sql` para registrar os serviços e os horários escolhidos em um agendamento.
- Páginas humanizadas para os erros 403, 404, 500, 502, 503 e 504, incluindo respostas JSON consistentes para as APIs durante manutenção.
- Chave privada `maintenance_mode` em `config.php`, com `Retry-After` e cache desabilitado para informar manutenções planejadas sem bloquear o painel administrativo.

### Alterado

- A aplicação PHP passou a ser publicada diretamente pela raiz do repositório, que corresponde a `public_html` no deploy Git da Hostinger. A antiga pasta `public_html/` foi removida para evitar o aninhamento no servidor.
- O `.htaccess` protege arquivos internos e configura os documentos de erro da aplicação na nova estrutura de publicação.
- A página inicial foi ajustada para concentrar o agendamento no painel flutuante/responsivo; a navegação e o rodapé foram simplificados e as seções públicas foram redistribuídas para uma leitura mais fluida.

### Corrigido

- A disponibilidade agora avalia horários individualmente por serviço, mostrando os estados disponível, selecionado e indisponível/agendado e mantendo a mensagem de indisponibilidade coerente com a seleção.
- O botão de confirmação deixa de encaminhar à página legada e abre o WhatsApp com o resumo completo do agendamento.
- Cada página de erro exibe seu próprio código, contexto e orientação ao visitante, em vez de reutilizar uma mensagem genérica.

### Observação de implantação

- Antes de publicar, aplique no banco as migrations ainda ausentes em `sql/migrations/`, em ordem numérica. Preserve `config.php` no servidor: ele não é versionado e deve continuar com `maintenance_mode` em `false` fora de uma janela de manutenção.
