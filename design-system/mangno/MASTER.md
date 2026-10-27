# MANGNO Design System — Master

> **LOGIC:** When building a specific page, first check `design-system/mangno/pages/[page-name].md`.
> If that file exists, its rules **override** this Master file. If not, strictly follow the rules below.

---

**Project:** MANGNO — "Ready Before It Happens." (educational disaster-preparedness simulation game)
**Generated:** 2026-10-21 · **Derived from:** ui-ux-pro-max skill (design-system + color / typography / ux / style / laravel domain searches, dials: variance 4 · motion 3 · density 6)

**Style thesis:** *Trust & Authority + Accessible & Ethical*, expressed as a **"Readiness Console"** —
professional emergency-preparedness software with simulation-mission framing for players.
Light content pages (warm off-white paper) with **deep charcoal** operational surfaces (header, footer,
hero, admin sidebar, auth visual panel) and a single **emergency-orange** action accent.

---

## 1. Color tokens (semantic rules)

| Token | Value | Usage rules |
|-------|-------|-------------|
| `--ink` | `#15181C` | Charcoal 900 — dark shell surfaces (header, footer, hero base, sidebar) |
| `--ink-2` | `#1D2126` | Charcoal 800 — elevated dark panels |
| `--ink-3` | `#262B31` | Charcoal 700 — dark hovers / borders on dark |
| `--paper` | `#F4F2ED` | Warm off-white page background (default `body`) |
| `--surface` | `#FFFFFF` | Cards / panels / tables on light |
| `--surface-2` | `#FAF9F6` | Subtle fills (table zebra, code-ish blocks, input bg) |
| `--text` | `#1B1E22` | Primary text on light (contrast ≥ 12:1) |
| `--muted` | `#5A6068` | Secondary text on light (≥ 5.5:1) |
| `--text-on-dark` | `#F2EFE9` | Primary text on charcoal |
| `--muted-on-dark` | `#A9AFB7` | Secondary text on charcoal (≥ 6:1) |
| `--line` | `#E3DFD5` | Borders / dividers on light |
| `--line-strong` | `#CFC9BC` | Emphasized borders on light |
| `--line-dark` | `rgba(255,255,255,.12)` | Borders on charcoal |
| `--accent` | `#C4470F` | **Emergency orange (solid actions, borders, fills)** — white text ≥ 4.9:1; as *text* on paper it is 4.45:1, so use `--accent-text` for text |
| `--accent-bright` | `#E8631F` | Orange for rails, icons, large text, active underlines **on charcoal** (5.2:1 on `--ink`) |
| `--accent-text` | `#A83C0C` | Orange **text on light surfaces** (eyebrows, links, badges) — 5.7:1 on paper |
| `--accent-soft` | `rgba(232,99,31,.10)` | Tint fills (active tabs, hover washes) |
| `--danger` | `#B42318` | Critical / destructive / validation errors |
| `--warning` | `#B54708` | Caution states |
| `--success` | `#157A3F` | Completed / success states |
| `--info` | `#14608F` | Informational states |

**Hazard accents** (scenario rails, progress fills, card edges — never for body text):
`--hazard-earthquake: #A66A00` (ochre) · `--hazard-fire: #C0392B` (orange-red) ·
`--hazard-typhoon: #14608F` (storm blue) · `--hazard-volcano: #8C2F39` (crimson)

**Rules:** orange = actions + hazard identity only; red = actual danger/failure only; green = completed;
amber = caution; blue = info. Never color alone for status — always pair with a text label.

---

## 2. Typography

- **Sans:** Inter (400/500/600/700/800) — headings, body, UI.
- **Mono:** JetBrains Mono (400/500/700) — eyebrows/labels, scores, ranks, version numbers, table numerics.
- **No display novelty fonts.** No Orbitron. No emoji as icons — inline SVG only (stroke 1.6–1.8, currentColor).

Scale (fluid where noted):
- Display / hero title: `clamp(2.25rem, 6vw, 3.5rem)`, weight 800, tracking `-0.02em`
- Page h1: `clamp(1.6rem, 3vw, 2.1rem)` / 700 / `-0.01em`
- h2: `1.35rem` / 700 · h3: `1.05rem` / 700
- Body: `1rem` / 1.65 · Small: `0.875rem` · Micro: `0.78rem`
- Eyebrow / label: JetBrains Mono `0.72rem`, uppercase, `letter-spacing: .14em`
- Scores & data: JetBrains Mono, `font-variant-numeric: tabular-nums`

---

## 3. Spacing, shape, elevation

- 4/8-based scale: `--s1:4 s2:8 s3:12 s4:16 s5:24 s6:32 s7:48 s8:64`; section rhythm `clamp(48px, 7vw, 84px)`.
- Radius: controls `6px`, cards/panels `10px`, pills only for true chips/badges. **No giant rounded cards.**
- Borders over shadows: `1px solid var(--line)` is the default separation. Shadows are subtle:
  `--shadow-sm: 0 1px 2px rgba(20,24,28,.06)` · `--shadow-md: 0 10px 30px rgba(20,24,28,.10)` (overlays only).
