<?php
	class Process_Manager_Helper extends Helper {

		private $_show = false;
		private $_pid_file;
		private $_pid;
		private $_shutdownCallbacks = [];
		private $_timeout = 7200;
		
		public function __construct(){

			register_shutdown_function([$this, 'callRegisteredShutdown']);

			return $this;

		}

		public function show($val = true){
			$this->_show = $val;
			return $this;
		}

		public function registerShutdownEvent() {
	        $callback = func_get_args();
	        
	        if (empty($callback)) {
	            //trigger_error('No callback passed to '.__FUNCTION__.' method', E_USER_ERROR);
	            return false;
	        }
	        if (!is_callable($callback[0])) {
	            //trigger_error('Invalid callback passed to the '.__FUNCTION__.' method', E_USER_ERROR);
	            return false;
	        }
	        $this->_shutdownCallbacks[] = $callback;
	        return true;
	    }

		public function callRegisteredShutdown() {
	        foreach ($this->_shutdownCallbacks as $arguments) {
	            $callback = array_shift($arguments);
	            call_user_func_array($callback, $arguments);
	        }
	    }

	    public function delete_pid_file(){
	    	if($this->_pid_file && file_exists($this->_pid_file)){
	    		unlink($this->_pid_file);
	    		$this->_pid_file 	= null;
	    		$this->_pid 		= null;
	    	}
	    }

		public function run($callback, $timeout = 7200){

			$backtrace = debug_backtrace()[0];	

			//SET THE PID FILE LOCATION
			$this->_pid_file 	= APP_PATH.'pid/'.base64_encode($backtrace['file'].':'.$backtrace['line']).'.pid';


			//KILL THE PROCESS IF NEEDED
			if(\Request::get('kill')){

				$temp_pid = $this->_pid;

				if($this->kill() && $this->_show){
					echo 'Killed process '.$temp_pid.'. <a href="'.\Request::generate_local_url(null, ['kill']).'">Click Here To Restart</a>';
				}
				elseif($this->_show){
					echo 'Unable to kill process '.$temp_pid.' because it is not running. <a href="'.\Request::generate_local_url(null, ['kill']).'">Click Here To Restart</a>';
				}
				exit;
			}
			

			//CHECK IF THE PID FILE NEEDS TO RUN
			if($this->check_pid()){
				$this->registerShutdownEvent([$this, 'delete_pid_file']);
				$callback();
				//unlink($this->_pid_file);
				//$this->_pid_file = null;
			}
			elseif($this->_show == true){
				echo 'The process id: '.$this->_pid.' is already running. <a href="'.\Request::generate_local_url(['kill' => 'true']).'">Click here to kill the process.</a>';
			}

			//CLOSE OUT
			exit;
		}

		public function kill(){

			//if(\Request::get('kill')){
				
				if(file_exists($this->_pid_file)){

					//GET THE PID
					$this->_pid = file_get_contents($this->_pid_file);

					//CHECK IF THE PROCESS IS RUNNING
					exec("ps -p {$this->_pid}", $output);

					//IF THE PROCESS IS NOT RUNNING DELETE THE PID FILE
					if(count($output) > 1){
						exec("kill ".$this->_pid);
					}

					unlink($this->_pid_file);

					//pr('Killed '.$this->_pid);
					//exit;

					$this->_pid_file 	= null;
					$this->_pid 		= null;

					return true;
				}
			//}

			return false;
		}

		public function check_pid(){

			//CHECK IF THE PID FILE EXISTS
			if(file_exists($this->_pid_file)){

				//GET THE PID
				$this->_pid = file_get_contents($this->_pid_file);

				//CHECK IF THE PROCESS IS RUNNING
				exec("ps -p {$this->_pid}", $output);

				//IF THE PROCESS IS NOT RUNNING DELETE THE PID FILE
				if(count($output) <= 1) unlink($this->_pid_file);
			}

			//UNLINK AFTER TIMEOUT
			if(file_exists($this->_pid_file) && filemtime($this->_pid_file)+$this->_timeout < time()) unlink($this->_pid_file);

			//CHECK AGAIN IF PID FILE EXISTS
			if(!file_exists($this->_pid_file)){

				//CREATE THE PID FILE
				$this->create($this->_pid_file);

				//RETURN TRUE TO SAY THE PID IS NOT RUNNING
				return true;
			}

			//RETURN FALSE TO SAY THAT THE PID IS STILL RUNNING
			return false;
		}

		//CREATE A PID FILE
		public function create(){

			$this->_pid = getmypid();

			//CREATE THE DIRECTORY IF NEEDED
			if(!file_exists(dirname($this->_pid_file))) mkdir(dirname($this->_pid_file), 0777, true);

			//WRITE THE PID FILE
			$handle = fopen($this->_pid_file, 'w+');
			fwrite($handle, $this->_pid);
			fclose($handle);
		}
	}