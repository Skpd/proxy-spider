<?php

namespace ProxySpider\Service;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\OptimisticLockException;
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

    public function gather($urlOrPath)
    {
        $spider = new Text($this->logger);
        $this->logger->debug("Parsing source $urlOrPath");

        try {
            $proxies = $spider->grabIt($urlOrPath);
        } catch (\RuntimeException $e) {
            $this->logger->critical('Failed to process');
            return;
        }

        $this->logger->debug('Got ' . count($proxies) . ' proxies');

        try {
            $this->repo->saveAll($proxies);
        } catch (OptimisticLockException $e) {
            $this->logger->critical("Lock failed, try to apply changes again.");
            throw $e;
        }
    }

    public function refreshProxies($parallel = false)
    {
        $this->logger->info('Getting proxies for refresh.');
        $proxies = $this->repo->getForRefresh();

        $this->logger->debug('Found ' . count($proxies) . ' proxies.');

        $this->logger->info('Starting Validation.');

        $gearman = null;

        if ($parallel) {
            $gearman = new \GearmanClient();
            $gearman->addServer();

            $logger = $this->logger;

            $gearman->setCompleteCallback(function (\GearmanTask $task) use ($logger) {
                $logger->debug($task->functionName() . ':' . $task->unique() . ' done.');
            });
        }

        $limit = 10;
        $batches = ceil(count($proxies) / $limit);

        $this->logger->debug("Splitting into $batches batches.");

        for ($i = 0; $i < $batches; $i++) {
            if ($parallel) {
                $gearman->addTask('spider.validate.proxies', serialize(array_slice($proxies, $i * $limit, $limit, true)));
            } else {
                $this->logger->debug('Batch ' . ($i + 1) . "/$batches");
                $this->validator->validate(
                    array_slice($proxies, $i * $limit, $limit, true),
                    [$this, 'markAsGood'],
                    [$this, 'markAsBad']
                );
            }
        }

        if ($parallel) {
            $gearman->runTasks();
        }


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
        $this->repo->save($proxy, $log);
    }

    public function markAsBad(Proxy $proxy)
    {
        $proxy->setPing(null);

        $log = new ValidationLog();
        $log->setProxy($proxy);
        $log->setResponseTime($this->validator->getTimeout() * 1000);
        $log->setStatus(ValidationLog::STATUS_BAD);

        $proxy->getValidationLogs()->add($log);

        $this->repo->save($proxy, $log);
    }
}