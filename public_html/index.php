<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/repositories.php';
require_once __DIR__ . '/includes/public-view.php';

$profissionais = [];
$servicos = [];
$falhaBanco = false;

try {
    $pdo = db();
    $profissionais = listarProfissionaisPublicos($pdo);
    $servicos = listarServicosPublicos($pdo);
} catch (Throwable $e) {
    $falhaBanco = true;
    registrarErroAplicacao($e, gerarRequestId());
}

require __DIR__ . '/includes/layout/home/head.php';
require __DIR__ . '/includes/layout/home/top.php';
?>
<main id="home">
<?php
if ($falhaBanco) {
    echo '<p class="container" role="status">Não foi possível carregar algumas informações agora. '
        . 'Tente novamente mais tarde.</p>';
}
require __DIR__ . '/includes/layout/home/about.php';
require __DIR__ . '/includes/layout/home/services.php';
require __DIR__ . '/includes/layout/home/sessions-packages.php';
require __DIR__ . '/includes/layout/home/availability-gallery.php';
require __DIR__ . '/includes/layout/home/contact-footer.php';
?>
<a href="https://wa.me/5548996137757" target="_blank" rel="noopener"
   class="whatsapp-float" aria-label="WhatsApp">
  <i class="fab fa-whatsapp"></i>
  <span class="wpp-tooltip">Tire suas dúvidas!</span>
</a>
<script src="/static/app.js" defer></script>
</body>
</html>
