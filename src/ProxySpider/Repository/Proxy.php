<?php

namespace ProxySpider\Repository;

use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityRepository;

/**
 * Proxy
 */
class Proxy extends EntityRepository
{

    /**
     * @return \ProxySpider\Entity\Proxy[]
     */
    public function getForRefresh()
    {
        $result = $this->findAll();

        return $result;
    }

    /**
     * @param \ProxySpider\Entity\Proxy[] $proxies
     */
    public function saveAll(array $proxies)
    {
        foreach ($proxies as $proxy) {
            /** @var \ProxySpider\Entity\Proxy|null $existing */
            $existing = $this->findOneBy(['ip' => $proxy->getIp(), 'port' => $proxy->getPort()]);

            if ($existing === null) {
                $this->_em->persist($proxy);
            } else {
                $existing->setSeen(new DateTime('now', new DateTimeZone('UTC')));
                $this->_em->persist($existing);
            }
        }

        $this->_em->flush();
    }
}
