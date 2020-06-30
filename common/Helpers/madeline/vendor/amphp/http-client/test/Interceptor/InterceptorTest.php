<?php

/** @noinspection PhpUnhandledExceptionInspection */
namespace Amp\Http\Client\Interceptor;

use Amp\Http\Client\ApplicationInterceptor;
use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\NetworkInterceptor;
use Amp\Http\Client\PooledHttpClient;
use Amp\Http\Client\Request;
use Amp\Http\Client\Request as ClientRequest;
use Amp\Http\Client\Response as ClientResponse;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Server;
use Amp\Http\Status;
use Amp\PHPUnit\AsyncTestCase;
use Amp\Promise;
use Amp\Socket\Server as SocketServer;
use Amp\Socket\SocketAddress;
use Amp\Socket\StaticConnector;
use Psr\Log\NullLogger;
use function Amp\call;
use function Amp\Socket\connector;
abstract class InterceptorTest extends AsyncTestCase
{
    /** @var HttpClientBuilder */
    private $builder;
    /** @var PooledHttpClient */
    private $client;
    /** @var SocketServer */
    private $serverSocket;
    /** @var Server */
    private $server;
    /** @var Request */
    private $request;
    /** @var Response */
    private $response;
    /** @var SocketAddress|null */
    private $serverAddress;
    public final function getServerAddress() : SocketAddress
    {
        return $this->serverSocket->getAddress();
    }
    protected final function givenApplicationInterceptor(ApplicationInterceptor $interceptor)
    {
        $this->builder = $this->builder->intercept($interceptor);
        $this->client = $this->builder->build();
    }
    protected final function givenNetworkInterceptor(NetworkInterceptor $interceptor)
    {
        $this->builder = $this->builder->interceptNetwork($interceptor);
        $this->client = $this->builder->build();
    }
    protected final function whenRequestIsExecuted(ClientRequest $request = null) : Promise
    {
        return call(function () use($request) {
            (yield $this->server->start());
            $this->serverAddress = $this->serverSocket->getAddress();
            try {
                /** @var ClientResponse $response */
                $response = (yield $this->client->request($request ?? new ClientRequest('http://example.org/')));
                $this->request = $response->getRequest();
                $this->response = $response;
                (yield $this->response->getBody()->buffer());
                (yield $this->response->getTrailers());
            } finally {
                (yield $this->server->stop());
                $this->serverSocket->close();
            }
        });
    }
    protected function setUp()
    {
        parent::setUp();
        $this->serverSocket = SocketServer::listen('tcp://127.0.0.1:0');
        $this->server = new Server([$this->serverSocket], new CallableRequestHandler(static function () {
            return new Response(Status::OK, ['content-type' => 'text-plain; charset=utf-8'], 'OK');
        }), new NullLogger());
        $staticConnector = new StaticConnector($this->serverSocket->getAddress()->toString(), connector());
        $this->builder = (new HttpClientBuilder())->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory($staticConnector)));
        $this->client = $this->builder->build();
    }
    protected final function thenRequestHasHeader(string $field, ...$values)
    {
        $this->assertSame($values, $this->request->getHeaderArray($field));
    }
    protected final function thenRequestDoesNotHaveHeader(string $field)
    {
        $this->assertSame([], $this->request->getHeaderArray($field));
    }
    protected final function thenResponseHasHeader(string $field, ...$values)
    {
        $this->assertSame($values, $this->response->getHeaderArray($field));
    }
    protected final function thenResponseDoesNotHaveHeader(string $field)
    {
        $this->assertSame([], $this->response->getHeaderArray($field));
    }
}