<?php

	if(!function_exists('pr')){
		function pr($data = false){
			echo '<pre>';
			if(!$data){
				var_dump($data);
			}
			else{
				print_r($data);
			}
			echo '</pre>';
		}
	}

	class Process_Records_Helper extends Helper {

		public $initialized = false;

		public function __construct(){

			$this->init();
		}

		public function init(){
			?>
				<script>
					var element = document.getElementById("process_records");
					if(element){
						element.outerHTML = "";
					delete element;
					}
					
				</script>

				<div id="process_records">

					<style>
						.progress {
							height: 20px;
						    margin-bottom: 20px;
						    overflow: hidden;
						    background-color: #f5f5f5;
						    border-radius: 4px;
						    -webkit-box-shadow: inset 0 1px 2px rgba(0,0,0,.1);
						    box-shadow: inset 0 1px 2px rgba(0,0,0,.1);
						}

						.progress-bar-striped {
							background-image: -webkit-linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent);
						    background-image: -o-linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent);
						    background-image: linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent);
						    -webkit-background-size: 40px 40px;
						    background-size: 40px 40px;
						}

						.progress-bar {
							float: left;
						    width: 0;
						    height: 100%;
						    font-size: 12px;
						    line-height: 20px;
						    color: #fff;
						    text-align: center;
						    background-color: #337ab7;
						    -webkit-box-shadow: inset 0 -1px 0 rgba(0,0,0,.15);
						    box-shadow: inset 0 -1px 0 rgba(0,0,0,.15);
						    -webkit-transition: width .1s ease;
						   	-o-transition: width .1s ease;
						    transition: width .1s ease;
						}

						.progress-bar.active {
							-webkit-animation: progress-bar-stripes 2s linear infinite;
						    -o-animation: progress-bar-stripes 2s linear infinite;
						    animation: progress-bar-stripes 2s linear infinite;
						}

						@-webkit-keyframes progress-bar-stripes {
							from {
								background-position: 40px 0;
						  	}
						  	to {
						    	background-position: 0 0;
						  	}
						}
						@keyframes progress-bar-stripes {
							from {
								background-position: 40px 0;
						  	}
						  	to {
						    	background-position: 0 0;
						  	}
						}

					</style>
					<div id="message_wrapper">&nbsp;</div>
					<div id="process_records_wrapper">
						<div id="progress_records_messages">

						</div>
						<div class="progress" style="width:800px;"><div id="progress-bar" class="progress-bar progress-bar-striped active" style="width:0%"></div></div>
					</div>
				</div>
			<?

			$this->initialized = true;
		}

		public function records($records){
			$this->records = $records;
			return $this;
		}

		public function callback($parent_class, $method = false){
			$this->callback_class = $parent_class;
			$this->callback_method = $method;

			if(!$method && is_callable($parent_class)){
				return $this->run();
			}
			return $this;
		}

		public function run(){

			if(!$this->initialized){
				$this->init();
			}

			date_default_timezone_set('America/Los_Angeles');
			$total_records = count($this->records);
			$this->start_time = time();

			foreach($this->records as $k => $record){
				usleep(2000);
				$current_time 	= time();
				$time_diff 		= $current_time-$this->start_time;
				$avg_time 		= $time_diff/($k+1);
				$est_time 		= $avg_time*$total_records;
				$finish_time 	= $this->start_time+$est_time;
				$finish_time 	= date('g:i a', $finish_time);

				//GENERATE THE MESSAGE
				ob_start();
				$percent_complete = floor((($k+1)/$total_records)*100);
				echo '<pre>'.'Records: '. ($k+1).'/'.$total_records.' (%'.$percent_complete.')'.'</pre>';
				echo '<pre>'.'Estimated Finish Time: '.$finish_time.'</pre>';
				$message = ob_get_clean();

				//OUTPUT THE MESSAGE WITH JAVASCRIPT
				?>
					<script id="update_progress_script">					
						document.getElementById("progress_records_messages").innerHTML = '<?=$message?>';
						document.getElementById('progress-bar').style.width = '<?=$percent_complete?>%';

						var element = document.getElementById("update_progress_script");
						element.outerHTML = "";
						delete element;

					</script>
				<?

				//SET THE PARENT AND METHOD
				$parent_class 		= $this->callback_class;
				$callback_method 	= $this->callback_method;

				//GET THE MESSAGE FROM THE CALLBACK
				if(!$callback_method && is_callable($parent_class)){
					$res = $parent_class($k, $record);
				}
				else{
					$res = $parent_class->$callback_method($k, $record);
				}
				

				//IF THERE WAS A MESSAGE UPDATE THE MESSAGE ELEMENT
				if($res){
					?>
						<script id="update_message_script">
							document.getElementById("message_wrapper").innerHTML = '<?=$res?>';
							var element = document.getElementById("update_message_script");
							element.outerHTML = "";
							delete element;
						</script>
					<?
				}
			}

			//GENERATE THE DURATION
			$duration 			= time()-$this->start_time;
			$duration 			= gmdate("H:i:s", $duration);
			$duration_parts 	= explode(':', $duration);
			$duration_text 		= array();
			if($duration_parts[0] !== '00'){
				$duration_text[] = $duration_parts[0].' hours';
			}
			if($duration_parts[1] !== '00'){
				$duration_text[] = $duration_parts[1].' minutes';
			}
			if($duration_parts[2] !== '00'){
				$duration_text[] = $duration_parts[2].' seconds';
			}
			$duration = implode(' and ', $duration_text);

			//OUTPUT THE FINAL RESULTS
			ob_start();
			pr('Done');
			pr('Records Processed: '.$total_records);
			pr('Duration: '.$duration);
			$message = ob_get_clean();

			//OUTPUT THE MESSAGE WITH JAVASCRIPT
			?>
				<script id="update_progress_script">					
					document.getElementById("progress_records_messages").innerHTML = '<?=$message?>';

					var element = document.getElementById("update_progress_script");
					element.outerHTML = "";
					delete element;

				</script>
			<?

		}
	}
?>