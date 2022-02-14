<?php

namespace App\Controllers;
use App\Services\AppServices\RedisSessionServices;
use App\Services\AppServices\AppConfigs;


class Controller
{
    protected $container;

    public $cloud_url = "http://localhost/public/";
    public $lang_file = 'english';
    public $lang = 'english';
    public $awsGalleryPath ;

    public function __construct($container){
        $this->container = $container;
        date_default_timezone_set('Asia/Kolkata');
        $_SESSION['microService'] = 'Dashboards';

        $this->lang_file = 'english';
        $this->lang= json_decode(file_get_contents('lang/'.$this->lang_file.'.json'), true);
        $this->setUserSession();
        $appConfigs = new AppConfigs();
        $config = $appConfigs->config;
        $this->awsGalleryPath = $config['awsgalleryUrl'];
        $this->zsf_customer_key = $config['zsf_customer_key'];
    }

    public function __get($property)
    {
        if($this->container->{$property})
        {
            return $this->container->{$property};
        }
    }

    public function setUserSession(){
        $this->sRedis = new RedisSessionServices();
        $sesData = (array) $this->sRedis->get_sessions();
        $_SESSION['user'] = (array) $sesData['user_data'];
        if(isset( $_SESSION['user']['user_key'])){
            $_SESSION['user']['user_id'] = $_SESSION['user']['user_key'];
        }
    }

    public function __destruct() {
        session_destroy  () ;
    }
}