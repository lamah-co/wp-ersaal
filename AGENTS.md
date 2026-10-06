# AGENTS.md — wp-ersaal Development Guide

This document defines rules and constraints for **developers and AI agents** working on the `wp-ersaal` WordPress plugin.

---

## Project Overview

**Plugin:** wp-ersaal — Ersaal SMS Gateway for WordPress & WooCommerce
**Language:** PHP 8.1+ (strict types), vanilla CSS, JavaScript
**Architecture:** Module-based (`src/Modules/`), PSR-4 autoloading

---

## Absolute Constraints

### 1. Ersaal Core is READ-ONLY
The production backend project at:
```
/home/x414i/Documents/lamah/erssal/ersaal-project/ersaal-2
```
is **completely off-limits for any modification**. You may read it to understand API contracts. Never write, create, or delete files inside it.

### 2. Landing Page is READ-ONLY
The Ersaal Landing Page at:
```
/home/x414i/Local Sites/ersaal-plugin/app/public/wp-content/plugins/git/ersaal-landingpage
```
is the **visual source of truth**. Read it to understand brand colors, typography, spacing, and component patterns. Never modify it. Never import from it at runtime. Never add it as a dependency.

### 3. No Force Pushes
```bash
# NEVER use:
git reset --hard
git push --force
git clean -fd
git tag -d <tag>
git push --delete origin <tag>
```

---

## Git Workflow

Every change must follow this cycle:
```
Feature/Fix → Test → git diff → Commit → Push → Next
```

Use **Conventional Commits**:
- `feat(module): description`
- `fix(module): description`
- `style(admin): description`
- `docs(ui): description`
- `chore(release): description`

One commit per logical unit of work. Do not bundle unrelated changes.

---

## UI/UX Rules

> **Before creating or modifying any wp-ersaal admin UI,**
> **read `docs/UI-DESIGN-RULES.md` first.**
> This is a requirement, not a suggestion.

### Quick Rules

- The Ersaal Landing Page is the **visual source of truth**
- All admin UI must use tokens defined in `admin/assets/css/ersaal-tokens.css`
- Do **not** introduce arbitrary colors outside the defined palette
- Do **not** introduce arbitrary border radii — use `--ersaal-radius-*` tokens
- Do **not** create one-off button styles — use `.ersaal-btn-*` variants
- Use **CSS Logical Properties** for RTL/LTR: `margin-inline-start`, `padding-inline`, `text-align: start`
- Keep admin UI **compact and functional** — no marketing-style layouts
- Avoid animations > 250ms; no floating, parallax, or pulsing effects
- Reuse shared components before adding page-specific styles
- Every new admin screen must support **RTL and LTR**
- Every destructive action must use the `.ersaal-btn-danger` style
- Every status must use the `.ersaal-badge-*` system
- Do **not** copy React/Tailwind/Next.js code from the Landing Page
- All CSS classes must be prefixed with `ersaal-` to avoid wp-admin conflicts
- Do **not** apply styles to `.wp-admin`, `body`, or global selectors

→ Full design system: [`docs/UI-DESIGN-RULES.md`](docs/UI-DESIGN-RULES.md)

---

## PHP Rules

- All files use `declare(strict_types=1);`
- Namespaces follow `Ersaal\ModuleName\ClassName`
- Use `$wpdb->prepare()` for all SQL — no raw queries
- All form handlers must verify nonce (`check_admin_referer`) and capability (`current_user_can('manage_options')`)
- Sanitize all inputs: `sanitize_text_field`, `sanitize_textarea_field`, `absint`, etc.
- Escape all outputs: `esc_html`, `esc_attr`, `esc_url`, `esc_textarea`
- Use `mb_*` string functions for Arabic text processing
- Run `php -l` on all PHP files before committing

---

## Security Rules

- No secrets or API keys committed to Git
- No local paths hardcoded in runtime files (e.g., `/home/x414i/`)
- Use `wp_generate_uuid4()` or `uniqid()` for idempotency keys
- CSV exports must strip dangerous characters (CSV Injection prevention)

---

## Module Pattern

New modules must implement `Ersaal\Contracts\ModuleInterface` and be registered in `src/Core/Plugin.php`:

```php
public function boot(): void
{
    $this->registry->registerModule(new \Ersaal\Modules\YourNew\YourNewModule($this->options));
    // ...
}
```

---

## CSS Architecture

```
admin/assets/css/
├── ersaal-tokens.css       ← Load FIRST on all Ersaal pages
├── help.css                ← Help page
├── manual-send.css         ← Manual Send page
└── [page].css              ← Page-specific additions only
```

`ersaal-tokens.css` must be enqueued on every Ersaal admin page.

---

## Release Checklist

Before tagging a release:
- [ ] `git status` → working tree clean
- [ ] `php -l` on all PHP files → no errors
- [ ] No `var_dump`, `print_r`, `console.log` left in code
- [ ] No hardcoded local paths in runtime files
- [ ] Version updated in `ersaal.php` header AND `ERSAAL_VERSION` constant
- [ ] `CHANGELOG.md` updated
- [ ] ZIP structure: single root folder `wp-ersaal/`
- [ ] `tests/` and `run_dbdelta.php` excluded from ZIP
- [ ] ZIP tested on a clean WordPress installation
- [ ] Git tag created: `git tag -a vX.Y.Z -m "wp-ersaal vX.Y.Z"`
- [ ] Tag pushed: `git push origin vX.Y.Z`
- [ ] GitHub Release created with ZIP attached
