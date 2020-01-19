<?php

namespace SunValley\LoopUtil\Common\Message;


use SunValley\LoopUtil\Common\Message\Exception\MalformedMessageException;

/**
 * This interface defines a message processor that can be process a feed partial or any number more messages to be parsed to a Message instance.
 *
 * @package SunValley\LoopUtil\Common\Server\Message
 */
interface MessageProcessorInterface
{
    /**
     * Feed data to parse and return messages after removing them
     *
     * @param string $data Data to be fed
     *
     * @return MessageInterface[] Removes and returns the parsed messages
     * @throws MalformedMessageException
     */
    public function feed(string $data): array;

    /**
     * Feed data to parse and returns the count of messages.
     *
     * @param string $data Data to be fed
     *
     * @return int Returns the current count of messages
     * @throws MalformedMessageException
     */
    public function feedAndCount(string $data): int;

    /**
     * Get all Messages that are accumulated and not delivered. Useful for exceptions only.
     *
     * @return array|MessageInterface[]
     */
    public function unshiftMessages(): array;

    /**
     * Get current processing message or last message that is getting processed
     *
     * @return MessageInterface|null
     */
    public function getMessage(): ?MessageInterface;

    /**
     * Check if there is anything in the buffer
     *
     * @return bool
     */
    public function hasBuffer(): bool;
}