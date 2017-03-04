<?php

namespace kuiper\web\annotation\filter;

use Interop\Container\ContainerInterface;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class PutOnly extends AbstractFilter
{
    /**
     * {@inheritdoc}
     */
    public function createMiddleware(ContainerInterface $container)
    {
        return new \kuiper\web\middlewares\PutOnly();
    }
}
