<?php
/**
 * Created by PhpStorm.
 * User: PC_MA27
 * Date: 20/11/2017
 * Time: 10:34
 */

namespace Orca\UserLogBundle\Controller;

use Orca\UserLogBundle\DB\GeoIPOrca;
use Orca\UserLogBundle\Entity\TblUserLog;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\DateTime;
use Doctrine\ORM\EntityManagerInterface;


class DashboardController extends Controller
{

    public function indexAction(Request $request)
    {
        $date = new \DateTime('now');
        $startDate = new \DateTime(date_format($date, 'Y').'-'.date_format($date, 'm').'-01');
        $startEnd = (new \DateTime($startDate->format('Y-m-t')))->add(new \DateInterval('P1D'));
        $start = $request->get('start',$startDate->format('Y-m-d')).' 00:00:00';
        $end = $request->get('end',$startEnd->format('Y-m-d')).' 23:59:59';

        $day = date_format($date, 'd');
        $month = date_format($date, 'm');
        $year = date_format($date, 'Y');
        $em = $this->getDoctrine()->getManager();


        $choosedYear = $request->get('year');
        if (!$choosedYear){
            $choosedYear = $year;
        }


        $em = $this->getDoctrine()->getManager();

        //$nbCNXByTerminalType = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getNbConnexionByTypeTerminal($month, $year);
       // $nbCNXByTerminal = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getNbConexionByTerminal($month, $year);
        $nbCNXByDay = $this->get('Orca\UserLogBundle\Repository\TblUserLogRepository')->getNbConnexionByDay($day, $month, $year);
        $nbErrorByDay = $this->get('Orca\UserLogBundle\Repository\TblUserLogRepository')->getNbErrorByDay($day, $month, $year);
        //var_dump($nbErrorByDay, $day,$month,$year);die();
        //$nbCNXByMonth = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getNbConnexionByMonth($month, $year);
        //$nbErrorByMonth = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getNbErrorsByMonth($month, $year);
        $months = $this->get('Orca\UserLogBundle\Repository\TblUserLogRepository')->getMonths($choosedYear);
        $nbCNXByTerminalAndByMonth = $this->get('Orca\UserLogBundle\Repository\TblUserLogRepository')->getNbConnexionByTerminalAndByMonth($choosedYear);
        $topfiveUsers = $this->get('Orca\UserLogBundle\Repository\TblUserLogRepository')->getTopFive($month,$year);
        $pays = $this->get('Orca\UserLogBundle\Repository\TblUserLogRepository')->getPays();
        /**
         * getByRange
         */
        $nbCNXByTerminalType = $this->get('Orca\UserLogBundle\Repository\TblUserLogRepository')->getNbConnexionByTypeTerminalRange($start, $end);
        $nbCNXByTerminal = $this->get('Orca\UserLogBundle\Repository\TblUserLogRepository')->getNbConexionByTerminalRange($start, $end);
        $nbCNXByMonth = $this->get('Orca\UserLogBundle\Repository\TblUserLogRepository')->getNbConnexionByRange($start, $end);
        $nbErrorByMonth = $this->get('Orca\UserLogBundle\Repository\TblUserLogRepository')->getNbErrorsByRange($start, $end);
        $topfiveUsers = $this->get('Orca\UserLogBundle\Repository\TblUserLogRepository')->getTopFiveRange($start,$end);
        //var_dump($topfiveUsers);die;

        $users = $ios = $navigator= array();
        $index  =0;
        $found = false;

        $yearsOfCNX = $this->get('Orca\UserLogBundle\Repository\TblUserLogRepository')->getYearsOfCNX();

        //for($i = 0 ; $i<5 ; $i++)$users[$i]['name']= $topfiveUsers[$i]['user'];

        for($i = 0 ; $i< count($topfiveUsers) ; $i++){
            if(!in_array($topfiveUsers[$i]['user'] , $users)){
                $user = $em->getRepository($this->getParameter('TblUserRepo'))->find($topfiveUsers[$i]['user']);
                $username = $user->__toString();
                //var_dump($username);die;
                $users[$index++] = $username;//$topfiveUsers[$i]['user'];
                if($topfiveUsers[$i]['terminalType'] == 'Navigateur Web'){
                    $navigator[$index] = $topfiveUsers[$i]['nbr'];
                    for ($j = $i+1 ; $j<count($topfiveUsers) ; $j++){
                        if ($topfiveUsers[$j]['terminalType'] == 'IOS' && $topfiveUsers[$j]['user'] == $topfiveUsers[$i]['user']){
                            $ios[$index] = $topfiveUsers[$j]['nbr'];
                            $found=1;
                        }
                    }
                    if (!$found)$ios[$index] = 0;
                    $found = false;
                }
                else if($topfiveUsers[$i]['terminalType'] == 'IOS'){
                    $ios[$index] = $topfiveUsers[$i]['nbr'];
                    for ($j = $i+1 ; $j<count($topfiveUsers) ; $j++){
                        if ($topfiveUsers[$j]['terminalType'] == 'Navigateur Web' && $topfiveUsers[$j]['user'] == $topfiveUsers[$i]['user']){
                            $navigator[$index] = $topfiveUsers[$j]['nbr'];
                            $found=1;
                        }
                    }
                    if (!$found)$ios[$index] = 0;
                    $found = false;
                }
            }
            if($index ==4)break;
        }

        return $this->render('@OrcaUserLog/Demo/dashboard.html.twig',[
            'nbCNXbyTypeTerminal'       => $nbCNXByTerminalType,
            'nbCNXbyTerminal'           => $nbCNXByTerminal,
            'nbCNXbyDay'                => $nbCNXByDay,
            'nbErrorbyDay'              => $nbErrorByDay,
            'nbCNXbyMonth'              => $nbCNXByMonth,
            'nbErrorbyMonth'            => $nbErrorByMonth,
            'months'                    => $months,
            'nbCNXbyTerminalAndbyMonth' => $nbCNXByTerminalAndByMonth,
            'topFiveUsers' => $users,
            'topIosUsers' => $ios,
            'topNavigatorUsers' => $navigator,
            'pays' => $pays,
            'years' =>  $yearsOfCNX
        ]);
    }

