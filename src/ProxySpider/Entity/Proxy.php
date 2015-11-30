<?php

namespace ProxySpider\Entity;

use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity(repositoryClass="ProxySpider\Repository\Proxy")
 * @Table(name="proxies", uniqueConstraints={@UniqueConstraint(name="search_idx", columns={"ip", "port"})})
 * @HasLifecycleCallbacks
 */
class Proxy
{
    #region fields
    /**
     * @var int
     *
     * @Column(type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @Column(type="string", nullable=true, length=64)
     */
    private $host;

    /**
     * @var int
     *
     * @Column(type="integer", nullable=false, options={"unsigned":true})
     */
    private $ip;

    /**
     * @var int
     *
     * @Column(type="integer", nullable=false)
     */
    private $port;

    /**
     * @var DateTime
     *
     * @Column(type="datetime", nullable=false)
     */
    private $created;

    /**
     * @var DateTime
     *
     * @Column(type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var DateTime
     *
     * @Column(type="datetime", nullable=false)
     */
    private $seen;

    /**
     * @var int
     *
     * @Column(type="integer", nullable=true)
     * @Index
     */
    private $ping;

    /**
     * @var int
     *
     * @Column(type="integer", nullable=true)
     */
    private $speed;

    /**
     * @var bool
     *
     * @Column(type="boolean", nullable=true)
     */
    private $postEnabled;

    /**
     * @var ArrayCollection|ValidationLog[]
     *
     * @OneToMany(targetEntity="ValidationLog", mappedBy="proxy")
     */
    private $validationLogs;
    #endregion

    public function __construct()
    {
        $this->created = new DateTime('now', new DateTimeZone('UTC'));
        $this->updated = new DateTime('now', new DateTimeZone('UTC'));
        $this->seen = new DateTime('now', new DateTimeZone('UTC'));
        $this->validationLogs = new ArrayCollection();
    }

    /** @PreUpdate */
    public function updateTimestamp()
    {
        $this->updated = new DateTime('now', new DateTimeZone('UTC'));
    }

    #region getters / setters
    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return int
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param int $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @return DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param DateTime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param DateTime $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * @return int
     */
    public function getPing()
    {
        return $this->ping;
    }

    /**
     * @param int $ping
     */
    public function setPing($ping)
    {
        $this->ping = $ping;
    }

    /**
     * @return int
     */
    public function getSpeed()
    {
        return $this->speed;
    }

    /**
     * @param int $speed
     */
    public function setSpeed($speed)
    {
        $this->speed = $speed;
    }

    /**
     * @return boolean
     */
    public function isPostEnabled()
    {
        return $this->postEnabled;
    }

    /**
     * @param boolean $postEnabled
     */
    public function setPostEnabled($postEnabled)
    {
        $this->postEnabled = $postEnabled;
    }

    /**
     * @return DateTime
     */
    public function getSeen()
    {
        return $this->seen;
    }

    /**
     * @param DateTime $seen
     */
    public function setSeen($seen)
    {
        $this->seen = $seen;
    }

    /**
     * @return ArrayCollection|ValidationLog[]
     */
    public function getValidationLogs()
    {
        return $this->validationLogs;
    }

    /**
     * @param ArrayCollection|ValidationLog[] $validationLogs
     */
    public function setValidationLogs($validationLogs)
    {
        $this->validationLogs = $validationLogs;
    }
    #endregion
}