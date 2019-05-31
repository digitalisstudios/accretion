<?php

	class CLIArgs {

		protected static $_args = [];

		public function __construct(){

		}

		public function parse(){
			foreach($_SERVER['argv'] as $arg){
				if(substr($arg, 0, 1) === '-'){
					if(strpos($arg, '=') !== false){
						$parts = explode('=', $arg);
						$argName = $parts[0];
						unset($parts[0]);
						$val = implode('=', $parts);
						self::$_args[$argName] = $val;
					}
					else{
						self::$_args[$arg] = true;
					}
				}
			}
		}

		public static function get($arg){

			//FIND THE ARGUMENT VALUE IF IT EXISTS
			if(isset(self::$_args[$arg])) return self::$_args[$arg];

			//DEFAULT TO FALSE
			return false;
		}

		public static function ask($message, $callback){

			\CLIPrinter::printOut($message, true, false);

			$handle = fopen("php://stdin", "r");

			$line = fgets($handle);

			$callback(trim($line));

			fclose($handle);
		}
	}