<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Models\Content;

class HtmlAbstractContent extends AbstractContent
{
    protected string $decoratedContent;
    protected string $wrapCssClass = 'cms';
    protected string $wrapHtmlTag = 'span';

    public function getContent(): string
    {
        if (!isset($this->decoratedContent)) {
            $this->decoratedContent = "<$this->wrapHtmlTag class=\"$this->wrapCssClass\">$this->content</$this->wrapHtmlTag>";
        }

        return $this->decoratedContent;
    }
}
