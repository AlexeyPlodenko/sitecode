<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Services\DataSerializers;

interface DataSerializer
{
    public function serialize(mixed $arg): string;
}
