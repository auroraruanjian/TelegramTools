<?php

namespace Amp\Http\Client\Interceptor;

use Amp\Http\Client\Request;
class RemoveResponseHeaderTest extends InterceptorTest
{
    public function testNetworkInterceptor() : \Generator
    {
        // execution order is reversed
        $this->givenNetworkInterceptor(new RemoveResponseHeader('foo'));
        $this->givenNetworkInterceptor(new SetResponseHeader('foo', 'bar'));
        $request = new Request('http://example.org/');
        (yield $this->whenRequestIsExecuted($request));
        $this->thenRequestDoesNotHaveHeader('foo');
        $this->thenResponseDoesNotHaveHeader('foo');
    }
    public function testApplicationInterceptor() : \Generator
    {
        // execution order is reversed
        $this->givenApplicationInterceptor(new RemoveResponseHeader('foo'));
        $this->givenApplicationInterceptor(new SetResponseHeader('foo', 'bar'));
        $request = new Request('http://example.org/');
        (yield $this->whenRequestIsExecuted($request));
        $this->thenRequestDoesNotHaveHeader('foo');
        $this->thenResponseDoesNotHaveHeader('foo');
    }
}