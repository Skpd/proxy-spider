#!/usr/bin/php
<?php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use GuzzleHttp\Exception\RequestException;
use ProxySpider\Service\Spider;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

//http://php.net/manual/ru/function.mb-detect-encoding.php#91051 not tested
define('UTF32_BIG_ENDIAN_BOM', chr(0x00) . chr(0x00) . chr(0xFE) . chr(0xFF));
define('UTF32_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE) . chr(0x00) . chr(0x00));
define('UTF16_BIG_ENDIAN_BOM', chr(0xFE) . chr(0xFF));
define('UTF16_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE));
define('UTF8_BOM', chr(0xEF) . chr(0xBB) . chr(0xBF));

require_once __DIR__ . '/../vendor/autoload.php';

$paths = ["src/ProxySpider/Entity"];
$dbParams = include __DIR__ . '/../config/db.local.php';
$config = Setup::createAnnotationMetadataConfiguration($paths);
$entityManager = EntityManager::create($dbParams, $config);

$logger = new \ProxySpider\Logger(new SplFileObject('php://output'));
$validator = new \ProxySpider\Validator('spider.dev0.in/check.php', $logger);

$service = new Spider(
    $entityManager->getRepository('ProxySpider\Entity\Proxy'),
    $logger,
    $validator
);

$definition = new InputDefinition([
    new InputArgument('link', InputArgument::IS_ARRAY),
    new InputOption('link', InputOption::VALUE_IS_ARRAY),
    new InputOption('follow', InputOption::VALUE_OPTIONAL),
]);

$input = new ArgvInput($argv, $definition);

if ($input->getOption('link')) {
    foreach ($input->getArgument('link') as $link) {
        getPage($link, $input->getOption('follow'), $logger, $service);
    }
} else {
    $logger->info('Working on proxyspy');
    $service->gather('http://txt.proxyspy.net/proxy.txt');

    $logger->info('Working on proxylists.net');
    $service->gather('http://www.proxylists.net/http_highanon.txt');
    $service->gather('http://www.proxylists.net/http.txt');
}


$logger->info('Done');

function detectUtfEncoding($str)
{
    $first2 = substr($str, 0, 2);
    $first3 = substr($str, 0, 3);
    $first4 = substr($str, 0, 4);

    if ($first3 == UTF8_BOM) return 'UTF-8';
    if ($first4 == UTF32_BIG_ENDIAN_BOM) return 'UTF-32BE';
    if ($first4 == UTF32_LITTLE_ENDIAN_BOM) return 'UTF-32LE';
    if ($first2 == UTF16_BIG_ENDIAN_BOM) return 'UTF-16BE';
    if ($first2 == UTF16_LITTLE_ENDIAN_BOM) return 'UTF-16LE';

    return false;
}

function getPage($link, $follow, \Psr\Log\LoggerInterface $logger, Spider $service, $cache = [])
{
    $logger->info("Working on $link");

    $client = new \GuzzleHttp\Client();
    $client->setDefaultOption('headers/User-Agent', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.71 Safari/537.36');

    try {
        $response = $client->get($link);
    } catch (RequestException $e) {
        $logger->alert($e->getMessage());
        return;
    }

    $contents = $response->getBody()->getContents();

    $guessedEncoding = detectUtfEncoding(substr($contents, 0, 5));
    if ($guessedEncoding !== false) {
        $logger->debug("Converting from $guessedEncoding to UTF-8");
        $contents = iconv($guessedEncoding, "UTF-8", $contents);
    }

    $filename = sys_get_temp_dir() . '/spider_' . uniqid();
    file_put_contents($filename, $contents);
    $service->gather($filename);
    unlink($filename);

    if ($follow) {
        if (preg_match_all("#href=(?:'|\")?([^'\"]+)(?:'|\")?#i", $contents, $m)) {
            $logger->debug('Following links');

            $total = count($m[1]);
            $logger->debug("Found $total links.");

            foreach ($m[1] as $n => $innerLink) {
                if (stristr($innerLink, 'img') !== false
                    || stristr($innerLink, 'js') !== false
                    || stristr($innerLink, 'css') !== false
                    || isset($cache[$innerLink])
                ) {
                    $logger->debug("Skipped $innerLink");
                    continue;
                }

                if (strpos($innerLink, 'http') !== false || strpos($innerLink, 'https') !== false) {
                    $url = $innerLink;
                } else {
                    $url = rtrim($link, '/') . '/' . ltrim($innerLink, '/');
                }

                $cache[$innerLink] = true;
                $logger->debug("Loading '$url' $n/$total");
                getPage($url, $follow, $logger, $service, $cache);
            }
        }
    }
}