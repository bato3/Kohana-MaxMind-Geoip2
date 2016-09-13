<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Walper for https://github.com/maxmind/MaxMind-DB-Reader-php  
 *
 * @author      Bato3
 * @package     Geoip
 * @copyright   (c) 2016 Http 742 team, All rights reserved.
 * @license      
 * 
 */
 
class Kohana_Geoip{
    static private $istance = NULL;
    
    static public function instance(){
        if(empty(self::$istance))
            self::$istance = new Geoip();
        return self::$istance;
    }
    
    private $reader = NULL;
    
    protected function __construct() {
        //use ;
        $this->reader = new  MaxMind\Db\Reader(Kohana::$config->load('geoip.database'));
    }
    
    public function get($ip) {
        return $this->reader->get($ip);
    }
    public function metadata() {
        return $this->reader->metadata();
    }
    public function close() {
        return $this->reader->close();
    }
    
    public function is_country($ip = NULL, $iso_code = NULL){
        if(empty($ip))
            $ip = $this->get_real_ip();
        
        if(empty($iso_code))
            $iso_code = I18n::lang();
        $iso_code = strtoupper($iso_code);
        
        $data = $this->get($ip);
        //$data = self::fregeoip($ip);
        
        if(empty($data))
            return -1;
        
        if(empty($data['country']))
            return 0;
        
        if(empty($data['registered_country']))
            return strtoupper($data['country']['iso_code']) == $iso_code;
        return ((strtoupper($data['country']['iso_code']) == $iso_code) * 0.5) 
             + ((strtoupper($data['registered_country']['iso_code']) 
                            == $iso_code) * 0.5);
    }
    
    public function country($ip) {
        return $this->reader->get($ip)['country']['iso_code'];
    }
    public function registered_country($ip) {
        return $this->reader->get($ip)['registered_country']['iso_code'];
    }
    
    static public function fregeoip($ip){
        return json_decode(@file_get_contents('http://freegeoip.net/json/'.$ip), true);
    }
    
    public function get_real_ip() {
        if (isset($_SERVER)) {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
                if (strpos($ip, ",")) {
                    $exp_ip = explode(",", $ip);
                    $ip = $exp_ip[0];
                }
            }
            else if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
                    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
            }
            else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $ip = $_SERVER["HTTP_CLIENT_IP"];
            }
            else if (isset($_SERVER['HTTP_FROM']) 
                && filter_var($_SERVER['HTTP_FROM'], FILTER_VALIDATE_IP)) {
                    $ip = $_SERVER['HTTP_FROM'];
            }
            else {
                $ip = $_SERVER["REMOTE_ADDR"];
            }
        }
        else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $ip = getenv('HTTP_X_FORWARDED_FOR');
                if (strpos($ip, ",")) {
                    $exp_ip = explode(",", $ip);
                    $ip = $exp_ip[0];
                }
            }
            else
            if (getenv('HTTP_CLIENT_IP')) {
                $ip = getenv('HTTP_CLIENT_IP');
            }
            else {
                $ip = getenv('REMOTE_ADDR');
            }
        }
    
        return $ip;
    }
}