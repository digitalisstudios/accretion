<?php
	class CronJobItem extends \CronJob_Helper {

		public $_line;
		public $_minute;
		public $_hour;
		public $_day;
		public $_month;
		public $_weekday;
		public $_command;
		public $_jobName;
		public $_needsUpdate = false;
		public $_simpleString = false;

		public function __construct($data){
			$this->_line = $data;
			//$this->jobName();
			$this->parseLine($data);
		}

		public function needsUpdate(){
			return $this->_needsUpdate;
		}

		public function parseLine($data = null){

			$data = !is_null($data) ? $data : $this->_line;

			if(substr($data, 0, 7) == 'MAILTO='){
				$this->_simpleString = $data;
				$this->_jobName = '';
				$this->_line = $data;
				$this->_needsUpdate = false;
			}

			$line_parts = explode(" ", trim($data));

			$this->minute($line_parts[0]);
			$this->hour($line_parts[1]);
			$this->day($line_parts[2]);
			$this->month($line_parts[3]);
			$this->weekday($line_parts[4]);

			for($x = 0; $x <= 4; $x++) unset($line_parts[$x]);

			$lines_without_time = implode(" ", $line_parts);

			$new_parts = explode('#JOB=', $lines_without_time);

			$this->command($new_parts[0]);

			if(isset($new_parts[1])){
				$this->jobName($new_parts[1]);
			}
			else{
				$this->jobName();
			}

			return $this;
		}

		public function minute($val = null){
			if(!is_null($val)){
				$this->_minute = trim($val);
				return $this;
			}

			return trim($this->_minute);
		}

		public function hour($val = null){
			if(!is_null($val)){
				$this->_hour = trim($val);
				return $this;
			}

			return trim($this->_hour);
		}

		public function day($val = null){
			if(!is_null($val)){
				$this->_day = trim($val);
				return $this;
			}

			return trim($this->_day);
		}

		public function month($val = null){
			if(!is_null($val)){
				$this->_month = trim($val);
				return $this;
			}

			return trim($this->_month);
		}

		public function weekday($val = null){
			if(!is_null($val)){
				$this->_weekday = trim($val);
				return $this;
			}

			return trim($this->_weekday);
		}

		public function command($val = null){
			if(!is_null($val)){
				$this->_command = trim($val);
				return $this;
			}

			return trim($this->_command);
		}

		public function jobName($val = null){
			if(!is_null($val)){
				$this->_jobName = $val;
				return $this;
			}

			if(is_null($this->_jobName) && strpos($this->_line, '#JOB=') === false){

				$jobs = $this->get_job_list(false);
				$found = false;
				foreach($jobs as $k => $job){
					if($job == $this->_line){
						$this->_jobName = $k;
						//$jobs[$k] = $this->asString();
						$this->_needsUpdate = true;
						$found = true;
						break;
					}
				}

				if(!$found){
					$this->_jobName = count($jobs)+1;
					$this->_needsUpdate = true;
					//$jobs[] = $this->asString();
				}

				//$this->writeJobs($jobs);
			}

			return $this->_jobName;
		}

		public function asString($withoutName = false){

			if($this->_simpleString) return $this->_simpleString;

			$parts = array_filter([
				trim($this->minute()),
				trim($this->hour()),
				trim($this->day()),
				trim($this->month()),
				trim($this->weekday()),
				trim($this->command()),
				'jobName' => '#JOB='.$this->jobName(),
			], 'strlen');

			if($withoutName){
				unset($parts['jobName']);
				return trim(implode(' ', $parts));
			}

			$this->_line = trim(implode(' ', $parts));

			return $this->_line;
		}

		public function sameCommand($command){

			return $this->command() == $command ? $this : false;
		}

		public function timesAsString(){
			return implode(' ', [
				trim($this->minute()),
				trim($this->hour()),
				trim($this->day()),
				trim($this->month()),
				trim($this->weekday())
			]);
		}

		public function sameTime($times){
			return $this->timesAsString() == $times ? $this : false;
		}

		public function sameName($name){
			return $this->_jobName == $name ? $this : false;
		}
	}
