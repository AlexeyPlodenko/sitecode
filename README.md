# Sitecode

Filament v4 basic CMS like plugin. Adds pages management with page structure defined in `.php` files.

## Installation

1. Run `composer require alexeyplodenko/sitecode`.
2. Run `php artisan sitecode:install`.
3. Register the plugin in Filament AdminPanelProvider `/app/Providers/Filament/AdminPanelProvider.php`:
```php
<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ...
            ->plugin(\Alexeyplodenko\Sitecode\SitecodePlugin::make()); // <-- Add this line
    }
}
```

## Usage

For example, we have the following Blade file `/resources/views/text.blade.php`:

```bladehtml
@extends('base')

@section('content')
    <main>
        <h1>My page title</h1>
        <p>My page content goes here...</p>
    </main>
@endsection
```

To make the content editable with `Sitecode`, create a file `/resources/views/text.admin.php` next to original `text.blade.php` file, with:

```php
<?php
$pageFields = new \Alexeyplodenko\Sitecode\Models\PageFields();

$pageFields->makeField('Title');
$pageFields->makeField('Text')->setEditorWysiwyg();
$pageFields->makeField('Image')->setEditorFile();

return $pageFields;
```

and then adjust the initial Blade file `/resources/views/text.blade.php`:
```bladehtml
<?php /** @var \Alexeyplodenko\Sitecode\Models\Page $page */ ?>

@extends('base')

@section('content')
    <main>
        @if ($page->hasContent('Title'))
            <h1>{{ $page->getContent('Title') }}</h1>
        @endif
        {!! $page->getContent('Text') !!}
        <div>
            <img src="{{ $page->getContent('Image') }}" alt="">
        </div>
    </main>
@endsection
```
