<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Models\Content;

use RuntimeException;

abstract class AbstractContent
{
    public function __construct(protected string|bool $content = '')
    {
    }

    public function __toString(): string
    {
        return $this->getContent();
    }

    public static function fromContent(string|bool $content = ''): static
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

    public function raw(): mixed
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
