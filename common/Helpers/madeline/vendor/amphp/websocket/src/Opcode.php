<?php

namespace Amp\Websocket;

final class Opcode
{
    const CONT = 0x0;
    const TEXT = 0x1;
    const BIN = 0x2;
    const CLOSE = 0x8;
    const PING = 0x9;
    const PONG = 0xa;
    /**
     * @codeCoverageIgnore Class cannot be instigated.
     */
    private function __construct()
    {
        // forbid instances
    }
}