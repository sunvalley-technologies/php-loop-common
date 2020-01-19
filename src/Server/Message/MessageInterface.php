<?php

namespace SunValley\LoopUtil\Common\Server\Message;


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
     */
    public function setBody(?string $body): void;

    /**
     * Return given headers value
     *
     * @param string $name (case insensitive)
     *
     * @return mixed|null Null is returned if header is not set
     */
    public function getHeader(string $name);

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