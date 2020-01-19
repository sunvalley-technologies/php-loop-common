<?php

namespace SunValley\LoopUtil\Common\Message\Exception;

use Exception;
use SunValley\LoopUtil\Common\Message\MessageInterface;
use Throwable;

class MessageException extends Exception
{

    /** @var MessageInterface|null */
    private $badMessage;

    public function __construct(
        ?MessageInterface $badMessage = null,
        string $errorMessage = '',
        Throwable $previous = null
    ) {
        $this->badMessage = $badMessage;

        parent::__construct($errorMessage, 0, $previous);
    }

    /**
     * Get the violating message if one exists
     * @return MessageInterface|null
     */
    public function getBadMessage(): ?MessageInterface
    {
        return $this->badMessage;
    }
}