<?php

namespace ProxySpider\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class Spider
{
    /** @var EntityManager */
    private $em;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(EntityManager $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    public function checkProxies()
    {

    }
}