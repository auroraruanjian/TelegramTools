<?php

use Amp\Http\Rfc7230;
require __DIR__ . "/../../vendor/autoload.php";
$rawHeaders = "Server: Microsoft-IIS/5.0\nDate: Tue, 31 Oct 2006 08:00:29 GMT\nConnection: close\nAllow: GET, HEAD, POST, TRACE, OPTIONS\nContent-Length: 0\nX-No-Value:\nX-No-Whitespace: Test\nX-Trailing-Whitespace:  \tFoobar\t\t  \n";
// Normalize line endings, which might be broken by Git otherwise
$rawHeaders = \str_replace("\n", "\r\n", \str_replace("\r\n", "\n", $rawHeaders));
$start = \microtime(true);
for ($i = 0; $i < 300000; $i++) {
    Rfc7230::parseHeaders($rawHeaders);
}
print \microtime(true) - $start . PHP_EOL;