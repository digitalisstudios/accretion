<?php

    class Accretion {

        //GLOBAL VARIABLES
        public static $Db                   = false;
        public static $Auth                 = false;
        public static $server_config        = false;
        public static $config               = false;
        public static $controller_name      = false;
        public static $controller           = false;
        public static $controller_object    = false;
        public static $template_name        = false;
        public static $template_path        = false;
        public static $method_name          = false;
        public static $dev_mode             = false;
        public static $controllers          = array();

        public function __construct(){

            //ONLY RUN INITIALIZATION IF THE CALLING CLASS IS THE FRAMEWORK
            if(get_class($this) == 'Accretion'){

                //DEFINE THE CLASSES TO LOAD
                $classes = [
                    'Functions',
                    '../config/Global_Functions', 
                    'Session', 
                    'Request', 
                    'View', 
                    'Helper',
                    'Controller',
                    'ORM_Wrapper', 
                    '../config/Global_Model_Method',
                    'Magic_Model',
                    'Model',
                    'DB', 
                    'Auth', 
                    'Config', 
                    'Reflect',
                    'Buffer',
                    'Storage',
                ];

                //LOAD THE CLASSES
                foreach($classes as $class) require_once __DIR__.'/'.$class.'.php';       

                //INITIALIZE THE CONFIGURATION
                \Config::init();
            }
        }

        //HANDLE ACCRETION ROUTING
        public function route(){
            
            //GET THE CONTROLLER
            $res = \Controller::get();

            //SEND BACK THE ACCRETION OBJECT
            return $this;
        }
    }