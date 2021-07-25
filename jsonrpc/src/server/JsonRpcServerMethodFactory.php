<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

use Exception;
use kuiper\reflection\exception\ClassNotFoundException;
use kuiper\reflection\ReflectionDocBlockFactoryInterface;
use kuiper\reflection\ReflectionType;
use kuiper\reflection\ReflectionTypeInterface;
use kuiper\reflection\type\VoidType;
use kuiper\rpc\exception\InvalidMethodException;
use kuiper\rpc\RpcMethod;
use kuiper\rpc\RpcMethodFactoryInterface;
use kuiper\rpc\RpcMethodInterface;
use kuiper\serializer\NormalizerInterface;
use ReflectionException;
use ReflectionMethod;

class JsonRpcServerMethodFactory implements RpcMethodFactoryInterface
{
    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * @var ReflectionDocBlockFactoryInterface
     */
    private $reflectionDocBlockFactory;

    /**
     * @var array
     */
    private $cachedTypes;
    /**
     * @var array
     */
    private $services;

    /**
     * JsonRpcSerializerResponseFactory constructor.
     */
    public function __construct(array $services, NormalizerInterface $normalizer, ReflectionDocBlockFactoryInterface $reflectionDocBlockFactory)
    {
        $this->services = $services;
        $this->normalizer = $normalizer;
        $this->reflectionDocBlockFactory = $reflectionDocBlockFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function create($service, string $method, array $args): RpcMethodInterface
    {
        $serviceName = $service;
        if (!isset($this->services[$service])) {
            $service = str_replace('.', '\\', $service);
        }
        if (!isset($this->services[$service])) {
            throw new InvalidMethodException("jsonrpc method $service.$method not found");
        }
        $target = $this->services[$service];
        try {
            $arguments = $this->resolveParams($target, $method, $args);
        } catch (Exception $e) {
            throw new InvalidMethodException("create method $service.$method parameters fail: ".$e->getMessage());
        }

        return new RpcMethod($target, $serviceName, $method, $arguments);
    }

    /**
     * @throws ReflectionException
     * @throws ClassNotFoundException
     */
    private function resolveParams(object $target, string $methodName, array $params): array
    {
        $paramTypes = $this->getParameterTypes($target, $methodName);
        $ret = [];
        foreach ($paramTypes as $i => $type) {
            $ret[] = $this->normalizer->denormalize($params[$i], $type);
        }

        return $ret;
    }

    /**
     * @throws ReflectionException
     * @throws ClassNotFoundException
     */
    private function getParameterTypes(object $target, string $methodName): array
    {
        $key = get_class($target).'::'.$methodName;
        if (isset($this->cachedTypes[$key])) {
            return $this->cachedTypes[$key];
        }

        $reflectionMethod = new ReflectionMethod($target, $methodName);
        $docParamTypes = $this->reflectionDocBlockFactory->createMethodDocBlock($reflectionMethod)->getParameterTypes();
        $paramTypes = [];
        foreach ($reflectionMethod->getParameters() as $i => $parameter) {
            $paramTypes[] = $this->createType($parameter->getType(), $docParamTypes[$parameter->getName()]);
        }

        return $this->cachedTypes[$key] = $paramTypes;
    }

    private function createType(?\ReflectionType $type, ReflectionTypeInterface $docType): ?ReflectionTypeInterface
    {
        if (null === $type && $docType instanceof VoidType) {
            return null;
        }
        if (null === $type) {
            return $docType;
        }
        $reflectionType = ReflectionType::fromPhpType($type);
        if ($reflectionType->isUnknown()) {
            return $docType;
        }

        return $reflectionType;
    }
}