<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Commands\InstallCache;

class InstallOnAnotherCommand extends AbstractInstallOnWebserverCommand
{
    public $signature = 'sitecode:install:cache:other';
    public $description = 'Install and configure Sitecode cache for Nginx';

    public function handle(): void
    {
        $this->info(
            'Unfortunately other web servers are not supported yet. '
            . 'Please check configuration for Apache or Nginx web server.'
        );
    }
}