- Container: `min(1200px, 100% - 40px)` → mobile gutters `16px` per side at ≤480px.

---

## 4. Layout rules

- **Public pages:** charcoal sticky header (68px desktop / 60px mobile) → light paper content → charcoal footer.
- **Landing:** dark photographic hero (scenario imagery), then all content sections on paper.
- **Player pages:** `dashboard-page container` — page header row, stats strip, mission grid (4→2→1).
- **Admin:** paper canvas, charcoal fixed sidebar (240px), white topbar, white panels, dense tables.
- **Auth:** split shell — charcoal visual panel + light form card; stacks on mobile (form first? visual first).
- Breakpoints: 1170 (nav collapse), 980 (admin drawer), 768 (grids 2-col, table stacking), 560 (1-col), 380 (tight).

---

## 5. Component conventions

- **Buttons:** `.btn` base (44px min height, 6px radius, 600 weight) → `.btn-primary` solid orange,
  `.btn-secondary` neutral outline on light (or light outline on dark contexts), `.btn-outline`
  ghost with border, `.btn-danger` red outline/solid for destructive. `.small` = 34px, `.full-width`.
  Hover = darken + 1px lift ≤150ms; focus-visible = 3px ring; `[disabled]/.is-disabled` = 50% + not-allowed.
- **Forms:** label above input; input 44px, white bg, `--line` border, focus ring orange 3px;
  `.field-error` red text linked below input; `.field-help` muted. Error summary = `.alert.error` with `role=alert`.
- **Tables:** `.table-wrap` (horizontal scroll fallback) + `.table-stack` (≤768px: rows become stacked
  label/value blocks via `data-label`). Head row = mono uppercase micro labels, bottom border strong.
  Numbers `tabular-nums`. Actions right-aligned in `.table-actions`.
- **Status:** `.status-badge` chip + always a text label; `.score-badge` bands (Excellent/Strong/Building/
  Start/Not Started); `.difficulty-tag` (easy/medium/hard).
- **Panels:** `.admin-panel` = white, 1px line, 10px radius, `.panel-header` with bottom border.
- **Alerts:** `.alert.success/.error` with icon stripe; `role=status|alert` preserved.
- **Empty states:** `.empty-state` centered muted text + optional hint; never a fake illustration.

---

## 6. Motion

- Micro: 140–200ms `ease` on color/background/border/transform.
- Reveal: `.reveal/.reveal-item` → opacity 0→1 + `translateY(10px)`→0, 400–500ms, IntersectionObserver-driven;
  **no-JS fallback:** content must be visible if JS never runs (only apply hidden state via `.js` hook if added).
- Hero spotlight follows pointer (existing JS) — keep intensity low; no parallax.
- `prefers-reduced-motion: reduce` → disable transforms/spotlight/carousel animation (JS already honors it).

---

## 7. Anti-patterns (forbidden)

Purple/blue AI gradients · glassmorphism-heavy surfaces · glowing borders · giant rounded cards ·
heavy shadows · decorative floating blobs · oversized novelty headings · repetitive identical card grids ·
pill overload · icon-per-line decoration · decorative animation · generic SaaS bento dashboards ·
fake statistics/testimonials · emoji icons · dark-navy monochrome theme (the legacy look).

---

## 8. Pre-delivery checklist

- [ ] Contrast ≥ 4.5:1 body / ≥ 3:1 large + UI boundaries, both on light and charcoal surfaces
- [ ] Visible `:focus-visible` ring on every interactive element; keyboard-reachable nav/menus
- [ ] Touch targets ≥ 44px; table stacks and forms fit at 360px without horizontal page overflow
- [ ] Status communicated by text + color, decorative SVG `aria-hidden`, meaningful images have alt
- [ ] `prefers-reduced-motion` respected; no layout-shifting hovers
- [ ] Existing routes, form actions, `@csrf`, name attributes, and Blade data bindings unchanged

---

## 9. Implementation notes (post-build verification)

- **View ↔ CSS contract:** every static class in the 30 Blade views resolves to a rule in
  `resources/css/*` (verified by script); JS-toggled states covered too — `.open` (nav drawer,
  admin sidebar + backdrop), `.is-active` (nav toggle, spec rows), `.is-visible` (reveals,
  password toggle), `.active` (nav/sidebar/tabs/stat links), `.is-current`, `.top-1..3`,
  `--spotlight-x/y`, `body.admin-nav-open`.
- **Dynamic badge classes:** score bands via `Str::slug($status)` → `.score-badge.{excellent,strong,building,start,not-started}`;
  difficulty via `strtolower()` → `.difficulty-tag.{easy,medium,hard}`; status badges use raw
  status/role strings → `.status-badge.{active,inactive,published,draft,archived,completed,not_started,in_progress,scheduled,admin,user}`.
- **Narrow-screen grids** use `minmax(min(320px, 100%), 1fr)` (`.admin-grid`, `.download-grid`) so
  360–390px viewports never overflow.
- **Text-on-light orange** always goes through `--accent-text`; `--accent` for fills/borders,
  `--accent-bright` for charcoal surfaces only.