    public function connexionAction(Request $request)
    {
        $date = new \DateTime('now');

        $month = date_format($date, 'm');
        $startDate = new \DateTime(date_format($date, 'Y').'-'.date_format($date, 'm').'-01');
        $startEnd = (new \DateTime($startDate->format('Y-m-t')))->add(new \DateInterval('P1D'));
        $start = $request->get('start',$startDate->format('Y-m-d')).' 00:00:00';
        $end = $request->get('end',$startEnd->format('Y-m-d')).' 23:59:59';


        $em = $this->getDoctrine()->getManager();
        $connexions = $this->get('Orca\UserLogBundle\Repository\TblUserLogRepository')->getConnexions($start, $end);

        return $this->render('@OrcaUserLog/Demo/connexion.html.twig', [
            'connexions' => $connexions
        ]);
    }

    public function wsConnexionAction(Request $request)
    {
        $date = new \DateTime('now');
        $startDate = new \DateTime(date_format($date, 'Y').'-'.date_format($date, 'm').'-01');
        $startEnd = (new \DateTime($startDate->format('Y-m-t')))->add(new \DateInterval('P1D'));
        $start = $request->get('start',$startDate->format('Y-m-d')).' 00:00:00';
        $end = $request->get('end',$startEnd->format('Y-m-d')).' 23:59:59';

        $em = $this->getDoctrine()->getManager();

        $wsConnexions = $this->get('Orca\UserLogBundle\Repository\TblUserLogRepository')->getWsConnexions($start, $end);

        return $this->render('@OrcaUserLog/Demo/wsConnexion.html.twig', [
            'connexions' => $wsConnexions
        ]);
    }

