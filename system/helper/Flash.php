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
			echo '<div class="flash-message-container">';
			if(isset($_SESSION['flash'])){
				foreach($_SESSION['flash'] as $message){
					?>
						<div class="alert alert-primary alert-dismissible fade show" role="alert"> 
							<button type="button" class="close" data-dismiss="alert" aria-label="Close">
								<span aria-hidden="true">×</span>
							</button> 
							<div class="alert-message">
								<?=$message?>
							</div>
						</div>
					<?
				}
				Session::update(function(){
					unset($_SESSION['flash']);
				});
			}
			echo '</div>';
		}
	}