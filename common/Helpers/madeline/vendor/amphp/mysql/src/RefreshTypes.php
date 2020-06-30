<?php

namespace Amp\Mysql;

final class RefreshTypes
{
    const REFRESH_GRANT = 0x1;
    const REFRESH_LOG = 0x2;
    const REFRESH_TABLES = 0x4;
    const REFRESH_HOSTS = 0x8;
    const REFRESH_STATUS = 0x10;
    const REFRESH_THREADS = 0x20;
    const REFRESH_SLAVE = 0x40;
    const REFRESH_MASTER = 0x80;
}