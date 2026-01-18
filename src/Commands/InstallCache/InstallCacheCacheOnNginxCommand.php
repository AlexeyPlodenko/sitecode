<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Commands\InstallCache;

class InstallCacheCacheOnNginxCommand extends AbstractInstallCacheOnWebserverCommand
{
    public $signature = 'sitecode:install:cache:nginx';
    public $description = 'Install and configure Sitecode cache for Nginx';

    public function handle(): void
    {
        $codeToAdd = $this->getNginxCode();
        $codeToAdd = str_replace('%PUBLIC_DIR_PATH%', public_path(), $codeToAdd);

        $this->info(
            "To install Sitecode cache for Nginx add the following code to your server configuration "
            . "and restart the web server:\n$codeToAdd"
        );

        $this->confirm('Hit ENTER to continue the installation');

        $this->createCacheDirectory();
    }

    protected function getNginxCode(): false|string
    {
        return file_get_contents(__DIR__ . '/nginx_instructions.txt');
    }
}
