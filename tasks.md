# Tasks: Responsive Design Overhaul

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~100–130 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | auto-chain |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: size-exception
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Full responsive overhaul | PR 1 | Single PR, ~16 files, ~100-130 lines total |

---

## Phase 1: Core Infrastructure — CSS Utilities (main.css)

- [x] **1.1** Add `.table-responsive` alias to existing `.table-container` rule (L999) — append class selector
- [x] **1.2** Add `.grid-responsive-2/3/4` utility classes: `auto-fit, minmax(280/220/180px, 1fr)`
- [x] **1.3** Add `.btn-group` class: `flex-wrap: wrap; gap: var(--space-2)`
- [x] **1.4** Add `.sidebar-backdrop` CSS: `fixed; inset:0; z-index:99; bg:rgba(0,0,0,0.5)`
- [x] **1.5** Add 480px MQ: touch targets — `.btn, .btn-action, .sidebar-toggle, .modal-close` → `min-height:44px; min-width:44px`
- [x] **1.6** Add 480px MQ: modal sizing — `.modal-content { width: calc(100% - 16px); max-width: none }`
- [x] **1.7** Add 480px MQ: `.breadcrumb` → `font-size: var(--font-size-sm)` (min 14px)
- [x] **1.8** Add header title truncation — `.header-title h1 { overflow:hidden; text-overflow:ellipsis; white-space:nowrap }`

## Phase 2: Sidebar Backdrop — JS + Template

- [x] **2.1** Add `toggleSidebar()` / `closeSidebar()` to `shared.js`
- [x] **2.2** Add escape-key listener to `shared.js`
- [x] **2.3** Add backdrop `<div>` to `sidebar.php`
- [x] **2.4** Update sidebar toggle — use `toggleSidebar()` instead of inline `classList.toggle`

## Phase 3: Grid Fixes — Fixed `repeat(N, 1fr)` → Responsive Classes

- [x] **3.1** `detalle-participante.php` — replace `repeat(5, 1fr)` with `grid-responsive-4`; remove responsive MQs
- [x] **3.2** `modules/morosidad/detalle.php` — replace `repeat(3, 1fr)` with `grid-responsive-3`
- [x] **3.3** `modules/morosidad/index.php` — replace `repeat(4, 1fr)` with `grid-responsive-4`
- [x] **3.4** `crear-usuario.php` — verified: existing MQ already collapses 2→1 col

## Phase 4: Table Wrappers — Migrate Inline overflow-x to `.table-responsive`

- [x] **4.1** `modules/electrodomesticos/pagos.php` — migrated
- [x] **4.2** `modules/telefonia/pagos.php` — migrated
- [x] **4.3** `modules/motocicletas/pagos.php` — migrated
- [x] **4.4** `modules/categoria/pagos.php` — migrated
- [x] **4.5** `modules/categoria/grupo.php` — migrated
- [x] **4.6** `modules/participantes/index.php` — migrated
- [x] **4.7** `detalle-participante.php` — migrated

## Phase 5: Table Wrappers — Add `.table-responsive` to Unwrapped Tables

- [x] **5.1** `modules/morosidad/detalle.php` — wrapped bare `<table>` in `<div class="table-responsive">`
- [x] **5.2** `modules/usuarios/index.php` — added `.table-responsive` fallback alongside `.table-container`
- [x] **5.3** `modules/morosidad/index.php` — added `.table-responsive` fallback alongside `.table-container`

## Phase 6: Individual Page Fixes — Verification Only

- [x] **6.1** `dashboard_participante.php` — no changes needed
- [x] **6.2** `modules/participantes/perfil.php` — no changes needed
- [x] **6.3** `modules/categoria/index.php` — uses `auto-fill` grids, already responsive

## Verification

- [x] **7.1-7.5** All verification items confirmed

---

## Summary

| Phase | Tasks | Focus |
|-------|-------|-------|
| 1 | 8 | CSS utility classes + touch targets |
| 2 | 4 | Sidebar backdrop (JS + template) |
| 3 | 4 | Grid fixes (repeat → responsive classes) |
| 4 | 7 | Migrate inline overflow-x to class |
| 5 | 3 | Add wrappers to bare tables |
| 6 | 3 | Page verification |
| 7 | 5 | Final verification |
| **Total** | **34** | ~16 files, ~100-130 lines |

### Implementation Order
Infrastructure first (Phase 1-2) → grid fixes (Phase 3) → table wrappers (Phase 4-5) → page fixes (Phase 6) → verification (Phase 7).

### Review Workload
~100-130 changed lines across ~16 files. Well under 400-line budget. Single PR, no chained split needed.

### Next Step
Ready for implementation (sdd-apply).
