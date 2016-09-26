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
            self::$istance = new Geoip(true);
        return self::$istance;
    }
    
    private $reader = NULL;
    
    protected function __construct($load_reader = true) {
        if($load_reader)
            $this->reader = new  MaxMind\Db\Reader(Kohana::$config->load('geoip.database'));
    }
    
    public static function factory_empty(){
        return new Geoip(false);
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
    
    /**
      * Klucz, pod jakim dane są przechowywane w sesji.
      */
    private function get_key($ip = NULL, $iso_code = NULL){
        if(empty($ip))
            $ip = $this->get_real_ip();
        
        if(empty($iso_code))
            $iso_code = I18n::lang();
        $iso_code = strtoupper($iso_code);
        
        return  'geoip-test'.(empty($iso_code)?'NULL':$iso_code)
                  .'-'.(empty($ip)?'NULL':$ip);
    }
    
    
    /**
     * Czy dane IP pochodzi z konkretnego kraju, wersja z cache.
     */
    public function is_country($ip = NULL, $iso_code = NULL){
        $key = $this->get_key($ip, $iso_code);
        
        $s = Session::instance();
        
        $val = $s->get($key);
        
        if($val === NULL){
            $val = $this->_is_country($ip, $iso_code);
            $s->set($key, $val);
        }
        
        return $val;
    }
    
    /**
     * Czy dane IP pochodzi z konkretnego kraju, wersja BEZ cache.
     */
    public function is_country_fresh_test($ip = NULL, $iso_code = NULL){
        $key = $this->get_key($ip, $iso_code);
        $val = $this->_is_country($ip, $iso_code);
        $s = Session::instance();
        $s->set($key, $val);
        
        return $val;
    }
    
    /**
     * Czy dane IP pochodzi z konkretnego kraju.
     */
    private function _is_country($ip = NULL, $iso_code = NULL){
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
            if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
                    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
            }
            else if (isset($_SERVER["HTTP_X_CLIENT_IP"])) {
                $ip = $_SERVER["HTTP_X_CLIENT_IP"];
            }
            else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $ip = $_SERVER["HTTP_CLIENT_IP"];
            }
            else if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
                if (strpos($ip, ",")) {
                    $exp_ip = explode(",", $ip);
                    $ip = $exp_ip[0];
                }
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
    
        if(!filter_var($ip, FILTER_VALIDATE_IP))
            return '::1';
        return $ip;
    }
}