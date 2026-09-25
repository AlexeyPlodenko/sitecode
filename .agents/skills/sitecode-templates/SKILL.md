---
name: sitecode-templates
description: >-
  Guide for creating, configuring, and rendering Sitecode CMS templates in Laravel & Filament.
  Use when authoring or modifying .admin.php and .blade.php companion files, defining PageFields schemas,
  configuring field types, handling repeaters/shared content, and rendering in Blade.
---

# Sitecode Page Templates & Schema Guide

Sitecode manages dynamic CMS pages in Laravel/Filament by pairing Blade views with PHP schema definitions.

---

## 1. Companion File Pattern

Every editable page view in `resources/views/` must have a companion `.admin.php` file with the exact same base path:
- **Blade View**: `resources/views/pages/about.blade.php`
- **Schema Companion**: `resources/views/pages/about.admin.php`

---

## 2. Defining Schemas (`.admin.php`)

The `.admin.php` file **must return** an instance of `\Alexeyplodenko\Sitecode\Models\PageFields`:

```php
<?php declare(strict_types=1);

use Alexeyplodenko\Sitecode\Models\PageFields;

$pageFields = new PageFields();

// Basic Fields
$pageFields->makeField('Headline')->label('Main Headline');
$pageFields->makeField('Subtitle')->setEditorTextInput()->setHint('Short summary');
$pageFields->makeField('Description')->setEditorTextarea();
$pageFields->makeField('Content')->setEditorWysiwyg();
$pageFields->makeField('HeroImage')->setEditorFile();
$pageFields->makeField('IsActive')->setEditorCheckbox();

// Repeaters / Lists
$items = $pageFields->makeFieldGroup('TeamMembers')->useRepeater('team');
$items->makeField('Name');
$items->makeField('Role');
$items->makeField('Photo')->setEditorFile();

// Shared Content (persisted globally across pages)
$shared = $pageFields->makeFieldGroup('GlobalNotice')->setIsShared(true);
$shared->makeField('NoticeBanner')->setEditorTextarea();

return $pageFields;
```

### Available Field Editor Types (`PageField` methods)
| Method | Underlying Component | Details |
| :--- | :--- | :--- |
| `setEditorTextInput()` | `TextInput` | Default single-line text input. |
| `setEditorTextarea()` | `Textarea` | Multi-line plain text. |
| `setEditorWysiwyg()` | `RichEditor` | Includes raw HTML modal edit action; attachments stored on configured disk. |
| `setEditorFile()` | `FileUpload` | Media uploads stored on `config('sitecode.disk')`. |
| `setEditorCheckbox()` | `Checkbox` | Boolean toggle. |

### Field Configuration & Filament Customization
```php
$field = $pageFields->makeField('Title')
    ->label('Custom Label')
    ->setHint('Helper hint text')
    ->setIsShared(false);

// Access underlying Filament component for advanced validation, prefixes, etc.
$component = $field->getFilamentComponent();
$component->required()->maxLength(120);
```

---

## 3. Rendering in Blade (`.blade.php`)

Always include the `@php` PHPDoc annotation at the top to assist IDE and static analysis:

```blade
@php /** @var \Alexeyplodenko\Sitecode\Models\Page $page */ @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <title>{{ $page->title }}</title>
</head>
<body>
    @if ($page->hasContent('Headline'))
        <h1>{{ $page->getContent('Headline') }}</h1>
    @endif

    {{-- WYSIWYG output is wrapped in <span class="sitecode"> --}}
    {!! $page->getContent('Content') !!}

    @if ($page->hasContent('HeroImage'))
        <img src="{{ $page->getContent('HeroImage') }}" alt="{{ $page->getContent('Headline') }}">
    @endif

    {{-- Repeater iteration --}}
    @foreach ($page->getContent('TeamMembers') ?? [] as $member)
        <div class="team-card">
            <img src="{{ $member['Photo'] ?? '' }}" alt="{{ $member['Name'] ?? '' }}">
            <h3>{{ $member['Name'] ?? '' }}</h3>
            <p>{{ $member['Role'] ?? '' }}</p>
        </div>
    @endforeach
</body>
</html>
```

### Key Blade Helper & Page Methods
- `$page->title`, `$page->url`, `$page->description`: Standard page record properties.
- `$page->hasContent('Field')`: Boolean check if field exists and is not empty.
- `$page->getContent('Field')`: Retrieves field value. Rich text is wrapped in `<span class="sitecode">`.
- `$page->getContentRaw('Field')`: Retrieves field value without any wrappers.

---

## 4. Controller Integration Helpers

When serving Sitecode-managed content from custom Laravel controllers/routes:

```php
use function sitecodeViewFromUrl;
use function sitecodeViewFromBlade;

// 1. By URL matching a Sitecode database record:
Route::get('/custom-route', function () {
    return sitecodeViewFromUrl('/custom-route', ['extraVar' => $data]);
});

// 2. Directly rendering a blade template with Sitecode data/shared content without a DB record:
Route::get('/dynamic/{slug}', function (string $slug) {
    return sitecodeViewFromBlade('pages/item.blade.php', ['slug' => $slug]);
});
```

---

## 5. Important Conventions & Gotchas
1. **Field Title Uniqueness**: Field titles within the same group/level must be unique (case-insensitive); duplicate names throw a `RuntimeException`.
2. **WYSIWYG Styling**: WYSIWYG rich text outputs wrap with class `.sitecode`. Style your typography targeting `.sitecode p`, `.sitecode ul`, etc.
3. **Cache Invalidation**: When testing changes in browser, note that static caching may be active (`public/sitecode_static_cache/`). Clear cache or log in to admin to bypass cache.

