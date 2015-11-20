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
        $this->destination->fwrite(
            date(DATE_ATOM)
            . " ($level) "
            . $message
            . ' [' . implode(':', $context) . ']'
            . PHP_EOL
        );
    }
}