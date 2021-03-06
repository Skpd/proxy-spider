<?php

namespace ProxySpider\Spider;

use ProxySpider\Entity\Proxy;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use RuntimeException;

class Text implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const IP_REGEX = '(?:^|\D)(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})';
    const PORT_REGEX = '([0-9]{2,5})(?:$|\D)';

    function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function grabIt($urlOrPath)
    {
        try {
            $source = new \SplFileObject($urlOrPath);
        } catch (RuntimeException $e) {
            $this->logger->alert("Failed to open URL '$urlOrPath'", [$e->getMessage()]);
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
}