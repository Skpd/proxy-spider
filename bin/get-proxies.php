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

$logger->info('Looking for Google results');
$baseUrl = 'https://ajax.googleapis.com/ajax/services/search/web?v=1.1&q=';
$searchTerm = 'inurl:txt+%2B:8888+%2B:3128';
$contents = file_get_contents($baseUrl . $searchTerm);
$json = json_decode($contents, true);
if (!empty($json) && $json['responseStatus'] === 200) {
    $logger->debug("Found ~{$json['responseData']['cursor']['resultCount']} pages");
    foreach ($json['responseData']['cursor']['pages'] as $page) {
        $contents = file_get_contents($baseUrl . $searchTerm . '&start=' . $page['start']);
        $json = json_decode($contents, true);

        if (!empty($json) && $json['responseStatus'] === 200) {
            foreach ($json['responseData']['results'] as $result) {
                $logger->debug("Found URL: {$result['url']}");
                $service->gather($result['url']);
            }
        } else {
            $logger->alert("Can't get search results page. Contents: $contents");
            break;
        }
    }
} else {
    $logger->alert("Can't get Google results. Contents: $contents");
}


$logger->info('Done');