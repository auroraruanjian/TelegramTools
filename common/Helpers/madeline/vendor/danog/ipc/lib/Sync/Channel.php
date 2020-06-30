<?php

namespace Amp\Ipc\Sync;

use Amp\Promise;
/**
 * Interface for sending messages between execution contexts.
 */
interface Channel
{
    /**
     * @return \Amp\Promise<mixed>
     *
     * @throws \Amp\Ipc\Context\StatusError Thrown if the context has not been started.
     * @throws \Amp\Ipc\Sync\SynchronizationError If the context has not been started or the context
     *     unexpectedly ends.
     * @throws \Amp\Ipc\Sync\ChannelException If receiving from the channel fails.
     * @throws \Amp\Ipc\Sync\SerializationException If unserializing the data fails.
     */
    public function receive() : Promise;
    /**
     * @param mixed $data
     *
     * @return \Amp\Promise<int> Resolves with the number of bytes sent on the channel.
     *
     * @throws \Amp\Ipc\Context\StatusError Thrown if the context has not been started.
     * @throws \Amp\Ipc\Sync\SynchronizationError If the context has not been started or the context
     *     unexpectedly ends.
     * @throws \Amp\Ipc\Sync\ChannelException If sending on the channel fails.
     * @throws \Error If an ExitResult object is given.
     * @throws \Amp\Ipc\Sync\SerializationException If serializing the data fails.
     */
    public function send($data) : Promise;
}