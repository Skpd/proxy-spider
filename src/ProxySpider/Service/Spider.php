<?php

namespace ProxySpider\Service;

use Doctrine\Common\Persistence\ObjectRepository;
use ProxySpider\Entity\Proxy;
use ProxySpider\Entity\ValidationLog;
use ProxySpider\Repository\Proxy as ProxyRepository;
use ProxySpider\Spider\Text;
use ProxySpider\Validator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class Spider implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var ProxyRepository */
    private $repo;
    /** @var Validator */
    private $validator;

    /**
     * @param ObjectRepository $repo
     * @param LoggerInterface $logger
     * @param Validator $validator
     */
    public function __construct(ObjectRepository $repo, LoggerInterface $logger, Validator $validator)
    {
        $this->logger = $logger;
        $this->repo = $repo;
        $this->validator = $validator;
    }

    public function gather($url)
    {
        $spider = new Text($this->logger);
        $this->logger->debug('Parsing source');

        try {
            $proxies = $spider->grabIt($url);
        } catch (\RuntimeException $e) {
            $this->logger->critical('Failed to process');
            return;
        }

        $this->logger->debug('Got ' . count($proxies) . ' proxies');
        $this->repo->saveAll($proxies);
    }

    public function refreshProxies()
    {
        $this->logger->info('Getting proxies for refresh.');
        $proxies = $this->repo->getForRefresh();

        $this->logger->debug('Found ' . count($proxies) . ' proxies.');

        $this->logger->info('Starting Validation.');
        $this->validator->validate($proxies, [$this, 'markAsGood'], [$this, 'markAsBad']);
        $this->logger->info('We are done.');
    }

    public function markAsGood(Proxy $proxy, $time)
    {
        $this->logger->debug("Proxy #{$proxy->getId()} - $time");

        $proxy->setPing((int)($time * 1000));
        $proxy->setPostEnabled(true);

        $log = new ValidationLog();
        $log->setProxy($proxy);
        $log->setResponseTime($proxy->getPing());
        $log->setStatus(ValidationLog::STATUS_OK);

        $proxy->getValidationLogs()->add($log);
        $this->repo->save($proxy);
    }

    public function markAsBad(Proxy $proxy)
    {
        $proxy->setPing(null);

        $log = new ValidationLog();
        $log->setProxy($proxy);
        $log->setResponseTime($this->validator->getTimeout() * 1000);
        $log->setStatus(ValidationLog::STATUS_BAD);

        $proxy->getValidationLogs()->add($log);

        $this->repo->save($proxy);
    }
}