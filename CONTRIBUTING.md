# Contributing to Ersaal SMS Gateway

Thank you for your interest in contributing to **Ersaal SMS Gateway for WordPress & WooCommerce**!

We welcome contributions from the community. To ensure code quality and smooth collaboration, please follow the guidelines below.

---

## Code of Conduct

Please be respectful, collaborative, and constructive when interacting with maintainers and other contributors.

---

## How to Contribute

### Reporting Bugs
1. Check the [existing issues](https://github.com/lamah-co/wp-ersaal/issues) to ensure the bug has not already been reported.
2. If it hasn't, open a new issue with a clear description, WordPress and PHP versions, steps to reproduce, and expected vs actual behavior.
3. **Do not report security vulnerabilities via public issues.** Please follow [SECURITY.md](SECURITY.md).

### Suggesting Enhancements
1. Open a new issue describing the feature, the use case, and how it benefits WordPress and WooCommerce users.
2. Discuss the design and approach before submitting large pull requests.

### Submitting Pull Requests
1. Fork the repository and create your branch from `main`:
   ```bash
   git checkout -b feat/your-feature-name
   ```
2. Follow our coding guidelines (see below).
3. Test your changes thoroughly on a local WordPress installation.
4. Commit your changes using **Conventional Commits**:
   * `feat(module): description`
   * `fix(module): description`
   * `docs(readme): description`
   * `style(admin): description`
   * `chore(release): description`
5. Push to your branch and submit a Pull Request to `main`.

---

## Coding Standards

### PHP Rules
* Target PHP 8.0 or higher.
* Always use `declare(strict_types=1);` at the top of every PHP file.
* Adhere to WordPress Coding Standards (WPCS).
* **Database queries:** Always use `$wpdb->prepare()` — no raw interpolated SQL.
* **Security & Nonces:** Verify CSRF nonces (`check_admin_referer` or `wp_verify_nonce`) and user capabilities (`current_user_can('manage_options')`) on all request handlers.
* **Sanitization & Escaping:** Sanitize all inputs (`sanitize_text_field`, `absint`, etc.) and escape all outputs (`esc_html`, `esc_attr`, `esc_url`).
* **Localization:** All user-facing strings must use internationalization functions (`__()`, `_e()`, `esc_html__()`, etc.) with the `'ersaal'` text domain.
* **Multibyte String handling:** Use `mb_*` functions for text processing to support Arabic and UTF-8 characters safely.

### UI & Styling
* All admin styling must use design tokens defined in `admin/assets/css/ersaal-tokens.css`.
* Every admin screen must fully support **RTL** and **LTR** layouts.
* Use CSS Logical Properties (`margin-inline-start`, `padding-inline`, `text-align: start`).
* Prefix all CSS classes with `ersaal-` to avoid conflicts with WordPress core or other plugins.

---

## License

By contributing to Ersaal SMS Gateway, you agree that your contributions will be licensed under the **GNU General Public License v2.0 or later** (GPL-2.0-or-later).
