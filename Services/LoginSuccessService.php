<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 15/03/2019
 * Time: 10:40
 */

namespace Orca\UserLogBundle\Services;


use Doctrine\ORM\EntityManager;
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

class LoginSuccessService
{

    protected $container;
    protected $em;

    public function __construct(ContainerInterface $container, EntityManager $em)
    {
        $this->container = $container;
        $this->em = $em;
    }

    public function onLoginSuccess(Request $request){

        $security = $this->container->get('security.token_storage');
        $ip = $request->getClientIp();

        $logEnity = $this->container->getParameter('orca_user_log.userlog_entity');
        $userLog = new $logEnity();

        if (is_null($security->getToken()))
        {
            if (empty($request->getSession())){
                $user = 0;
            } elseif(empty($request->getSession()->get('connected'))) {
                $user = 0;
            }else{
                $user = $request->getSession()->get('connected')->getUserId();
            }
        } else {
            $user = $security->getToken()->getUser()->getUserId();
        }

        $userLog->setUser($user);
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
        $em->flush();

        return $userLog;
    }

}