<?php

namespace Amp\Mysql\Test;

use Amp\Delayed;
use Amp\Mysql\CommandResult;
use Amp\Mysql\Connection;
use Amp\Mysql\ConnectionConfig;
use Amp\Mysql\Internal\Processor;
use Amp\Mysql\Pool;
use Amp\Mysql\Statement;
use Amp\Promise;
use Amp\Sql\Connector;
use Amp\Sql\Transaction as SqlTransaction;
use Amp\Success;
use function Amp\call;
interface StatementOperation extends Statement
{
}
class PoolTest extends LinkTest
{
    protected function getLink(string $connectionString) : Promise
    {
        return new Success(new Pool(ConnectionConfig::fromString($connectionString)));
    }
    protected function createPool(array $connections) : Pool
    {
        $connector = $this->createMock(Connector::class);
        $connector->method('connect')->will($this->returnCallback(function () use($connections) : Promise {
            static $count = 0;
            return new Success($connections[$count++ % \count($connections)]);
        }));
        $config = ConnectionConfig::fromString('host=host;user=user;password=password');
        return new Pool($config, \count($connections), Pool::DEFAULT_IDLE_TIMEOUT, $connector);
    }
    /**
     * @param int $count
     *
     * @return \Amp\Mysql\Internal\Processor[]|\PHPUnit\Framework\MockObject\MockObject[]
     */
    private function makeProcessorSet(int $count)
    {
        $processors = [];
        for ($i = 0; $i < $count; ++$i) {
            $processor = $this->createMock(Processor::class);
            $processor->method('isAlive')->willReturn(true);
            $processors[] = $processor;
        }
        return $processors;
    }
    private function makeConnectionSet(array $processors)
    {
        return \array_map((function (Processor $processor) : Connection {
            return new self($processor);
        })->bindTo(null, Connection::class), $processors);
    }
    /**
     * @return array
     */
    public function getConnectionCounts() : array
    {
        return \array_map(function (int $count) : array {
            return [$count];
        }, \range(2, 10, 2));
    }
    /**
     * @dataProvider getConnectionCounts
     *
     * @param int $count
     */
    public function testSingleQuery(int $count)
    {
        $result = $this->createMock(StatementOperation::class);
        $processors = $this->makeProcessorSet($count);
        $connection = $processors[0];
        $connection->expects($this->once())->method('prepare')->with('SQL Query')->will($this->returnValue(new Delayed(10, $result)));
        $pool = $this->createPool($this->makeConnectionSet($processors));
        $return = (yield $pool->prepare('SQL Query'));
        $this->assertInstanceOf(Statement::class, $return);
    }
    /**
     * @dataProvider getConnectionCounts
     *
     * @param int $count
     */
    public function testConsecutiveQueries(int $count)
    {
        $rounds = 3;
        $result = $this->createMock(StatementOperation::class);
        $processors = $this->makeProcessorSet($count);
        foreach ($processors as $connection) {
            $connection->method('prepare')->with('SQL Query')->will($this->returnValue(new Delayed(10, $result)));
        }
        $pool = $this->createPool($this->makeConnectionSet($processors));
        $promises = [];
        for ($i = 0; $i < $count; ++$i) {
            $promises[] = $pool->prepare('SQL Query');
        }
        $results = (yield $promises);
        foreach ($results as $result) {
            $this->assertInstanceOf(Statement::class, $result);
        }
    }
    /**
     * @dataProvider getConnectionCounts
     *
     * @param int $count
     */
    public function testMutlipleTransactions(int $count)
    {
        $processors = $this->makeProcessorSet($count);
        $connection = $processors[0];
        $result = new CommandResult(0, 0);
        $connection->expects($this->exactly(3))->method('query')->will($this->returnValue(new Delayed(10, $result)));
        $pool = $this->createPool($this->makeConnectionSet($processors));
        $return = (yield $pool->beginTransaction(SqlTransaction::ISOLATION_COMMITTED));
        $this->assertInstanceOf(SqlTransaction::class, $return);
        (yield $return->rollback());
    }
    /**
     * @dataProvider getConnectionCounts
     *
     * @param int $count
     */
    public function testConsecutiveTransactions(int $count)
    {
        $rounds = 3;
        $result = new CommandResult(0, 0);
        $processors = $this->makeProcessorSet($count);
        foreach ($processors as $connection) {
            $connection->method('query')->will($this->returnCallback(function () use($result) {
                return new Delayed(10, $result);
            }));
        }
        $pool = $this->createPool($this->makeConnectionSet($processors));
        $promises = [];
        for ($i = 0; $i < $count; ++$i) {
            $promises[] = $pool->beginTransaction(SqlTransaction::ISOLATION_COMMITTED);
        }
        $results = (yield \array_map(function (Promise $promise) : Promise {
            return call(function () use($promise) {
                $transaction = (yield $promise);
                $this->assertInstanceOf(SqlTransaction::class, $transaction);
                return (yield $transaction->rollback());
            });
        }, $promises));
        foreach ($results as $result) {
            $this->assertInstanceof(CommandResult::class, $result);
        }
    }
    /**
     * @dataProvider getConnectionCounts
     *
     * @param int $count
     */
    public function testExtractConnection(int $count)
    {
        $processors = $this->makeProcessorSet($count);
        $query = "SELECT * FROM test";
        foreach ($processors as $connection) {
            $connection->expects($this->once())->method('query')->with($query);
        }
        $pool = $this->createPool($this->makeConnectionSet($processors));
        $promises = [];
        for ($i = 0; $i < $count; ++$i) {
            $promises[] = $pool->extractConnection();
        }
        $results = (yield $promises);
        foreach ($results as $result) {
            $this->assertInstanceof(Connection::class, $result);
            $result->query($query);
        }
    }
    /**
     * @dataProvider getConnectionCounts
     *
     * @param int $count
     */
    public function testConnectionClosedInPool(int $count)
    {
        $processors = $this->makeProcessorSet($count);
        $query = "SELECT * FROM test";
        $result = new CommandResult(0, 0);
        foreach ($processors as $processor) {
            $processor->expects($this->exactly(2))->method('query')->with($query)->willReturn(new Delayed(10, $result));
        }
        $processor = $this->createMock(Processor::class);
        $processor->method('isAlive')->willReturnOnConsecutiveCalls(true, false);
        $processor->expects($this->once())->method('query')->with($query)->willReturn(new Delayed(10, $result));
        \array_unshift($processors, $processor);
        $pool = $this->createPool($this->makeConnectionSet($processors));
        $this->assertSame($count + 1, $pool->getConnectionLimit());
        $promises = [];
        for ($i = 0; $i < $count + 1; ++$i) {
            $promises[] = $pool->query($query);
        }
        (yield $promises);
        $promises = [];
        for ($i = 0; $i < $count; ++$i) {
            $promises[] = $pool->query($query);
        }
        (yield $promises);
    }
}