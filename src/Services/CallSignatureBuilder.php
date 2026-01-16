<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Services;

use Alexeyplodenko\Sitecode\Services\DataSerializers\DataSerializer;

class CallSignatureBuilder
{
    protected array $builders = [];

    public function __construct(protected DataSerializer $defaultBuilder,
                                protected array          $builderWhenArgInstance = [])
    {
    }

    public function registerBuilder(string $name, DataSerializer $builder): void
    {
        $this->builders[$name] = $builder;
    }

    public function buildSignature(string $method, array $args): string
    {
        $signature = "$method:";

        foreach ($args as $arg) {
            $builderFound = false;
            if (is_object($arg)) {
                foreach ($this->builderWhenArgInstance as $case) {
                    if ($arg instanceof $case[0]) {
                        $builder = $this->getBuilderInstanceByName($case[1]);
                        $signature .= $builder->serialize($arg) .':';
                        $builderFound = true;
                        break;
                    }
                }
            }

            if (!$builderFound) {
                $signature .= $this->defaultBuilder->serialize($arg) .':';
            }
        }

        return $signature;
    }

    protected function getBuilderInstanceByName(string $name): ?DataSerializer
    {
        return $this->builders[$name] ?? null;
    }
}
