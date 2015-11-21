<?php

namespace ProxySpider;

use ProxySpider\Entity\Proxy;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class Validator implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private $handle;
    private $requests = [];

    private $timeout = 10;
    private $testUrl;

    public function __construct($testUrl, LoggerInterface $logger)
    {
        $this->testUrl = $testUrl;
        $this->handle = curl_multi_init();
        $this->logger = $logger;
    }

    /**
     * @param Proxy[] $proxies
     * @param callable $successCallback
     * @param callable $failureCallback
     */
    public function validate(array $proxies, callable $successCallback, callable $failureCallback)
    {
        $limit = 100;
        $batches = ceil(count($proxies) / $limit);

        $this->logger->debug("Splitting into $batches batches.");

        for ($i = 0; $i < $batches; $i++) {
            $this->logger->debug('Batch ' . ($i + 1) . "/$batches");
            $this->initializeRequests(array_slice($proxies, $i * $limit, $limit, true));
            $this->runRequests();
            $this->processRequests($successCallback, $failureCallback);
        }
    }

    /**
     * @param Proxy[] $proxies
     */
    private function initializeRequests(array $proxies)
    {
        $this->requests = [];

        foreach ($proxies as $proxy) {
            $ch = curl_init($this->testUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_CONNECTTIMEOUT => $this->timeout,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_POSTFIELDS => 'foo=bar',
                CURLOPT_PROXY => $proxy->getIp() . ':' . $proxy->getPort(),
                CURLOPT_RETURNTRANSFER => true
            ]);

            $this->requests[] = [
                'handle' => $ch,
                'proxy' => $proxy
            ];
            curl_multi_add_handle($this->handle, $ch);
        }
    }

    private function runRequests()
    {
        //see #63842 https://bugs.php.net/bug.php?id=63842
        if (curl_multi_select($this->handle) === -1) {
            usleep(100);
        }

        $active = 0;

        do {
            $mrc = curl_multi_exec($this->handle, $active);
        } while ($mrc === CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc === CURLM_OK) {
            //see #63842 https://bugs.php.net/bug.php?id=63842
            if (curl_multi_select($this->handle) === -1) {
                usleep(100);
            }

            do {
                $mrc = curl_multi_exec($this->handle, $active);
            } while ($mrc === CURLM_CALL_MULTI_PERFORM);
        }
    }

    private function processRequests(callable $successCallback, callable $failureCallback)
    {
        foreach ($this->requests as $request) {
            $content = curl_multi_getcontent($request['handle']);
            $info = curl_getinfo($request['handle']);

            if ($info['http_code'] == 200 && $content === 'ok') {
                call_user_func($successCallback, $request['proxy'], $info['total_time']);
            } else {
                call_user_func($failureCallback, $request['proxy']);
            }

            curl_multi_remove_handle($this->handle, $request['handle']);
        }
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }
}