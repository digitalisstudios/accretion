<?php
	
	class CLIProgressBar {

		public $_start_time;
		public $_total 				= 0;
		public $_done 				= 0;
		public $_size 				= 60;
		public $_showTime 			= false;
		public $_sub_progress;
		public $_clearFinish 		= true;
		public $_progressColors 	= [100 => '5fd7ff'];

		public function __construct($total = 0, $size = 60){

			//SET DEFAULTS
			$this->_start_time 		= time();
			$this->_total 			= $total;
			$this->_size 			= $size;
		}

		public function progressColor($color, $position = 100){
			$progressColors = $this->_progressColors;

			$progressColors[$position] = $color;

			ksort($progressColors);

			$this->_progressColors = $progressColors;

			return $this;
		}

		//public function __destruct(){

			//CLEAR VARS
			//foreach($this as $k => $v) $this->$k = $v;
		//}

		public function clearFinish($val = true){
			$this->_clearFinish = $val;
			return $this;
		}

		//ADD A SUB PROGRESS BAR
		public function sub_progress($progress_bar = null){

			//CHECK IF A PROGRESS BAR WAS PASSED
			if(!is_null($progress_bar)){

				//SET A NEW SUB PROGRESS BAR
				$this->_sub_progress = $progress_bar;

				//SEND BACK THE MAIN OBJECT
				return $this;
			}

			//SEND BACK THE SUB PROGRESS BAR
			return $this->_sub_progress;
		}

		//SET THE TOTAL FOR THIS OBJECT
		public function setTotal($total = 0){
			$this->_total = $total;
			return $this;
		}

		//ESTIMATE THE ETA
		public function get_eta(){

			//CALCULATE THE ESTIMATED TIME REMAINING
			$est 		= $this->_start_time+(((time()-$this->_start_time)/($this->_done))*$this->_total);

			//GET THE ESTIMATE AS HUMAN READABLE
			$human_time = \CLIPrinter::humanTime($est, 'ymdhis');

			//SEND BACK THE ETA
			return date('g:i a', $est). ($human_time !== '' ? ' ('.$human_time.')' : '');
		}

		//GET THE DURATION OF THIS PROGRESS BAR
		public function get_duration(){
			
			//CALCULATE THE HUMAN READABLE TIME
			$res = \CLIPrinter::humanTime($this->_start_time, 'ymdhis');

			//SEND BACK THE RESULT
			return strlen($res) ? $res : '1s';
		}

		public function get_percent(){

			//CALCULATE PERCENT
			$perc 		= number_format(((double)($this->_done/$this->_total)*100), 2, '.', '');

			//CALCULATE SUB PERCENT 
			if(!is_null($this->_sub_progress)) $perc += number_format(($this->_sub_progress->get_percent()/$this->_total), 2, '.', '');

			//SEND BACK THE PERCENT
			return $perc;
		}

		public function getProgressColor($perc = null){

			$perc = is_null($perc) ? $this->get_percent() : $perc;

			foreach($this->_progressColors as $progress => $color){
				if($perc <= $progress){
					return $color;
				}
			}

			return $color;
		}

		public function render($done = 0, $msg = ""){

			//SET THE AMOUNT COMPLETED
			$this->_done = $done;

			//SKIP THE THE AMOUNT COMPLETED IS MORE THAN THE TOTAL
			if($done > $this->_total) return $this;

			//CLEAR EVERYTHING FROM THIS LINE DOWN
			if(\CLIPrinter::isCli()) \CLIPrinter::uecho("\033[2K\r");

			//SET SOME DEFAULTS
			$now 		= time();
		    $perc 		= $this->get_percent();
		    $bar 		= floor(($perc/100)*$this->_size);
		    $color 		= $this->getProgressColor($perc);

		    //GENERATE THE STATUS BAR
		    $status_bar = "\r".\CLIPrinter::getIndent();
		    $status_bar .= \CLIStyle::text(str_repeat("█", $bar))->background("b2b2b2")->color($color);
		    $status_bar .= \CLIStyle::text(str_repeat("█", $this->_size-$bar))->background("b2b2b2")->color("b2b2b2");
		    $status_bar .= \CLIStyle::text(" ".number_format($perc, 2)."%")->bold(true)."  ";
		    	
		    //GENERATE TIMES IF NEEDED
		    if($this->_showTime === true) $status_bar .= "  $done/$this->_total ETA: ".$this->get_eta().".  elapsed: ".$this->get_duration().".";

		    //RENDER THE STATUS BAR AND MESSAGE
		    \CLIPrinter::printOut("$status_bar $msg\r", false, false);

		    //IF WE ARE DONE CLEAR THE STATUS BAR
		    if($done == $this->_total && $this->_clearFinish === true) \CLIPrinter::uecho("\r\033[2K");

		    //SEND BACK THE STATUS BAR
		    return $this;	    
		}
	}