<?php

/** @noinspection PhpUnhandledExceptionInspection */
use Amp\Http\Client\Connection\Http2ConnectionException;
use Amp\Http\Client\Connection\Http2StreamException;
use Amp\Http\Client\Connection\Internal\Http2Parser;
use Amp\Http\Client\Connection\Internal\Http2Processor;
use function Amp\getCurrentTime;
require __DIR__ . '/../vendor/autoload.php';
$data = \file_get_contents(__DIR__ . '/fixture/h2.log');
$processor = new class implements Http2Processor
{
    public function handlePong(string $data)
    {
        // empty stub
    }
    public function handlePing(string $data)
    {
        // empty stub
    }
    public function handleShutdown(int $lastId, int $error)
    {
        // empty stub
    }
    public function handleStreamWindowIncrement(int $streamId, int $windowSize)
    {
        // empty stub
    }
    public function handleConnectionWindowIncrement(int $windowSize)
    {
        // empty stub
    }
    public function handleHeaders(int $streamId, array $pseudo, array $headers)
    {
        // empty stub
    }
    public function handlePushPromise(int $streamId, int $pushId, array $pseudo, array $headers)
    {
        // empty stub
    }
    public function handlePriority(int $streamId, int $parentId, int $weight)
    {
        // empty stub
    }
    public function handleStreamReset(int $streamId, int $errorCode)
    {
        // empty stub
    }
    public function handleStreamException(Http2StreamException $exception)
    {
        // empty stub
    }
    public function handleConnectionException(Http2ConnectionException $exception)
    {
        // empty stub
    }
    public function handleData(int $streamId, string $data)
    {
        // empty stub
    }
    public function handleSettings(array $settings)
    {
        // empty stub
    }
    public function handleStreamEnd(int $streamId)
    {
        // empty stub
    }
};
$start = getCurrentTime();
for ($i = 0; $i < 10000; $i++) {
    $parser = (new Http2Parser($processor))->parse();
    $parser->send($data);
}
print 'Runtime: ' . (getCurrentTime() - $start) . ' milliseconds' . "\r\n";