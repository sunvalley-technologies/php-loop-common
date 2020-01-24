<?php

namespace SunValley\LoopUtil\Common\Message;


/**
 * Class Message represents a RFC(2)822 message (with extra protocol line)
 *
 * @package SunValley\LoopUtil\Common\Server\Message
 */
interface MessageInterface
{

    /**
     * Returns true header $name is set
     *
     * @param string $name (case insensitive)
     *
     * @return bool
     */
    public function hasHeader(string $name): bool;

    /**
     * Set the body of the message
     *
     * @param string|null $body
     *
     * @return $this
     */
    public function setBody(?string $body): self;

    /**
     * Return given headers value
     *
     * @param string $name (case insensitive)
     *
     * @return string|null Null is returned if header is not set
     */
    public function getHeader(string $name): ?string;

    /**
     * Set a given header value
     *
     * @param string $name
     * @param string|null $value A NULL value deletes the header
     * @return $this
     */
    public function setHeader(string $name, ?string $value): self;

    /**
     * Get all headers
     *
     * @return array Headers in an associative array, keys as header name and values as header value
     */
    public function getHeaders(): array;

    /**
     * Returns the protocol (first line of the request)
     *
     * @return string
     */
    public function getProtocol(): ?string;

    /**
     * Set the protocol
     *
     * @param string|null $protocol NULL deletes the protocol
     * @return $this
     */
    public function setProtocol(?string $protocol): self;

    /**
     * Get the body of the message
     *
     * @return string|null
     */
    public function getBody(): ?string;

    /**
     * Returns a string to be sent via transport level
     *
     * @return string
     */
    public function toTransport(): string;
}