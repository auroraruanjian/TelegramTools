<?php

/** @noinspection PhpUnusedPrivateFieldInspection */
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpUnhandledExceptionInspection */
namespace Amp\Http\Http2;

use Amp\Http\HPack;
final class Http2Parser
{
    const PREFACE = "PRI * HTTP/2.0\r\n\r\nSM\r\n\r\n";
    const DEFAULT_MAX_FRAME_SIZE = 1 << 14;
    const HEADER_NAME_REGEX = '/^[\\x21-\\x40\\x5b-\\x7e]+$/';
    const KNOWN_RESPONSE_PSEUDO_HEADERS = [":status" => true];
    const KNOWN_REQUEST_PSEUDO_HEADERS = [":method" => true, ":authority" => true, ":path" => true, ":scheme" => true];
    // SETTINGS Flags - https://http2.github.io/http2-spec/#rfc.section.6.5
    const ACK = 0x1;
    // HEADERS Flags - https://http2.github.io/http2-spec/#rfc.section.6.2
    const NO_FLAG = 0x0;
    const END_STREAM = 0x1;
    const END_HEADERS = 0x4;
    const PADDED = 0x8;
    const PRIORITY_FLAG = 0x20;
    // Frame Types - https://http2.github.io/http2-spec/#rfc.section.11.2
    const DATA = 0x0;
    const HEADERS = 0x1;
    const PRIORITY = 0x2;
    const RST_STREAM = 0x3;
    const SETTINGS = 0x4;
    const PUSH_PROMISE = 0x5;
    const PING = 0x6;
    const GOAWAY = 0x7;
    const WINDOW_UPDATE = 0x8;
    const CONTINUATION = 0x9;
    // Settings
    const HEADER_TABLE_SIZE = 0x1;
    // 1 << 12
    const ENABLE_PUSH = 0x2;
    // 1
    const MAX_CONCURRENT_STREAMS = 0x3;
    // INF
    const INITIAL_WINDOW_SIZE = 0x4;
    // 1 << 16 - 1
    const MAX_FRAME_SIZE = 0x5;
    // 1 << 14
    const MAX_HEADER_LIST_SIZE = 0x6;
    // INF
    // Error codes
    const GRACEFUL_SHUTDOWN = 0x0;
    const PROTOCOL_ERROR = 0x1;
    const INTERNAL_ERROR = 0x2;
    const FLOW_CONTROL_ERROR = 0x3;
    const SETTINGS_TIMEOUT = 0x4;
    const STREAM_CLOSED = 0x5;
    const FRAME_SIZE_ERROR = 0x6;
    const REFUSED_STREAM = 0x7;
    const CANCEL = 0x8;
    const COMPRESSION_ERROR = 0x9;
    const CONNECT_ERROR = 0xa;
    const ENHANCE_YOUR_CALM = 0xb;
    const INADEQUATE_SECURITY = 0xc;
    const HTTP_1_1_REQUIRED = 0xd;
    public static function getFrameName(int $type) : string
    {
        $names = [self::DATA => 'DATA', self::HEADERS => 'HEADERS', self::PRIORITY => 'PRIORITY', self::RST_STREAM => 'RST_STREAM', self::SETTINGS => 'SETTINGS', self::PUSH_PROMISE => 'PUSH_PROMISE', self::PING => 'PING', self::GOAWAY => 'GOAWAY', self::WINDOW_UPDATE => 'WINDOW_UPDATE', self::CONTINUATION => 'CONTINUATION'];
        return $names[$type] ?? '0x' . \bin2hex(\chr($type));
    }
    public static function logDebugFrame(string $action, int $frameType, int $frameFlags, int $streamId, int $frameLength) : bool
    {
        $env = \getenv("AMP_DEBUG_HTTP2_FRAMES") ?: "0";
        if ($env !== "0" && $env !== "false" || \defined("AMP_DEBUG_HTTP2_FRAMES") && \AMP_DEBUG_HTTP2_FRAMES) {
            \fwrite(\STDERR, $action . ' ' . self::getFrameName($frameType) . ' <flags = ' . \bin2hex(\chr($frameFlags)) . ', stream = ' . $streamId . ', length = ' . $frameLength . '>' . "\r\n");
        }
        return true;
    }
    /** @var string */
    private $buffer = '';
    /** @var int */
    private $bufferOffset = 0;
    /** @var int */
    private $headerSizeLimit = self::DEFAULT_MAX_FRAME_SIZE;
    // Should be configurable?
    /** @var bool */
    private $continuationExpected = false;
    /** @var int */
    private $headerFrameType = 0;
    /** @var string */
    private $headerBuffer = '';
    /** @var int */
    private $headerStream = 0;
    /** @var HPack */
    private $hpack;
    /** @var Http2Processor */
    private $handler;
    public function __construct(Http2Processor $handler)
    {
        $this->hpack = new HPack();
        $this->handler = $handler;
    }
    public function parse(string $settings = null) : \Generator
    {
        $lastReset = \time();
        $totalBytesReceivedSinceReset = 0;
        $payloadBytesReceivedSinceReset = 0;
        if ($settings !== null) {
            $this->parseSettings($settings, \strlen($settings), self::NO_FLAG, 0);
        }
        $this->buffer = yield;
        while (true) {
            $frameHeader = (yield from $this->consume(9));
            list($frameLength, $frameType, $frameFlags, $streamId) = \__destructure(array('length', 'type', 'flags', 'id'), \unpack('Nlength/ctype/cflags/Nid', "\0" . $frameHeader));
            $streamId &= 0x7fffffff;
            $frameBuffer = $frameLength === 0 ? '' : (yield from $this->consume($frameLength));
            \assert(self::logDebugFrame('recv', $frameType, $frameFlags, $streamId, $frameLength));
            try {
                // Do we want to allow increasing the maximum frame size?
                if ($frameLength > self::DEFAULT_MAX_FRAME_SIZE) {
                    throw new Http2ConnectionException("Frame size limit exceeded", self::FRAME_SIZE_ERROR);
                }
                if ($this->continuationExpected && $frameType !== self::CONTINUATION) {
                    throw new Http2ConnectionException("Expected continuation frame", self::PROTOCOL_ERROR);
                }
                $now = \time();
                if ($lastReset === $now) {
                    // Inspired by nginx flood detection:
                    // https://github.com/nginx/nginx/commit/af0e284b967d0ecff1abcdce6558ed4635e3e757
                    if ($totalBytesReceivedSinceReset / 2 > $payloadBytesReceivedSinceReset + 1024) {
                        throw new Http2ConnectionException("Flood detected", self::ENHANCE_YOUR_CALM);
                    }
                } else {
                    $lastReset = $now;
                    $totalBytesReceivedSinceReset = 0;
                    $payloadBytesReceivedSinceReset = 0;
                }
                $totalBytesReceivedSinceReset += 9 + $frameLength;
                switch ($frameType) {
                    case self::DATA:
                        $payloadBytesReceivedSinceReset += $frameLength;
                        $this->parseDataFrame($frameBuffer, $frameLength, $frameFlags, $streamId);
                        break;
                    case self::PUSH_PROMISE:
                        $payloadBytesReceivedSinceReset += $frameLength;
                        $this->parsePushPromise($frameBuffer, $frameLength, $frameFlags, $streamId);
                        break;
                    case self::HEADERS:
                        $payloadBytesReceivedSinceReset += $frameLength;
                        $this->parseHeaders($frameBuffer, $frameLength, $frameFlags, $streamId);
                        break;
                    case self::PRIORITY:
                        $this->parsePriorityFrame($frameBuffer, $frameLength, $streamId);
                        break;
                    case self::RST_STREAM:
                        $this->parseStreamReset($frameBuffer, $frameLength, $streamId);
                        break;
                    case self::SETTINGS:
                        $this->parseSettings($frameBuffer, $frameLength, $frameFlags, $streamId);
                        break;
                    case self::PING:
                        $this->parsePing($frameBuffer, $frameLength, $frameFlags, $streamId);
                        break;
                    case self::GOAWAY:
                        $this->parseGoAway($frameBuffer, $frameLength, $streamId);
                        return;
                    case self::WINDOW_UPDATE:
                        $this->parseWindowUpdate($frameBuffer, $frameLength, $streamId);
                        break;
                    case self::CONTINUATION:
                        $payloadBytesReceivedSinceReset += $frameLength;
                        $this->parseContinuation($frameBuffer, $frameFlags, $streamId);
                        break;
                    default:
                        // Ignore and discard unknown frame per spec
                        break;
                }
            } catch (Http2StreamException $exception) {
                $this->handler->handleStreamException($exception);
            } catch (Http2ConnectionException $exception) {
                $this->handler->handleConnectionException($exception);
                throw $exception;
            }
        }
    }
    private function consume(int $bytes) : \Generator
    {
        $bufferEnd = $this->bufferOffset + $bytes;
        while (\strlen($this->buffer) < $bufferEnd) {
            $this->buffer .= yield;
        }
        $consumed = \substr($this->buffer, $this->bufferOffset, $bytes);
        if ($bufferEnd > 2048) {
            $this->buffer = \substr($this->buffer, $bufferEnd);
            $this->bufferOffset = 0;
        } else {
            $this->bufferOffset += $bytes;
        }
        return $consumed;
    }
    private function parseDataFrame(string $frameBuffer, int $frameLength, int $frameFlags, int $streamId)
    {
        $isPadded = $frameFlags & self::PADDED;
        $headerLength = $isPadded ? 1 : 0;
        if ($frameLength < $headerLength) {
            $this->throwInvalidFrameSizeError();
        }
        $header = $headerLength === 0 ? '' : \substr($frameBuffer, 0, $headerLength);
        $padding = $isPadded ? \ord($header[0]) : 0;
        if ($streamId === 0) {
            $this->throwInvalidZeroStreamIdError();
        }
        if ($frameLength - $headerLength - $padding < 0) {
            $this->throwInvalidPaddingError();
        }
        $data = \substr($frameBuffer, $headerLength, $frameLength - $headerLength - $padding);
        $this->handler->handleData($streamId, $data);
        if ($frameFlags & self::END_STREAM) {
            $this->handler->handleStreamEnd($streamId);
        }
    }
    /** @see https://http2.github.io/http2-spec/#rfc.section.6.6 */
    private function parsePushPromise(string $frameBuffer, int $frameLength, int $frameFlags, int $streamId)
    {
        $isPadded = $frameFlags & self::PADDED;
        $headerLength = $isPadded ? 5 : 4;
        if ($frameLength < $headerLength) {
            $this->throwInvalidFrameSizeError();
        }
        $header = \substr($frameBuffer, 0, $headerLength);
        $padding = $isPadded ? \ord($header[0]) : 0;
        $pushId = \unpack("N", $header)[1] & 0x7fffffff;
        if ($frameLength - $headerLength - $padding < 0) {
            $this->throwInvalidPaddingError();
        }
        $this->headerFrameType = self::PUSH_PROMISE;
        $this->pushHeaderBlockFragment($pushId, \substr($frameBuffer, $headerLength, $frameLength - $headerLength - $padding));
        if ($frameFlags & self::END_HEADERS) {
            $this->continuationExpected = false;
            list($pseudo, $headers) = $this->parseHeaderBuffer();
            $this->handler->handlePushPromise($streamId, $pushId, $pseudo, $headers);
        } else {
            $this->continuationExpected = true;
        }
        if ($frameFlags & self::END_STREAM) {
            $this->handler->handleStreamEnd($streamId);
        }
    }
    private function parseHeaderBuffer() : array
    {
        if ($this->headerStream === 0) {
            throw new Http2ConnectionException('Invalid stream ID 0 for header block', self::PROTOCOL_ERROR);
        }
        if ($this->headerBuffer === '') {
            throw new Http2StreamException('Invalid empty header section', $this->headerStream, self::PROTOCOL_ERROR);
        }
        $decoded = $this->hpack->decode($this->headerBuffer, $this->headerSizeLimit);
        if ($decoded === null) {
            throw new Http2ConnectionException("Compression error in headers", self::COMPRESSION_ERROR);
        }
        $headers = [];
        $pseudo = [];
        foreach ($decoded as list($name, $value)) {
            if (!\preg_match(self::HEADER_NAME_REGEX, $name)) {
                throw new Http2StreamException("Invalid header field name", $this->headerStream, self::PROTOCOL_ERROR);
            }
            if ($name[0] === ':') {
                if (!empty($headers)) {
                    throw new Http2ConnectionException("Pseudo header after other headers", self::PROTOCOL_ERROR);
                }
                if (isset($pseudo[$name])) {
                    throw new Http2ConnectionException("Repeat pseudo header", self::PROTOCOL_ERROR);
                }
                $pseudo[$name] = $value;
                continue;
            }
            $headers[$name][] = $value;
        }
        $this->headerBuffer = '';
        $this->headerStream = 0;
        return [$pseudo, $headers];
    }
    private function pushHeaderBlockFragment(int $streamId, string $buffer)
    {
        if ($this->headerStream !== 0 && $this->headerStream !== $streamId) {
            throw new Http2ConnectionException("Expected CONTINUATION frame for stream ID " . $this->headerStream, self::PROTOCOL_ERROR);
        }
        $this->headerStream = $streamId;
        $this->headerBuffer .= $buffer;
    }
    /** @see https://http2.github.io/http2-spec/#HEADERS */
    private function parseHeaders(string $frameBuffer, int $frameLength, int $frameFlags, int $streamId)
    {
        if ($streamId === 0) {
            $this->throwInvalidZeroStreamIdError();
        }
        $headerLength = 0;
        $isPadded = $frameFlags & self::PADDED;
        $isPriority = $frameFlags & self::PRIORITY_FLAG;
        if ($isPadded) {
            $headerLength++;
        }
        if ($isPriority) {
            $headerLength += 5;
        }
        if ($frameLength < $headerLength) {
            $this->throwInvalidFrameSizeError();
        }
        $header = \substr($frameBuffer, 0, $headerLength);
        $padding = $isPadded ? \ord($header[0]) : 0;
        if ($isPriority) {
            list($parent, $weight) = \__destructure(array('parent', 'weight'), \unpack("Nparent/cweight", $header, $isPadded ? 1 : 0));
            $parent &= 0x7fffffff;
            if ($parent === $streamId) {
                $this->throwInvalidRecursiveDependency($streamId);
            }
            $this->handler->handlePriority($streamId, $parent, $weight + 1);
        }
        if ($frameLength - $headerLength - $padding < 0) {
            $this->throwInvalidPaddingError();
        }
        $this->headerFrameType = self::HEADERS;
        $this->pushHeaderBlockFragment($streamId, \substr($frameBuffer, $headerLength, $frameLength - $headerLength - $padding));
        $ended = $frameFlags & self::END_STREAM;
        if ($frameFlags & self::END_HEADERS) {
            $this->continuationExpected = false;
            $headersTooLarge = \strlen($this->headerBuffer) > $this->headerSizeLimit;
            list($pseudo, $headers) = $this->parseHeaderBuffer();
            // This must happen after the parsing, otherwise we loose the connection state and must close the whole
            // connection, which is not what we want here…
            if ($headersTooLarge) {
                throw new Http2StreamException("Headers exceed maximum configured size of {$this->headerSizeLimit} bytes", $streamId, self::ENHANCE_YOUR_CALM);
            }
            $this->handler->handleHeaders($streamId, $pseudo, $headers, $ended);
        } else {
            $this->continuationExpected = true;
        }
        if ($ended) {
            $this->handler->handleStreamEnd($streamId);
        }
    }
    private function parsePriorityFrame(string $frameBuffer, int $frameLength, int $streamId)
    {
        if ($frameLength !== 5) {
            $this->throwInvalidFrameSizeError();
        }
        list($parent, $weight) = \__destructure(array('parent', 'weight'), \unpack("Nparent/cweight", $frameBuffer));
        if ($exclusive = $parent & 0x80000000) {
            $parent &= 0x7fffffff;
        }
        if ($streamId === 0) {
            $this->throwInvalidZeroStreamIdError();
        }
        if ($parent === $streamId) {
            $this->throwInvalidRecursiveDependency($streamId);
        }
        $this->handler->handlePriority($streamId, $parent, $weight + 1);
    }
    private function parseStreamReset(string $frameBuffer, int $frameLength, int $streamId)
    {
        if ($frameLength !== 4) {
            $this->throwInvalidFrameSizeError();
        }
        if ($streamId === 0) {
            $this->throwInvalidZeroStreamIdError();
        }
        $errorCode = \unpack('N', $frameBuffer)[1];
        $this->handler->handleStreamReset($streamId, $errorCode);
    }
    private function parseSettings(string $frameBuffer, int $frameLength, int $frameFlags, int $streamId)
    {
        if ($streamId !== 0) {
            $this->throwInvalidNonZeroStreamIdError();
        }
        if ($frameFlags & self::ACK) {
            if ($frameLength) {
                $this->throwInvalidFrameSizeError();
            }
            return;
            // Got ACK, nothing to do
        }
        if ($frameLength % 6 !== 0) {
            $this->throwInvalidFrameSizeError();
        }
        if ($frameLength > 60) {
            // Even with room for a few future options, sending that a big SETTINGS frame is just about
            // wasting our processing time. We declare this a protocol error.
            throw new Http2ConnectionException("Excessive SETTINGS frame", self::PROTOCOL_ERROR);
        }
        $settings = [];
        while ($frameLength > 0) {
            list($key, $value) = \__destructure(array('key', 'value'), \unpack("nkey/Nvalue", $frameBuffer));
            if ($value < 0) {
                throw new Http2ConnectionException("Invalid setting: {$value}", self::PROTOCOL_ERROR);
            }
            $settings[$key] = $value;
            $frameBuffer = \substr($frameBuffer, 6);
            $frameLength -= 6;
        }
        $this->handler->handleSettings($settings);
    }
    /** @see https://http2.github.io/http2-spec/#rfc.section.6.7 */
    private function parsePing(string $frameBuffer, int $frameLength, int $frameFlags, int $streamId)
    {
        if ($frameLength !== 8) {
            $this->throwInvalidFrameSizeError();
        }
        if ($streamId !== 0) {
            $this->throwInvalidNonZeroStreamIdError();
        }
        if ($frameFlags & self::ACK) {
            $this->handler->handlePong($frameBuffer);
        } else {
            $this->handler->handlePing($frameBuffer);
        }
    }
    /** @see https://http2.github.io/http2-spec/#rfc.section.6.8 */
    private function parseGoAway(string $frameBuffer, int $frameLength, int $streamId)
    {
        if ($frameLength < 8) {
            $this->throwInvalidFrameSizeError();
        }
        if ($streamId !== 0) {
            $this->throwInvalidNonZeroStreamIdError();
        }
        list($lastId, $error) = \__destructure(array('last', 'error'), \unpack("Nlast/Nerror", $frameBuffer));
        $this->handler->handleShutdown($lastId & 0x7fffffff, $error);
    }
    /** @see https://http2.github.io/http2-spec/#rfc.section.6.9 */
    private function parseWindowUpdate(string $frameBuffer, int $frameLength, int $streamId)
    {
        if ($frameLength !== 4) {
            $this->throwInvalidFrameSizeError();
        }
        $windowSize = \unpack('N', $frameBuffer)[1];
        if ($windowSize === 0) {
            if ($streamId) {
                throw new Http2StreamException("Invalid zero window update value", $streamId, self::PROTOCOL_ERROR);
            }
            throw new Http2ConnectionException("Invalid zero window update value", self::PROTOCOL_ERROR);
        }
        if ($streamId) {
            $this->handler->handleStreamWindowIncrement($streamId, $windowSize);
        } else {
            $this->handler->handleConnectionWindowIncrement($windowSize);
        }
    }
    /** @see https://http2.github.io/http2-spec/#rfc.section.6.10 */
    private function parseContinuation(string $frameBuffer, int $frameFlags, int $streamId)
    {
        if ($streamId !== $this->headerStream) {
            throw new Http2ConnectionException("Invalid CONTINUATION frame stream ID", self::PROTOCOL_ERROR);
        }
        if ($this->headerBuffer === '') {
            throw new Http2ConnectionException("Unexpected CONTINUATION frame for stream ID " . $this->headerStream, self::PROTOCOL_ERROR);
        }
        $this->pushHeaderBlockFragment($streamId, $frameBuffer);
        $ended = $frameFlags & self::END_STREAM;
        if ($frameFlags & self::END_HEADERS) {
            $this->continuationExpected = false;
            $isPush = $this->headerFrameType === self::PUSH_PROMISE;
            $pushId = $this->headerStream;
            list($pseudo, $headers) = $this->parseHeaderBuffer();
            if ($isPush) {
                $this->handler->handlePushPromise($streamId, $pushId, $pseudo, $headers);
            } else {
                $this->handler->handleHeaders($streamId, $pseudo, $headers, $ended);
            }
        }
        if ($ended) {
            $this->handler->handleStreamEnd($streamId);
        }
    }
    private function throwInvalidFrameSizeError()
    {
        throw new Http2ConnectionException("Invalid frame length", self::PROTOCOL_ERROR);
    }
    private function throwInvalidRecursiveDependency(int $streamId)
    {
        throw new Http2ConnectionException("Invalid recursive dependency for stream {$streamId}", self::PROTOCOL_ERROR);
    }
    private function throwInvalidPaddingError()
    {
        throw new Http2ConnectionException("Padding greater than length", self::PROTOCOL_ERROR);
    }
    private function throwInvalidZeroStreamIdError()
    {
        throw new Http2ConnectionException("Invalid zero stream ID", self::PROTOCOL_ERROR);
    }
    private function throwInvalidNonZeroStreamIdError()
    {
        throw new Http2ConnectionException("Invalid non-zero stream ID", self::PROTOCOL_ERROR);
    }
}