<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 12/03/2019
 * Time: 11:00
 */

namespace Orca\UserLogBundle\Subscriber;

//use Orca\UserLogBundle\Entity\TblUserLog;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\DriverException;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Doctrine\DBAL\Exception\SyntaxErrorException;
use Doctrine\ORM\ORMException;
use http\Exception\BadMethodCallException;
use Orca\UserLogBundle\Event\LoginSuccessfullEvent;
use Symfony\Component\Debug\Exception\ContextErrorException;
use Symfony\Component\Debug\Exception\UndefinedFunctionException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Orca\UserLogBundle\DB\GeoIPOrca;

class LogEventsSubscriber
{

    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public static function getSubscribedEvents()
    {

    }

    public function onKernelException(GetResponseForExceptionEvent $event){
        //var_dump($event);die();
        $controller = $event->getRequest()->attributes->get('_controller');
        $url = $event->getRequest()->server->get('REQUEST_URI');
        $wdt = substr($url, 1, 4);
        $masterRequest = $event->isMasterRequest();
        $request = $event->getRequest();
        $response = $event->getResponse();
        $security = $this->container->get('security.token_storage');
        $em = $this->container->get('doctrine.orm.entity_manager');
        $terminalDetector = $this->container->get('mobile_detect.mobile_detector');
        $exception = $event->getException();

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

        
        //$statusCode = $exception instanceof ContextErrorException ? 500 : $exception->getStatusCode();


        //&& $action == $controller && $action != null
        if ($wdt != '_wdt' && $masterRequest == true && $uri == $url){

            //var_dump("test");die();
            // get User()

            //Get Status codev
            if ($exception instanceof ContextErrorException || $exception instanceof BadMethodCallException || $exception instanceof ORMException || $exception instanceof UndefinedFunctionException || $exception instanceof \LogicException ||$exception instanceof SyntaxErrorException || $exception instanceof InvalidFieldNameException
                || $exception instanceof \UnexpectedValueException || $exception instanceof \Doctrine\DBAL\Exception\DriverException || $exception instanceof DBALException){
                $statusCode = 500;
            }else{
                $statusCode = $exception->getStatusCode();
            }

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


                    //$userLog = new TblUserLog();
                    $var = $this->container->getParameter('userlog_entity');
                    $userLog = new $var();
                    //var_dump($userLog);die();


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

                    $exceptionMsg = $exception->getMessage();
                    $userLog->setExceptionMsg($exceptionMsg);



                    $userLog->setHeader(json_encode($header));
                    $userLog->setPostParams(json_encode($postParameters));
                    $userLog->setGetParams(json_encode($getParameters));
                    //dump($userLog);die();
                    $em->persist($userLog);
                    $em->flush();

                    return $userLog;
                }
            }
        }
    }

    public function onAuthenticationSuccess(LoginSuccessfullEvent $event){
        //var_dump($event);die();
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        //var_dump($event->getResponse());
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
        $errorCodes = array(400, 401, 402,403,404,405,406,407,408,409,410,411,412,413,414,415,416,417,500,501,502,503,504,505);
        //var_dump($wdt,$masterRequest,$uri);die();

        if (!in_array($statusCode, $errorCodes)){
            //&& $action == $controller && $action != null
            if ($wdt != '_wdt' && $masterRequest == true && $uri == $url ){

                //var_dump("test");die();
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


                        //$userLog = new TblUserLog();
                        $var = $this->container->getParameter('userlog_entity');
                        $userLog = new $var();
                        //var_dump($userLog);die();


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

                        /*$errorCodes = array(400, 401, 402,403,404,405,406,407,408,409,410,411,412,413,414,415,416,417,500,501,502,503,504,505);

                        if (in_array($statusCode, $errorCodes)){
                            $exceptionMsg = $response->getContent();
                            $userLog->setExceptionMsg($exceptionMsg);
                        }*/



                        $userLog->setHeader(json_encode($header));
                        $userLog->setPostParams(json_encode($postParameters));
                        $userLog->setGetParams(json_encode($getParameters));
                        //dump($userLog);die();
                        $em->persist($userLog);
                        $em->flush();

                        return $userLog;
                    }
                }
            }
        }

    }
}