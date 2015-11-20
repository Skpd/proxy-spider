#!/usr/bin/php
<?php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

require_once __DIR__ . '/../vendor/autoload.php';

$paths = ["src/ProxySpider/Entity"];
$dbParams = include __DIR__ . '/../config/db.php';
$config = Setup::createAnnotationMetadataConfiguration($paths);
$entityManager = EntityManager::create($dbParams, $config);

$logger = new \ProxySpider\Logger(new SplFileObject('php://output'));
$validator = new \ProxySpider\Validator('spider.dev0.in/check.php');

$service = new \ProxySpider\Service\Spider(
    $entityManager->getRepository('ProxySpider\Entity\Proxy'),
    $logger,
    $validator
);

$logger->info('Working on proxyspy');
$service->gather('http://txt.proxyspy.net/proxy.txt');
$logger->info('Done');