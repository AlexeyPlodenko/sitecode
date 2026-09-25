---
name: sitecode-package-dev
description: >-
  Guide for developing, extending, and testing the alexeyplodenko/sitecode package itself.
  Use when modifying package internals, adding field types, troubleshooting static cache behavior,
  or testing Filament admin integration via Chrome DevTools MCP.
---

# Sitecode Package Development & Architecture Guide

This skill guides engineering work directly on the `alexeyplodenko/sitecode` Laravel & Filament package.

---

## 1. Architectural Overview

```
src/
├── Commands/
│   ├── GenerateAgentUrlCommand.php   # 'sitecode:agent-url' generator
│   └── InstallCommand.php            # 'sitecode:install' setup & cache directory setup
├── Filament/
│   └── Resources/Pages/
│       ├── PagesResource.php         # Filament CRUD resource definition & hooks
│       ├── Schemas/PagesForm.php     # Filament form schema configuration
│       ├── Tables/PagesTable.php     # Filament table list & filters
│       └── Pages/                    # CreatePages, EditProperties, EditContent, etc.
├── Http/
│   ├── Controllers/AgentAuthController.php # Passwordless agent login controller
│   └── Middlewares/PageCacheMiddleware.php # High-speed static cache serving
├── Models/
│   ├── Page.php                      # Eloquent page model (JSON content & state)
│   ├── PageField.php                 # Schema field definition & Filament mapping
│   ├── PageFields.php                # Schema container (groups, repeaters, shared)
│   └── SharedContent.php             # Global cross-page content storage
├── Services/
│   ├── PagesCache.php                # Static HTML cache writer & invalidator
│   └── PagesRepository.php           # Page lookup & retrieval service
├── SitecodePlugin.php                # Filament v4/v5 panel plugin contract
└── SitecodeServiceProvider.php       # Laravel package bootstrapper
```

---

## 2. Static File Cache Mechanics

Sitecode achieves sub-10ms response times by caching full HTML responses directly to disk:

- **Storage Location**: `public/sitecode_static_cache/`
- **Serving Path**:
  - Web server level (e.g. Nginx `try_files` pointing to `sitecode_static_cache`)
  - Middleware level (`PageCacheMiddleware`) if handled through Laravel
- **Bypass Conditions**:
  - Request is POST/PUT/DELETE
  - Request is to Filament admin path
  - Query parameters present (e.g., debug or auth callbacks)
  - User is logged into auth session
- **Invalidation Lifecycle**:
  - Automatically triggered on `Page::saved`
  - Explicitly called in `PagesResource::beforeDelete()`
  - Manual invalidation via `PagesCache::invalidate($page)` or `clear-cache` action.

---

## 3. Agent Authentication Flow & Browser Testing

When developing features or running browser automation via Chrome DevTools MCP:

### Mode 1: Signed URL Generation (Preferred)
```bash
php artisan sitecode:agent-url [--email=admin@example.com] [--ttl=15]
```
1. Generates short-lived signed link with HMAC signature.
2. Direct navigation logs the agent into the `web` guard and redirects to Filament dashboard.
3. Does **not** alter password hashes or database state.

### Headless Verification with Chrome DevTools MCP:
```json
// Example: Navigate directly using output of sitecode:agent-url
navigate_page({ "url": "http://admin.local/sitecode/agent-login?expires=...&signature=..." })
```

---

## 4. Coding & Quality Standards
- **PHP**: `^8.2`, always declare `declare(strict_types=1);` at file top.
- **Filament**: Maintain compatibility with both Filament **v4** and **v5**.
- **Field Extensibility**: To add a new editor field type:
  1. Add case to `Alexeyplodenko\Sitecode\Enums\ContentEditor`.
  2. Add setter helper in `PageField.php` (e.g. `setEditorColorPicker()`).
  3. Map the enum case to a Filament component in `PageField::getFilamentComponent()`.

