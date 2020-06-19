<?php

declare(strict_types=1);

namespace kuiper\di\annotation;

use kuiper\di\ComponentCollection;
use ReflectionClass;

trait ComponentTrait
{
    /**
     * @var ReflectionClass
     */
    protected $class;

    /**
     * @var string
     */
    protected $componentId;

    public function setTarget($class): void
    {
        $this->class = $class;
    }

    public function getTarget(): ReflectionClass
    {
        return $this->class;
    }

    public function setComponentId(string $componentId): string
    {
        return $this->componentId;
    }

    public function getComponentId(): string
    {
        return $this->componentId ?? $this->class->getName();
    }

    public function handle(): void
    {
        ComponentCollection::register($this->getComponentId(), $this);
    }
}
