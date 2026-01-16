<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Services;

use Alexeyplodenko\Sitecode\Models\Page;
use Alexeyplodenko\Sitecode\Models\PageFields;
use RuntimeException;

class BladeViewAdmin extends BladeView
{
    protected PageFields $adminFields;

    public static function fromBladeView(BladeView $bladeView): static
    {
        $bladeViewAdmin = new static();
        if ($bladeView->getView()) {
            $bladeViewAdmin->setView($bladeView->getView());
        }
        if ($bladeView->getBasePath()) {
            $bladeViewAdmin->setBasePath($bladeView->getBasePath());
        }

        return $bladeViewAdmin;
    }

    public function getFilePath(): string
    {
        if (!isset($this->basePath)) {
            throw new RuntimeException('Base path is not set. Set it with BladeViewAdmin->setBasePath() method.');
        }

        $viewPath = $this->getViewAsPath();
        return "{$this->basePath}/{$viewPath}.admin.php";
    }

    public function loadFile(?Page $page = null): static
    {
        if (!$this->fileLoaded) {
            $filePath = $this->getFilePath();
            $this->adminFields = $this->isFileExist() ? include($filePath) : new PageFields();
        }

        return $this;
    }

    public function getAdminFields(?Page $page = null): PageFields
    {
        $this->loadFile($page);
        return $this->adminFields;
    }
}
