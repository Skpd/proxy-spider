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
$validator = new \ProxySpider\Validator('spider.dev0.in/check.php', $logger);

$service = new \ProxySpider\Service\Spider(
    $entityManager->getRepository('ProxySpider\Entity\Proxy'),
    $logger,
    $validator
);

$logger->info('Working on proxyspy');
$service->gather('http://txt.proxyspy.net/proxy.txt');

$logger->info('Working on proxylists.net');
$service->gather('http://www.proxylists.net/http_highanon.txt');

$logger->info('Working on orcahub');
$client = new \GuzzleHttp\Client();
$client->setDefaultOption('headers/User-Agent', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.71 Safari/537.36');
$response = $client->get('http://orcahub.com/proxy-list/');
if (preg_match_all("#href='([^']+)'#i", $response->getBody()->getContents(), $m)) {

    $logger->debug('Found ' . count($m[1]) . ' pages.');

    foreach ($m[1] as $link) {
        $logger->debug("Loading $link");
        $service->gather("http://orcahub.com/proxy-list/$link");
    }
}


$logger->info('Done');