<?php

namespace Simply\Container\Entry;

use Psr\Container\ContainerInterface;
use Simply\Container\Exception\ContainerException;

/**
 * Container entry that loads the value using a separate entry provider.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ProviderEntry implements EntryInterface
{
    /** @var string The provider class used to load the value */
    private $provider;

    /** @var string The method called for the provider class to load the value */
    private $method;

    /**
     * ProviderEntry constructor.
     * @param callable $providerMethod The provider callable provided as an array
     */
    public function __construct(callable $providerMethod)
    {
        if (!\is_array($providerMethod)) {
            throw new \InvalidArgumentException('The provider entry callable must be provided as an array');
        }

        [$provider, $method] = $providerMethod;

        $this->setProvider($provider);
        $this->setMethod($method);
    }

    /**
     * Sets the provider class based on the given provider instance.
     * @param object $provider The provider instance
     */
    private function setProvider(object $provider): void
    {
        $this->provider = \get_class($provider);
    }

    /**
     * Sets the method to call for the provider instance.
     * @param string $method The method to call for the provider
     */
    private function setMethod(string $method): void
    {
        $this->method = $method;
    }

    /** {@inheritdoc} */
    public static function createFromCacheParameters(array $parameters): EntryInterface
    {
        /** @var ProviderEntry $entry */
        $entry = (new \ReflectionClass(static::class))->newInstanceWithoutConstructor();

        [
            $entry->provider,
            $entry->method,
        ] = $parameters;

        return $entry;
    }

    /** {@inheritdoc} */
    public function getCacheParameters(): array
    {
        return [
            $this->provider,
            $this->method,
        ];
    }

    /** {@inheritdoc} */
    public function isFactory(): bool
    {
        return false;
    }

    /** {@inheritdoc} */
    public function getValue(ContainerInterface $container)
    {
        $provider = $container->get($this->provider);

        if (!\is_object($provider)) {
            throw new ContainerException('Unexpected value returned for the provider object by the container');
        }

        return $provider->{$this->method}($container);
    }
}
