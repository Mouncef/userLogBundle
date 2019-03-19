<?php

namespace Orca\UserLogBundle\Model;

use Doctrine\ORM\Mapping as ORM;


/**
 * Class TblUserModel
 * @package Orca\UserLogBundle\Model
 *
 * @ORM\MappedSuperclass()
 */
class TblUserModel
{

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime")
     */
    protected $date;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer")
     */
    protected $user;

    /**
     * @var string
     *
     * @ORM\Column(name="pays", type="string", length=255, nullable=true)
     */
    protected $pays;
    /**
     * @var string
     *
     * @ORM\Column(name="code_pays", type="string", length=255, nullable=true)
     */
    protected $codePays;
    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=255, nullable=true)
     */
    protected $ip;

    /**
     * @var string
     *
     * @ORM\Column(name="ville", type="string", length=255, nullable=true)
     */
    protected $ville;

    /**
     * @var string
     *
     * @ORM\Column(name="terminal", type="string", length=255, nullable=true)
     */
    protected $terminal;

    /**
     * @var string
     *
     * @ORM\Column(name="action", type="string", length=255, nullable=true)
     */
    protected $action;

    /**
     * @var string
     *
     * @ORM\Column(name="route_name", type="string", length=255, nullable=true)
     */
    protected $routeName;

    /**
     * @var string
     *
     * @ORM\Column(name="uri", type="text", nullable=true)
     */
    protected $uri;

    /**
     * @var string
     *
     * @ORM\Column(name="error_code", type="string", length=255, nullable=true)
     */
    protected $errorCode;

    /**
     * @var string
     *
     * @ORM\Column(name="terminal_type", type="string", length=255, nullable=true)
     */
    protected $terminalType;

    /**
     * @ORM\Column(name="header", type="text", nullable=true)
     */
    protected $header;

    /**
     * @ORM\Column(name="post_params", type="text", nullable=true)
     */
    protected $postParams;

    /**
     * @ORM\Column(name="get_params", type="text", nullable=true)
     */
    protected $getParams;

    /**
     * @ORM\Column(name="exception_msg", type="text", nullable=true)
     */
    protected $exceptionMsg;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return int
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param int $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getPays()
    {
        return $this->pays;
    }

    /**
     * @param string $pays
     */
    public function setPays($pays)
    {
        $this->pays = $pays;
    }

    /**
     * @return string
     */
    public function getTerminal()
    {
        return $this->terminal;
    }

    /**
     * @param string $terminal
     */
    public function setTerminal($terminal)
    {
        $this->terminal = $terminal;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * @return string
     */
    public function getVille()
    {
        return $this->ville;
    }

    /**
     * @param string $ville
     */
    public function setVille($ville)
    {
        $this->ville = $ville;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @return string
     */
    public function getRouteName()
    {
        return $this->routeName;
    }

    /**
     * @param string $routeName
     */
    public function setRouteName($routeName)
    {
        $this->routeName = $routeName;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * @return string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @param string $errorCode
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;
    }

    /**
     * @return string
     */
    public function getTerminalType()
    {
        return $this->terminalType;
    }

    /**
     * @param string $terminalType
     */
    public function setTerminalType($terminalType)
    {
        $this->terminalType = $terminalType;
    }

    /**
     * @return string
     */
    public function getCodePays()
    {
        return $this->codePays;
    }

    /**
     * @param string $codePays
     */
    public function setCodePays($codePays)
    {
        $this->codePays = $codePays;
    }

    /**
     * @return mixed
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @param mixed $header
     */
    public function setHeader($header)
    {
        $this->header = $header;
    }

    /**
     * @return mixed
     */
    public function getPostParams()
    {
        return $this->postParams;
    }

    /**
     * @param mixed $postParams
     */
    public function setPostParams($postParams)
    {
        $this->postParams = $postParams;
    }

    /**
     * @return mixed
     */
    public function getGetParams()
    {
        return $this->getParams;
    }

    /**
     * @param mixed $getParams
     */
    public function setGetParams($getParams)
    {
        $this->getParams = $getParams;
    }

    /**
     * @return mixed
     */
    public function getExceptionMsg()
    {
        return $this->exceptionMsg;
    }

    /**
     * @param mixed $exceptionMsg
     */
    public function setExceptionMsg($exceptionMsg)
    {
        $this->exceptionMsg = $exceptionMsg;
    }
}
