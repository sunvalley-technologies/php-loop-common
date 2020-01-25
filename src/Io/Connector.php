<?php

namespace SunValley\LoopUtil\Common\Io;

use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\ConnectorInterface;
use SunValley\LoopUtil\Common\Server\ConnectionHandlerInterface;

/**
 * Class Connector wraps any React's Connector and allows to use a ConnectionHandlerInterface for each connection made from this connector.
 *
 * @package SunValley\LoopUtil\Common\Io
 * @see ConnectionHandlerInterface
 */
class Connector
{

    /** @var ConnectorInterface */
    protected $transport;

    /** @var ConnectionHandlerInterface */
    protected $handlerTemplate;

    /**
     * GenericServer constructor.
     * @param ConnectionHandlerInterface $handlerTemplate This handler is used as a template and cloned for each coming connection.
     * @param ConnectorInterface $transport A connector or leave as default react connector
     */
    public function __construct(ConnectionHandlerInterface $handlerTemplate, ConnectorInterface $transport)
    {
        $this->transport = $transport;
        $this->handlerTemplate = $handlerTemplate;
    }

    /**
     * Connects to given uri and returns a promise when connection is successful.
     *
     * @param $uri
     * @return PromiseInterface<ConnectionHandlerInterface> Resolves with a connection handler
     */
    public function connect(string $uri): PromiseInterface
    {
        return $this->transport->connect($uri)->then(
            function (ConnectionInterface $connection) {
                $handler = $this->createHandler();
                $handler->open($connection);
                $connection->on('close', [$handler, 'close']);

                return $handler;
            }
        );
    }

    protected function createHandler(): ConnectionHandlerInterface
    {
        return clone $this->handlerTemplate;
    }
}