<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Commands\InstallCache;

class InstallCacheCacheOnApacheCommand extends AbstractInstallCacheOnWebserverCommand
{
    public $signature = 'sitecode:install:cache:apache';
    public $description = 'Install and configure Sitecode cache for Apache';
    protected string $insertBefore = '# Redirect Trailing Slashes';

    public function handle(): void
    {
        $this->info('Installing Sitecode cache for Apache...');

        if ($this->confirm('Do you want to apply .htaccess modifications?', true)) {
            $this->modifyHtaccess();
        }
        
        $this->createCacheDirectory();

        $this->info('Installation for Apache complete.');
    }
    
    protected function modifyHtaccess(): bool
    {
        $codeToAdd = $this->getHtaccessCode();
        $codeToAdd = trim($codeToAdd, "\r\n");
        
        $htaccessPath = public_path('.htaccess');
        if (!is_file($htaccessPath)) {
            $this->warn(
                "The file \"$htaccessPath\" does not exist. "
                . "You can get it from Laravel repository on Github https://github.com/laravel/laravel and insert "
                ."the following block of code:\n$codeToAdd\n\n before the line $this->insertBefore..."
            );
            return false;
        }
        
        if (!is_readable($htaccessPath)) {
            $this->warn(
                "The file \"$htaccessPath\" is not readable by the PHP process. ".
                "Please add the following block of code:\n$codeToAdd\n\n before the line $this->insertBefore... "
                . "to the file."
            );
            return false;
        }
        
        if (!is_writable($htaccessPath)) {
            $this->warn(
                "The file \"$htaccessPath\" is not writable by the PHP process. ".
                "Please add the following block of code:\n$codeToAdd\n\n before the line $this->insertBefore... "
                . "to the file."
            );
            return false;
        }

        $htaccessCode = file_get_contents($htaccessPath);
        $htaccessCodeLines = explode("\n", $htaccessCode);

        $isSitecodeHtaccessAdded = $this->findLine($htaccessCodeLines, '### Sitecode start');
        if ($isSitecodeHtaccessAdded) {
            $this->info("The Sitecode cache configuration found in $htaccessPath. Skipping.");
            return true;
        }
        
        $insertAt = $this->findLine($htaccessCodeLines, $this->insertBefore);
        if (!$insertAt) {
            $this->warn(
                "The line $this->insertBefore... does not exist in the file \"$htaccessPath\". "
                ."Add the following code manually:\n$codeToAdd"
            );
            return false;
        }

        array_splice($htaccessCodeLines, $insertAt, 0, "$codeToAdd\n");
        $htaccessCode = implode("\n", $htaccessCodeLines);
        file_put_contents($htaccessPath, $htaccessCode);

        $this->info('Sitecode cache configuration applied.');
        
        return true;
    }

    protected function findLine(array $lines, string $lineToFind): ?int
    {
        return array_find_key($lines, fn($line) => str_contains($line, $lineToFind));

    }

    protected function getHtaccessCode(): false|string
    {
        return file_get_contents(__DIR__ . '/htaccess_instructions.txt');
    }
}
