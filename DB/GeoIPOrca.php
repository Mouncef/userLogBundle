<?php
/**
 * Created by PhpStorm.
 * User: PC_MA29
 * Date: 15/02/2018
 * Time: 10:11
 */
namespace Orca\UserLogBundle\DB;
//use Orca\UserLogBundle\DB\src\GeoIP;
define("GEOIP_DATABASE_COUNTRY", dirname(__FILE__)."/GeoLite2-Country.mmdb");
define("GEOIP_DATABASE_CITY", dirname(__FILE__)."/GeoLite2-City.mmdb");
use GeoIp2\Database\Reader;


class GeoIPOrca
{

    private $reader;

    public function __construct()
    {
        $this->reader = new Reader(GEOIP_DATABASE_CITY);
    }


    function GetClientIP($validate = False)
    {
        $ipkeys = array('REMOTE_ADDR', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP');

        /*
        now we check each key against $_SERVER if contain such value
        */
        $ip = '';
        foreach ($ipkeys as $keyword) {
            if (isset($_SERVER[$keyword])) {
                if ($this->ValidatePublicIP($_SERVER[$keyword])) {
                    $ip = $_SERVER[$keyword];
                    break;
                }
            }
        }

        $ip = (empty($ip) ? 'Unknown' : $ip);
        return $ip;

    }

    function ValidatePublicIP($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return true;
        } else {
            return false;
        }
    }

    function getInfoIP(){
        $get_client_ip = $this->GetClientIP(true);
        //$record = $reader->city('128.101.101.101');
        $infoReturn = [];
        try {
            $data_geo = $this->reader->city($get_client_ip);
            //var_dump($data_geo);
            //die;
            $name = $data_geo->country->name;
            $isoCode = $data_geo->country->isoCode;
            //$mostSpecificSubdivision = $data_geo->mostSpecificSubdivision->name;
            $data = $city = $data_geo->city->name;
            //$postal = $data_geo->postal->code; // '55455'
            $latitude = $data_geo->location->latitude; // 50.45
            $longitude = $data_geo->location->longitude;// 30.5233

            if (is_null($data)){
                $url = 'http://maps.googleapis.com/maps/api/geocode/json?latlng='.$latitude.','.$longitude.'&sensor=false';

                $data = @file_get_contents($url);
                $jsondata = json_decode($data,true);
                $addr = '';
                $addr2 = '';
                $addr3 = '';
                if(is_array($jsondata )&& $jsondata ['status'] == "OK")
                {
                    //$addr = $jsondata ['results'][0]['address_components'][4]['long_name'];
                    //$addr2 = $jsondata ['results'][0]['address_components'][4]['short_name'];
                    $addr3 = $jsondata ['results'][0]['address_components'][3]['long_name'];
                }
                //$info = "Country: " . $addr . " | Region: " . $addr2 . " | City: " . $addr3;
                $data = ($city) ?  $city : $addr3;
            }
            $infoReturn = [
                'country'=>$name,
                'isoCode'=>$isoCode,
                'city'=>$data,
                'latitude'=>$latitude,
                'longitude'=>$longitude
            ];


        } catch (\Exception $e) {
            //echo $e->getMessage();//die;
            $infoReturn = null;
        }

        return $infoReturn;
    }

}