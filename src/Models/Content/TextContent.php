<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Models\Content;

class TextContent extends AbstractContent
{
    public function getContent(): string
    {
        return $this->content;
    }

    public function raw(): string
    {
        return $this->content;
    }
}
