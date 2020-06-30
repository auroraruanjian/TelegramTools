<?php

/** @noinspection PhpUnhandledExceptionInspection */
namespace Amp\Test\Artax\Cookie;

use Amp\Http\Client\Cookie\CookieJar;
use Amp\Http\Client\Cookie\CookieJarTest;
use Amp\Http\Client\Cookie\InMemoryCookieJar;
class InMemoryCookieJarTest extends CookieJarTest
{
    protected function createJar() : CookieJar
    {
        return new InMemoryCookieJar();
    }
}