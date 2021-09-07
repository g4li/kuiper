<?php

declare(strict_types=1);

namespace kuiper\rpc\server;

use kuiper\rpc\ServiceLocator;
use kuiper\swoole\ServerPort;

class Service
{
    /**
     * @var ServiceLocator
     */
    private $serviceLocator;

    /**
     * @var object
     */
    private $service;

    /**
     * @var string[]
     */
    private $methods;

    /**
     * @var ServerPort
     */
    private $serverPort;

    /**
     * @var int
     */
    private $weight;

    /**
     * ServiceObject constructor.
     *
     * @param string   $serviceName
     * @param string   $version
     * @param object   $service
     * @param string[] $methods
     */
    public function __construct(
        ServiceLocator $serviceLocator, object $service, array $methods,
        ServerPort $serverPort, int $weight = 100)
    {
        $this->serviceLocator = $serviceLocator;
        $this->service = $service;
        $this->methods = $methods;
        $this->serverPort = $serverPort;
        $this->weight = $weight;
    }

    /**
     * @return ServiceLocator
     */
    public function getServiceLocator(): ServiceLocator
    {
        return $this->serviceLocator;
    }

    /**
     * @return string
     */
    public function getServiceName(): string
    {
        return $this->serviceLocator->getName();
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->serviceLocator->getVersion();
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->serviceLocator->getNamespace();
    }

    /**
     * @return object
     */
    public function getService(): object
    {
        return $this->service;
    }

    /**
     * @return string[]
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    public function hasMethod(string $method): bool
    {
        return in_array($method, $this->methods, true);
    }

    /**
     * @return ServerPort
     */
    public function getServerPort(): ServerPort
    {
        return $this->serverPort;
    }

    /**
     * @return int
     */
    public function getWeight(): int
    {
        return $this->weight;
    }
}
