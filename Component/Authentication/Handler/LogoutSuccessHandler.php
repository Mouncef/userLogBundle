<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 13/11/2017
 * Time: 15:55
 */

namespace Orca\UserLogBundle\Component\Authentication\Handler;


use Doctrine\ORM\EntityManager;
use Orca\UserLogBundle\Entity\TblUserLog;
use SunCat\MobileDetectBundle\DeviceDetector\MobileDetector;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Symfony\Component\Routing\Router;
use Orca\UserLogBundle\DB\GeoIPOrca;



class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{

    protected $em;
    protected $router;
    protected $mobileDetector;
    protected $storage;
    protected $container;

    public function __construct(EntityManager $em, Router $router, MobileDetector $mobileDetector, TokenStorage $storage, ContainerInterface $container)
    {
        $this->em = $em;
        $this->router = $router;
        $this->mobileDetector = $mobileDetector;
        $this->storage = $storage;
        $this->container = $container;
    }
    
    public function onLogoutSuccess(Request $request)
    {
        if(is_null($this->storage->getToken())){
            $user = $request->getSession()->get('connected')->getUserId();
        } else {
            $user = $this->storage->getToken()->getUser()->getUserId();
        }

        $ip = $request->getClientIp();

        $var = $this->container->getParameter('orca_user_log.userlog_entity');
        $userLog = new $var();

        $userLog->setUser($user);
        $userLog->setDate(new \DateTime('now'));
        $userLog->setAction('Logout_BO');
        $userLog->setIp($ip);
        $userLog->setRouteName($request->attributes->get('_route'));
        $userLog->setUri($request->getRequestUri());
        $userLog->setErrorCode('200');


        if ($ip == '::1' or $ip =='127.0.0.1'){
            $userLog->setPays('Localhost');
            $userLog->setVille('Localhost');
            $userLog->setCodePays('Localhost');
        } else {

            $geoIPORCA = new GeoIPOrca();
            $vars = $geoIPORCA->getInfoIP();

            $userLog->setPays($vars['country']);
            $userLog->setVille($vars['city']);
            $userLog->setCodePays($vars['isoCode']);
        }


        $terminalDetector = $this->container->get('mobile_detect.mobile_detector');

        if ($terminalDetector->isTablet()) {
            $userLog->setTerminal('Tablet');

            if ($terminalDetector->isIOS()){
                $userLog->setTerminalType('IOS');
            } elseif ($terminalDetector->isAndroidOs()){
                $userLog->setTerminalType('Android');
            } elseif ($terminalDetector->isWindowsMobileOs()) {
                $userLog->setTerminalType('Windows Phone');
            } else {
                $userLog->setTerminalType('OS non reconnu !');
            }

        } elseif ($terminalDetector->isMobile()) {
            $userLog->setTerminal('Mobile');

            if ($terminalDetector->isIOS()){
                $userLog->setTerminalType('IOS');
            } elseif ($terminalDetector->isAndroidOs()){
                $userLog->setTerminalType('Android');
            } elseif ($terminalDetector->isWindowsMobileOs()) {
                $userLog->setTerminalType('Windows Phone');
            } else {
                $userLog->setTerminalType('OS non reconnu !');
            }

        } else {
            $userLog->setTerminal('Desktop');
            $userLog->setTerminalType('Navigateur Web');
        }

        $em = $this->em;
        $em->persist($userLog);
        $em->flush();

        $session = $request->getSession();
        $session->clear();

        $response = new RedirectResponse($this->router->generate('userLog_homepage_login'));

        return $response;
    }
}