<?php
/**
 * Created by PhpStorm.
 * User: PC_MA27
 * Date: 20/11/2017
 * Time: 10:34
 */

namespace Orca\UserLogBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


class DashboardController extends Controller
{
    public function indexAction()
    {
        $date = new \DateTime('now');
        $day = date_format($date, 'd');
        $month = date_format($date, 'm');
        $year = date_format($date, 'Y');


        $em = $this->getDoctrine()->getManager();

        $nbCNXByTerminalType = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getNbConnexionByTypeTerminal($month, $year);
        $nbCNXByTerminal = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getNbConexionByTerminal($month, $year);
        $nbCNXByDay = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getNbConnexionByDay($day, $month, $year);
        $nbErrorByDay = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getNbErrorByDay($day, $month, $year);
        $nbCNXByMonth = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getNbConnexionByMonth($month, $year);
        $nbErrorByMonth = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getNbErrorsByMonth($month, $year);
        $months = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getMonths($year);
        $nbCNXByTerminalAndByMonth = $em->getRepository('OrcaUserLogBundle:TblUserLog')->getNbConnexionByTerminalAndByMonth($year);


        return $this->render('OrcaUserLogBundle:Demo:dashboard.html.twig',[
            'nbCNXbyTypeTerminal'       => $nbCNXByTerminalType,
            'nbCNXbyTerminal'           => $nbCNXByTerminal,
            'nbCNXbyDay'                => $nbCNXByDay,
            'nbErrorbyDay'              => $nbErrorByDay,
            'nbCNXbyMonth'              => $nbCNXByMonth,
            'nbErrorbyMonth'            => $nbErrorByMonth,
            'months'                    => $months,
            'nbCNXbyTerminalAndbyMonth' => $nbCNXByTerminalAndByMonth
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
}