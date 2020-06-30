<?php
namespace Common\API\telegram;

use danog\MadelineProto\EventHandler;
use danog\MadelineProto\RPCErrorException;

/**
 * Event handler class.
 */
class MyEventHandler extends EventHandler
{
    public function onAny($update)
    {
        //TelegramClient::$websocket[$this->API->authorization['user']['phone']]->emit('message',$update);
        dump($update);
        //debug_print_backtrace();
    }
}
