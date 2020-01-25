<?php


namespace SunValley\LoopUtil\Common\Server;

use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;

/**
 * Interface ConnectionHandlerInterface defines an interface to watch and manage connections and while listening the server events.
 *
 * Note that each connection handler handles one and one connection only.
 *
 * @package SunValley\LoopUtil\Common\Server
 */
interface ConnectionHandlerInterface
{

    /**
     * Called when a connection is opened
     *
     * @param ConnectionInterface $connection
     */
    public function open(ConnectionInterface $connection): void;

    /**
     * Called when server or connection is paused.
     *
     * @return PromiseInterface<void,\Throwable> Should return a promise that resolves when all connections are closed gracefully. Should reject with an error.
     */
    public function pause(): PromiseInterface;

    /**
     * Called when server or connection is resumed
     *
     * @return PromiseInterface<void,\Throwable> Should return a promise that resolves when manager is resumed
     */
    public function resume(): PromiseInterface;

    /**
     * Called when server or connection is closing. This is a termination call that indicates that the socket(s) will be closing forcefully.
     * 
     */
    public function close(): void;

}