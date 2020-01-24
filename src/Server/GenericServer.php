<?php

namespace SunValley\LoopUtil\Common\Server;

use Evenement\EventEmitter;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\ServerInterface;
use SplObjectStorage as ObjectStorage;

use function React\Promise\all;

/**
 * A generic server that delegates connection handling to the given connection handler .
 * This generic server provides socket handling by bridging socket and server actions to connection handler.
 * This is mostly made for internal process or service communication and/or servers that are providing persistent connections.
 * @package SunValley\LoopUtil\Common\Server
 */
class GenericServer extends EventEmitter implements GenericServerInterface
{

    /** @var ServerInterface[] */
    protected $sockets = [];

    /** @var ConnectionHandlerInterface */
    protected $handlerTemplate;

    /**@var LoggerInterface|null */
    protected $logger;

    /**
     * @var ObjectStorage|ConnectionHandlerInterface[]
     */
    protected $handlers;

    /**
     * GenericServer constructor.
     * @param ConnectionHandlerInterface $handlerTemplate This handler is used as a template and cloned for each coming connection.
     * @param LoggerInterface|null $logger
     */
    public function __construct(ConnectionHandlerInterface $handlerTemplate, ?LoggerInterface $logger = null)
    {
        $this->handlerTemplate = $handlerTemplate;
        if ($logger === null) {
            $logger = new NullLogger();
        }
        $this->logger = $logger;
        $this->handlers = new ObjectStorage();
    }

    /**
     * Listen for connections on the given socket
     *
     * @param ServerInterface $transport
     */
    public function listen(ServerInterface $transport): void
    {
        if (in_array($transport, $this->sockets, true)) {
            throw new \InvalidArgumentException('Given transport is already listened through this server');
        }

        $this->logger->notice(sprintf('Listening from socket %s', $transport->getAddress()));

        $transport->on('connection', \Closure::fromCallable([$this, 'handleConnection']));
        $transport->on('error', \Closure::fromCallable([$this, 'handleError']));

        $this->sockets[] = $transport;
    }

    protected function handleConnection(ConnectionInterface $connection): void
    {
        $this->logger->debug(
            sprintf('New connection from %s to %s', $connection->getRemoteAddress(), $connection->getLocalAddress())
        );
        $handler = clone $this->handlerTemplate;
        try {
            $connection->on('close', [$this->handlers, 'detach']);
            $handler->open($connection);
            $this->handlers->attach($handler);
        } catch (\Throwable $e) {
            $connection->close();
            $this->logger->error($e);
        }
    }

    protected function handleError(\Throwable $error): void
    {
        $this->logger->warning(sprintf('Connection error: %s', $error->getMessage()));
    }

    /**
     * Close all listening sockets on this server
     */
    public function close(): void
    {
        $this->logger->notice(sprintf('Server is closing now...'));

        foreach ($this->sockets as $socket) {
            $socket->close();
        }
    }

    /**
     * This method is not supported. Call individual sockets to get the address.
     */
    public function getAddress()
    {
        throw new \BadMethodCallException('This method is not supported. Call individual sockets to get the address.');
    }

    /** @inheritDoc */
    public function pause()
    {
        $this->_pause();
    }

    protected function _pause(): PromiseInterface
    {
        $this->logger->notice(sprintf('Server is pausing now...'));

        foreach ($this->sockets as $socket) {
            $socket->pause();
        }

        $promises = [];
        foreach ($this->handlers as $handler) {
            $promises[] = $handler->pause();
        }

        return all($promises);
    }

    /** @inheritDoc */
    public function resume()
    {
        $this->logger->notice(sprintf('Server is resuming now...'));

        foreach ($this->handlers as $handler) {
            $handler->resume();
        }

        foreach ($this->sockets as $socket) {
            $socket->resume();
        }
    }

    /** @inheritDoc */
    public function closeGracefully(): PromiseInterface
    {
        return $this->_pause()->then([$this, 'close']);
    }
}