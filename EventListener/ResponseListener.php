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
use Orca\UserLogBundle\DB\GeoIPOrca;

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

        $header = $request->headers->all();
        $postParameters = $request->request->all();
        $getParameters = $request->query->all();


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

        //&& $action == $controller && $action != null
        if ($wdt != '_wdt' && $masterRequest == true && $uri == $url ){

            // get User()

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


            if ($em->isOpen()){
                // inserting
                //!empty($routeName) &&
                if ( $routeName!=='fos_js_routing_js' && $routeName!=='_wdt') {


                    $userLog = new TblUserLog();

                    $userLog->setDate(new \DateTime('now'));
                    if ($routeName == null){
                        $routeName = "No Route !";
                    }
                    $userLog->setRouteName($routeName);
                    if ($action == null){
                        $action = "No Action !";
                    }
                    $userLog->setAction($action);

                    if (strpos($uri,"processlist")!= false){
                        $uri = '/userLogChart/processlist';
                    }
                    $userLog->setUri($uri);

                    $userLog->setUser($user);
                    $userLog->setIp($ip);

                    if ($ip == '::1' or $ip == '127.0.0.1') {
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

                    $userLog->setHeader(json_encode($header));
                    $userLog->setPostParams(json_encode($postParameters));
                    $userLog->setGetParams(json_encode($getParameters));

                    $em->persist($userLog);
                    $em->flush();

                }
            }



        }







    }
}