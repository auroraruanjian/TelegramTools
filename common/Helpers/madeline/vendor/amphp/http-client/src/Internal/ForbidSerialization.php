<?php

namespace Amp\Http\Client\Internal;

/** @internal */
trait ForbidSerialization
{
    public final function __sleep()
    {
        throw new \Error(__CLASS__ . ' does not support serialization');
    }
}