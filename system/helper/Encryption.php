<?php 
	class Encryption_Helper extends Helper {
		
		public static function encrypt($string){
			
		    $key = pack('H*', Config::get('encryption_key'));
		    $key_size 			= strlen($key);
		    $iv_size 			= mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		    $iv 				= mcrypt_create_iv($iv_size, MCRYPT_RAND);
		    $ciphertext 		= mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $string, MCRYPT_MODE_CBC, $iv);
		    $ciphertext 		= $iv . $ciphertext;
		    $ciphertext_base64 	= base64_encode($ciphertext);
		    return $ciphertext_base64;
		}

		public static function decrypt($string){
			
			$key = pack('H*', Config::get('encryption_key'));
			$ciphertext_dec 	= base64_decode($string);
			$iv_size 			= mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		    $iv_dec 			= substr($ciphertext_dec, 0, $iv_size);
		    $ciphertext_dec 	= substr($ciphertext_dec, $iv_size);
		    $plaintext_dec 		= mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec);
		    return $plaintext_dec;

		}

		public static function generate_key(){
			return bin2hex(openssl_random_pseudo_bytes(32));
		}
	}

?>