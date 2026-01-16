<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Services;

/**
 * @author https://stackoverflow.com/a/17486315
 */
class CachedDecorator
{
    protected array $cache = [];

    public function __construct(protected object $instance, protected CallSignatureBuilder $callSignatureBuilder)
    {
    }

    public function __call(string $method, array $args)
    {
        $callSignature = $this->makeCallSignature($method, $args);

        if ($this->hasCached($callSignature)) {
            return $this->getCached($callSignature);
        }

        return $this->callMethod($method, $args, $callSignature);
    }

    protected function makeCallSignature(string $method, array $args): string
    {
        return $method .':'. $this->callSignatureBuilder->buildSignature($method, $args);
    }

    protected function hasCached(string $callSignature): bool
    {
        return array_key_exists($callSignature, $this->cache);
    }

    protected function getCached(string $callSignature): mixed
    {
        return $this->cache[$callSignature] ?? null;
    }

    protected function callMethod(string $method, array $args, string $callSignature): mixed
    {
        $this->cache[$callSignature] = call_user_func_array([$this->instance, $method], $args);
        return $this->cache[$callSignature];
    }
}
