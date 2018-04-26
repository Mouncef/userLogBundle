<?php

namespace Orca\UserLogBundle\Repository;

use Doctrine\ORM\Query\ResultSetMapping;

/**
 * TblUserLogRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TblUserLogRepository extends \Doctrine\ORM\EntityRepository
{
    public function getNbConnexionByTypeTerminal($month, $year)
    {
        $query = $this->_em->createQueryBuilder()
            ->select('COUNT(u.id) as ct, u.terminalType')
            ->from('OrcaUserLogBundle:TblUserLog', 'u')
            ->where('u.action = :var')
            ->andWhere("DATE_FORMAT(u.date, '%m') = :month")
            ->andWhere("DATE_FORMAT(u.date, '%Y') = :year")
            ->groupBy('u.terminalType')
            ->setParameters(['var' => 'Login_BO', 'month' => $month, 'year' => $year])
            ->getQuery()
            ->getResult();

        return $query;
    }
    public function getNbConexionByTerminal($month, $year)
    {
        $query = $this->_em->createQueryBuilder()
            ->select('COUNT(u.id) as ct, u.terminal')
            ->from('OrcaUserLogBundle:TblUserLog', 'u')
            ->where('u.action = :var')
            ->andWhere("DATE_FORMAT(u.date, '%m') = :month")
            ->andWhere("DATE_FORMAT(u.date, '%Y') = :year")
            ->groupBy('u.terminal')
            ->setParameters(['var' => 'Login_BO', 'month' => $month, 'year' => $year])
            ->getQuery()
            ->getResult();

        return $query;
    }
    public function getNbConnexionByDay($day, $month, $year)
    {
        $query = $this->_em->createQueryBuilder()
            ->select('COUNT(u.id) as ct')
            ->from('OrcaUserLogBundle:TblUserLog', 'u')
            ->where('u.action = :var')
            ->andWhere("DATE_FORMAT(u.date, '%d') = :day")
            ->andWhere("DATE_FORMAT(u.date, '%m') = :month")
            ->andWhere("DATE_FORMAT(u.date, '%Y') = :year")
            ->setParameters(['var' => 'Login_BO', 'day' => $day, 'month' => $month, 'year' => $year])
            ->getQuery()
            ->getResult();

        return $query;
    }
    public function getNbErrorByDay($day, $month, $year)
    {
        $query = $this->_em->createQueryBuilder()
            ->select('COUNT(u.id) as ct')
            ->from('OrcaUserLogBundle:TblUserLog', 'u')
            ->where('u.errorCode != 200')
            ->andWhere('u.errorCode != 302')
            ->andWhere("DATE_FORMAT(u.date, '%d') = :day")
            ->andWhere("DATE_FORMAT(u.date, '%m') = :month")
            ->andWhere("DATE_FORMAT(u.date, '%Y') = :year")
            ->setParameters(['day' => $day, 'month' => $month, 'year' => $year])
            ->getQuery()
            ->getResult();

        return $query;
    }
    public function getNbConnexionByMonth($month, $year)
    {
        $query = $this->_em->createQueryBuilder()
            ->select('COUNT(u.id) as ct')
            ->from('OrcaUserLogBundle:TblUserLog', 'u')
            ->where('u.action = :var')
            ->andWhere("DATE_FORMAT(u.date, '%m') = :month")
            ->andWhere("DATE_FORMAT(u.date, '%Y') = :year")
            ->setParameters(['var' => 'Login_BO', 'month' => $month, 'year' => $year])
            ->getQuery()
            ->getResult();

        return $query;
    }
    public function getNbErrorsByMonth($month, $year)
    {
        $query = $this->_em->createQueryBuilder()
            ->select('COUNT(u.id) as ct')
            ->from('OrcaUserLogBundle:TblUserLog', 'u')
            ->where('u.errorCode != 200')
            ->andWhere('u.errorCode != 302')
            ->andWhere("DATE_FORMAT(u.date, '%m') = :month")
            ->andWhere("DATE_FORMAT(u.date, '%Y') = :year")
            ->setParameters(['month' => $month, 'year' => $year])
            ->getQuery()
            ->getResult();

        return $query;
    }
    public function getMonths($year)
    {
        $query = $this->_em->createQueryBuilder()
            ->select("DISTINCT (DATE_FORMAT(u.date, '%m')) as mois")
            ->from('OrcaUserLogBundle:TblUserLog', 'u')
            ->where("DATE_FORMAT(u.date, '%Y') = :year")
            ->setParameter('year', $year)
            ->getQuery()
            ->getResult();

        return $query;
    }
    public function getNbConnexionByTerminalAndByMonth($year)
    {
        $sql = "SELECT terminal, GROUP_CONCAT(nb) AS nb
                FROM(
                SELECT DISTINCT u.`terminal`,DATE_FORMAT(u.`date`, \"%m\") AS mois, COUNT(u.action) AS nb
                FROM `tbl_user_log` u 
                WHERE u.`action` = :var
                AND DATE_FORMAT(u.`date`, \"%Y\") = :year
                GROUP BY u.`terminal`, DATE_FORMAT(u.`date`, \"%m\")
                ) tab
                GROUP BY tab.terminal";
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('terminal', 'terminal');
        $rsm->addScalarResult('nb', 'nb');
        $query = $this->_em->createNativeQuery($sql, $rsm)
            ->setParameters(['var' => 'Login_BO', 'year' => $year]);

        return $query->getResult();
    }
    public function getConnexions($mois)
    {
        $sql = "SELECT l.`user_id`, COUNT(*) AS nb_connexion,MAX(l.date) AS last_conn, GROUP_CONCAT( DISTINCT l.`terminal`) AS terminals,
                (
                SELECT COUNT(lo.`error_code`)
                FROM `tbl_user_log` lo
                WHERE lo.`error_code` NOT IN (200,302)
                AND lo.`user_id`=l.`user_id`
                ) AS nb_erreur
                FROM `tbl_user_log` l 
                WHERE l.`action` = 'Login_BO'
                AND DATE_FORMAT(l.`date`, \"%m\") = :mois
                GROUP BY l.`user_id`
                ORDER BY nb_connexion DESC";

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('user_id', 'user');
        $rsm->addScalarResult('nb_connexion', 'nb_con');
        $rsm->addScalarResult('nb_erreur', 'nb_err');
        $rsm->addScalarResult('last_conn', 'last_conn');
        $rsm->addScalarResult('terminals', 'ters');
        $query = $this->_em->createNativeQuery($sql, $rsm)
            ->setParameter('mois', $mois);

        return $query->getResult();
    }

    public function getWsConnexions($mois, $annee)
    {
        /*$sql = "SELECT l.`user_id`, COUNT(*) AS nb_connexion,MAX(l.date) AS last_conn, GROUP_CONCAT( DISTINCT l.`terminal`) AS terminals, l.`header`, l.`post_params`, l.`get_params`,
                (
                SELECT COUNT(lo.`error_code`)
                FROM `tbl_user_log` lo
                WHERE lo.`error_code` NOT IN (200,302)
                AND lo.`post_params`=l.`post_params`
                ) AS nb_erreur
                FROM `tbl_user_log` l 
                WHERE l.`action` LIKE '%Ws%'
                AND DATE_FORMAT(l.`date`, \"%m\") = :mois
                AND (l.`route_name` LIKE '%Token%' OR l.`route_name` LIKE '%auth%' )
                GROUP BY l.`post_params`
                ORDER BY nb_connexion DESC";*/

        $sql = "SELECT l.`id`, COUNT(*) AS nb_connexion,MAX(l.date) AS last_conn, GROUP_CONCAT( DISTINCT l.`terminal`) AS terminals, l.`header`, l.`post_params`, l.`get_params`
                FROM `tbl_user_log` l 
                WHERE l.`action` LIKE '%Ws%'
                AND DATE_FORMAT(l.`date`, \"%m\") = :mois
                AND DATE_FORMAT(l.`date`, \"%Y\") = :annee
                AND (l.`route_name` LIKE '%Token%' OR l.`route_name` LIKE '%auth%' )
                AND l.`error_code` IN (200,302)
                AND l.`header` IS NOT NULL
                GROUP BY REPLACE(CONCAT(post_params,get_params),'[]','')
                ORDER BY nb_connexion DESC";

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('nb_connexion', 'nb_con');
        $rsm->addScalarResult('last_conn', 'last_conn');
        $rsm->addScalarResult('terminals', 'ters');
        $rsm->addScalarResult('header', 'header');
        $rsm->addScalarResult('post_params', 'postParams');
        $rsm->addScalarResult('get_params', 'getParams');
        $query = $this->_em->createNativeQuery($sql, $rsm)
            ->setParameters(['mois'=>$mois, 'annee'=>$annee]);

        return $query->getResult();
    }

    public function getWsActions($mois, $annee)
    {

        $sql = "SELECT l.`id`, l.`date`, l.`action`, l.`uri`, l.`terminal_type`, l.`ville`, l.`user_id`, l.`error_code`, l.`header`, l.`post_params`, l.`get_params`
                FROM `tbl_user_log` l
                WHERE l.`error_code` IN (200,302)
                AND l.`action` LIKE :ws
                AND l.`uri` LIKE :api
                AND l.`user_id` != 0
                AND DATE_FORMAT(l.`date`, \"%m\") = :mois
                AND DATE_FORMAT(l.`date`, \"%Y\") = :annee
                ORDER BY l.`date` DESC";

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('date', 'date');
        $rsm->addScalarResult('action', 'action');
        $rsm->addScalarResult('uri', 'uri');
        $rsm->addScalarResult('terminal_type', 'terminalType');
        $rsm->addScalarResult('ville', 'ville');
        $rsm->addScalarResult('user_id', 'user');
        $rsm->addScalarResult('error_code', 'errorCode');
        $rsm->addScalarResult('header', 'header');
        $rsm->addScalarResult('post_params', 'postParams');
        $rsm->addScalarResult('get_params', 'getParams');
        $query = $this->_em->createNativeQuery($sql, $rsm)
            ->setParameters(['mois' => $mois, 'ws'  =>  'Ws\\\%', 'api' =>  '%/api/%', 'annee' => $annee])
        ;

        /*$query = $this->_em->createQueryBuilder()
            ->select('l.id','l.date', 'l.action', 'l.uri', 'l.terminalType', 'l.ville', 'l.user', 'l.errorCode','l.header','l.postParams','l.getParams')
            ->from('OrcaUserLogBundle:TblUserLog', 'l')
            ->where('l.errorCode in (200,302)')
            ->andWhere('l.action LIKE :action ')
            ->andWhere('l.uri LIKE :uri ')
            ->andWhere('l.user != 0 ')
            ->setParameters(['action'=> 'Ws\\\%', 'uri' => '%/api/%'])
            ->orderBy('l.date ', 'DESC')
            ->getQuery()
            ->getResult()
        ;*/

        return $query->getResult();
    }

    public function getBoActions($mois, $annee)
    {
        $sql = "SELECT l.`id`, l.`date`, l.`action`, l.`uri`, l.`terminal_type`, l.`ville`, l.`user_id`, l.`error_code`, l.`header`, l.`post_params`, l.`get_params`
                FROM `tbl_user_log` l
                WHERE l.`error_code` IN (200,302)
                AND l.`action` NOT LIKE :ws
                AND l.`uri` NOT LIKE :api
                AND l.`uri` NOT LIKE :log
                AND l.`user_id` != 0
                AND l.`route_name` NOT LIKE :login
                AND l.`route_name` NOT LIKE :logout
                AND DATE_FORMAT(l.`date`, \"%m\") = :mois
                AND DATE_FORMAT(l.`date`, \"%Y\") = :annee
                ORDER BY l.`date` DESC";

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('date', 'date');
        $rsm->addScalarResult('action', 'action');
        $rsm->addScalarResult('uri', 'uri');
        $rsm->addScalarResult('terminal_type', 'terminalType');
        $rsm->addScalarResult('ville', 'ville');
        $rsm->addScalarResult('user_id', 'user');
        $rsm->addScalarResult('error_code', 'errorCode');
        $rsm->addScalarResult('header', 'header');
        $rsm->addScalarResult('post_params', 'postParams');
        $rsm->addScalarResult('get_params', 'getParams');
        $query = $this->_em->createNativeQuery($sql, $rsm)
            ->setParameters(['mois' => $mois, 'ws'  =>  'Ws\\\%', 'api' =>  '%/api/%', 'annee' => $annee, 'login' => 'login_check', 'logout' => 'logout', 'log' =>  '%/userLogChart/%'])
        ;

        return $query->getResult();
    }

    public function getErrors()
    {
        $query = $this->_em->createQueryBuilder()
            ->select('l.id','l.date', 'l.action', 'l.uri', 'l.terminalType', 'l.ville', 'l.user', 'l.errorCode','l.header','l.postParams','l.getParams')
            ->from('OrcaUserLogBundle:TblUserLog', 'l')
            ->where('l.errorCode not in (200,302)')
            ->orderBy('l.date ', 'DESC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();

        return $query;
    }
    public function getTopFive($month, $year)
    {
        $query = $this->_em->createQueryBuilder()
            ->select('l.user', 'l.terminalType', 'COUNT(l.user) as nbr')
            ->from('OrcaUserLogBundle:TblUserLog', 'l')
            ->where('l.action = :login')
            ->andWhere("DATE_FORMAT(l.date, '%m') = :month")
            ->andWhere("DATE_FORMAT(l.date, '%Y') = :year")
            ->groupBy('l.user', 'l.terminalType')
            ->orderBy('nbr', 'DESC')
            ->setParameter('login', 'Login_BO')
            ->setParameter('month', $month)
            ->setParameter('year', $year)
            ->getQuery()
            ->getResult();

        return $query;
    }
    public function getPays()
    {
        $query = $this->_em->createQueryBuilder()
            ->select('l.codePays as code', 'l.pays as name', 'COUNT(l.codePays) as value')
            ->from('OrcaUserLogBundle:TblUserLog', 'l')
            ->where('l.pays != :localhost')
            ->andWhere('l.action = :login')
            ->groupBy('l.codePays')
            ->setParameter('localhost', 'Localhost')
            ->setParameter('login', 'Login_BO')
            ->getQuery()
            ->getResult();

        return $query;
    }
    public function getProcessList()
    {
        $sql = "SHOW FULL PROCESSLIST";

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('Id', 'Id');
        $rsm->addScalarResult('User', 'User');
        $rsm->addScalarResult('Host', 'Host');
        $rsm->addScalarResult('db', 'db');
        $rsm->addScalarResult('Command', 'Command');
        $rsm->addScalarResult('Time', 'Time');
        $rsm->addScalarResult('State', 'State');
        $rsm->addScalarResult('Info', 'Info');
        $rsm->addScalarResult('Progress', 'Progress');
        $query = $this->_em->createNativeQuery($sql, $rsm);

        return $query->getResult();
    }

    /**
     * Get Stats By Range of Dates
     */
    public function getNbConnexionByTypeTerminalRange($start, $end)
    {
        $query = $this->_em->createQueryBuilder()
            ->select('COUNT(u.id) as ct, u.terminalType')
            ->from('OrcaUserLogBundle:TblUserLog', 'u')
            ->where('u.action = :var')
            ->andWhere("u.date >= :start")
            ->andWhere("u.date <= :end")
            ->groupBy('u.terminalType')
            ->setParameters(['var' => 'Login_BO', 'start' => $start, 'end' => $end])
            ->getQuery()
            ->getResult();

        return $query;
    }
    public function getNbConnexionByRange($start, $end)
    {
        $query = $this->_em->createQueryBuilder()
            ->select('COUNT(u.id) as ct')
            ->from('OrcaUserLogBundle:TblUserLog', 'u')
            ->where('u.action = :var')
            ->andWhere("u.date >= :start")
            ->andWhere("u.date < :end")
            ->setParameters(['var' => 'Login_BO', 'start' => $start, 'end' => $end])
            ->getQuery()
            //->getDQL();
            ->getResult();

        return $query;
    }
    public function getNbErrorsByRange($start, $end)
    {
        $query = $this->_em->createQueryBuilder()
            ->select('COUNT(u.id) as ct')
            ->from('OrcaUserLogBundle:TblUserLog', 'u')
            ->where('u.errorCode != 200')
            ->andWhere('u.errorCode != 302')
            ->andWhere("u.date >= :start")
            ->andWhere("u.date <= :end")
            ->setParameters(['start' => $start, 'end' => $end])
            ->getQuery()
            ->getResult();

        return $query;
    }
    public function getTopFiveRange($start, $end)
    {
        $query = $this->_em->createQueryBuilder()
            ->select('l.user', 'l.terminalType', 'COUNT(l.user) as nbr')
            ->from('OrcaUserLogBundle:TblUserLog', 'l')
            ->where('l.action = :login')
            ->andWhere("l.date >= :start")
            ->andWhere("l.date <= :end")
            ->groupBy('l.user', 'l.terminalType')
            ->orderBy('nbr', 'DESC')
            ->setParameter('login', 'Login_BO')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();

        return $query;
    }
    public function getNbConexionByTerminalRange($start, $end)
    {
        $query = $this->_em->createQueryBuilder()
            ->select('COUNT(u.id) as ct, u.terminal')
            ->from('OrcaUserLogBundle:TblUserLog', 'u')
            ->where('u.action = :var')
            ->andWhere("u.date >= :start")
            ->andWhere("u.date <= :end")
            ->groupBy('u.terminal')
            ->setParameters(['var' => 'Login_BO', 'start' => $start, 'end' => $end])
            ->getQuery()
            ->getResult();

        return $query;
    }
    public function getYearsOfCNX()
    {
        $sql = "SELECT DISTINCT DATE_FORMAT(u.`date`, \"%Y\") as annees FROM tbl_user_log u
                WHERE u.action = 'Login_BO'
                ORDER BY annees DESC
                ";

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('annees', 'year');
        $query = $this->_em->createNativeQuery($sql, $rsm);

        return $query->getResult();
    }
}
