<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 09/11/2017
 * Time: 14:44
 */

namespace Orca\UserLogBundle\Component\Authentication\Handler;


use Doctrine\ORM\EntityManager;
use Orca\UserLogBundle\Entity\TblUserLog;
use SunCat\MobileDetectBundle\DeviceDetector\MobileDetector;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Orca\UserLogBundle\DB\GeoIPOrca;


class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{

    protected $em;
    protected $router;
    protected $mobileDetector;
    protected $container;

    public function __construct(EntityManager $em, Router $router, MobileDetector $mobileDetector, ContainerInterface $container)
    {
        $this->em = $em;
        $this->router = $router;
        $this->mobileDetector = $mobileDetector;
        $this->container = $container;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        //die('login');

        $user = $token->getUser();
        $ip = $request->getClientIp();


        $var = $this->container->getParameter('orca_user_log.userlog_entity');
        $userLog = new $var();

        $userLog->setUser($user->getUserId());
        $userLog->setDate(new \DateTime('now'));

        $userLog->setIp($ip);
        $userLog->setRouteName($request->attributes->get('_route'));

        $userLog->setAction('Login_BO');
        $userLog->setErrorCode('200');

        $userLog->setUri($request->getRequestUri());

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
        $em->flush($userLog);

        $session = new Session();
        $session->set('connected', $user);

        $response = new RedirectResponse($this->router->generate('userLog_homepage_login'));

        return $response;
    }

}