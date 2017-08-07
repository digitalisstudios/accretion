<?php 
	class Encryption_Helper extends Helper {
		
		public static function encrypt($string){
		    return openssl_encrypt($string, "AES-128-ECB", pack('H*', \Config::get('encryption_key')));
		}

		public static function decrypt($string){
			return openssl_decrypt($string,"AES-128-ECB", pack('H*', \Config::get('encryption_key')));
		}

		public static function generate_key(){
			return bin2hex(openssl_random_pseudo_bytes(32));
		}
	}

?>