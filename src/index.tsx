import { Hono } from 'hono'
import { serveStatic } from 'hono/cloudflare-workers'

const app = new Hono()

// Serve static files
app.use('/static/*', serveStatic({ root: './' }))

app.get('/', (c) => {
  return c.html(`<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reiki Ana — Massoterapeuta</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Lato:wght@300;400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="/static/style.css" />
  <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
</head>
<body>

  <!-- NAVBAR -->
  <nav id="navbar">
    <div class="nav-container">
      <a href="#home" class="nav-logo">
        <span class="logo-script">Reiki Ana</span>
        <span class="logo-sub">Massoterapeuta</span>
      </a>
      <button class="nav-toggle" id="navToggle" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
      <ul class="nav-links" id="navLinks">
        <li><a href="#home" class="nav-link">Início</a></li>
        <li><a href="#sobre" class="nav-link">Sobre</a></li>
        <li><a href="#servicos" class="nav-link">Serviços</a></li>
        <li><a href="#sessoes" class="nav-link">Sessões</a></li>
        <li><a href="#pacotes" class="nav-link">Pacotes</a></li>
        <li><a href="#horarios" class="nav-link">Horários</a></li>
        <li><a href="#contato" class="nav-link btn-nav">Agendar</a></li>
      </ul>
    </div>
  </nav>

  <!-- HERO -->
  <section id="home" class="hero">
    <div class="hero-overlay"></div>
    <div class="hero-content">
      <p class="hero-eyebrow">Bem-vinda ao espaço de</p>
      <h1 class="hero-title">
        <span class="script">Reiki Ana</span>
      </h1>
      <p class="hero-subtitle">Massoterapeuta</p>
      <p class="hero-tagline">Preparada para ter o <strong>relaxamento</strong> que você merece?</p>
      <div class="hero-actions">
        <a href="https://wa.me/5548996137757" target="_blank" class="btn-primary">
          <i class="fab fa-whatsapp"></i> Agendar pelo WhatsApp
        </a>
        <a href="#servicos" class="btn-ghost">Ver Serviços</a>
      </div>
      <div class="hero-badges">
        <div class="badge"><i class="fas fa-star"></i> Sessões de 1h</div>
        <div class="badge"><i class="fas fa-leaf"></i> Técnicas Naturais</div>
        <div class="badge"><i class="fas fa-heart"></i> Bem-estar Garantido</div>
      </div>
    </div>
    <div class="hero-scroll">
      <span>Role para baixo</span>
      <i class="fas fa-chevron-down"></i>
    </div>
  </section>

  <!-- SINAIS -->
  <section class="sinais-section">
    <div class="container">
      <div class="sinais-wrapper">
        <div class="sinais-text">
          <p class="section-eyebrow">Você precisa de uma massagem?</p>
          <h2 class="sinais-title script-heading">Sinais de que você precisa<br/><em>de uma massagem</em></h2>
        </div>
        <div class="sinais-cards">
          <div class="sinal-item"><i class="fas fa-face-frown"></i> Mal humor</div>
          <div class="sinal-item"><i class="fas fa-person-dots-from-line"></i> Dores musculares</div>
          <div class="sinal-item"><i class="fas fa-moon"></i> Insônia e baixa produtividade</div>
          <div class="sinal-item"><i class="fas fa-battery-quarter"></i> Cansaço físico</div>
        </div>
      </div>
    </div>
  </section>

  <!-- SOBRE -->
  <section id="sobre" class="sobre-section">
    <div class="container sobre-grid">
      <div class="sobre-image-wrap">
        <div class="sobre-img-placeholder">
          <img src="https://www.genspark.ai/api/files/s/PHPB75Pg" alt="Espaço de atendimento Reiki Ana" />
        </div>
        <div class="sobre-badge-float">
          <i class="fas fa-spa"></i>
          <span>Ambiente acolhedor</span>
        </div>
      </div>
      <div class="sobre-content">
        <p class="section-eyebrow">Quem sou eu</p>
        <h2 class="section-title">Olá, sou a <span class="script">Ana</span></h2>
        <p class="sobre-text">
          Sou massoterapeuta apaixonada pelo bem-estar e pela cura natural. 
          Com técnicas cuidadosamente aplicadas, ofereço um espaço seguro e acolhedor 
          para que você encontre o equilíbrio entre corpo e mente.
        </p>
        <p class="sobre-text">
          Atuo com massagens relaxantes, terapêuticas, drenagem linfática, Reiki e 
          diversas outras terapias que promovem saúde integral e qualidade de vida.
        </p>
        <div class="sobre-dicas">
          <h4><i class="fas fa-lightbulb"></i> Dicas para antes da sua massagem</h4>
          <ul>
            <li><i class="fas fa-check"></i> Faça uma refeição leve</li>
            <li><i class="fas fa-check"></i> Vista-se com roupa fácil de tirar e vestir</li>
            <li><i class="fas fa-check"></i> Vá ao banheiro antes da sessão</li>
            <li><i class="fas fa-check"></i> Desligue o celular</li>
            <li><i class="fas fa-check"></i> Esteja pronto para relaxar!</li>
          </ul>
        </div>
        <a href="https://wa.me/5548996137757" target="_blank" class="btn-primary">
          <i class="fab fa-whatsapp"></i> Me chama para agendar!
        </a>
      </div>
    </div>
  </section>

  <!-- SERVIÇOS -->
  <section id="servicos" class="servicos-section">
    <div class="container">
      <div class="section-header">
        <p class="section-eyebrow">O que ofereço</p>
        <h2 class="section-title">Meus <span class="script">Serviços</span></h2>
        <p class="section-desc">Terapias e massagens pensadas para o seu bem-estar completo</p>
      </div>
      <div class="servicos-grid">

        <div class="servico-card" id="massagem-relaxante">
          <div class="servico-img">
            <img src="https://www.genspark.ai/api/files/s/JxWMZCMW" alt="Massagem Relaxante" />
            <div class="servico-tag">Mais Pedida</div>
          </div>
          <div class="servico-body">
            <h3>Massagem Relaxante</h3>
            <p>Alivia tensões e proporciona profundo relaxamento muscular e mental. Ideal para quem precisa descansar e renovar as energias.</p>
            <div class="servico-footer">
              <span class="servico-preco">R$ 75,00</span>
              <span class="servico-duracao"><i class="fas fa-clock"></i> 1h</span>
            </div>
          </div>
        </div>

        <div class="servico-card" id="massagem-terapeutica">
          <div class="servico-img servico-img-placeholder">
            <div class="placeholder-inner">
              <i class="fas fa-hands"></i>
            </div>
          </div>
          <div class="servico-body">
            <h3>Massagem Terapêutica</h3>
            <p>Focada em tratar dores musculares específicas, tensões e desconfortos. Trabalha pontos de tensão com técnicas profissionais.</p>
            <div class="servico-footer">
              <span class="servico-preco">R$ 80,00</span>
              <span class="servico-duracao"><i class="fas fa-clock"></i> 1h</span>
            </div>
          </div>
        </div>

        <div class="servico-card" id="drenagem">
          <div class="servico-img servico-img-placeholder purple">
            <div class="placeholder-inner">
              <i class="fas fa-droplet"></i>
            </div>
          </div>
          <div class="servico-body">
            <h3>Drenagem Linfática</h3>
            <p>Ativa o sistema linfático, reduzindo inchaços e retenção de líquidos. Proporciona leveza e bem-estar natural.</p>
            <div class="servico-footer">
              <span class="servico-preco">R$ 80,00</span>
              <span class="servico-duracao"><i class="fas fa-clock"></i> 1h</span>
            </div>
          </div>
        </div>

        <div class="servico-card" id="modeladora">
          <div class="servico-img servico-img-placeholder green">
            <div class="placeholder-inner">
              <i class="fas fa-person"></i>
            </div>
          </div>
          <div class="servico-body">
            <h3>Massagem Modeladora</h3>
            <p>Modela e define o corpo, melhora a circulação e combate a celulite. Para uma silhueta mais harmoniosa.</p>
            <div class="servico-footer">
              <span class="servico-preco">R$ 100,00</span>
              <span class="servico-duracao"><i class="fas fa-clock"></i> 1h</span>
            </div>
          </div>
        </div>

        <div class="servico-card" id="pedras">
          <div class="servico-img">
            <img src="https://www.genspark.ai/api/files/s/B5ujCiDj" alt="Pedras Quentes" />
          </div>
          <div class="servico-body">
            <h3>Pedras Quentes</h3>
            <p>Pedras vulcânicas aquecidas que promovem relaxamento muscular profundo, ação anti-inflamatória e liberam serotonina.</p>
            <div class="servico-beneficios">
              <span><i class="fas fa-circle-check"></i> Relaxamento muscular</span>
              <span><i class="fas fa-circle-check"></i> Anti-inflamatório</span>
              <span><i class="fas fa-circle-check"></i> Libera serotonina</span>
              <span><i class="fas fa-circle-check"></i> Alivia dores menstruais</span>
            </div>
            <div class="servico-footer">
              <a href="https://wa.me/5548996137757" class="btn-small">Consultar valor</a>
            </div>
          </div>
        </div>

        <div class="servico-card" id="ventosaterapia">
          <div class="servico-img">
            <img src="https://www.genspark.ai/api/files/s/sM02mKkO" alt="Ventosaterapia" />
          </div>
          <div class="servico-body">
            <h3>Ventosaterapia</h3>
            <p>Técnica da medicina chinesa com copos de sucção. Estimula circulação sanguínea, alivia dores musculares e reduz tensões.</p>
            <div class="servico-footer">
              <span class="servico-preco">R$ 80,00</span>
              <span class="servico-duracao"><i class="fas fa-clock"></i> 1h</span>
            </div>
          </div>
        </div>

        <div class="servico-card" id="reiki">
          <div class="servico-img servico-img-placeholder gold">
            <div class="placeholder-inner">
              <i class="fas fa-hands-praying"></i>
            </div>
          </div>
          <div class="servico-body">
            <h3>Reiki</h3>
            <p>Terapia energética que equilibra os chakras, reduz o estresse e promove cura holística do corpo e da mente.</p>
            <div class="servico-footer">
              <span class="servico-preco">R$ 80,00</span>
              <span class="servico-duracao"><i class="fas fa-clock"></i> 1h</span>
            </div>
          </div>
        </div>

        <div class="servico-card" id="cone-hindu">
          <div class="servico-img servico-img-placeholder rose">
            <div class="placeholder-inner">
              <i class="fas fa-fire"></i>
            </div>
          </div>
          <div class="servico-body">
            <h3>Cone Hindú</h3>
            <p>Terapia auricular com cones de ervas que promove limpeza do canal auditivo e equilíbrio energético.</p>
            <div class="servico-footer">
              <span class="servico-preco">R$ 60,00</span>
              <span class="servico-duracao"><i class="fas fa-clock"></i> 1h</span>
            </div>
          </div>
        </div>

        <div class="servico-card" id="auriculoterapia">
          <div class="servico-img servico-img-placeholder teal">
            <div class="placeholder-inner">
              <i class="fas fa-brain"></i>
            </div>
          </div>
          <div class="servico-body">
            <h3>Auriculoterapia</h3>
            <p>Estimulação de pontos no pavilhão auricular que correspondem a órgãos e sistemas do corpo. Equilibra e trata diversas condições.</p>
            <div class="servico-footer">
              <span class="servico-preco">R$ 75,00</span>
              <span class="servico-duracao"><i class="fas fa-clock"></i> 1h</span>
            </div>
          </div>
        </div>

        <div class="servico-card" id="reflexologia">
          <div class="servico-img">
            <img src="https://www.genspark.ai/api/files/s/ZfRuIuK5" alt="Reflexologia Podal" />
          </div>
          <div class="servico-body">
            <h3>Reflexologia Podal</h3>
            <p>Massagem nos pés que estimula pontos reflexos conectados a órgãos e sistemas do corpo, promovendo equilíbrio global.</p>
            <div class="servico-footer">
              <span class="servico-preco">R$ 75,00</span>
              <span class="servico-duracao"><i class="fas fa-clock"></i> 1h</span>
            </div>
          </div>
        </div>

        <div class="servico-card" id="escalda-pes">
          <div class="servico-img">
            <img src="https://www.genspark.ai/api/files/s/n8UWPNbC" alt="Escalda Pés" />
          </div>
          <div class="servico-body">
            <h3>Escalda Pés</h3>
            <p>Reduz dores musculares, ativa a circulação e diminui inchaço. Perfeito para descansar após dias agitados.</p>
            <div class="servico-footer">
              <a href="https://wa.me/5548996137757" class="btn-small">Consultar valor</a>
            </div>
          </div>
        </div>

        <div class="servico-card" id="limpeza">
          <div class="servico-img servico-img-placeholder pink">
            <div class="placeholder-inner">
              <i class="fas fa-spa"></i>
            </div>
          </div>
          <div class="servico-body">
            <h3>Limpeza de Pele</h3>
            <p>Tratamento facial completo para remover impurezas, hidratar e renovar a pele, deixando-a mais saudável e luminosa.</p>
            <div class="servico-footer">
              <span class="servico-preco">R$ 100,00</span>
              <span class="servico-duracao"><i class="fas fa-clock"></i> 1h</span>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- TABELA DE SESSÕES -->
  <section id="sessoes" class="sessoes-section">
    <div class="container">
      <div class="section-header">
        <p class="section-eyebrow">Valores</p>
        <h2 class="section-title">Tabela de <span class="script">Sessões</span></h2>
        <p class="section-desc">Todas as sessões têm duração de 1 hora</p>
      </div>
      <div class="sessoes-wrapper">
        <div class="sessoes-group">
          <h3 class="sessoes-group-title"><i class="fas fa-hand-holding-heart"></i> Massagens</h3>
          <div class="sessao-row">
            <span class="sessao-nome">Massagem Relaxante</span>
            <span class="sessao-preco">R$ 75,00</span>
          </div>
          <div class="sessao-row">
            <span class="sessao-nome">Massagem Terapêutica</span>
            <span class="sessao-preco">R$ 80,00</span>
          </div>
          <div class="sessao-row">
            <span class="sessao-nome">Drenagem Linfática</span>
            <span class="sessao-preco">R$ 80,00</span>
          </div>
          <div class="sessao-row destaque">
            <span class="sessao-nome">Massagem Modeladora</span>
            <span class="sessao-preco">R$ 100,00</span>
          </div>
        </div>
        <div class="sessoes-group">
          <h3 class="sessoes-group-title"><i class="fas fa-spa"></i> Estética</h3>
          <div class="sessao-row destaque">
            <span class="sessao-nome">Limpeza de Pele</span>
            <span class="sessao-preco">R$ 100,00</span>
          </div>
        </div>
        <div class="sessoes-group">
          <h3 class="sessoes-group-title"><i class="fas fa-yin-yang"></i> Terapias</h3>
          <div class="sessao-row">
            <span class="sessao-nome">Reiki</span>
            <span class="sessao-preco">R$ 80,00</span>
          </div>
          <div class="sessao-row">
            <span class="sessao-nome">Cone Hindú</span>
            <span class="sessao-preco">R$ 60,00</span>
          </div>
          <div class="sessao-row">
            <span class="sessao-nome">Auriculoterapia</span>
            <span class="sessao-preco">R$ 75,00</span>
          </div>
          <div class="sessao-row">
            <span class="sessao-nome">Reflexologia Podal</span>
            <span class="sessao-preco">R$ 75,00</span>
          </div>
        </div>
      </div>
      <div class="sessoes-cta">
        <p>Dúvidas? Me chame no WhatsApp!</p>
        <a href="https://wa.me/5548996137757" target="_blank" class="btn-primary">
          <i class="fab fa-whatsapp"></i> 48 99613-7757
        </a>
      </div>
    </div>
  </section>

  <!-- PACOTES -->
  <section id="pacotes" class="pacotes-section">
    <div class="container">
      <div class="section-header light">
        <p class="section-eyebrow">Economize mais</p>
        <h2 class="section-title white">Nossos <span class="script">Pacotes</span></h2>
        <p class="section-desc white">Pacotes especiais para você cuidar de si com mais frequência</p>
      </div>

      <div class="pacotes-grid">

        <div class="pacote-card">
          <div class="pacote-header">
            <h3>Pacote Relax Total</h3>
            <div class="pacote-tag">Para estresse e ansiedade</div>
          </div>
          <div class="pacote-body">
            <p>Para aliviar estresse e ansiedade.</p>
            <div class="pacote-detail"><i class="fas fa-circle-check"></i> 4 sessões de 1h</div>
            <div class="pacote-preco">
              <span class="de">De R$ 280,00</span>
              <span class="por">Por R$ <strong>230,00</strong></span>
            </div>
          </div>
          <a href="https://wa.me/5548996137757?text=Olá! Tenho interesse no Pacote Relax Total." target="_blank" class="btn-pacote">
            <i class="fab fa-whatsapp"></i> Quero esse pacote
          </a>
        </div>

        <div class="pacote-card destaque-card">
          <div class="pacote-badge-top">🌟 Mais Popular</div>
          <div class="pacote-header">
            <h3>Pacote Drenagem</h3>
            <div class="pacote-tag">Leveza e bem-estar</div>
          </div>
          <div class="pacote-body">
            <p>Relaxamento, leveza e bem-estar natural.</p>
            <div class="pacote-detail"><i class="fas fa-circle-check"></i> 4 sessões</div>
            <div class="pacote-preco">
              <span class="de">De R$ 320,00</span>
              <span class="por">Por R$ <strong>270,00</strong></span>
            </div>
          </div>
          <a href="https://wa.me/5548996137757?text=Olá! Tenho interesse no Pacote Drenagem." target="_blank" class="btn-pacote">
            <i class="fab fa-whatsapp"></i> Quero esse pacote
          </a>
        </div>

        <div class="pacote-card">
          <div class="pacote-header">
            <h3>Pacote Terapêutico</h3>
            <div class="pacote-tag">Dores e tensões</div>
          </div>
          <div class="pacote-body">
            <p>Foco em dores musculares e tensões.</p>
            <div class="pacote-detail"><i class="fas fa-circle-check"></i> 4 sessões + ventosaterapia</div>
            <div class="pacote-preco">
              <span class="de">De R$ 320,00</span>
              <span class="por">Por R$ <strong>270,00</strong></span>
            </div>
          </div>
          <a href="https://wa.me/5548996137757?text=Olá! Tenho interesse no Pacote Terapêutico." target="_blank" class="btn-pacote">
            <i class="fab fa-whatsapp"></i> Quero esse pacote
          </a>
        </div>

        <div class="pacote-card">
          <div class="pacote-header">
            <h3>Pacote Detox Energético</h3>
            <div class="pacote-tag">Renovar corpo e mente</div>
          </div>
          <div class="pacote-body">
            <p>Para renovar corpo e mente.</p>
            <div class="pacote-detail"><i class="fas fa-circle-check"></i> 3 sessões de drenagem</div>
            <div class="pacote-detail"><i class="fas fa-circle-check"></i> + 1 sessão de massagem relaxante</div>
            <div class="pacote-preco">
              <span class="de">De R$ 320,00</span>
              <span class="por">Por R$ <strong>250,00</strong></span>
            </div>
          </div>
          <a href="https://wa.me/5548996137757?text=Olá! Tenho interesse no Pacote Detox Energético." target="_blank" class="btn-pacote">
            <i class="fab fa-whatsapp"></i> Quero esse pacote
          </a>
        </div>

        <div class="pacote-card">
          <div class="pacote-header">
            <h3>Pacote Relax Plus</h3>
            <div class="pacote-tag">Relaxamento completo</div>
          </div>
          <div class="pacote-body">
            <p>Sessão de massagem relaxante 1h + Reiki 30min.</p>
            <div class="pacote-detail"><i class="fas fa-circle-check"></i> 4 sessões</div>
            <div class="pacote-preco">
              <span class="de">De R$ 520,00</span>
              <span class="por">Por R$ <strong>380,00</strong></span>
            </div>
          </div>
          <a href="https://wa.me/5548996137757?text=Olá! Tenho interesse no Pacote Relax Plus." target="_blank" class="btn-pacote">
            <i class="fab fa-whatsapp"></i> Quero esse pacote
          </a>
        </div>

        <div class="pacote-card">
          <div class="pacote-header">
            <h3>Pacote Modeladora</h3>
            <div class="pacote-tag">Modelagem e leveza</div>
          </div>
          <div class="pacote-body">
            <p>Modelagem, leveza e bem-estar.</p>
            <div class="pacote-detail"><i class="fas fa-circle-check"></i> 4 sessões de 1h</div>
            <div class="pacote-preco">
              <span class="de">De R$ 400,00</span>
              <span class="por">Por R$ <strong>320,00</strong></span>
            </div>
          </div>
          <a href="https://wa.me/5548996137757?text=Olá! Tenho interesse no Pacote Modeladora." target="_blank" class="btn-pacote">
            <i class="fab fa-whatsapp"></i> Quero esse pacote
          </a>
        </div>

        <div class="pacote-card pacote-flex">
          <div class="pacote-header">
            <h3>Pacote Total Flex</h3>
            <div class="pacote-tag">Monte seu pacote</div>
          </div>
          <div class="pacote-body">
            <p>Feito para você que não gosta de cair na rotina. Monte o seu pacote personalizado!</p>
            <div class="pacote-detail"><i class="fas fa-circle-check"></i> Totalmente personalizado</div>
          </div>
          <a href="https://wa.me/5548996137757?text=Olá! Quero montar meu Pacote Total Flex personalizado!" target="_blank" class="btn-pacote btn-orcamento">
            <i class="fab fa-whatsapp"></i> Faça seu orçamento
          </a>
        </div>

      </div>
    </div>
  </section>

  <!-- HORÁRIOS -->
  <section id="horarios" class="horarios-section">
    <div class="container">
      <div class="horarios-grid">
        <div class="horarios-content">
          <p class="section-eyebrow">Disponibilidade</p>
          <h2 class="section-title">Horários <span class="script">Disponíveis</span></h2>
          <div class="horario-item">
            <div class="horario-icon"><i class="fas fa-calendar-day"></i></div>
            <div class="horario-info">
              <strong>Segunda a Quarta</strong>
              <span>18:00 às 22:00 hs</span>
            </div>
          </div>
          <div class="horario-item">
            <div class="horario-icon"><i class="fas fa-calendar-day"></i></div>
            <div class="horario-info">
              <strong>Sexta-feira</strong>
              <span>18:00 às 22:00 hs</span>
            </div>
          </div>
          <div class="horario-item destaque-horario">
            <div class="horario-icon"><i class="fas fa-sun"></i></div>
            <div class="horario-info">
              <strong>Sábado</strong>
              <span>09:00 às 18:00 hs</span>
            </div>
          </div>
          <div class="horarios-obs">
            <i class="fas fa-info-circle"></i>
            <span>Agendamento exclusivo pelo WhatsApp</span>
          </div>
        </div>
        <div class="horarios-cta-box">
          <div class="lembrete-card">
            <div class="lembrete-icon">🔔</div>
            <h3>Lembrete</h3>
            <p>Não esqueça de agendar sua massagem com a Reiki Ana Massoterapeuta.</p>
            <a href="https://wa.me/5548996137757" target="_blank" class="btn-primary full-width">
              <i class="fab fa-whatsapp"></i> Agendar Agora
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- GALERIA -->
  <section class="galeria-section">
    <div class="container">
      <div class="section-header">
        <p class="section-eyebrow">Nosso espaço</p>
        <h2 class="section-title">Galeria de <span class="script">Tratamentos</span></h2>
      </div>
      <div class="galeria-grid">
        <div class="galeria-item tall">
          <img src="https://www.genspark.ai/api/files/s/B5ujCiDj" alt="Pedras Quentes" />
          <div class="galeria-overlay"><span>Pedras Quentes</span></div>
        </div>
        <div class="galeria-item">
          <img src="https://www.genspark.ai/api/files/s/cyrhQdF6" alt="Pedras Quentes" />
          <div class="galeria-overlay"><span>Pedras Quentes</span></div>
        </div>
        <div class="galeria-item">
          <img src="https://www.genspark.ai/api/files/s/ZfRuIuK5" alt="Reflexologia" />
          <div class="galeria-overlay"><span>Reflexologia Podal</span></div>
        </div>
        <div class="galeria-item wide">
          <img src="https://www.genspark.ai/api/files/s/JxWMZCMW" alt="Massagem Relaxante" />
          <div class="galeria-overlay"><span>Massagem Relaxante</span></div>
        </div>
        <div class="galeria-item">
          <img src="https://www.genspark.ai/api/files/s/pk9do6Gi" alt="Produtos Naturais" />
          <div class="galeria-overlay"><span>Produtos Naturais</span></div>
        </div>
        <div class="galeria-item">
          <img src="https://www.genspark.ai/api/files/s/iDGNm6ua" alt="Aromaterapia" />
          <div class="galeria-overlay"><span>Aromaterapia</span></div>
        </div>
      </div>
    </div>
  </section>

  <!-- CONTATO -->
  <section id="contato" class="contato-section">
    <div class="container">
      <div class="section-header">
        <p class="section-eyebrow">Fale comigo</p>
        <h2 class="section-title">Entre em <span class="script">Contato</span></h2>
        <p class="section-desc">Agende sua sessão agora mesmo!</p>
      </div>
      <div class="contato-grid">
        <div class="contato-info">
          <div class="contato-card">
            <div class="contato-icon wpp">
              <i class="fab fa-whatsapp"></i>
            </div>
            <div class="contato-detail">
              <strong>WhatsApp</strong>
              <a href="https://wa.me/5548996137757" target="_blank">48 99613-7757</a>
            </div>
          </div>
          <div class="contato-card">
            <div class="contato-icon insta">
              <i class="fab fa-instagram"></i>
            </div>
            <div class="contato-detail">
              <strong>Instagram</strong>
              <a href="https://instagram.com/reiki_anaterapeuta" target="_blank">@reiki_anaterapeuta</a>
            </div>
          </div>
          <div class="contato-msgs">
            <h4>Mensagens rápidas</h4>
            <a href="https://wa.me/5548996137757?text=Olá! Gostaria de agendar uma sessão de Massagem Relaxante." target="_blank" class="msg-chip">
              <i class="fab fa-whatsapp"></i> Agendar Massagem Relaxante
            </a>
            <a href="https://wa.me/5548996137757?text=Olá! Gostaria de agendar uma Drenagem Linfática." target="_blank" class="msg-chip">
              <i class="fab fa-whatsapp"></i> Agendar Drenagem
            </a>
            <a href="https://wa.me/5548996137757?text=Olá! Gostaria de saber mais sobre os pacotes disponíveis." target="_blank" class="msg-chip">
              <i class="fab fa-whatsapp"></i> Conhecer os Pacotes
            </a>
            <a href="https://wa.me/5548996137757?text=Olá! Gostaria de agendar uma sessão de Reiki." target="_blank" class="msg-chip">
              <i class="fab fa-whatsapp"></i> Agendar Reiki
            </a>
          </div>
        </div>
        <div class="contato-destaque">
          <div class="contato-hero-card">
            <img src="https://www.genspark.ai/api/files/s/j3nIKbtP" alt="Reiki Ana" />
            <div class="contato-hero-overlay">
              <p class="script-light">Me chama para agendar!</p>
              <a href="https://wa.me/5548996137757" target="_blank" class="btn-wpp-big">
                <i class="fab fa-whatsapp"></i> 48 99613-7757
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <span class="logo-script footer-logo">Reiki Ana</span>
          <span class="logo-sub">Massoterapeuta</span>
          <p>Cuidando do seu bem-estar com técnicas naturais e muito amor.</p>
          <div class="footer-social">
            <a href="https://wa.me/5548996137757" target="_blank" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
            <a href="https://instagram.com/reiki_anaterapeuta" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
          </div>
        </div>
        <div class="footer-links">
          <h4>Serviços</h4>
          <ul>
            <li><a href="#massagem-relaxante">Massagem Relaxante</a></li>
            <li><a href="#drenagem">Drenagem Linfática</a></li>
            <li><a href="#pedras">Pedras Quentes</a></li>
            <li><a href="#ventosaterapia">Ventosaterapia</a></li>
            <li><a href="#reiki">Reiki</a></li>
          </ul>
        </div>
        <div class="footer-horarios">
          <h4>Horários</h4>
          <p>Seg – Qua: 18h às 22h</p>
          <p>Sex: 18h às 22h</p>
          <p>Sáb: 9h às 18h</p>
          <a href="https://wa.me/5548996137757" target="_blank" class="btn-primary mt-2">
            <i class="fab fa-whatsapp"></i> Agendar
          </a>
        </div>
      </div>
      <div class="footer-bottom">
        <p>© 2025 Reiki Ana Massoterapeuta · Todos os direitos reservados</p>
      </div>
    </div>
  </footer>

  <!-- WHATSAPP FLOAT -->
  <a href="https://wa.me/5548996137757" target="_blank" class="whatsapp-float" aria-label="WhatsApp">
    <i class="fab fa-whatsapp"></i>
    <span class="wpp-tooltip">Agendar agora!</span>
  </a>

  <script src="/static/app.js"></script>
</body>
</html>`)
})

export default app
