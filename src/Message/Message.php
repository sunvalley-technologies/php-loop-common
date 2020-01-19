<?php

namespace SunValley\LoopUtil\Common\Message;

/**
 * Class Message represents a RFC(2)822 message
 *
 * @package SunValley\LoopUtil\Common\Server\Message
 */
class Message implements MessageInterface
{
    private $protocol = null;
    private $headers = [];
    private $ciHeaders = [];
    private $body = null;

    protected $eol = "\r\n";

    public function __construct(array $headers, ?string $protocol = null, ?string $body = null, string $eol = "\r\n")
    {
        // for parsing
        $this->headers = $headers;
        // for checking
        if (!empty($headers)) {
            $this->ciHeaders = array_combine(array_map('strtolower', array_keys($headers)), array_values($headers));
        }
        $this->protocol = $protocol;
        $this->body = $body;
        $this->eol = $eol;
    }

    /** @inheritDoc */
    public function hasHeader(string $name): bool
    {
        return isset($this->ciHeaders[strtolower($name)]);
    }

    /** @inheritDoc */
    public function getHeader(string $name): ?string
    {
        return $this->ciHeaders[strtolower($name)] ?? null;
    }

    /** @inheritDoc */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /** @inheritDoc */
    public function getProtocol(): ?string
    {
        return $this->protocol;
    }

    /** @inheritDoc */
    public function setBody(?string $body): void
    {
        $this->body = $body;
    }

    /** @inheritDoc */
    public function getBody(): ?string
    {
        return $this->body;
    }

    /**
     * Get defined end of line for this message. Used to build the message back to transport format.
     *
     * @return string
     */
    public function getEol(): string
    {
        return $this->eol;
    }

    /**
     * Set defined end of line for this message. Used to build the message back to transport format.
     *
     * @param string $eol
     */
    public function setEol(string $eol): void
    {
        $this->eol = $eol;
    }

    /** @inheritDoc */
    public function toTransport(): string
    {
        return ((string)$this) . $this->eol . $this->eol;
    }

    public function __toString(): string
    {
        $str = '';
        $eol = $this->eol;
        if ($this->protocol !== null) {
            $str = $this->protocol . $eol;
        }

        $headers = $this->headers;
        $body = $this->body;
        if ($body !== null) {
            $body = $this->encodeBody($body);
            $headers['Content-Length'] = strlen($body);
        }

        foreach ($headers as $key => $value) {
            $value = $this->encodeHeader($value);
            $str .= "{$key}: {$value}$eol";
        }
        $str .= "$eol";

        if ($body !== null) {
            $body = $this->encodeBody($body);

            $str .= $body;
        }

        return $str;
    }

    /**
     * Encodes a single header
     *
     * @param string $value
     *
     * @return string
     */
    protected function encodeHeader(string $value): string
    {
        return $value;
    }

    /**
     * Encodes body
     *
     * @param string $body
     *
     * @return string
     */
    protected function encodeBody(string $body): string
    {
        return $body;
    }
}