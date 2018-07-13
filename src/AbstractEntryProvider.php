<?php

namespace Simply\Container;

/**
 * Abstract entry provider that provides the most basic provider initialization functionality.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
abstract class AbstractEntryProvider implements EntryProvider
{
    /**
     * Returns a new instance of the entry provider.
     * @return EntryProvider New entry provider instance initialized with default constructor
     */
    public static function initialize(): EntryProvider
    {
        return new static();
    }

    /**
     * Returns provided list of all classes returned by the container methods.
     * @return string[] Provided list of all classes returned by the container methods
     */
    public function getMethods(): array
    {
        $methods = [];
        $reflection = new \ReflectionClass($this);

        foreach ($reflection->getMethods() as $method) {
            $identifier = $this->getMethodIdentifier($method);

            if ($identifier !== null) {
                $methods[$identifier] = $method->getName();
            }
        }

        return $methods;
    }

    /**
     * Tells the identifier to use for the given provider method.
     * @param \ReflectionMethod $method The provider method
     * @return string|null The identifier for the method or null if not applicable
     */
    private function getMethodIdentifier(\ReflectionMethod $method): ?string
    {
        if (!$method->isPublic() || $method->isStatic()) {
            return null;
        }

        if (strncmp($method->getName(), '__', 2) === 0) {
            return null;
        }

        $type = $method->getReturnType();

        if ($type === null || $type->isBuiltin()) {
            return null;
        }

        return $type->getName();
    }
}
