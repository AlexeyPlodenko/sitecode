<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Commands\InstallCache;

use Illuminate\Console\Command;

abstract class AbstractInstallOnWebserverCommand extends Command
{
    protected function createCacheDirectory(): bool
    {
        $cachePath = public_path('sitecode_static_cache');

        $this->info("Creating cache directory at $cachePath");
        
        if (is_dir($cachePath)) {
            $this->info('The cache directory already exists.');
            
            $isCreated = true;
            
        } else {
            $isCreated = @mkdir($cachePath, 0755);
            if ($isCreated) {
                $this->info('The cache directory created.');
            }
        }
        
        if (!$isCreated) {
            $publicPath = public_path();
            
            if (!is_dir($publicPath)) {
                $this->warn(
                    "Failed to create the directory $cachePath. The parent directory does not exist $publicPath. "
                    . "Please create it manually. Make it writable for PHP and readable for web server."
                );
                return false;
            }

            if (!is_writable($publicPath)) {
                $this->warn(
                    "Failed to create the directory $cachePath. The parent directory is not writable by PHP. "
                    . "Please create it manually. Make it writable for PHP and readable for web server."
                );
                return false;
            }

            $this->warn(
                "Failed to create the directory $cachePath. Please create it manually. "
                . "Make it writable for PHP and readable for web server."
            );
            return false;
        }

        if (!is_writable($cachePath)) {
            $this->warn(
                "Make the directory $cachePath writable for PHP."
            );
            return false;
        }
        
        return true;
    }
}
