# Sitecode - AI Agent & Tooling Guide

Sitecode is a lightweight CMS plugin for Filament (v4 & v5) where page schemas are defined declaratively in PHP companion files alongside Blade views.

---

## 1. Core Mental Model & Conventions

1. **Companion Template Pair**:
   - Every CMS page template pairs `resources/views/{view}.blade.php` with `resources/views/{view}.admin.php`.
   - `{view}.admin.php` **must** return an `\Alexeyplodenko\Sitecode\Models\PageFields` instance defining editable fields.
2. **Blade Rendering**:
   - Annotate views with `@php /** @var \Alexeyplodenko\Sitecode\Models\Page $page */ @endphp`.
   - Retrieve content via `$page->getContent('FieldName')` and check presence via `$page->hasContent('FieldName')`.
   - WYSIWYG content output wraps automatically in `<span class="sitecode">...</span>`.
3. **Static Caching**:
   - Full HTML pages cache to `public/sitecode_static_cache/`.
   - Invalidate cache when modifying pages or test with an authenticated session to bypass cache.

---

## 2. Passwordless Agent Authentication

Sitecode provides non-destructive authentication for AI agents (Chrome DevTools MCP / browser tooling) and CI suites without needing user passwords.

### Mode 1: Signed URL via CLI (Recommended)
```bash
php artisan sitecode:agent-url [--email=admin@example.com] [--ttl=15]
```
- Outputs a short-lived cryptographically signed URL.
- Navigate to the URL using browser tooling (`navigate_page`).
- Authenticates into the `web` session and redirects directly to Filament.

### Mode 2: Static Token via `.env` (Headless Access)
```env
SITECODE_AGENT_TOKEN=your_secret_token_min_16_chars
```
Direct access URL:
```
http://admin.<your-domain>/sitecode/agent-login?token=<SITECODE_AGENT_TOKEN>
```

### Key Config (`config/sitecode.php`)
- `SITECODE_AGENT_AUTH_ENABLED`: default `true` in `local` environment.
- `SITECODE_AGENT_AUTH_ALLOW_PRODUCTION`: default `false` (requires explicit opt-in for prod).

---

## 3. Dedicated Agent Skills

For detailed runbooks and code generation schemas, use the repository skills:

- **`sitecode-templates`** ([SKILL.md](file:///.agents/skills/sitecode-templates/SKILL.md)):
  Authoring companion `.admin.php` schemas, field types (TextInput, Textarea, WYSIWYG, File, Checkbox), repeaters, shared content, and Blade templates.
- **`sitecode-package-dev`** ([SKILL.md](file:///.agents/skills/sitecode-package-dev/SKILL.md)):
  Package internals, `PagesResource`, `PageCacheMiddleware`, static cache invalidation lifecycle, and Filament v4/v5 extensions.

