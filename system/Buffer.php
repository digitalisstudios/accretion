<?php

	//THIS CLASS WILL BUFFER OUTPUT AND REUTURN IT AS A STRING
	//IF OUTPUT BUFFERING HAS ALREADY BEEN STARTED THEN IT WILL GRAB THE OUTPUT AND REPLACE IT WHEN THE CALLBACK IS FINISHED

	class Buffer extends Accretion {

		public function __construct(){

		}

		public function start($callback, $vars = array()){
			
			//ASSUME THERE IS NOTHING IN THE BUFFER
			$original_output = false;

			//CHECK THE BUFFER
			if(ob_get_level()){
				$original_output = ob_get_clean();	
			}

			//START BUFFERING
			ob_start();

			$callback($vars);

			//GET THE OUTPUT
			$res = ob_get_clean();

			if($original_output !== false){
				ob_start();
				echo $original_output;
			}

			return strlen($res) ? $res : false;
		}
	}
?>