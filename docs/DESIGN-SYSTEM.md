# Design System — Reiki Ana

Status: design system inicial extraído do frontend atual (`public/static/style-01-foundation.css`)
Última revisão: 2026-09-25

> **Governança:** o status da fase de frontend está em `PLANO_MESTRE_ANAREIKI.md`. Modernizações visuais aqui descritas dependem de apresentação e aprovação do Jose antes da implementação. A identidade aprovada **deve ser preservada** na migração para PHP.

---

## 1. Princípio visual

A linguagem é **acolhedora, feminina e serena** — coerente com massoterapia e Reiki: roxo/lilás (espiritualidade, calma), rosa (cuidado, afeto), toques de dourado e verde. Tipografia com serifas elegantes para títulos e sans humanista para texto.

O site público mantém o CSS atual sem alteração de linguagem. O **painel admin** usa Bootstrap 5, mas herda a paleta (acento roxo) para não parecer um sistema genérico. Não alterar drasticamente o visual sem apresentar antes ao Jose.

---

## 2. Cores

Tokens reais definidos em `public/static/style-01-foundation.css` (`:root`):

| Token | Valor | Uso |
|---|---|---|
| `--purple` | `#7B4F9E` | cor primária: logo, títulos, CTA, acento admin |
| `--purple-dark` | `#5A3270` | hover de botões, fundos escuros, footer |
| `--purple-light` | `#C9A8E0` | detalhes, bordas suaves, gradientes |
| `--rose` | `#E8A4C0` | acento secundário, subtítulos, destaques |
| `--rose-light` | `#F5DDE8` | fundos de seção suaves |
| `--rose-pale` | `#FDF0F5` | fundo de navbar, blocos muito claros |
| `--gold` | `#C9A96E` | detalhes premium (pacotes, selos) |
| `--green` | `#6B7F5E` | acento natural/terapêutico pontual |
| `--cream` | `#FBF7F2` | fundo base do site |
| `--dark` | `#2D1B3D` | texto/fundos bem escuros |
| `--text` | `#4A3555` | texto principal |
| `--text-light` | `#7A6A87` | texto secundário |
| `--white` | `#FFFFFF` | fundo e texto sobre cor |

Regras:

- **roxo** é a cor de identidade e de ação principal (CTA);
- **rosa** é acento e complemento — não competir com o roxo no CTA;
- **dourado** só em contexto premium (pacotes/selos);
- fundos `--cream`/`--rose-pale` dão respiro; evitar excesso de cor saturada.

Contraste (WCAG AA): texto sobre `--cream`/`--white` usa `--text`/`--purple-dark`; texto sobre roxo usa branco. Verificar contraste de qualquer texto sobre imagem (ver `RULES.md` §8).

---

## 3. Tipografia

Fontes (Google Fonts, já carregadas no `<head>`):

```css
--serif-display: 'Playfair Display', serif;      /* títulos com ênfase, itálico no logo */
--serif-heading: 'Cormorant Garamond', serif;    /* headings elegantes */
--sans:          'Lato', sans-serif;              /* corpo de texto */
```

Escala atual:

- corpo: `16px`, `line-height: 1.6`;
- `.hero-title`: `clamp(3.5rem, 10vw, 6rem)` (mobile: `clamp(3rem, 12vw, 4.5rem)`);
- `.section-title`: `clamp(2rem, 4vw, 2.8rem)`;
- subtítulos de seção: `clamp(1.2rem, 3vw, 1.6rem)`.

Classes utilitárias existentes: `.script` (Playfair itálico, cor roxo), `.script-heading` (Cormorant). `.section-title .script` usa rosa.

Regras: títulos fortes e elegantes; textos longos em `--text-light`; evitar parágrafos extensos em cards; boa leitura no mobile.

---

## 4. Espaçamentos

- container: `max-width: 1200px`, `padding: 0 24px`;
- seções com respiro generoso vertical;
- reduzir espaçamentos no mobile sem perder hierarquia.

---

## 5. Border radius e sombras

```css
--radius:    16px;   /* cards, blocos */
--radius-sm: 10px;   /* botões, inputs */
--shadow:    0 8px 32px rgba(123,79,158,0.12);
--shadow-lg: 0 20px 60px rgba(123,79,158,0.18);
```

Sombras sempre em tom roxo translúcido (nunca cinza neutro). Usar com moderação — cards em destaque, navbar sticky, elementos flutuantes.

---

## 6. Transições

