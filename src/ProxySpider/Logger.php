<?php

namespace ProxySpider;

use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
{
    /** @var \SplFileObject */
    private $destination;

    function __construct(\SplFileObject $destination)
    {
        $this->destination = $destination;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        if (!empty($context)) {
            $contextString = ' [' . implode(':', $context) . ']';
        } else {
            $contextString = '';
        }

        $this->destination->fwrite(
            date(DATE_ATOM)
            . " ($level) "
            . $message
            . $contextString
            . PHP_EOL
        );
    }
}