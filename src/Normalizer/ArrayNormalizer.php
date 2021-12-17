<?php

namespace Terrazza\Component\Serializer\Normalizer;

use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use Terrazza\Component\Serializer\Annotation\IAnnotationFactory;
use Terrazza\Component\Serializer\INameConverter;
use Terrazza\Component\Serializer\INormalizer;
use Terrazza\Component\Serializer\TraceKeyTrait;
use Throwable;

class ArrayNormalizer implements INormalizer {
    use TraceKeyTrait;
    private LoggerInterface $logger;
    private IAnnotationFactory $annotationFactory;
    private array $nameConverter;

    public function __construct(LoggerInterface $logger, IAnnotationFactory $annotationFactory, array $nameConverter=null) {
        $this->logger                               = $logger;
        $this->annotationFactory                    = $annotationFactory;
        $this->nameConverter                        = $nameConverter ?? [];
    }

    /**
     * @param array $nameConverter
     * @return INormalizer
     */
    public function withNameConverter(array $nameConverter) : INormalizer {
        $normalizer                                 = clone $this;
        $normalizer->nameConverter                  = $nameConverter;
        return $normalizer;
    }

    /**
     * @param object $object
     * @return array
     * @throws ReflectionException
     */
    public function normalize(object $object) : array {
        $values                                     = [];
        foreach ($this->getAttributes($object) as $attributeName => $attributeValid) {
            if ($attributeValid === true) {
                $this->pushTraceKey($attributeName);
                $values[$attributeName]             = $this->getAttributeValue($object, $attributeName);
                $this->popTraceKey();
            }
        }
        return $values;
    }

    /**
     * @param object $object
     * @param string $attributeName
     * @return mixed
     * @throws ReflectionException
     */
    private function getAttributeValue(object $object, string $attributeName) {
        $logMethod                                  = __METHOD__."()";
        $refClass                                   = new ReflectionClass($object);
        $this->logger->debug("$logMethod $attributeName in class ".$refClass->getName(),
            ["line" => __LINE__]);
        $refProperty                                = $refClass->getProperty($attributeName);
        $property                                   = $this->annotationFactory->getAnnotationProperty($refProperty);
        $refProperty->setAccessible(true);
        if ($refProperty->isInitialized($object)) {
            $this->logger->debug("$logMethod property", [
                "line"              => __LINE__,
                "name"              => $property->getName(),
                "isArray"           => $property->isArray(),
                "isBuiltIn"         => $property->isBuiltIn(),
                "type"              => $property->getType(),
            ]);
            $attributeValue                         = $refProperty->getValue($object);
            if (is_null($attributeValue)) {
                return null;
            }
            if ($property->isBuiltIn()) {
                return $attributeValue;
            } elseif ($propertyTypeClass = $property->getType()) {
                /** @var class-string $propertyTypeClass */
                if ($property->isArray()) {
                    $attributeValues                = [];
                    foreach ($attributeValue as $singleAttributeValue) {
                        $attributeValues[]          = $this->getAttributeValueByTypeClass($propertyTypeClass, $singleAttributeValue);
                    }
                    return $attributeValues;
                } else {
                    return $this->getAttributeValueByTypeClass($propertyTypeClass, $attributeValue);
                }
            } else {
                throw new RuntimeException($this->getTraceKeys()." propertyValue for propertyType cannot be resolved");
            }
        } else {
            throw new RuntimeException($this->getTraceKeys()." property is not initialized");
        }
    }

