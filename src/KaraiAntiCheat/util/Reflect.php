<?php

namespace KaraiAntiCheat\util;

use pocketmine\Server;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class Reflect
{
    static private array $propCache = [];
    static private array $methCache = [];

    /**
     * @param object $instance
     * @param string $key
     * @param mixed $value
     * @param string|null $className
     * @return void
     */
    static public function put(object $instance, string $key, mixed $value, string $className = null): void
    {
        if (is_null($className)) $className = $instance::class;

        $refProp = self::getProperty($className, $key);
        $refProp?->setValue($instance, $value);
    }

    /**
     * @param object $instance
     * @param string $key
     * @param string|null $className
     * @return mixed
     */
    public static function tryGet(object $instance, string $key, string $className = null): mixed
    {
        if (is_null($className)) $className = $instance::class;

        $refProp = self::getProperty($className, $key);
        return $refProp?->getValue($instance);
    }

    /**
     * @param string $className
     * @param string $key
     * @return ReflectionProperty|null
     */
    static private function getProperty(string $className, string $key): ?ReflectionProperty
    {
        $cacheKey = "$className&$key";
        if (!isset(self::$propCache[$cacheKey])) {
            try {
                $refClass = new ReflectionClass($className);
                $refProp = $refClass->getProperty($key);
                $refProp->setAccessible(true);
                self::$propCache[$cacheKey] = $refProp;
            } catch (\Exception $e) {
                Server::getInstance()->getLogger()->debug(
                    sprintf(
                        "[Reflect::getProperty] Reflection failed for property '%s' in class '%s'. Error: %s",
                        $key,
                        $className,
                        $e->getMessage()
                    )
                );
                return null;
            }
        }

        return self::$propCache[$cacheKey] ?? null;
    }

    /**
     * @param string $className
     * @param string $methodName
     * @return ReflectionMethod|null
     */
    static public function func(string $className, string $methodName): ?ReflectionMethod
    {
        return self::getMethod($className, $methodName) ?? null;
    }

    /**
     * @param string $className
     * @param string $methodName
     * @return ReflectionMethod|null
     */
    static private function getMethod(string $className, string $methodName): ?ReflectionMethod
    {
        $cacheKey = "$className&$methodName";

        // Check if the method is cached
        if (!isset(self::$methCache[$cacheKey])) {
            try {
                $refClass = new ReflectionClass($className);
                $refMeth = $refClass->getMethod($methodName);
                $refMeth->setAccessible(true);
                self::$methCache[$cacheKey] = $refMeth;
            } catch (\Exception $e) {
                Server::getInstance()->getLogger()->debug(
                    sprintf(
                        "[Reflect::getMethod] Reflection failed for method '%s' in class '%s'. Error: %s",
                        $methodName,
                        $className,
                        $e->getMessage()
                    )
                );
                return null;
            }
        }

        return self::$methCache[$cacheKey] ?? null;
    }

    private static function safeExport(mixed $value): string {
        try {
            return var_export($value, true);
        } catch (\Throwable $e) {
            return json_encode($value, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: 'unserializable';
        }
    }
}
