<?php

namespace Alexeyplodenko\Sitecode\Exceptions\Models\Page;

use Alexeyplodenko\Sitecode\Exceptions\Exception;
use Throwable;

class CacheDisabled extends Exception
{
    public function __construct(string $message = 'Cache is disabled.', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
