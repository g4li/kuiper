<?php

namespace kuiper\reflection\filter;

use kuiper\reflection\TypeFilterInterface;

class NullTypeFilter implements TypeFilterInterface
{
    /**
     * checks whether the value is valid.
     *
     * @param mixed $value
     */
    public function isValid($value): bool
    {
        return null === $value;
    }

    /**
     * Sanitizes input value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function sanitize($value)
    {
        return null;
    }
}