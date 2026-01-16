<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Enums;

Enum ContentEditor: int
{
    case TextInput = 1;
    case Textarea = 2;
    case WYSIWYG = 3;
    case File = 4;
}
