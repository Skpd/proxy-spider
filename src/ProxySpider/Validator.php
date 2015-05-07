<?php

namespace ProxySpider;

class Validator
{
    private $handle;
    private $requests = [];
    private $successCallback;
    private $failureCallback;

    private $timeout = 10;
    private $testUrl;

    public function __construct(callable $successCallback, callable $failureCallback, $testUrl)
    {
        $this->successCallback = $successCallback;
        $this->failureCallback = $failureCallback;
        $this->testUrl = $testUrl;

        $this->handle = curl_multi_init();
    }

    public function validate(array $proxies)
    {
        $this->initializeRequests($proxies);
        $this->runRequests();
        $this->processRequests();
    }

    /**
     * @param array $proxies
     */
    private function initializeRequests(array $proxies)
    {
        foreach ($proxies as $proxy) {
            $ch = curl_init($this->testUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_CONNECTTIMEOUT => $this->timeout,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_POSTFIELDS => 'foo=bar',
                CURLOPT_PROXY => $proxy,
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

    private function processRequests()
    {
        foreach ($this->requests as $request) {
            $content = curl_multi_getcontent($request['handle']);
            $info = curl_getinfo($request['handle']);

            if ($content === 'ok') {
                $this->{'successCallback'}($request['proxy'], $info['total_time']);
            } else {
                $this->{'failureCallback'}($request['proxy']);
            }

            curl_multi_remove_handle($this->handle, $request['handle']);
        }
    }
}