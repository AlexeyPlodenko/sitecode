<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Services\DataSerializers;

use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

/**
 * Use this serializer, when PrintRDataSerializer throws or runs out of memory.
 */
class VarDumperDataSerializer implements DataSerializer
{
    protected static VarCloner $cloner;
    protected static CliDumper $dumper;

    public function serialize(mixed $arg): string
    {
        $var = static::getVarCloner()->cloneVar($arg);
        return static::getCliDumper()->dump($var, true);
    }

    protected static function getVarCloner(): VarCloner
    {
        if (!isset(static::$cloner)) {
            static::$cloner = new VarCloner();
            static::$cloner->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);
        }

        return static::$cloner;
    }

    protected static function getCliDumper(): CliDumper
    {
        if (!isset(static::$dumper)) {
            static::$dumper = new CliDumper();
        }

        return static::$dumper;
    }
}
