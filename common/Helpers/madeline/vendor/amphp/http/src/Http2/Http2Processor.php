<?php

namespace Amp\Http\Http2;

interface Http2Processor
{
    public function handlePong(string $data);
    public function handlePing(string $data);
    public function handleShutdown(int $lastId, int $error);
    public function handleStreamWindowIncrement(int $streamId, int $windowSize);
    public function handleConnectionWindowIncrement(int $windowSize);
    public function handleHeaders(int $streamId, array $pseudo, array $headers, bool $streamEnded);
    public function handlePushPromise(int $streamId, int $pushId, array $pseudo, array $headers);
    public function handlePriority(int $streamId, int $parentId, int $weight);
    public function handleStreamReset(int $streamId, int $errorCode);
    public function handleStreamException(Http2StreamException $exception);
    public function handleConnectionException(Http2ConnectionException $exception);
    public function handleData(int $streamId, string $data);
    public function handleSettings(array $settings);
    public function handleStreamEnd(int $streamId);
}