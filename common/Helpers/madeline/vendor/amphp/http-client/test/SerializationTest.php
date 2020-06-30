<?php

namespace Amp\Http\Client;

use Amp\ByteStream\InMemoryStream;
use PHPUnit\Framework\TestCase;
class SerializationTest extends TestCase
{
    public function testRequest()
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Amp\\Http\\Client\\Request does not support serialization');
        \serialize(new Request('https://google.com/'));
    }
    public function testResponse()
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Amp\\Http\\Client\\Response does not support serialization');
        \serialize(new Response('1.1', 200, 'OK', [], new InMemoryStream(''), new Request('/')));
    }
}