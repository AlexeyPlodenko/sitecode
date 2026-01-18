# Sitecode

Filament v4 and v5 basic CMS like plugin. Adds pages management with page structure defined in `.php` files.

## Installation

1. Run `composer require alexeyplodenko/sitecode`.
2. Run `php artisan sitecode:install` to install cache feature.
3. Run `php artisan migrate` to create DB tables to store data.
4. Create a public disk, if you need to edit images and files in Sitecode. Go to `/config/filesystems.php`, add the following to the `'disks'` array:
    ```php
    'public_media_uploads' => [
        'driver' => 'local',
        'root' => public_path('media'),
        'url' => env('APP_URL') .'/media',
        'visibility' => 'public',
        'throw' => true,
        'report' => true,
    ],
    ```
5. Register the plugin in Filament AdminPanelProvider `/app/Providers/Filament/AdminPanelProvider.php`:
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

For example, we have the following Blade file `/resources/views/home.blade.php`:

```bladehtml
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Website</title>
</head>
<body>
<main>
    <h1>My page title</h1>
    <p>My page content goes here...</p>
    <div>
        <img src="/my-image.jpg" alt="">
    </div>
</main>
</body>
</html>

```

To make the content editable with `Sitecode`, create a file `/resources/views/home.admin.php` next to original `text.blade.php` file, with:

```php
<?php
$pageFields = new \Alexeyplodenko\Sitecode\Models\PageFields();

$pageFields->makeField('Title');
$pageFields->makeField('Text')->setEditorWysiwyg();
$pageFields->makeField('Image')->setEditorFile();

return $pageFields;
```

and then adjust the initial Blade file `/resources/views/home.blade.php`:
```bladehtml
@php /** @var \Alexeyplodenko\Sitecode\Models\Page $page */ @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <title>{{ $page->title }}</title>
</head>
<body>
<main>
    @if ($page->hasContent('Title'))
        <h1>{{ $page->getContent('Title') }}</h1>
    @endif
    {!! $page->getContent('Text') !!}
    <div>
        <img src="{{ $page->getContent('Image') }}" alt="">
    </div>
</main>
</body>
</html>
```

## Special cases

### Custom admin. panel domain

Define your custom admin. panel domain as `SITECODE_ADMIN_URL=https://admin.example.com` in `/.env` file, when the domain is different from your website domain.

### Custom disk name

Define your custom filesystem disk name as `SITECODE_DISK=sitecode_public_media` in `/.env` file, if you do not want to use the default one.
