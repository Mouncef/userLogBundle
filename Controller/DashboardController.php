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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class DashboardController extends Controller
{
    public function indexAction(Request $request)
    {

        $date = new \DateTime('now');
        $startDate = new \DateTime(date_format($date, 'Y').'-'.date_format($date, 'm').'-01');
        $startEnd = (new \DateTime($startDate->format('Y-m-t')))->add(new \DateInterval('P1D'));
        $start = $request->get('start',$startDate->format('Y-m-d')).' 00:00:00';
        $end = $request->get('end',$startEnd->format('Y-m-d')).' 23:59:59';
        $date = new \DateTime('now');
        $day = date_format($date, 'd');
        $month = date_format($date, 'm');
        $year = date_format($date, 'Y');

        $choosedYear = $request->get('year');
        if (!$choosedYear){
            $choosedYear = $year;
        }


        $em = $this->getDoctrine()->getManager();

        //$nbCNXByTerminalType = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getNbConnexionByTypeTerminal($month, $year);
       // $nbCNXByTerminal = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getNbConexionByTerminal($month, $year);
        $nbCNXByDay = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getNbConnexionByDay($day, $month, $year);
        $nbErrorByDay = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getNbErrorByDay($day, $month, $year);
        //$nbCNXByMonth = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getNbConnexionByMonth($month, $year);
        //$nbErrorByMonth = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getNbErrorsByMonth($month, $year);
        $months = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getMonths($choosedYear);
        $nbCNXByTerminalAndByMonth = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getNbConnexionByTerminalAndByMonth($choosedYear);
        $topfiveUsers = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getTopFive($month,$year);
        $pays=$em->getRepository('OrcaUserLogBundle:TblUserLog')->getPays();
        /**
         * getByRange
         */
        $nbCNXByTerminalType = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getNbConnexionByTypeTerminalRange($start, $end);
        $nbCNXByTerminal = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getNbConexionByTerminalRange($start, $end);
        $nbCNXByMonth = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getNbConnexionByRange($start, $end);
        $nbErrorByMonth = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getNbErrorsByRange($start, $end);
        $topfiveUsers = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getTopFiveRange($start,$end);

        $users = $ios = $navigator= array();
        $index  =0;
        $found = false;

        $yearsOfCNX = $em->getRepository(TblUserLog::class)->getYearsOfCNX();

        //for($i = 0 ; $i<5 ; $i++)$users[$i]['name']= $topfiveUsers[$i]['user'];

        for($i = 0 ; $i< count($topfiveUsers) ; $i++){
            if(!in_array($topfiveUsers[$i]['user'] , $users)){
                $users[$index++] = $topfiveUsers[$i]['user'];
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
        return $this->render('OrcaUserLogBundle:Demo:dashboard.html.twig',[
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

    public function connexionAction()
    {
        $date = new \DateTime('now');

        $month = date_format($date, 'm');


        $em = $this->getDoctrine()->getManager();
        $connexions = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getConnexions($month);

        return $this->render('OrcaUserLogBundle:Demo:connexion.html.twig', [
            'connexions' => $connexions
        ]);
    }

    public function erreurAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $host = $request->getHttpHost();

        $errors = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getErrors();

        return $this->render('OrcaUserLogBundle:Demo:erreur.html.twig', [
            'errors'        => $errors,
            'host'          => $host
        ]);
    }

    public function processlistAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $host = $request->getHttpHost();

        $data = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getProcessList();
//        var_dump($data); die;

        return $this->render('OrcaUserLogBundle:Demo:processlist.html.twig', [
            'data'        => $data
            ]);
    }

    public function AjaxprocesslistAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $host = $request->getHttpHost();

        $data = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getProcessList();
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
        return $this->render('OrcaUserLogBundle:Demo:speedtest.html.twig');
    }
}