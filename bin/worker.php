#!/usr/bin/php
<?php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

require_once __DIR__ . '/../vendor/autoload.php';

$paths = ["src/ProxySpider/Entity"];
$dbParams = include __DIR__ . '/../config/db.local.php';
$config = Setup::createAnnotationMetadataConfiguration($paths);
$entityManager = EntityManager::create($dbParams, $config);

$logger = new \ProxySpider\Logger(new SplFileObject('php://output'));
$validator = new \ProxySpider\Validator('spider.dev0.in/check.php', $logger);

$service = new \ProxySpider\Service\Spider(
    $entityManager->getRepository('ProxySpider\Entity\Proxy'),
    $logger,
    $validator
);

$worker = new GearmanWorker();
$worker->addServer();

$worker->addFunction('spider.validate.proxies', function (GearmanJob $job) use ($service, $validator, $logger, $entityManager) {
    $logger->debug("Received job {$job->unique()}");
    $proxies = unserialize($job->workload());

    foreach ($proxies as &$proxy) {
        $proxy = $entityManager->merge($proxy);
    }

    $validator->setTimeout(3);
    $validator->validate($proxies, [$service, 'markAsGood'], [$service, 'markAsBad']);

    $logger->debug("{$job->unique()} done");
});

while ($worker->work()) ;