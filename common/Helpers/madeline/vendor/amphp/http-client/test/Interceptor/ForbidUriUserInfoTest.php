<?php

namespace Amp\Http\Client\Interceptor;

use Amp\Http\Client\InvalidRequestException;
use Amp\Http\Client\Request;
class ForbidUriUserInfoTest extends InterceptorTest
{
    public function test() : \Generator
    {
        $this->givenApplicationInterceptor(new ForbidUriUserInfo());
        $request = new Request('https://user@localhost:13242/');
        try {
            (yield $this->whenRequestIsExecuted($request));
            $this->fail('Exception expected');
        } catch (InvalidRequestException $e) {
            $exceptionRequest = $e->getRequest();
            $exceptionRequest->removeAttributes();
            $this->assertEquals($request, $exceptionRequest);
            $this->expectException(InvalidRequestException::class);
            $this->expectExceptionMessage('The user information (username:password) component of URIs has been deprecated');
            throw $e;
        }
    }
}