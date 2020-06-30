<?php

namespace Amp\Http\Client\Internal;

/** @internal */
trait ForbidCloning
{
    protected final function __clone()
    {
        // clone is automatically denied to all external calls
        // final protected instead of private to also force denial for all children
    }
}