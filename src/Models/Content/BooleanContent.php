<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Models\Content;

class BooleanContent extends AbstractContent
{
    protected string $trueValue = 'true';
    protected string $falseValue = 'false';

    public function setTrueValue(string $value): static
    {
        $this->trueValue = $value;

        return $this;
    }

    public function setFalseValue(string $value): static
    {
        $this->falseValue = $value;

        return $this;
    }

    public function isTrue(): bool
    {
        return $this->content === true;
    }

    public function isFalse(): bool
    {
        return $this->content === false;
    }

    public function getContent(): string
    {
        return $this->content ? $this->trueValue : $this->falseValue;
    }

    public function raw(): bool
    {
        return $this->content;
    }
}
