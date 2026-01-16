<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Services\DataSerializers;

/**
 * The simplest and fastest structured variable serializer.
 * Might cause closure and out of memory issues. Then use VarDumperDataSerializer.
 */
class PrintRDataSerializer implements DataSerializer
{
    public function serialize(mixed $arg): string
    {
        return print_r($arg, true);
    }
}
