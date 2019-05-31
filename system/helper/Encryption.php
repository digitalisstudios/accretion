<?php 
	class Encryption_Helper extends Helper {
		
		public static function encrypt($string, $base64 = false){
		    return openssl_encrypt($base64 ? base64_encode($string) : $string, "AES-128-ECB", pack('H*', \Config::get('encryption_key')));
		}

		public static function decrypt($string, $base64 = false){
			return openssl_decrypt($base64 ? base64_decode($string) : $string, "AES-128-ECB", pack('H*', \Config::get('encryption_key')));
		}

		public static function generate_key(){
			return bin2hex(openssl_random_pseudo_bytes(32));
		}
	}

?>