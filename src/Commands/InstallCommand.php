<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Commands;

use Alexeyplodenko\Sitecode\Commands\InstallCache\InstallCacheOnAnotherCommand;
use Alexeyplodenko\Sitecode\Commands\InstallCache\InstallCacheCacheOnApacheCommand;
use Alexeyplodenko\Sitecode\Commands\InstallCache\InstallCacheCacheOnNginxCommand;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    public $signature = 'sitecode:install';
    public $description = 'Install and configure Sitecode';

    /**
     * @var string[] 
     */
    protected array $webServers = [
        'Apache' => InstallCacheCacheOnApacheCommand::class,
        'Nginx' => InstallCacheCacheOnNginxCommand::class,
        'Another' => InstallCacheOnAnotherCommand::class,
    ];
    
    public function handle(): void
    {
        $this->info('Installing Sitecode...');

        if ($this->confirm('Do you want to install Sitecode cache for webserver?', true)) {
            $this->installCacheForWebserver();
        }

        $this->info('Sitecode installation complete.');
    }
    
    protected function installCacheForWebserver(): void
    {
        $webServer = $this->choice(
            'Which web server are you using?',
            array_keys($this->webServers)
        );

        $this->call($this->webServers[$webServer]);
    }
}
