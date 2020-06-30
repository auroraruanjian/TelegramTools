<?php

// This implementation doesn't do any validation and just splits at the first colon in each line.
function parse(string $rawHeaders) : array
{
    $lines = \explode("\r\n", $rawHeaders);
    $headers = [];
    foreach ($lines as $line) {
        if (empty($line)) {
            break;
        }
        if (\strpos($line, ':') !== false) {
            $parts = \explode(':', $line, 2);
            $headers[\strtolower($parts[0])][] = \trim($parts[1] ?? '');
        }
    }
    return $headers;
}
$rawHeaders = "Server: Microsoft-IIS/5.0\nDate: Tue, 31 Oct 2006 08:00:29 GMT\nConnection: close\nAllow: GET, HEAD, POST, TRACE, OPTIONS\nContent-Length: 0\nX-No-Value:\nX-No-Whitespace: Test\nX-Trailing-Whitespace:  \tFoobar\t\t  \n";
$start = \microtime(true);
for ($i = 0; $i < 300000; $i++) {
    parse($rawHeaders);
}
print \microtime(true) - $start . PHP_EOL;