    public function boAction(Request $request) {

        $date = new \DateTime('now');
        $startDate = new \DateTime(date_format($date, 'Y').'-'.date_format($date, 'm').'-01');
        $startEnd = (new \DateTime($startDate->format('Y-m-t')))->add(new \DateInterval('P1D'));
        $start = $request->get('start',$startDate->format('Y-m-d')).' 00:00:00';
        $end = $request->get('end',$startEnd->format('Y-m-d')).' 23:59:59';
        $nbday = '-'.$this->container->getParameter('userlog_nbdays').' day';
        if(is_null($nbday)){
            $nbday = '-4 day';
        }
        $mdate = $date->modify($nbday);

        $em = $this->getDoctrine()->getManager();
        $host = $request->getHttpHost();

        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository($this->getParameter('TblUserRepo'))->findAll();

        //$actions = $this->get('Orca\UserLogBundle\Repository\TblUserLogRepository')->getBoActions($start, $end);


        return $this->render('@OrcaUserLog/Demo/boActions.html.twig', [
            //'actions'        => $actions,
            'host'          => $host,
            'users' => $users
        ]);
    }

    public function wsAction(Request $request) {

        $date = new \DateTime('now');
        $startDate = new \DateTime(date_format($date, 'Y').'-'.date_format($date, 'm').'-01');
        $startEnd = (new \DateTime($startDate->format('Y-m-t')))->add(new \DateInterval('P1D'));
        $start = $request->get('start',$startDate->format('Y-m-d')).' 00:00:00';
        $end = $request->get('end',$startEnd->format('Y-m-d')).' 23:59:59';
        $nbday = '-'.$this->container->getParameter('userlog_nbdays').' day';
        if(is_null($nbday)){
            $nbday = '-4 day';
        }
        $mdate = $date->modify($nbday);
        $month = date_format($mdate, 'm');
        $year = date_format($mdate, 'Y');
        $day = date_format($mdate, 'd');

        $em = $this->getDoctrine()->getManager();
        $host = $request->getHttpHost();

        $errors = $this->get('Orca\UserLogBundle\Repository\TblUserLogRepository')->getWsActions($start, $end);

        return $this->render('@OrcaUserLog/Demo/wsActions.html.twig', [
            'errors'        => $errors,
            'host'          => $host
        ]);
    }

    public function alerteAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $host = $request->getHttpHost();

        $date = new \DateTime('now');
        $startDate = new \DateTime(date_format($date, 'Y').'-'.date_format($date, 'm').'-01');
        $startEnd = (new \DateTime($startDate->format('Y-m-t')))->add(new \DateInterval('P1D'));
        $start = $request->get('start',$startDate->format('Y-m-d')).' 00:00:00';
        $end = $request->get('end',$startEnd->format('Y-m-d')).' 23:59:59';
        $nbday = '-'.$this->container->getParameter('userlog_nbdays').' day';
        if(is_null($nbday)){
            $nbday = '-4 day';
        }
        $mdate = $date->modify($nbday);
        $month = date_format($mdate, 'm');
        $year = date_format($mdate, 'Y');
        $day = date_format($mdate, 'd');

        $errors = $this->get('Orca\UserLogBundle\Repository\TblUserLogRepository')->getErrors($start, $end);

        return $this->render('@OrcaUserLog/Demo/erreur.html.twig', [
            'errors'        => $errors,
            'host'          => $host
        ]);
    }

    public function processlistAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $host = $request->getHttpHost();

        $data = $this->get('Orca\UserLogBundle\Repository\TblUserLogRepository')->getProcessList();
