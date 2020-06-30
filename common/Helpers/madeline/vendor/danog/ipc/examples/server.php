<?php

require 'vendor/autoload.php';
use Amp\Ipc\IpcServer;
use Amp\Ipc\Sync\ChannelledSocket;
use Amp\Loop;
use function Amp\asyncCall;
Loop::run(static function () {
    $clientHandler = function (ChannelledSocket $socket) {
        echo "Accepted connection" . PHP_EOL;
        while ($payload = (yield $socket->receive())) {
            echo "Received {$payload}" . PHP_EOL;
            if ($payload === 'ping') {
                (yield $socket->send('pong'));
                (yield $socket->disconnect());
            }
        }
        echo "Closed connection" . PHP_EOL . "==========" . PHP_EOL;
    };
    $server = new IpcServer(\sys_get_temp_dir() . '/test');
    while ($socket = (yield $server->accept())) {
        asyncCall($clientHandler, $socket);
    }
});