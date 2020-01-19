<?php


namespace SunValley\LoopUtil\Common\Io;


use Evenement\EventEmitter;
use React\Stream\ReadableStreamInterface;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;

/**
 * Pauses a given stream and buffers all events while paused
 *
 * This class is used to buffer all events that happen on a given stream while
 * it is paused. This allows you to pause a stream and no longer watch for any
 * of its events. Once the stream is resumed, all buffered events will be
 * emitted. Explicitly closing the resulting stream clears all buffers.
 *
 * Note that this is an internal class only and nothing you should usually care
 * about.
 *
 * << Copied from reactphp/http >>
 *
 * @see ReadableStreamInterface
 */
class PauseBufferStream extends AbstractReadableStreamHandlingStream
{
    private $dataPaused = '';
    private $endPaused = false;
    private $closePaused = false;
    private $errorPaused;
    private $implicit = false;

    /**
     * pause and remember this was not explicitly from user control
     *
     * @internal
     */
    public function pauseImplicit()
    {
        $this->pause();
        $this->implicit = true;
    }

    /**
     * resume only if this was previously paused implicitly and not explicitly from user control
     *
     * @internal
     */
    public function resumeImplicit()
    {
        if ($this->implicit) {
            $this->resume();
        }
    }

    public function isReadable()
    {
        return !$this->closed;
    }

    public function pause()
    {
        if ($this->closed) {
            return;
        }

        parent::pause();
        $this->implicit = false;
    }

    public function resume()
    {
        if ($this->closed) {
            return;
        }

        $this->paused = false;
        $this->implicit = false;

        if ($this->dataPaused !== '') {
            $this->emit('data', array($this->dataPaused));
            $this->dataPaused = '';
        }

        if ($this->errorPaused) {
            $this->emit('error', array($this->errorPaused));
            $this->close();
            
            return ;
        }

        if ($this->endPaused) {
            $this->endPaused = false;
            $this->emit('end');
            $this->close();
                
            return ;
        }

        if ($this->closePaused) {
            $this->closePaused = false;
            $this->close();
            
            return ;
        }

        $this->input->resume();
    }

    public function close()
    {
        if ($this->closed) {
            return;
        }
        
        $this->dataPaused = '';
        $this->endPaused = $this->closePaused = false;
        $this->errorPaused = null;

        parent::close();
    }
    
    protected function handleData($data): void
    {
        if ($this->paused) {
            $this->dataPaused .= $data;
            return;
        }

        $this->emit('data', array($data));
    }
    
    protected function handleError(\Throwable $e): void
    {
        if ($this->paused) {
            $this->errorPaused = $e;
            return;
        }

        parent::handleError($e);
    }
    
    protected function handleEnd(): void
    {
        if ($this->paused) {
            $this->endPaused = true;
            return;
        }

        if (!$this->closed) {
            $this->emit('end');
            $this->close();
        }
    }
    
    protected function handleClose(): void
    {
        if ($this->paused) {
            $this->closePaused = true;
            return;
        }

        parent::handleClose();
    }
}
