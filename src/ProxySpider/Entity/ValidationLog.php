<?php

namespace ProxySpider\Entity;

use DateTime;
use DateTimeZone;

/**
 * @Entity()
 * @Table(name="validation_logs")
 */
class ValidationLog
{
    const STATUS_OK = 'ok';
    const STATUS_BAD = 'bad';

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
     * @var DateTime
     *
     * @Column(type="datetime", nullable=false)
     */
    private $date;

    /**
     * @var string
     *
     * @Column(type="string", nullable=true, length=64)
     */
    private $status;

    /**
     * @var int
     *
     * @Column(type="integer", nullable=true)
     */
    private $responseTime;

    /**
     * @var Proxy
     *
     * @ManyToOne(targetEntity="Proxy", inversedBy="validationLogs", cascade={"persist"})
     */
    private $proxy;

    #endregion

    public function __construct()
    {
        $this->date = new DateTime('now', new DateTimeZone('UTC'));
    }

    #region getters / setters
    /**
     * @return Proxy
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * @param Proxy $proxy
     */
    public function setProxy($proxy)
    {
        $this->proxy = $proxy;
    }

    /**
     * @return int
     */
    public function getResponseTime()
    {
        return $this->responseTime;
    }

    /**
     * @param int $responseTime
     */
    public function setResponseTime($responseTime)
    {
        $this->responseTime = $responseTime;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param DateTime $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
    #endregion
}