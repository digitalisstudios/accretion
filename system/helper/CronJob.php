<?php

	class CronJob_Helper extends Helper {

		public $_jobs;

		public function __construct($getSettings = true){
			
			if(get_class($this) == 'CronJob_Helper'){

				$this->_jobs = new \ORM_Wrapper;
				foreach(glob(__DIR__.'/CronJob/*.php') as $file){
					require_once $file;
				}

				if($getSettings) $this->get_settings();

				
			}

			return $this;
		}

		public function get_job_list(){

			$user = "";

			if(get_current_user() == 'root'){
				$cron_user = \Config::server_config()->cron_user;
				if($cron_user && $cron_user != 'root'){
					$user = "-u {$cron_user} ";
				}
			}

			$output = shell_exec("crontab -l {$user}");

			if($output){

				$parts = array_values(array_filter(explode("\n", $output)));

				$new_parts = [];
				foreach($parts as $part){
					if($part !== 'SHELL="/bin/bash"' && substr($part, 0, 1) !== '#') $new_parts[] = trim($part); 
				}

				$output = implode("\n", $new_parts);
			}
			else{
				$output = "";
			}

			return array_values(array_filter(explode("\n", $output)));
		}

		public function parseLineAsSetting($line){
			$parts = explode(" ", $line);

			$new_parts = array_chunk(explode(" ", $line), 5);

			$time = implode(" ",$new_parts[0]);
			$command = implode(" ", $new_parts[1]);

			$name = null;

			if(strpos($command, " #JOB=") !== false){
				$cmd_parts = explode(" #JOB=", $command);
				$name = $cmd_parts[1];
				$command = $cmd_parts[0];
			}

			$res = [
				$time,
				$command
			];

			return [
				'data' => $res,
				'name' => $name
			];
		}

		public function jobs_as_strings(){
			$strings = [];

			foreach($this->_jobs as $job){
				$strings[] = $job->asString();
			}

			return $strings;
		}

		public function needsUpdate(){

			foreach($this->_jobs as $job) if($job->_needsUpdate) return $this->writeJobs($this->jobs_as_strings());

			return false;
		}

		public function get_current_jobs(){			

			foreach($this->get_job_list() as $line){

				$found = false;

				$job = new CronJobItem($line);

				foreach($this->_jobs as $k => $v){

					if($v->asString() == $line){
						$found = true;
						continue;
					}
				}

				if(!$found){
					$this->_jobs->push($job);
				}
			}
			//$this->needsUpdate();
			return $this;
		}

		public function get_non_job_lines(){
			$res = new \ORM_Wrapper;

			$this->get_current_jobs();

			foreach($this->_jobs as $job){
				if($job->_simpleString){
					$res->push($job);
				}
			}

			return $res;
		}

		public function convertJobsToStrings($jobs){
			$res = [];

			foreach($jobs as $job){
				$res[] = $job->asString();
			}

			return $res;
		}

		public function get_settings(){

			$jobs = $this->get_non_job_lines();

			//GET THE CURRENT JOBS
			$this->get_non_job_lines();

			//GET THE CRON JOBS FROM THE SETTINGS
			$settingsJobs = \Config::get('cron');

			foreach($settingsJobs as $k => $job){
				if(is_object($job) && isset($job->wget)){

					foreach($job as $x => $v){
						$time = $v;
						break;

					}

					if(!isset($job->wget->output)){
						$job->wget->output = false; 
					}

					if(!$job->wget->output){
						$job->wget->output = '> /dev/null 2>&1';
					}
					elseif($job->wget->output === true){
						$job->wget->output = '';
					}

					if(!isset($job->wget->headers)){
						$job->wget->headers = [];
					}

					if(isset($job->wget->local)){
						$job->wget->url = (isset($_SERVER['HTTPS']) ? "https" : "http").'://'.$_SERVER['HTTP_HOST'].WEB_APP.$job->wget->local;
						$job->wget->headers['Auth-Key'] = \Config::get('encryption_key');
					}

					$cmd_parts = [];
					//$cmd_parts[] = $time;
					$cmd_parts[] = 'wget';
					if($job->wget->output === '> /dev/null 2>&1'){
						$cmd_parts[] = "-O -";
					}
					$cmd_parts[] = $job->wget->url;

					foreach($job->wget->headers as $h => $header){
						$cmd_parts[] = "--header=\"{$h}: {$header}\"";
					}

					$cmd_parts[] = $job->wget->output;

					$new_job = [
						$time,
						implode(' ', $cmd_parts),
					];

					$settingsJobs->$k = $new_job;
				}
			}




			foreach($settingsJobs as $k => $job){

				$res = $this->getJobBySetting($job, $k);

				if(!$res){
					$line = $job[0].' '.$job[1].' '.'#JOB='.$k;
					$res = new CronJobItem($line);
					$res->_needsUpdate = true;
				}

				$jobs->push($res);
			}

			$res = $this->convertJobsToStrings($jobs);

			$res2 = $this->jobs_as_strings();

			$same = true;

			foreach($res as $k => $v){
				if($res2[$k] !== $v){
					$same = false;
					break;
				}
			}

			if(!$same){
				$this->writeJobs($res);
			}
			
			return $this;
		}

		public function getJobBySetting($setting, $settingName){
			
			$res = [];
			$named_jobs = $this->getJobByName($settingName);
			$command_jobs = $this->getJobByCommand($setting[1]);
			$time_jobs = $this->getJobBytime($setting[0]);

			foreach(['named_jobs','command_jobs','time_jobs'] as $var_name){
				foreach($$var_name as $x => $y){
					foreach($res as $k => $job){
						if($job !== $y){
							$res[] = $y;
						}
					}
				}
			}

			foreach($res as $r){
				if($r->sameName($settingName)){
					return $r;
				}
				elseif($r->sameCommand($setting[1]) && $r->sameTime($setting[0])){
					return $r;
				}

				elseif($r->sameCommand($setting[1]) && $r->sameName($settingName)){
					return $r;
				}
			}

			return false;
		}

		public function getJobBytime($time){

			$res = new \ORM_Wrapper;

			foreach($this->_jobs as $job){
				if($job->sameTime($time)){
					$res->push($job);
				}
			}

			return $res;
		}

		public function getJobByCommand($command){

			$res = new \ORM_Wrapper;

			foreach($this->_jobs as $job){
				if($job->sameCommand($command)){
					$res->push($job);
				}
			}

			return $res;

		}

		public function getJobByName($name){

			$res = new \ORM_Wrapper;
			foreach($this->_jobs as $job){
				if(trim($job->jobName()) == trim($name)){
					$res->push($job);
				}
			}

			return $res;
		}

		public function writeJobs($jobs){

			$user = "";

			if(get_current_user() == 'root'){
				$cron_user = \Config::server_config()->cron_user;
				if($cron_user && $cron_user != 'root'){
					$user = "-u {$cron_user} ";
				}
			}

			exec("crontab {$user}-r");

			foreach($jobs as $job){

				if(is_object($job)) $job = $job->asString();

				$cmd = 'echo -e '.$user.'"`crontab '.$user.'-l`\n'.addslashes($job).'" | crontab ';

				exec($cmd, $output, $res);
			}
			return $this;
		}
	}