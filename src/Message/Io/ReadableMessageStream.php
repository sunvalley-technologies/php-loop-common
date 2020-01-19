<?php

namespace SunValley\LoopUtil\Common\Message\Io;

use React\Stream\ReadableStreamInterface;
use SunValley\LoopUtil\Common\Io\AbstractReadableStreamHandlingStream;
use SunValley\LoopUtil\Common\Message\Exception\IncompleteMessageException;
use SunValley\LoopUtil\Common\Message\Exception\MalformedMessageException;
use SunValley\LoopUtil\Common\Message\Message;
use SunValley\LoopUtil\Common\Message\MessageProcessor;

/**
 * Class MessageStream handles messages coming from an incoming stream.
 * @package SunValley\LoopUtil\Common\Io
 */
class ReadableMessageStream extends AbstractReadableStreamHandlingStream
{

    /** @var MessageProcessor */
    protected $processor;

    /**
     * MessageStream constructor.
     * @param ReadableStreamInterface $input Input stream to read from
     * @param MessageProcessor $processor Message processor that will process coming data from the input stream
     */
    public function __construct(ReadableStreamInterface $input, MessageProcessor $processor)
    {
        parent::__construct($input);
        $this->processor = $processor;
    }

    protected function handleData($data): void
    {
        try {
            foreach ($this->processor->feed($data) as $message) {
                $this->emit('data', [$message]);
            }
        } catch (MalformedMessageException $e) {
            $this->handleError($e);
        }
    }

    protected function handleClose(): void
    {
        if ($this->closed) {
            return;
        }

        $message = $this->processor->getMessage();
        if ($message !==null || $this->processor->hasBuffer()) {
            if ($message === null) {
                $error = new IncompleteMessageException(null, 'Connection closed with buffer has data');
            } else {
                $error = new IncompleteMessageException($message, 'Connection closed with incomplete message');
            }

            $this->emit('error', [$error]);
        }
        
        
        parent::handleClose();
    }
}
