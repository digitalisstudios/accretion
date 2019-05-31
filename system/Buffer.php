<?php

	//THIS CLASS WILL BUFFER OUTPUT AND REUTURN IT AS A STRING
	//IF OUTPUT BUFFERING HAS ALREADY BEEN STARTED THEN IT WILL GRAB THE OUTPUT AND REPLACE IT WHEN THE CALLBACK IS FINISHED

	class Buffer extends Accretion {

		private static $_flushed = false;

		public function __construct(){

		}

		public static function start($callback, $vars = array()){
			ob_start();

			$callback($vars);

			$res = ob_get_clean();

			return strlen($res) ? $res : false;
			
		}

		public static function flush(){

			if(!self::$_flushed){
				@ini_set('zlib.output_compression', 'Off');
				@ini_set('output_buffering', 'Off');
				if(function_exists('apache_setenv')) @apache_setenv('no-gzip', 1);
				if(!headers_sent()) header('X-Accel-Buffering: no');
				self::$_flushed = true;
			}
			

			@ob_end_flush();
			ob_implicit_flush(1);
			

			/*
			@ini_set('zlib.output_compression', 'Off');
			@ini_set('output_buffering', 'Off');
			@ini_set('output_handler', '');
			if(function_exists('apache_setenv')) @apache_setenv('no-gzip', 1);
			if(!headers_sent()){ header("Content-Encoding: none");}
			@ob_end_flush();
			ob_implicit_flush(1);
			*/
		}
	}