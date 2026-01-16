<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Services;

use Alexeyplodenko\Sitecode\Models\IncludeSectionResult;
use Alexeyplodenko\Sitecode\Models\InlineBladeResult;
use Alexeyplodenko\Sitecode\Models\Page;
use Alexeyplodenko\Sitecode\Models\PageFields;
use Illuminate\Support\Facades\Blade;
//use PhpParser\ParserFactory;
use RuntimeException;

class BladeView
{
    protected string $bladeViewContent;
    protected string $view;
    protected string $basePath;
    protected string $viewAsPath;
    protected bool $fileExists;
    protected bool $fileLoaded = false;

    public static function fromView(string $view): static
    {
        $self = new static();
        $self->setView($view);

        return $self;
    }

    public function getView(): string
    {
        return $this->view;
    }

    public function setView(string $view): static
    {
        $this->view = $view;

        return $this;
    }

    public function getPageFields(?Page $page = null): PageFields
    {
        if (!$this->isFileExist()) {
            return new PageFields();
        }

        $basePath = $this->getBasePath();

        $viewAdmin = BladeViewAdmin::fromView($this->view);
        $viewAdmin->setBasePath($basePath);
        $pageFields = $viewAdmin->getAdminFields($page);

        $inlineViewRes = BladeView::inlineBlade($basePath, $this->getBladeViewContent());
        
        foreach ($inlineViewRes->embeddedViews[2] as $view) {
            $viewAdmin = BladeViewAdmin::fromView($view);
            $viewAdmin->setBasePath($basePath);

            $adminFields = $viewAdmin->getAdminFields($page);

            $pageFields->concat($adminFields);
        }

        return $pageFields;
    }

    public function isFileExist(): bool
    {
        if (!isset($this->fileExists)) {
            $viewFilePath = $this->getFilePath();
            $this->fileExists = is_file($viewFilePath);
        }

        return $this->fileExists;
    }

    public function setBasePath(string $basePath): static
    {
        $this->basePath = $basePath;

        return $this;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function getFilePath(): string
    {
        if (!isset($this->basePath)) {
            throw new RuntimeException('Base path is not set. Set it with BladeView->setBasePath() method.');
        }

        $viewPath = $this->getViewAsPath();
        return "{$this->basePath}/{$viewPath}.blade.php";
    }

    public function loadFile(): static
    {
        if (!$this->fileLoaded) {
            $filePath = $this->getFilePath();
            $this->bladeViewContent = file_get_contents($filePath);
        }

        return $this;
    }

    public function getBladeViewContent(): string
    {
        $this->loadFile();
        return $this->bladeViewContent;
    }

    public function getPhpFragments(): array
    {
        $phpCode = Blade::compileString($this->bladeViewContent);
        return $this->extractPhpFragments($phpCode);
    }

    public function getContentTitles(): array
    {
        $phpFragments = $this->getPhpFragments();
        $phpCode = implode("\n", $phpFragments);

        // AST parsing the best. nikic/php-parser package can be utilized for that. But let's go the easy way for now
//        $phpParser = (new ParserFactory())->createForHostVersion();
//        foreach ($phpFragments as $phpFragment) {
//            $ast = $phpParser->parse($phpFragment);
//            // ...
//        }

        preg_match_all('/->getContent\([\'"]([^\'|"]+)[\'"]\)/', $phpCode, $titles);
        return $titles[1];
    }

    /**
     * This method would take the given Blade content ($bladeContent) and replace all @extends and @include
     * instructions with the file contents, to have a single large Blade file.
     */
    public static function inlineBlade(string $viewBasePath,
                                       string $bladeContent,
                                       array $embeddedViews = [[], [], []]): InlineBladeResult
    {
        // @TODO Output @section('content') into @yield('content'), and not just anywhere as now, to keep the order of the fields correct
        
        $includeRes = static::includeSections($viewBasePath, $bladeContent, ['extends', 'include']);
        
        $embeddedViews[0] = array_merge($embeddedViews[0], $includeRes->foundSections[0]);
        $embeddedViews[1] = array_merge($embeddedViews[1], $includeRes->foundSections[1]);
        $embeddedViews[2] = array_merge($embeddedViews[2], $includeRes->foundSections[2]);

        $bladeContent = $includeRes->bladeContent;

        if ($includeRes->foundSections[0]) {
            return static::inlineBlade($viewBasePath, $bladeContent, $embeddedViews);
        }

        return new InlineBladeResult($bladeContent, $embeddedViews);
    }

    protected function getViewAsPath(): string
    {
        if (!isset($this->viewAsPath)) {
            $this->viewAsPath = str_replace('.', '/', $this->view);
        }
        return $this->viewAsPath;
    }

    /**
     * @param string $viewBasePath
     * @param string $bladeContent
     * @param string[] $sections
     * @return IncludeSectionResult
     */
    protected static function includeSections(string $viewBasePath,
                                              string $bladeContent,
                                              array $sections): IncludeSectionResult
    {
        assert($sections, '$sections should not be empty.');
        
        $sectionsRegEx = '('. implode('|', $sections) . ')';
        preg_match_all("/@$sectionsRegEx\('([^)]+)'\)/", $bladeContent, $foundSections);

        foreach ($foundSections[0] as $i => $includesCode) {
            $path = DotNotationString::fromString($foundSections[2][$i])->toPath('.blade.php');
            $codeToInline = file_get_contents("$viewBasePath/$path");

            $bladeContent = str_replace($includesCode, $codeToInline, $bladeContent);
        }

        return new IncludeSectionResult($bladeContent, $foundSections);
    }

    protected function extractPhpFragments(string $code, $maxBytes = 1024 * 1024): array
    {
        $res = [];
        $i = 0;
        $open = '<?';
        $openFull = $open .'php';
        $openEcho = '<?=';
        $close = '?>';

        while (true) {
            $start = strpos($code, $open, $i);
            if ($start === false) {
                break;
            }

            $afterStart = substr($code, $start + 2, 3);
            if (strncasecmp($afterStart, 'xml', 3) === 0) {
                $i = $start + 2;
                continue;
            }

            $openLen = 2;
            if (strncasecmp(substr($code, $start, 5), $openFull, 5) === 0) {
                $openLen = 5;
            } elseif (substr($code, $start, 3) === $openEcho) {
                $openLen = 3;
            }

            $closePos = strpos($code, $close, $start + $openLen);
            if ($closePos === false) {
                break;
            }

            $fragment = substr($code, $start, ($closePos - $start) + 2);
            $res[] = $fragment;

            $i = $closePos + 2;

            if ($i >= $maxBytes) {
                throw new RuntimeException("$maxBytes were processed. Breaking to prevent an infinite loop.");
            }
        }

        return $res;
    }
}
