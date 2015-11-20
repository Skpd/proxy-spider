<?php

namespace ProxySpider\Spider;

use ProxySpider\Entity\Proxy;
use Psr\Log\LoggerInterface;
use RuntimeException;

class Text
{
    const IP_REGEX = '(?:^|\D)(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})';
    const PORT_REGEX = '([0-9]{2,5})(?:$|\D)';
    /** @var LoggerInterface */
    private $logger;

    function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function grabIt($url)
    {
        try {
            $source = new \SplFileObject($url);
        } catch (RuntimeException $e) {
            $this->logger->alert("Failed to open URL '$url'");
            throw $e;
        }

        $proxies = [];

        while ($source->valid()) {
            try {
                $line = $source->fgets();
            } catch (RuntimeException $e) {
                $this->logger->alert("Failed to get string");
                throw $e;
            }

            if (!empty($line)) {
                if (preg_match('#' . self::IP_REGEX . ':' . self::PORT_REGEX . '#', $line, $m)) {
                    $ip = $m[1];
                    if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
                        $this->logger->debug("Invalid IP $ip");
                        continue;
                    }

                    $proxy = new Proxy();
                    $proxy->setIp(ip2long($ip));
                    $proxy->setPort((int)$m[2]);

                    $proxies[] = $proxy;
                }
            }
        }

        return $proxies;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     * @return Text
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
        return $this;
    }
}