```css
--transition: all 0.35s cubic-bezier(.4,0,.2,1);
```

Respeitar `prefers-reduced-motion`: desativar animações de entrada e transições longas.

---

## 7. Grid e responsividade

Breakpoints reais:

```css
1024px   /* ajuste de navegação e grids */
768px    /* menu mobile, grids em coluna */
480px    /* layout mobile completo */
```

- desktop: múltiplas colunas (serviços, pacotes);
- tablet: 2 colunas;
- mobile: 1 coluna.

Usar Flexbox e CSS Grid nativos (já é o padrão do CSS atual). Não introduzir Bootstrap no **site público** — só no admin.

---

## 8. Botões

Classes existentes: `.btn-primary` (roxo, texto branco, CTA principal), `.btn-ghost`, `.btn-nav`, `.btn-small`, `.btn-pacote`, `.btn-orcamento`.

Regras:

- CTA principal sempre em roxo;
- ícone opcional (Font Awesome, hoje via CDN);
- hover com leve elevação;
- no mobile, CTA principal pode ocupar largura total;
- `border-radius` próximo de `--radius-sm`.

---

## 9. Inputs e formulários (NOVO — Fase 4/5)

Antes o site não tinha formulário. Com o agendamento e o admin, passam a existir. Regras obrigatórias:

- **label sempre visível** (nunca depender só de placeholder);
- placeholder mostra um exemplo terminado em `…` (ex.: `(48) 99999-9999`), nunca uma instrução;
- foco com contorno acessível e visível (não remover `outline` sem substituto);
- validação **no servidor** (ver `API.md` §2.2); validação no cliente é só conveniência;
- mensagem de erro diz o problema **e** o que fazer (ver `RULES.md` §8.1);
- campos de telefone/e-mail: `type` correto (`tel`, `email`) para teclado mobile adequado.

No site público, estilizar inputs com os tokens roxo/rosa. No admin, usar componentes de formulário do Bootstrap com acento `--purple` sobrescrito via CSS.

---

## 10. Cards

Padrões atuais: cards de serviço e de pacote — imagem/ícone no topo, título claro, destaque em rosa/dourado, CTA pequeno. Hover sutil, sombra roxa translúcida. Manter proporção de imagem.

---

## 11. Tabelas (NOVO — admin, Fase 5)

Só no painel. Usar tabela responsiva do Bootstrap com:

- cabeçalho claro;
- ações por linha (editar, ativar/desativar, mudar status);
- estados **vazio / carregando / erro** explícitos;
- paginação quando houver volume de agendamentos.

---

## 12. Estados obrigatórios

Todo elemento interativo deve cobrir: `hover`, `focus-visible`, `active`, `disabled`; e listas/consultas: `loading`, `empty`, `error`. Atualização assíncrona (calendário de horários, toasts) usa `aria-live="polite"`.

---

## 13. Ícones

Hoje via Font Awesome (CDN). Ícone decorativo usa `aria-hidden="true"`; ícone funcional tem label acessível; ícone não substitui texto essencial.

> `ponytail:` Font Awesome via CDN é aceitável para o site atual. Se a lista de ícones usados for pequena, avaliar SVG inline para eliminar a dependência externa — decidir na Fase 3, não antes.

---

## 14. Painel admin (Bootstrap 5)

- carregar Bootstrap via CDN ou arquivo local em `static/`;
- **herdar a identidade**: sobrescrever `--bs-primary` para `#7B4F9E` (roxo) e usar `--cream` como fundo;
- layout simples: sidebar + conteúdo; sem componentes pesados desnecessários;
- reutilizar os padrões de input/tabela/estado desta doc;
- o admin não precisa da mesma exuberância do site público — prioriza clareza e rapidez de uso.

### Tela de autenticação

- usar fundo em roxo profundo com detalhes radiais discretos em lilás/rosa;
- manter conteúdo em cartão claro, com largura confortável e campos/botão bem destacados;
- animações de entrada e hover devem ser curtas e respeitar `prefers-reduced-motion`;
- preservar o fluxo de autenticação existente e os estados acessíveis de foco e mensagens.

---

## 15. Como criar novos componentes mantendo consistência

1. usar os tokens existentes antes de criar novos;
2. verificar se já existe padrão parecido no CSS atual;
3. preservar a paleta roxo/rosa/lilás;
4. manter variações pequenas;
5. validar alterações visuais relevantes com o Jose antes de implementar.
