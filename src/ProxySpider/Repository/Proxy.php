<?php

namespace ProxySpider\Repository;

use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;

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
     * @throws OptimisticLockException
     * @throws \Exception
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

            try {
                $this->_em->flush();
            } catch (OptimisticLockException $e) {
                throw $e;
            } finally {
                $this->_em->clear();
            }
        }
    }

    public function save(\ProxySpider\Entity\Proxy $proxy)
    {
        $this->_em->persist($proxy);
        $this->_em->flush($proxy);
    }
}
