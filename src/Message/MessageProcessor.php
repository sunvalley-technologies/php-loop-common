<?php


namespace SunValley\LoopUtil\Common\Message;

use Exception;
use SunValley\LoopUtil\Common\Message\Exception\MalformedMessageException;

/**
 * Class MessageProcessor is a stream simple message processor. Can parse RFC (2)822 style messages with Content-Length header.
 *
 * ### Usage
 *
 * ````php
 * <?php
 * $parser = new MessageProcessor("\n");
 * $stream->on('data', function($chunk) use ($parser) {
 *     foreach($parser->feed($chunk) as $message) {
 *
 *     }
 * });
 * ?>
 * ````
 *
 * This parser depends on `Content-Length` header set.
 *
 * @package SunValley\LoopUtil\Common\Server\Message
 */
class MessageProcessor
{

    protected $buffer = '';
    /** @var MessageInterface|null */
    protected $message;
    /** @var array|MessageInterface[] */
    protected $messages = [];
    protected $eol;

    /**
     * MessageProcessor constructor.
     *
     * @param string|null $eol Set end of line separator. If NULL is given, both CRLF and LFLF command endings are checked on each message and message is built with the found eol.
     */
    public function __construct(?string $eol = null)
    {
        $this->setEol($eol);
    }

    /**
     * Set end of line
     *
     * @param string|null $eol Set end of line separator. If NULL is given, both CRLF and LFLF command endings are checked on each message and message is built with the found eol.
     */
    public function setEol(?string $eol): void
    {
        $this->eol = $eol;
    }

    /**
     * Get end of line separator
     *
     * @return string|null
     */
    public function getEol(): ?string
    {
        return $this->eol;
    }

    /**
     * Feed data to parse and return messages after removing them
     *
     * @param string $data Data to be fed
     *
     * @return array Removes and returns the parsed messages
     * @throws MalformedMessageException
     */
    public function feed(string $data): array
    {
        $this->_feed($data);

        return $this->unshiftMessages();
    }

    /**
     * Feed data to parse and returns the count of messages.
     *
     * @param string $data Data to be fed
     *
     * @return int Returns the current count of messages
     * @throws MalformedMessageException
     */
    public function feedAndCount(string $data): int
    {
        $this->_feed($data);

        return count($this->messages);
    }

    /**
     * Internal feed method that is recurred
     *
     * @param string $data
     * @return int
     * @throws MalformedMessageException
     */
    protected function _feed(string $data): int
    {
        if ($this->message === null) {
            $this->feedHeaders($data);
        } else {
            $this->feedBody($data);
        }

        return count($this->messages);
    }

    /**
     * Get all Messages that are accumulated and not delivered. Useful for exceptions only.
     *
     * @return array|MessageInterface[]
     */
    public function unshiftMessages(): array
    {
        $messages = array_merge($this->messages, []);
        $this->messages = [];

        return $messages;
    }

    /**
     * @param string $data
     *
     * @throws MalformedMessageException
     */
    protected function feedHeaders(string $data): void
    {
        $this->buffer .= $data;
        [$eol, $eolPos] = $this->findEol();

        if ($eol !== null) {
            if ($eolPos === 0) {
                $headersString = '';
            } else {
                $headersString = substr($this->buffer, 0, $eolPos) ;
            }
            
            $this->buffer = substr($this->buffer, $eolPos + strlen($eol) * 2);
            if ($this->buffer === false) {
                $this->buffer = '';
            }
            
            // handle extra spaces caused by buggy clients here
            $cleanedHeader = trim($headersString);
            if (empty($cleanedHeader)) {
                $this->buffer = substr($this->buffer, strlen($headersString));
                if (empty($this->buffer)) {
                    $this->buffer = '';

                    return;
                }

                $this->_feed('');

                return;
            }
            $this->message = $this->buildMessageFromHeaders($headersString, $eol);
            $this->feedBody();
        }
    }

