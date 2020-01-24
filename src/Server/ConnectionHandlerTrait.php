<?php

namespace SunValley\LoopUtil\Common\Server;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use SunValley\LoopUtil\Common\Io\Exception\IoException;

use function React\Promise\reject;
use function React\Promise\resolve;
use function React\Promise\Stream\first;

trait ConnectionHandlerTrait
{

    use LoggerAwareTrait;

    /** @var ConnectionInterface */
    protected $connection;

    /**
     * Opens and saves the connection then calls handle method.
     *
     * @param ConnectionInterface $connection
     */
    public function open(ConnectionInterface $connection): void
    {
        $this->connection = $connection;
        $this->handle();
    }

    /**
     * Implementing functions can use connection property to access connection to finalize setting up the connection.
     */
    abstract protected function handle(): void;

    /**
     * Write raw data
     * @param mixed|string $data
     * @return PromiseInterface<null,\Throwable> Returns a promise that returns NULL on data sent or a rejection when data cannot be written to client.
     */
    public function write($data): PromiseInterface
    {
        if ($this->connection === null) {
            throw new \BadMethodCallException('No connection is set to this handler!');
        }

        if ($this->connection->write($data) === false) {
            return reject(new IoException('Cannot write data to the connection'));
        }

        return resolve();
    }

    public function resume(): PromiseInterface
    {
        return resolve(); // base implementation closes the connection basically, so calling this method has no effect.
    }

    public function pause($data = null): PromiseInterface
    {
        $closingPromise = first($this->connection, 'close');
        $this->connection->end($data);
        return $closingPromise;
    }

    public function close(): void
    {
        $this->connection->close();
    }

    /**
     * Handle an error, by default this closes the connection and logs the error.
     *
     * @param \Throwable $error
     * @param string $logLevel One of the `LogLevel` constants. Defaults to warning.
     */
    protected function error(\Throwable $error, string $logLevel = LogLevel::WARNING): void
    {
        $this->logger && $this->logger->log($logLevel, "An exception occurred while reading: \n$error");

        $this->close();
    }


}