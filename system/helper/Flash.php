<?php
	class Flash_Helper extends Helper {

		public function __construct(){

		}

		public function add_flash($message){
			if(is_array($message)){
				foreach($message as $m){
					Session::update(function($message){
						$_SESSION['flash'][] = $message;
					}, $m);
				}
			}
			else{
				Session::update(function($message){
					$_SESSION['flash'][] = $message;
				}, $message);
			}

			return $this;
			
		}

		public function render_flash(){
			if(isset($_SESSION['flash'])){
				foreach($_SESSION['flash'] as $message){
					?>
						<div class="alert alert-info alert-dismissible fade in" role="alert"> 
							<button type="button" class="close" data-dismiss="alert" aria-label="Close">
								<span aria-hidden="true">×</span>
							</button> 
							<?=$message?>
						</div>
					<?
				}
				Session::update(function(){
					unset($_SESSION['flash']);
				});
			}
		}
	}
?>