    /**
     * @param string $headersString
     * @param string $eol
     *
     * @return MessageInterface|Message
     * @throws MalformedMessageException
     */
    protected function buildMessageFromHeaders(string $headersString, string $eol): MessageInterface
    {
        // regex unfolds the header string, trim cleans up extra whitespaces before and after header string
        $cleanedHeaderString = trim($headersString);
        $unfoldedHeaderString = $this->unfoldHeaderString($cleanedHeaderString, $eol);
        $headers = explode($eol, $unfoldedHeaderString);
        if (empty($headers)) {
            throw new MalformedMessageException('Dropping empty/invalid message');
        }

        $protocol = null;
        $firstLine = reset($headers);
        if (!$this->isHeaderLine($firstLine)) {
            $protocol = array_shift($headers);
            try {
                $this->validateProtocol($protocol);
            } catch (Exception $e) {
                throw new MalformedMessageException(
                    sprintf(
                        'Validating protocol failed because %s. Protocol line is "%s".',
                        $e->getMessage(),
                        $protocol
                    ), $e->getCode(), $e
                );
            }
        }

        if (empty($headers)) {
            throw new MalformedMessageException('No headers found!');
        }
        
        $parsed = [];
        foreach ($headers as $headerLine) {
            $headerLine = trim($headerLine);
            $split = explode(':', $headerLine, 2);
            if (!isset($split[1])) {
                throw new MalformedMessageException(
                    sprintf("Malformed header message where current buffer is \n <<<  %s  >>>\n\n", $this->buffer)
                );
            }
            [$name, $value] = $split;
            $name = trim($name);
            $value = trim($value);
            $value = $this->decodeHeaderValue($value);
            $parsed[$name] = $value;
        }

        return $this->generateMessage($protocol, $parsed, $eol);
    }

    /**
     * Validate the protocol and throw an exception if format is wrong.
     *
     * @param string $protocol
     *
     * @throws Exception
     */
    protected function validateProtocol(string $protocol): void
    {
    }

    protected function generateMessage(?string $protocol, array $headers, string $eol): MessageInterface
    {
        return new Message($headers, $protocol, null, $eol);
    }

    /**
     * Feeds body, called after a message is created with headers
     *
     * @param string|null $data
     *
     * @throws MalformedMessageException
     */
    protected function feedBody(string $data = null): void
    {
        if ($data !== null) {
            $this->buffer .= $data;
        }
        if (!$this->message->hasHeader('Content-Length')) {
            $remainingData = $this->buffer;
            $this->done();
            if (!empty($remainingData)) {
                $this->_feed($remainingData);
            }

            return;
        }
        $length = (int)$this->message->getHeader('Content-Length');
        if (strlen($this->buffer) < $length) {
            //Wait for buffer to fill
            return;
        }
        if ($length > 0) {
            $body = $this->decodeBody(substr($this->buffer, 0, $length));
            $this->message->setBody($body);
        }
        $remainingData = substr($this->buffer, $length);
        $this->done();
        if (!empty($remainingData)) {
            $this->_feed($remainingData);
        }

        return;
    }


    /**
     * Decodes a single header
     *
     * @param string $value
     *
     * @return string
     */
    protected function decodeHeaderValue(string $value): string
    {
        return $value;
    }

    /**
     * Decodes body
     *
     * @param string $body
     *
     * @return string
     */
    protected function decodeBody(string $body): string
    {
        return $body;
    }

    /**
     * Called when a message is completed
     */
    protected function done(): void
    {
        $this->buffer = '';
        $this->messages[] = $this->message;
        $this->message = null;
    }

    /**
     * Check if  the given line is a header line
     *
     * @param $string
     *
     * @return bool
     */
    protected function isHeaderLine($string): bool
    {
        return preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+:/', trim($string));
    }

    /**
     * Unfold LWSP
     *
     * @param string $headerString
     * @param string $eol
     * @return string|string[]|null
     */
    protected function unfoldHeaderString(string $headerString, string $eol)
    {
        return preg_replace("/${eol}(\t|\s)+/", ' ', $headerString);
    }

    /**
     * Try to determine the eol from current buffer
     *
     * @return array An array with first element as end of line and second element as where it is found. If not found, then elements set as NULL and false respectively.
     */
    protected function findEol(): array
    {
        $eol = $this->eol;
        if ($this->eol !== null) {
            $endOfHeaders = strpos($this->buffer, $eol . $eol);
        } else {
            $endOfHeaders = false;
        }

        if ($endOfHeaders === false) {
            $eol = "\n";
            $endOfHeaders = strpos($this->buffer, $eol . $eol);
            if ($endOfHeaders === false) {
                $eol = "\r\n";
                $endOfHeaders = strpos($this->buffer, $eol . $eol);
            }
        }

        if ($endOfHeaders === false) {
            $eol = null;
        }

        return [$eol, $endOfHeaders];
    }


}