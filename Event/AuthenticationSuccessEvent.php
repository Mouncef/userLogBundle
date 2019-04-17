<?php

namespace Orca\UserLogBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * AuthenticationSuccessEvent.
 *
 */
class AuthenticationSuccessEvent extends Event
{

    /**
     * @var UserInterface
     */
    protected $user;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @param UserInterface $user
     */
    public function __construct(UserInterface $user, Request $request)
    {
        $this->user     = $user;
        $this->request     = $request;
    }

    /**
     * @return UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }


}
