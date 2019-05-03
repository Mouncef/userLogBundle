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
    protected $userlog_entity;
    protected $userlog_repository;
    protected $table_name;
    protected $user_class;
    protected $userlog_nbdays;
    const REPO_AS_SERVICE = 'Orca\UserLogBundle\Repository\TblUserLogRepository';

    function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->userlog_entity = $this->getParameter('orca_user_log.userlog_entity');
        $this->userlog_repository = $this->getParameter('orca_user_log.userlog_repository');
        $this->table_name = $this->getParameter('orca_user_log.table_name');
        $this->user_class = $this->getParameter('orca_user_log.user_class');
        $this->userlog_nbdays = $this->getParameter('userlog_nbdays');
    }
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
        $nbCNXByDay = $this->get(self::REPO_AS_SERVICE)->getNbConnexionByDay($day, $month, $year);
        $nbErrorByDay = $this->get(self::REPO_AS_SERVICE)->getNbErrorByDay($day, $month, $year);
        //var_dump($nbErrorByDay, $day,$month,$year);die();
        //$nbCNXByMonth = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getNbConnexionByMonth($month, $year);
        //$nbErrorByMonth = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getNbErrorsByMonth($month, $year);
        $months = $this->get(self::REPO_AS_SERVICE)->getMonths($choosedYear);
        $nbCNXByTerminalAndByMonth = $this->get(self::REPO_AS_SERVICE)->getNbConnexionByTerminalAndByMonth($choosedYear);
        $topfiveUsers = $this->get(self::REPO_AS_SERVICE)->getTopFive($month,$year);
        $pays = $this->get(self::REPO_AS_SERVICE)->getPays();
        /**
         * getByRange
         */
        $nbCNXByTerminalType = $this->get(self::REPO_AS_SERVICE)->getNbConnexionByTypeTerminalRange($start, $end);
        $nbCNXByTerminal = $this->get(self::REPO_AS_SERVICE)->getNbConexionByTerminalRange($start, $end);
        $nbCNXByMonth = $this->get(self::REPO_AS_SERVICE)->getNbConnexionByRange($start, $end);
        $nbErrorByMonth = $this->get(self::REPO_AS_SERVICE)->getNbErrorsByRange($start, $end);
        $topfiveUsers = $this->get(self::REPO_AS_SERVICE)->getTopFiveRange($start,$end);
        //var_dump($topfiveUsers);die;

        $users = $ios = $navigator= array();
        $index  =0;
        $found = false;

        $yearsOfCNX = $this->get(self::REPO_AS_SERVICE)->getYearsOfCNX();

        //for($i = 0 ; $i<5 ; $i++)$users[$i]['name']= $topfiveUsers[$i]['user'];

        for($i = 0 ; $i< count($topfiveUsers) ; $i++){
            if(!in_array($topfiveUsers[$i]['user'] , $users)){
                $user = $em->getRepository($this->user_class)->find($topfiveUsers[$i]['user']);

                $username = $user ? $user->__toString() : 'Anony.';
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
        $startDate = new \DateTime(date_format($date, 'Y').'-'.date_format($date, 'm').'-01');
        $startEnd = (new \DateTime($startDate->format('Y-m-t')))->add(new \DateInterval('P1D'));
        $start = $request->get('date_start',$startDate->format('Y-m-d')).' 00:00:00';
        $end = $request->get('date_end',$startEnd->format('Y-m-d')).' 23:59:59';
        $nbday = '-'.$this->userlog_nbdays.' day';
        if(is_null($nbday)){
            $nbday = '-4 day';
        }
        $mdate = $date->modify($nbday);

        return $this->render('@OrcaUserLog/Demo/connexion.html.twig', [
            'date_start' => $start,
            'date_end' => $end
        ]);
    }
    public function connexionAjaxAction(Request $request)
    {
        $draw = $request->get('draw');
        $offset = trim($request->get('start'));
        $limit = trim($request->get('length'));
        $search = $request->get('search');

        $order = $request->get('order');
        $dir = $order[0]['dir'];
        $iCol = $order[0]['column'];

        $date = new \DateTime('now');
        $startDate = new \DateTime($date->format('Y-m-01'));
        $startEnd = (new \DateTime($startDate->format('Y-m-t')))->add(new \DateInterval('P1D'));
        $start = $request->get('date_start',$startDate->format('Y-m-d')).' 00:00:00';
        $end = $request->get('date_end',$startEnd->format('Y-m-d')).' 23:59:59';
        $nbday = '-'.$this->userlog_nbdays.' day';
        if(is_null($nbday)){
            $nbday = '-4 day';
        }
        $mdate = $date->modify($nbday);

        $em = $this->getDoctrine()->getManager();
        $host = $request->getHttpHost();

        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository($this->user_class)->findAll();

        $result=[];
        $actions = $this->get(self::REPO_AS_SERVICE)->getConnexions($start, $end, $search, $dir, $iCol,$offset,$limit);
        $countActions = $this->get(self::REPO_AS_SERVICE)->getCountConnexions($start, $end, $search);
        $result = [
            'draw'  =>  $draw,
            'recordsFiltered'   =>  $countActions,
            'recordsTotal'   =>  $countActions,
        ];

        if (count($actions) > 0){
            foreach ($actions as $i=> $action){
                $user = $em->find($this->user_class,$action['user']);
                $action['user'] = $user ? $user->__toString() : 'Anony.';
                $result['data'][] = $action;
            }
        }else{
            $result['data'] = [];
        }
        return new JsonResponse($result);
    }
    public function wsConnexionAction(Request $request)
    {
        $date = new \DateTime('now');
        $startDate = new \DateTime(date_format($date, 'Y').'-'.date_format($date, 'm').'-01');
        $startEnd = (new \DateTime($startDate->format('Y-m-t')))->add(new \DateInterval('P1D'));
        $start = $request->get('date_start',$startDate->format('Y-m-d')).' 00:00:00';
        $end = $request->get('date_end',$startEnd->format('Y-m-d')).' 23:59:59';
        $nbday = '-'.$this->userlog_nbdays.' day';
        if(is_null($nbday)){
            $nbday = '-4 day';
        }
        $mdate = $date->modify($nbday);

        return $this->render('@OrcaUserLog/Demo/wsConnexion.html.twig', [
            'date_start' => $start,
            'date_end' => $end
        ]);
    }
    public function wsConnexionAjaxAction(Request $request)
    {
        $draw = $request->get('draw');
        $offset = trim($request->get('start'));
        $limit = trim($request->get('length'));
        $search = $request->get('search');

        $order = $request->get('order');
        $dir = $order[0]['dir'];
        $iCol = $order[0]['column'];

        $date = new \DateTime('now');
        $startDate = new \DateTime($date->format('Y-m-01'));
        $startEnd = (new \DateTime($startDate->format('Y-m-t')))->add(new \DateInterval('P1D'));
        $start = $request->get('date_start',$startDate->format('Y-m-d')).' 00:00:00';
        $end = $request->get('date_end',$startEnd->format('Y-m-d')).' 23:59:59';
        $nbday = '-'.$this->userlog_nbdays.' day';
        if(is_null($nbday)){
            $nbday = '-4 day';
        }
        $mdate = $date->modify($nbday);

        $em = $this->getDoctrine()->getManager();
        $host = $request->getHttpHost();

        $em = $this->getDoctrine()->getManager();

        $result=[];
        $actions = $this->get(self::REPO_AS_SERVICE)->getWsConnexions($start, $end, $search, $dir, $iCol,$offset,$limit);
        $countActions = $this->get(self::REPO_AS_SERVICE)->getCountWsConnexions($start, $end, $search);
        $result = [
            'draw'  =>  $draw,
            'recordsFiltered'   =>  $countActions,
            'recordsTotal'   =>  $countActions,
        ];

        if (count($actions) > 0){
            foreach ($actions as $i=> $action){
                $user = $em->find($this->user_class,$action['id']);
                $action['id'] = $user ? $user->__toString() : 'Anony.';
                $result['data'][] = $action;
            }
        }else{
            $result['data'] = [];
        }
        return new JsonResponse($result);
    }
    public function boAction(Request $request) {

        $date = new \DateTime('now');
        $startDate = new \DateTime(date_format($date, 'Y').'-'.date_format($date, 'm').'-01');
        $startEnd = (new \DateTime($startDate->format('Y-m-t')))->add(new \DateInterval('P1D'));
        $start = $request->get('date_start',$startDate->format('Y-m-d')).' 00:00:00';
        $end = $request->get('date_end',$startEnd->format('Y-m-d')).' 23:59:59';
        $nbday = '-'.$this->userlog_nbdays.' day';
        if(is_null($nbday)){
            $nbday = '-4 day';
        }
        $mdate = $date->modify($nbday);

        $host = $request->getHttpHost();

        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository($this->user_class)->findAll();

        //$actions = $this->get(self::$this->REPO_AS_SERVICE)->getBoActions($start, $end);


        return $this->render('@OrcaUserLog/Demo/boActions.html.twig', [
            //'actions'        => $actions,
            'host'          => $host,
            'users' => $users,
            'date_start' => $start,
            'date_end' => $end
        ]);
    }

    public function wsAction(Request $request) {

//        $date = new \DateTime('now');
//        $startDate = new \DateTime(date_format($date, 'Y').'-'.date_format($date, 'm').'-01');
//        $startEnd = (new \DateTime($startDate->format('Y-m-t')))->add(new \DateInterval('P1D'));
//        $start = $request->get('start',$startDate->format('Y-m-d')).' 00:00:00';
//        $end = $request->get('end',$startEnd->format('Y-m-d')).' 23:59:59';
//        $nbday = '-'.$this->userlog_nbdays.' day';
//        if(is_null($nbday)){
//            $nbday = '-4 day';
//        }
//        $mdate = $date->modify($nbday);
//        $month = date_format($mdate, 'm');
//        $year = date_format($mdate, 'Y');
//        $day = date_format($mdate, 'd');
//
//        $em = $this->getDoctrine()->getManager();
//        $host = $request->getHttpHost();
        $date = new \DateTime('now');
        $startDate = new \DateTime(date_format($date, 'Y').'-'.date_format($date, 'm').'-01');
        $startEnd = (new \DateTime($startDate->format('Y-m-t')))->add(new \DateInterval('P1D'));
        $start = $request->get('date_start',$startDate->format('Y-m-d')).' 00:00:00';
        $end = $request->get('date_end',$startEnd->format('Y-m-d')).' 23:59:59';
        $nbday = '-'.$this->userlog_nbdays.' day';
        if(is_null($nbday)){
            $nbday = '-4 day';
        }
        $mdate = $date->modify($nbday);

        $em = $this->getDoctrine()->getManager();
        $host = $request->getHttpHost();

        $em = $this->getDoctrine()->getManager();

        //$errors = $this->get(self::REPO_AS_SERVICE)->getWsActions($start, $end);

        return $this->render('@OrcaUserLog/Demo/wsActions.html.twig', [
           // 'errors'        => $errors,
            'host'          => $host,
            'date_start' => $start,
            'date_end' => $end
        ]);
    }

    public function alerteAction(Request $request)
    {
        $host = $request->getHttpHost();
        $date = new \DateTime('now');
        $startDate = new \DateTime(date_format($date, 'Y').'-'.date_format($date, 'm').'-01');
        $startEnd = (new \DateTime($startDate->format('Y-m-t')))->add(new \DateInterval('P1D'));
        $start = $request->get('date_start',$startDate->format('Y-m-d')).' 00:00:00';
        $end = $request->get('date_end',$startEnd->format('Y-m-d')).' 23:59:59';
        $nbday = '-'.$this->userlog_nbdays.' day';
        if(is_null($nbday)){
            $nbday = '-4 day';
        }
        $mdate = $date->modify($nbday);

        return $this->render('@OrcaUserLog/Demo/erreur.html.twig', [
            'date_start' => $start,
            'date_end' => $end,
            'host'          => $host
        ]);
    }
    public function alerteAjaxAction(Request $request)
    {
        //var_dump($request);die();
        $draw = $request->get('draw');
        $offset = trim($request->get('start',0));
        $limit = trim($request->get('length',10));
        $search = $request->get('search');

        $order = $request->get('order');
        $dir = $order[0]['dir'];
        $iCol = $order[0]['column'];

        $date = new \DateTime('now');
        $startDate = new \DateTime($date->format('Y-m-01'));
        $startEnd = (new \DateTime($startDate->format('Y-m-t')))->add(new \DateInterval('P1D'));
        $start = $request->get('date_start',$startDate->format('Y-m-d')).' 00:00:00';
        $end = $request->get('date_end',$startEnd->format('Y-m-d')).' 23:59:59';
        $nbday = '-'.$this->userlog_nbdays.' day';
        if(is_null($nbday)){
            $nbday = '-4 day';
        }
        $mdate = $date->modify($nbday);

        $em = $this->getDoctrine()->getManager();
        $host = $request->getSchemeAndHttpHost();

        $em = $this->getDoctrine()->getManager();
       // $users = $em->getRepository($this->user_class)->findAll();

        $result=[];
        $actions = $this->get(self::REPO_AS_SERVICE)->getErrors($start, $end, $search, $dir, $iCol,$offset,$limit);
        $countActions = $this->get(self::REPO_AS_SERVICE)->getCountErrors($start, $end, $search, $dir, $iCol);
        $result = [
            'draw'  =>  $draw,
            'recordsFiltered'   =>  $countActions,
            'recordsTotal'   =>  $countActions,
        ];

        if (count($actions) > 0){
            foreach ($actions as $i=> $action){
                $action['Host'] = $host;
                $user = $em->find($this->user_class,$action['user']);
                $action['user'] = $user ? $user->__toString() : 'Anony.';
                $result['data'][] = $action;
            }
        }else{
            $result['data'] = [];
        }
        return new JsonResponse($result);
    }

    public function processlistAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $host = $request->getHttpHost();

        $data = $this->get(self::REPO_AS_SERVICE)->getProcessList();
//        var_dump($data); die;

        return $this->render('@OrcaUserLog/Demo/processlist.html.twig', [
            'data'        => $data
            ]);
    }

    public function AjaxprocesslistAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $host = $request->getHttpHost();

        $data = $this->get(self::REPO_AS_SERVICE)->getProcessList();
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
            $data = $this->get(self::REPO_AS_SERVICE)->getAllActionsByUserIdByRange($start, $end, $userId);
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
        $offset = trim($request->get('start'));
        $limit = trim($request->get('length'));
        $search = $request->get('search');

        $order = $request->get('order');
        $dir = $order[0]['dir'];
        $iCol = $order[0]['column'];

        $date = new \DateTime('now');
        $startDate = new \DateTime($date->format('Y-m-01'));
        $startEnd = (new \DateTime($startDate->format('Y-m-t')))->add(new \DateInterval('P1D'));
        $start = $request->get('date_start',$startDate->format('Y-m-d')).' 00:00:00';
        $end = $request->get('date_end',$startEnd->format('Y-m-d')).' 23:59:59';
        $nbday = '-'.$this->userlog_nbdays.' day';
        if(is_null($nbday)){
            $nbday = '-4 day';
        }
        $mdate = $date->modify($nbday);

        $em = $this->getDoctrine()->getManager();
        $host = $request->getHttpHost();

        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository($this->user_class)->findAll();

        $result=[];
        $actions = $this->get(self::REPO_AS_SERVICE)->getBoActions($start, $end, $search, $dir, $iCol,$offset,$limit);
        $countActions = $this->get(self::REPO_AS_SERVICE)->getCountBoActions($start, $end, $search, $dir, $iCol);
        $result = [
            'draw'  =>  $draw,
            'recordsFiltered'   =>  $countActions,
            'recordsTotal'   =>  $countActions,
        ];

        if (count($actions) > 0){
            foreach ($actions as $i=> $action){
                $action['Host'] = $host;
                $user = $em->find($this->user_class,$action['Utilisateur']);
                $action['Utilisateur'] = $user ? $user->__toString() : 'Anony.';
                $result['data'][] = $action;
                }
        }else{
            $result['data'] = [];
        }
        return new JsonResponse($result);
    }

    public function getWSActionsAjaxAction(Request $request){

        //var_dump($request);die();
        $draw = $request->get('draw');
        $offset = trim($request->get('start'));
        $limit = trim($request->get('length'));
        $search = $request->get('search');

        $order = $request->get('order');
        $dir = $order[0]['dir'];
        $iCol = $order[0]['column'];

        $date = new \DateTime('now');
        $startDate = new \DateTime($date->format('Y-m-01'));
        $startEnd = (new \DateTime($startDate->format('Y-m-t')))->add(new \DateInterval('P1D'));
        $start = $request->get('date_start',$startDate->format('Y-m-d')).' 00:00:00';
        $end = $request->get('date_end',$startEnd->format('Y-m-d')).' 23:59:59';
        $nbday = '-'.$this->userlog_nbdays.' day';
        if(is_null($nbday)){
            $nbday = '-4 day';
        }
        $mdate = $date->modify($nbday);

        $em = $this->getDoctrine()->getManager();
        $host = $request->getHttpHost();

        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository($this->user_class)->findAll();

        $result=[];
        //$actions = $this->get(self::REPO_AS_SERVICE)->getBoActions($start, $end, $search, $dir, $iCol,$offset,$limit);
        $actions = $this->get(self::REPO_AS_SERVICE)->getWsActions($start, $end, $search, $dir, $iCol,$offset,$limit);
        $countActions = $this->get(self::REPO_AS_SERVICE)->getCountWsActions($start, $end, $search, $dir, $iCol);
        //$countActions = $this->get(self::REPO_AS_SERVICE)->getCountBoActions($start, $end, $search, $dir, $iCol);
        $result = [
            'draw'  =>  $draw,
            'recordsFiltered'   =>  $countActions,
            'recordsTotal'   =>  $countActions,
        ];

        if (count($actions) > 0){
            foreach ($actions as $i=> $action){
                $action['Host'] = $host;
                $user = $em->find($this->user_class,$action['Utilisateur']);
                $action['Utilisateur'] = $user ? $user->__toString() : 'Anony.';
                $result['data'][] = $action;
                }
        }else{
            $result['data'] = [];
        }
        return new JsonResponse($result);
    }

}