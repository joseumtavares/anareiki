/* ===================================
   REIKI ANA — app.js
   Interações e animações
   =================================== */

document.addEventListener('DOMContentLoaded', () => {

  // ---------- NAVBAR SCROLL ----------
  const navbar = document.getElementById('navbar');
  window.addEventListener('scroll', () => {
    if (window.scrollY > 50) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  });

  // ---------- MOBILE MENU ----------
  const navToggle = document.getElementById('navToggle');
  const navLinks  = document.getElementById('navLinks');

  navToggle.addEventListener('click', () => {
    navLinks.classList.toggle('open');
    const spans = navToggle.querySelectorAll('span');
    navLinks.classList.contains('open')
      ? (spans[0].style.transform = 'rotate(45deg) translate(5px,5px)',
         spans[1].style.opacity   = '0',
         spans[2].style.transform = 'rotate(-45deg) translate(5px,-5px)')
      : (spans[0].style.transform = '',
         spans[1].style.opacity   = '',
         spans[2].style.transform = '');
  });

  // Fechar menu ao clicar em link
  document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => {
      navLinks.classList.remove('open');
      const spans = navToggle.querySelectorAll('span');
      spans[0].style.transform = '';
      spans[1].style.opacity   = '';
      spans[2].style.transform = '';
    });
  });

  // ---------- ACTIVE NAV LINK ----------
  const sections = document.querySelectorAll('section[id]');
  const navItems = document.querySelectorAll('.nav-link');

  const observerNav = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        navItems.forEach(n => n.classList.remove('active'));
        const active = document.querySelector(`.nav-link[href="#${entry.target.id}"]`);
        if (active) active.classList.add('active');
      }
    });
  }, { rootMargin: '-40% 0px -55% 0px' });

  sections.forEach(s => observerNav.observe(s));

  // ---------- REVEAL ON SCROLL ----------
  const revealEls = document.querySelectorAll(
    '.servico-card, .pacote-card, .sessoes-group, .sinal-item, .horario-item, .contato-card, .galeria-item, .sessoes-cta'
  );

  revealEls.forEach(el => el.classList.add('reveal'));

  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.classList.add('visible');
        }, 60 * (Array.from(revealEls).indexOf(entry.target) % 4));
        revealObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });

  revealEls.forEach(el => revealObserver.observe(el));

  // ---------- SMOOTH SCROLL ----------
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      const target = document.querySelector(this.getAttribute('href'));
      if (!target) return;
      e.preventDefault();
      const offset = 80;
      const top = target.getBoundingClientRect().top + window.pageYOffset - offset;
      window.scrollTo({ top, behavior: 'smooth' });
    });
  });

  // ---------- NAV LINK ACTIVE STYLE ----------
  const style = document.createElement('style');
  style.textContent = `.nav-link.active { color: var(--purple) !important; font-weight: 700; }`;
  document.head.appendChild(style);

  // ---------- GALERIA LIGHTBOX SIMPLES ----------
  const galeriaItems = document.querySelectorAll('.galeria-item');

  galeriaItems.forEach(item => {
    item.addEventListener('click', () => {
      const img = item.querySelector('img');
      const label = item.querySelector('.galeria-overlay span');
      if (!img) return;

      const overlay = document.createElement('div');
      overlay.className = 'lightbox-overlay';
      const inner = document.createElement('div');
      inner.className = 'lightbox-inner';
      const closeButton = document.createElement('button');
      closeButton.className = 'lightbox-close';
      closeButton.type = 'button';
      closeButton.setAttribute('aria-label', 'Fechar');
      closeButton.textContent = '×';
      const image = document.createElement('img');
      image.src = img.src;
      image.alt = img.alt;
      const caption = document.createElement('p');
      caption.className = 'lightbox-caption';
      caption.textContent = label ? label.textContent : '';
      inner.append(closeButton, image, caption);
      overlay.append(inner);
      document.body.appendChild(overlay);
      document.body.style.overflow = 'hidden';

      requestAnimationFrame(() => overlay.classList.add('show'));

      overlay.addEventListener('click', (e) => {
        if (e.target === overlay || e.target === closeButton) {
          overlay.classList.remove('show');
          setTimeout(() => {
            overlay.remove();
            document.body.style.overflow = '';
          }, 300);
        }
      });
    });
  });

  // Estilos do lightbox
  const lbStyle = document.createElement('style');
  lbStyle.textContent = `
    .lightbox-overlay {
      position: fixed; inset: 0; z-index: 9999;
      background: rgba(20,10,30,0.92);
      display: flex; align-items: center; justify-content: center;
      opacity: 0; transition: opacity 0.3s ease;
      cursor: zoom-out;
    }
    .lightbox-overlay.show { opacity: 1; }
    .lightbox-inner {
      position: relative; max-width: 90vw; max-height: 90vh;
      display: flex; flex-direction: column; align-items: center; gap: 14px;
      cursor: default;
    }
    .lightbox-inner img {
      max-width: 90vw; max-height: 80vh;
      border-radius: 12px; object-fit: contain;
      box-shadow: 0 20px 60px rgba(0,0,0,0.5);
    }
    .lightbox-caption {
      color: rgba(255,255,255,0.7); font-size: 0.9rem;
      font-family: 'Cormorant Garamond', serif; font-style: italic;
      letter-spacing: 0.05em;
    }
    .lightbox-close {
      position: absolute; top: -44px; right: 0;
      background: rgba(255,255,255,0.12); border: none; cursor: pointer;
      color: #fff; font-size: 1.6rem; line-height: 1;
      width: 40px; height: 40px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      transition: background 0.2s;
    }
    .lightbox-close:hover { background: rgba(255,255,255,0.25); }
  `;
  document.head.appendChild(lbStyle);

  // ---------- TOOLTIP NOS PREÇOS DA TABELA ----------
  document.querySelectorAll('.sessao-row').forEach(row => {
    row.addEventListener('mouseenter', () => {
      row.style.background = 'rgba(123,79,158,0.05)';
      row.style.borderRadius = '8px';
    });
    row.addEventListener('mouseleave', () => {
      if (!row.classList.contains('destaque')) {
        row.style.background = '';
        row.style.borderRadius = '';
      }
    });
  });

  // ---------- BOTÃO VOLTAR AO TOPO ----------
  const backTop = document.createElement('button');
  backTop.className = 'back-to-top';
  const backTopIcon = document.createElement('i');
  backTopIcon.className = 'fas fa-chevron-up';
  backTop.append(backTopIcon);
  backTop.setAttribute('aria-label', 'Voltar ao topo');
  document.body.appendChild(backTop);

  const btStyle = document.createElement('style');
  btStyle.textContent = `
    .back-to-top {
      position: fixed; bottom: 100px; right: 28px; z-index: 998;
      width: 44px; height: 44px; border-radius: 50%;
      background: var(--white); border: 2px solid var(--purple-light);
      color: var(--purple); font-size: 0.9rem; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 4px 16px rgba(123,79,158,0.2);
      opacity: 0; transform: translateY(10px);
      transition: all 0.3s ease; pointer-events: none;
    }
    .back-to-top.show {
      opacity: 1; transform: translateY(0); pointer-events: all;
    }
    .back-to-top:hover {
      background: var(--purple); color: var(--white);
      transform: translateY(-3px);
    }
  `;
  document.head.appendChild(btStyle);

  window.addEventListener('scroll', () => {
    if (window.scrollY > 400) backTop.classList.add('show');
    else backTop.classList.remove('show');
  });

  backTop.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  // ---------- HOVER CARDS PACOTES ----------
  document.querySelectorAll('.pacote-card').forEach(card => {
    card.addEventListener('mouseenter', () => {
      const btn = card.querySelector('.btn-pacote');
      if (btn) btn.style.background = 'var(--purple)';
    });
    card.addEventListener('mouseleave', () => {
      const btn = card.querySelector('.btn-pacote');
      if (btn && !btn.classList.contains('btn-orcamento')) {
        btn.style.background = '';
      }
    });
  });

  console.log('🌸 Reiki Ana — Site carregado com sucesso!');
});
