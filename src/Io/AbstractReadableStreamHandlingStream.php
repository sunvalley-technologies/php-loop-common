<?php

namespace SunValley\LoopUtil\Common\Io;

use Evenement\EventEmitter;
use React\Stream\ReadableStreamInterface;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;

/**
 * Provides an abstract implementation for a readable stream that is handling another readable stream
 *
 * @package SunValley\LoopUtil\Common\Io
 */
abstract class AbstractReadableStreamHandlingStream extends EventEmitter implements ReadableStreamInterface
{

    /** @var ReadableStreamInterface */
    protected $input;
    /** @var bool */
    protected $closed = false;
    /** @var bool */
    protected $paused = false;

    public function __construct(ReadableStreamInterface $input)
    {
        $this->input = $input;

        $this->input->on('data', \Closure::fromCallable(array($this, 'handleData')));
        $this->input->on('end', \Closure::fromCallable(array($this, 'handleEnd')));
        $this->input->on('error', \Closure::fromCallable(array($this, 'handleError')));
        $this->input->on('close', \Closure::fromCallable(array($this, 'handleClose')));
    }

    public function isReadable()
    {
        return !$this->closed && $this->input->isReadable();
    }

    public function pause()
    {
        if ($this->closed) {
            return;
        }

        $this->paused = true;
        $this->input->pause();
    }

    public function resume()
    {
        if ($this->closed) {
            return;
        }

        $this->paused = false;
        $this->input->resume();
    }

    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
        Util::pipe($this, $dest, $options);

        return $dest;
    }

    public function close()
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;

        $this->input->close();

        $this->emit('close');
        $this->removeAllListeners();
    }

    /**
     * Implement to handle incoming data from input stream
     * 
     * @param $data
     */
    abstract protected function handleData($data): void;

    /**
     * Handles errors coming from input stream
     * 
     * @param \Throwable $e
     */
    protected function handleError(\Throwable $e): void
    {
        $this->emit('error', array($e));
        $this->close();
    }

    /**
     * Handle end coming from input stream
     */
    protected function handleEnd(): void
    {
        if (!$this->closed) {
            $this->emit('end');
            $this->handleClose();
        }
    }

    /**
     * Handle close coming from input stream
     */
    protected function handleClose(): void
    {
        $this->close();
    }

}