<?php

namespace Orca\UserLogBundle\EventListener;

use Orca\UserLogBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Created by PhpStorm.
 * User: PC_MA29
 * Date: 17/04/2019
 * Time: 10:58
 */
class AuthenticationSuccessListener
{


    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event){
        $this->container->get('orca_user_log.service.login_success')->onLoginSuccess($event->getRequest());
    }

}