<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Models\Content;

use RuntimeException;

abstract class AbstractContent
{
    public function __construct(protected string $content = '')
    {
    }

    public function __toString(): string
    {
        return $this->getContent();
    }

    public static function fromContent(string $content = ''): static
    {
        return new static($content);
    }

    public function isEmpty(): bool
    {
        return !$this->content;
    }

    public function getContent(): string
    {
        return htmlspecialchars($this->content, ENT_QUOTES);
    }
    
    public function raw(): string
    {
        return $this->content;
    }

    public function forJson(): string
    {
        // @TODO
        throw new RuntimeException('TODO');
    }

    public function forJavaScript(): string
    {
        // @TODO
        throw new RuntimeException('TODO');
    }

    public function forXml(): string
    {
        // @TODO
        throw new RuntimeException('TODO');
    }
}
