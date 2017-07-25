<?php

    namespace App;

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

        public function __construct($route = false){

            //ONLY RUN INITIALIZATION IF THE CALLING CLASS IS THE FRAMEWORK
            if(get_class($this) == 'Accretion'){

                //DEFINE THE CLASSES TO LOAD
                $classes = [
                    'global/Global_Functions', 
                    'Session', 
                    'Request', 
                    'View', 
                    'Helper',
                    'Controller',
                    'ORM_Wrapper', 
                    'global/Global_Model_Method',
                    'Magic_Model',
                    'Model',
                    'DB', 
                    'Auth', 
                    'Config', 
                    'Reflect',
                    'Buffer',
                ];

                //LOAD THE CLASSES
                foreach($classes as $class) require_once dirname(__FILE__).'/'.$class.'.php';                   

                //INITIALIZE THE CONFIGURATION
                Config::init();

                //ROUTE THE FRAMEWORK IF NEEDED
                if($route) Controller::get();
            }
        }
        
        public function show_static($obj = null){

            if(is_null($obj)){
                $obj = $this;
            }
            $class = new ReflectionClass($obj);
            return $class->getStaticProperties();
        }
    }
?>