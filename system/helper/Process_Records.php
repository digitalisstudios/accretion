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

						#process_records {
							width: 100%;
							max-width: 800px;
							margin:auto;
							background: #ededed;
						    padding: 20px;
						    border-radius: 5px;
						    box-shadow: rgba(0,0,0,0.3) 0 3px 5px;
						    margin-top: 20px;
						    border: 1px solid #d0d0d0;
						    color: #666;
						    font-family: helvetica;
						}

						#process_records_header {
						    /*content: 'Process Records';*/
						    background: #ddd;
						    margin: -20px;
						    margin-bottom: 10px;
						    padding: 10px;
						    box-sizing: border-box;
						    display: block;
						    border-bottom: #ccc solid 1px;
						    text-shadow: #fff 0 1px 0px, #333 0 -1px 0px;
						    font-weight: bold;
						    text-transform: capitalize;
						    letter-spacing: 0.4em;
						    color: #9a9898;
						}

						.progress {
							height: 20px;
						    margin-bottom: 10px;
						    overflow: hidden;
						    background-color: #f5f5f5;
						    border-radius: 4px;
						    -webkit-box-shadow: inset 0 1px 2px rgba(0,0,0,.1);
						    box-shadow: inset 0 1px 2px rgba(0,0,0,.1);
						    margin-top: 10px;
						    width: 100%;
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

					<div id="process_records_header">
						Process Records <label style="float: right; padding: 10px; margin:-10px; background: #ccc;"><input type="checkbox" name="stop_process" value="<?=$this->pid?>" id="stop_process"> <span>Stop</span></label>
					</div>
					
					<div id="process_records_wrapper">
						<div id="progress_records_messages"></div>					
						<div class="progress"><div id="progress-bar" class="progress-bar progress-bar-striped active" style="width:0%"></div></div>
					</div>
					<div id="message_wrapper">&nbsp;</div>
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

			ignore_user_abort(false);


			if(!$this->initialized){
				$this->init();
			}

			date_default_timezone_set('America/Los_Angeles');
			if(is_object($this->records) && get_class($this->records) == 'ORM_Wrapper'){
				$this->total_records = $this->records->count();
			}
			else{
				$this->total_records = count($this->records);
			}
			
			$this->start_time = time();

			$this->pid = getmypid();

			foreach($this->records as $this->current_key => $record){

				if(connection_aborted()){
					$this->total_records = $this->current_key+1;
					break;
				}
				
				//WAIT FOR 20 MILISECONDS
				usleep(2000);

				if($this->current_key == 0){
					$this->finish_time = $this->get_eta();

					//GENERATE THE MESSAGE
					$this->percent_complete = 0;
					$message = '<b>Records:</b> 1/'.$this->total_records.' (%0)'.'<span style="float:right; display:block;"><b>Estimated Finish Time:</b> '.$this->finish_time."</span>";

					//OUTPUT THE MESSAGE WITH JAVASCRIPT
					?>
						<script id="update_progress_script">					
							document.getElementById("progress_records_messages").innerHTML = '<?=$message?>';
							document.getElementById('progress-bar').style.width = '<?=$this->percent_complete?>%';

							var element = document.getElementById("update_progress_script");
							element.outerHTML = "";
							delete element;

						</script>
					<?
				}			

				//SET THE PARENT AND METHOD
				$parent_class 		= $this->callback_class;
				$callback_method 	= $this->callback_method;

				//GET THE MESSAGE FROM THE CALLBACK
				if(!$callback_method && is_callable($parent_class)){
					$res = $parent_class($this->current_key, $record);
				}
				else{
					$res = $parent_class->$callback_method($this->current_key, $record);
				}

				$this->finish_time = $this->get_eta();

				//GENERATE THE MESSAGE
				$this->percent_complete = floor(((($this->current_key+1)*100)/($this->total_records*100))*100);
				$message = '<b>Records:</b> '. ($this->current_key+1).'/'.$this->total_records.' (%'.$this->percent_complete.')'.'<span style="float:right; display:block;"><b>Estimated Finish Time:</b> '.$this->finish_time."</span>";

				//OUTPUT THE MESSAGE WITH JAVASCRIPT
				?>
					<script id="update_progress_script">	

						if(document.getElementById('stop_process').checked == true){
							window.stop();
						}				
						document.getElementById("progress_records_messages").innerHTML = '<?=$message?>';
						document.getElementById('progress-bar').style.width = '<?=$this->percent_complete?>%';

						var element = document.getElementById("update_progress_script");
						element.outerHTML = "";
						delete element;

					</script>
				<?
				

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

			$duration = $this->get_duration();

			//OUTPUT THE FINAL RESULTS
			ob_start();
			pr('Done');
			pr('Records Processed: '.$this->total_records);
			pr('Duration: '.$duration);
			$message = ob_get_clean();

			//OUTPUT THE MESSAGE WITH JAVASCRIPT
			?>
				<script id="update_progress_script">

					if(typeof(handle) != 'undefined' && handle != null){
						clearInterval(handle);
					}				
					
					document.getElementById("progress_records_messages").innerHTML = '<?=$message?>';

					var element = document.getElementById("update_progress_script");
					element.outerHTML = "";
					delete element;

				</script>
			<?

		}

		//ESTIMATE THE ETA
		public function get_eta(){
			return date('g:i a', $this->start_time+(((time()-$this->start_time)/($this->current_key+1))*$this->total_records));
		}

		public function get_duration(){
			
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

			return implode(' and ', $duration_text);
		}

		public function render_message($message){

			//OUTPUT THE MESSAGE WITH JAVASCRIPT
			?>

				<script id="update_progress_script_new">

					var element = document.getElementById("update_progress_script");

					if(typeof(element) != 'undefined' && element != null){
						element.outerHTML = "";
						delete element;
						clearInterval(handle);
					}

					document.getElementById("update_progress_script_new").id = 'update_progress_script';

					document.getElementById("message_wrapper").innerHTML = '<?=$message?>';

					handle = setInterval(function(){
						document.getElementById("message_wrapper").innerHTML += '.';
					}, 1000);

				</script>
			<?

		}
	}
?>