//        var_dump($data); die;

        return $this->render('@OrcaUserLog/Demo/processlist.html.twig', [
            'data'        => $data
            ]);
    }

    public function AjaxprocesslistAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $host = $request->getHttpHost();

        $data = $this->get('Orca\UserLogBundle\Repository\TblUserLogRepository')->getProcessList();
        $response = array(
            "draw"=> $request->get('draw',1),
            "recordsTotal"=> count($data),
            "recordsFiltered"=> count($data),
            "data"=>$data
        );

        return new Response(json_encode($response), 200, ['Content-Type' => 'application/json']);
    }

    public function speedtestAction()
    {
        return $this->render('@OrcaUserLog/Demo/speedtest.html.twig');
    }

    public function timeLineAction(Request $request){

        if (!isset($_REQUEST['start']) && !isset($_REQUEST['end'])){
            $date = new \DateTime('now');
            $startDate = new \DateTime(date_format($date, 'Y').'-'.date_format($date, 'm').'-'. date_format($date, 'd'));
            $startEnd = (new \DateTime($startDate->format('Y-m-t')))->add(new \DateInterval('P1D'));
            $start = $request->get('start',$startDate->format('Y-m-d')).' 00:00:00';
            $end = $request->get('end',$startEnd->format('Y-m-d')).' 23:59:59';
        }else{
            $start = $request->get('start').' 00:00:00';
            $end = $request->get('end').' 23:59:59';
        }


        /*$date = new \DateTime('now');
        $now = date_format($date, 'Y-m-d');*/
        $userId = $request->get('id');
        $data = "";

        if ($request->isXmlHttpRequest()){
            $data = $this->get('Orca\UserLogBundle\Repository\TblUserLogRepository')->getAllActionsByUserIdByRange($start, $end, $userId);
        }
        //return new Response(json_encode($data), 200, ['Content-Type' => 'application/json']);

        if (!empty($data)){
            return new Response($this->renderView('@OrcaUserLog/Demo/timeLine.html.twig', [
                'data'        => $data
            ]));
        }else{
            return new Response("<h2 style='text-align: center;'>Aucune donnée trouvée pour l'utilisateur choisi !</h2>");
        }

    }
    
    public function getBoActionsAjaxAction(Request $request){

        //var_dump($request);die();
        $draw = $request->get('draw');
        $offset = $request->get('start');
        $limit = $request->get('length');
        $search = $request->get('search');

        $order = $request->get('order');
        $dir = $order[0]['dir'];
        $iCol = $order[0]['column'];

        $date = new \DateTime('now');
        $startDate = new \DateTime(date_format($date, 'Y').'-'.date_format($date, 'm').'-01');
        $startEnd = (new \DateTime($startDate->format('Y-m-t')))->add(new \DateInterval('P1D'));
        $start = $request->get('start',$startDate->format('Y-m-d')).' 00:00:00';
        $end = $request->get('end',$startEnd->format('Y-m-d')).' 23:59:59';
        $nbday = '-'.$this->container->getParameter('userlog_nbdays').' day';
        if(is_null($nbday)){
            $nbday = '-4 day';
        }
        $mdate = $date->modify($nbday);

        $em = $this->getDoctrine()->getManager();
        $host = $request->getHttpHost();

        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository($this->getParameter('TblUserRepo'))->findAll();

        $result=[];
        $actions = $this->get('Orca\UserLogBundle\Repository\TblUserLogRepository')->getBoActions($start, $end, $search, $dir, $iCol);
        $countActions = $this->get('Orca\UserLogBundle\Repository\TblUserLogRepository')->getCountBoActions($start, $end, $search, $dir, $iCol);
        $result = [
            'draw'  =>  $draw,
            'recordsFiltered'   =>  $countActions,
            'recordsTotal'   =>  $countActions,
        ];


//var_dump(count($actions));die;
        if (count($actions) > 0){
            foreach ($actions as $i=> $a){
                $j = (int)$offset + (int)$limit;
                if ($i>=$offset && $i < $j){
                    $action = $this->get('Orca\UserLogBundle\Repository\TblUserLogRepository')->getBoActionsAjax($start, $end, $a['id']);//$a['id']

                    //$action = $qb->getQuery()->getSingleResult();

                    $result['data'][] = [
                        'id' => $action['id'],
                        'Date' => $action['date'],
                        'Utilisateur' => $action['user'],
                        'URL' => $action['uri'],
                        'Header' => $action['header'],
                        'Post' => $action['postParams'],
                        'Get' => $action['getParams'],
                        'Terminal' => $action['terminalType'],
                        'Zone' => $action['ville'],
                        'Host' => $host
                    ];
                }
            }
        }else{
            $result['data'] = [];
        }

        //var_dump($result);die;
        return new JsonResponse($result);
    }

}