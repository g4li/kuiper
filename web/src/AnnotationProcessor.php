<?php

declare(strict_types=1);

namespace kuiper\web;

use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\annotation\Controller;
use kuiper\di\ComponentCollection;
use kuiper\web\annotation\filter\FilterInterface;
use kuiper\web\annotation\RequestMapping;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;
use Slim\Interfaces\RouteGroupInterface;
use Slim\Interfaces\RouteInterface;

class AnnotationProcessor
{
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var AnnotationReaderInterface
     */
    private $annotationReader;
    /**
     * @var RouteCollectorProxyInterface
     */
    private $routeCollector;

    public function __construct(ContainerInterface $container, AnnotationReaderInterface $annotationReader, RouteCollectorProxyInterface $routeCollector)
    {
        $this->container = $container;
        $this->annotationReader = $annotationReader;
        $this->routeCollector = $routeCollector;
    }

    public function process(): void
    {
        $seen = [];
        foreach (ComponentCollection::getAnnotations(Controller::class) as $annotation) {
            /** @var RequestMapping $requestMapping */
            /** @var Controller $annotation */
            $controllerClass = $annotation->getTarget();
            if (isset($seen[$controllerClass->getName()])) {
                continue;
            }
            $seen[$controllerClass->getName()] = true;
            $requestMapping = $this->annotationReader->getClassAnnotation($controllerClass, RequestMapping::class);
            if ($requestMapping) {
                $routeGroup = $this->routeCollector->group($requestMapping->value, function (RouteCollectorProxyInterface $group) use ($controllerClass) {
                    $this->addMapping($group, $controllerClass);
                });
                $this->addFilters($routeGroup, $this->annotationReader->getClassAnnotations($controllerClass));
            } else {
                $this->addMapping($this->routeCollector, $controllerClass);
            }
        }
    }

    private function addMapping(RouteCollectorProxyInterface $routeCollector, \ReflectionClass $controllerClass): void
    {
        $controller = $this->container->get($controllerClass->getName());
        foreach ($controllerClass->getMethods() as $reflectionMethod) {
            if ($reflectionMethod->isPublic() && !$reflectionMethod->isStatic()) {
                /** @var RequestMapping $mapping */
                $mapping = $this->annotationReader->getMethodAnnotation($reflectionMethod, RequestMapping::class);
                if ($mapping) {
                    $route = $routeCollector->map($mapping->method, $mapping->value, [$controller, $reflectionMethod->getName()]);
                    if ($mapping->name) {
                        $route->setName($mapping->name);
                    }
                    $this->addFilters($route, $this->annotationReader->getMethodAnnotations($reflectionMethod));
                }
            }
        }
    }

    /**
     * @param RouteGroupInterface|RouteInterface $route
     */
    private function addFilters($route, array $annotations): void
    {
        /** @var FilterInterface[] $filters */
        $filters = [];
        foreach ($annotations as $annotation) {
            if ($annotation instanceof FilterInterface) {
                $filters[] = $annotation;
            }
        }
        if (!empty($filters)) {
            usort($filters, static function (FilterInterface $a, FilterInterface $b) {
                return $a->getPriority() - $b->getPriority();
            });
            foreach ($filters as $filter) {
                $route->add($filter->createMiddleware($this->container));
            }
        }
    }
}
