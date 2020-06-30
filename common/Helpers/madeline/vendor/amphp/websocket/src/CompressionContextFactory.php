<?php

namespace Amp\Websocket;

interface CompressionContextFactory
{
    /**
     * Create a compression context from a header received from a websocket client request.
     *
     * @param string $headerIn Header from request.
     * @param string $headerOut Sec-Websocket-Extension response header.
     *
     * @return CompressionContext|null
     */
    public function fromClientHeader(string $headerIn, string &$headerOut = null);
    /**
     * Create a compression context from a header received from a websocket server response.
     *
     * @param string $header Header from response.
     *
     * @return CompressionContext|null
     */
    public function fromServerHeader(string $header);
    /**
     * @return string Header value for Sec-Websocket-Extension header.
     */
    public function createRequestHeader() : string;
}