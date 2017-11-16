<?php
/**
 * Created by PhpStorm.
 * User: PC_MA27
 * Date: 15/11/2017
 * Time: 10:22
 */

namespace Orca\UserLogBundle\EventListener;


use Exception;
use Orca\UserLogBundle\Entity\TblUserLog;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class ResponseListener
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $controller = $event->getRequest()->attributes->get('_controller');
        $url = $event->getRequest()->server->get('REQUEST_URI');
        $wdt = substr($url, 1, 4);
        $masterRequest = $event->isMasterRequest();
        $request = $event->getRequest();
        $response = $event->getResponse();
        $security = $this->container->get('security.token_storage');
        $em = $this->container->get('doctrine.orm.entity_manager');
        $terminalDetector = $this->container->get('mobile_detect.mobile_detector');


        //Get Uri
        $uri = $event->getRequest()->getRequestUri();

        // Get Ip
        $ip = $request->getClientIp();

        //Get routeName
        $routeName = $request->attributes->get('_route');

        //Get Action
        $action = $request->attributes->get('_controller');

        //Get Status code
        $statusCode = $response->getStatusCode();


        if ($wdt != '_wdt' && $masterRequest == true && $uri == $url && $action == $controller && $action != null){

            // get User()
            if ($security->getToken() == null){
                $user ='0';
            } else {
                $user = $security->getToken()->getUser()->getUserId();
            }

            if ($em->isOpen()){
                // inserting

                if (!empty($routeName) && $routeName!=='fos_js_routing_js' && $routeName!=='_wdt') {


                    $userLog = new TblUserLog();

                    $userLog->setDate(new \DateTime('now'));
                    $userLog->setRouteName($routeName);
                    $userLog->setAction($action);
                    if (strlen($uri) > 250)
                    {
                        $userLog->setUri('Uri trop longue !!');
                    } else {
                        $userLog->setUri($uri);
                    }
                    $userLog->setUser($user);
                    $userLog->setIp($ip);

                    if ($ip == '::1' or $ip == '127.0.0.1') {
                        $userLog->setPays('Localhost');
                        $userLog->setVille('Localhost');
                    } else {
                        $url = 'http://www.geoplugin.net/json.gp?ip=' . $ip;
                        $result = file_get_contents($url);
                        $vars = json_decode($result, true);
                        $userLog->setPays($vars['geoplugin_countryName']);
                        $userLog->setVille($vars['geoplugin_city']);
                    }


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

                    $userLog->setErrorCode($statusCode);

                    $em->persist($userLog);
                    $em->flush();

                }
            }



        }







    }
}