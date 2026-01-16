<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Services;

class DotNotationString
{
    protected string $string;

    public static function fromString(string $string): static
    {
        $instance = new static();
        $instance->setString($string);
        return $instance;
    }

    public function getString(): string
    {
        return $this->string;
    }

    public function setString(string $string): static
    {
        $this->string = $string;

        return $this;
    }

    public function toPath(?string $ext = null): string
    {
        return str_replace('.', '/', $this->string) . $ext;
    }
}