    /**
     * @psalm-suppress RedundantConditionGivenDocblockType
     * @param class-string $propertyTypeClass
     * @param mixed $attributeValue
     * @return mixed
     * @throws ReflectionException
     */
    private function getAttributeValueByTypeClass(string $propertyTypeClass, $attributeValue) {
        $logMethod                                  = __METHOD__."()";
        if ($nameConverterClass = $this->getNameConverterClass($propertyTypeClass)) {
            $this->logger->debug("$logMethod nameConverterClass for property found",
                ["line" => __LINE__, 'className' => $propertyTypeClass]);
            if (class_exists($nameConverterClass)) {
                $converter                          = new ReflectionClass($nameConverterClass);
                if ($converter->implementsInterface(INameConverter::class)) {
                    /** @var INameConverter $convertClass */
                    $convertClass                   = $converter->newInstance($attributeValue);
                    try {
                        return $convertClass->getValue();
                    } catch (Throwable $exception) {
                        $errorCode                  = (int)$exception->getCode();
                        throw new RuntimeException("getValue() for nameConvertClass $propertyTypeClass failure: " . $exception->getMessage(), $errorCode, $exception);
                    }
                } else {
                    throw new RuntimeException("$nameConverterClass does not implement " . INameConverter::class);
                }
            } else {
                throw new RuntimeException("$nameConverterClass does not exists");
            }
        } else {
            $this->logger->debug("$logMethod nameConverterClass for property not found",
                ["line" => __LINE__, 'className' => $propertyTypeClass]);
            return $this->normalize($attributeValue);
        }
    }

    /**
     * @param class-string $fromType
     * @return class-string|null
     * @throws ReflectionException
     */
    private function getNameConverterClass(string $fromType) :?string {
        $logMethod                                  = __METHOD__."()";
        if (array_key_exists($fromType, $this->nameConverter)) {
            $this->logger->debug("$logMethod nameConverter for $fromType found",
                ["line" => __LINE__]);
            return $this->nameConverter[$fromType];
        } else {
            $this->logger->debug("$logMethod no nameConverter for $fromType",
                ["line" => __LINE__]);
            $converter                              = new ReflectionClass($fromType);
            if ($parentClass = $converter->getParentClass()) {
                return $this->getNameConverterClass($parentClass->getName());
            } else {
                return null;
            }
        }
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    private function _str_starts_with(string $haystack, string $needle) : bool {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    /**
     * @param object $object
     * @return array
     */
    private function getAttributes(object $object) : array {
        $logMethod                                  = __METHOD__."()";
        /*
        if (stdClass::class === get_class($object)) {
            return array_keys((array) $object);
        }
        */
        $attributes                                 = [];
        $refClass                                   = new ReflectionClass($object);
        foreach ($refClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName                             = $method->getName();
            if ($method->isStatic() ||
                $method->isConstructor() ||
                $method->isDestructor()
            ) {
                $this->logger->debug("$logMethod skip method $methodName", ["line" => __LINE__]);
                continue;
            }
            $attributeName                          = null;
            if ($this->_str_starts_with($methodName, $needle = 'get')) {
                $attributeName                      = substr($methodName, 3);
                $this->logger->debug("$logMethod method $methodName starts with $needle",
                    ["line" => __LINE__]);
            }
            elseif ($this->_str_starts_with($methodName, $needle = 'has')) {
                $attributeName                      = substr($methodName, 3);
                $this->logger->debug("$logMethod method $methodName starts with $needle",
                    ["line" => __LINE__]);
            } elseif ($this->_str_starts_with($methodName, $needle = 'is')) {
                $attributeName                      = substr($methodName, 2);
                $this->logger->debug("$logMethod method $methodName starts with $needle",
                    ["line" => __LINE__]);
            }
            if ($attributeName !== null) {
                $attributeName                      = lcfirst($attributeName);
                if ($refClass->hasProperty($attributeName)) {
                    $attributes[$attributeName]     = true;
                }
            }
        }

        foreach ($refClass->getProperties() as $property) {
            $propertyName                           = $property->getName();
            if (array_key_exists($propertyName, $attributes)) {
                $this->logger->debug("$logMethod skip property $propertyName, already found as method",
                    ["line" => __LINE__]);
                continue;
            }
            if (!$property->isPublic()) {
                $this->logger->debug("$logMethod skip property $propertyName, is not public",
                    ["line" => __LINE__]);
                continue;
            }
            if ($property->isStatic()) {
                $this->logger->debug("$logMethod skip property $propertyName, is static",
                    ["line" => __LINE__]);
                continue;
            }
            $attributeName                          = $property->getName();
            $attributes[$attributeName]             = true;
        }

        return $attributes;
    }
}