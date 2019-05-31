<?php

	class CLIPrinter {

		//CLASS PROPERTIES
		public static 		$_indent;	
		public static 		$_isCli;
		protected static 	$_instantiated;
		private static 		$_classFiles 	= [];
		private static 		$_instance;
		private static 		$_cursorInstance;
		private static 		$_bufferInstance;

		public function __construct($forceInit = false, $clearScreen = true){

			//CHECK IF WE NEED TO INITIALIZE THE CLASS
			if(!self::$_instantiated || $forceInit === true) self::init($clearScreen);
		}

		//HANDLE LOADING CLASSES DYNAMICALLY
		public static function __callStatic($name, $arguments = []){

			//SEND BACK THE CLASS IF IT EXISTS
			return isset(self::$_classFiles[$name]) ? new $name : null;
		}

		public static function cursor(){

			if(is_null(self::$_cursorInstance)) self::$_cursorInstance = new CLICursor;

			return self::$_cursorInstance;
		}

		public static function buffer(){

			if(is_null(self::$_bufferInstance)) self::$_bufferInstance = new CLIBuffer;

			return self::$_bufferInstance;
		}

		public static function setDefaults(){

			//SET THE DEFAULTS
			self::$_indent 			= 0;
			self::$_isCli 			= null;

			//SET THE DEFAULTS FOR THE BUFFER
			CLIBuffer::setDefaults();
			CLICursor::setDefaults();

			return self::$_instance;
		}

		public static function _autoLoadClass($name){

			//HANDLE NAMESPACES FOR AUTOLOADED CLASS NAMES
			$name = end(explode("\\", $name));

			//TRY TO INCLUDE THE CLASS
			if(isset(self::$_classFiles[$name])) include_once self::$_classFiles[$name];

			//CALL THE CLASS
			return self::$name();
		}

		//INSTANTIATE A NEW VERSION OF THIS PRINTER
		private static function init($clearScreen = true){

			//GET THE CLASS FILES IF NEEDED
			if(!CLIPrinter::$_instantiated) foreach(glob(__DIR__.'/*.php') as $file) self::$_classFiles[pathinfo($file, PATHINFO_FILENAME)] = $file;

			//CLEAR THE SCREEN
			if($clearScreen === true) CLIBuffer::clearScreen();

			//FLAG AS INSTANTIATED
			self::$_instantiated = true;

			self::$_instance = new CLIPrinter;

			return self::$_instance;
		}

		public static function isCli(){

			//SET IF WE ARE IN A CLI
			if(is_null(self::$_isCli)) self::$_isCli = php_sapi_name() == 'cli';

			//SEND BACK THE CLI MODE
			return self::$_isCli;
		}

		//CLEAR A CONSOLE LINE BY LINE NUMBER
		public static function clearLine($line = null){

			//GET THE CURRENT LINE
			if(!is_null($line)) $current_line = self::cursor()->position();

			//CHECK IF WE ARE CLEARING A SPECIFIC LINE JUMP TO THAT LINE
			if(!is_null($line)) self::cursor()->moveTo($line);

			//CLEAR THE LINE
			self::uecho("\r\033[2K\r");

			//FLUSH THE OUTPUT
			self::buffer()->flushBuffer();

			//IF WE MOVED THE CURSOR MOVE BACK
			if(!is_null($line)) self::cursor()->moveTo($current_line);

			return static::$_instance;
		}

		//CLEAR THE OUTPUT OF THE CONSOLE AFTER A LINE
		public static function clearAfterLine($line = null){

			//GET THE CURRENT LINE
			$current_line = self::cursor()->position();

			//MOVE THE CURSOR TO THE TARGET LINE
			self::cursor()->moveTo(is_null($line) ? $current_line : $line);

			//CLEAR THE DATA AFTER THE LINE
			self::uecho("\r\n\033[J\r");

			//MOVE THE CURSOR TO THE ORIGINAL LINE
			self::cursor()->moveTo($current_line);

			return static::$_instance;
		}

		//RENDER TO THE CLI
		public static function printOut($msg, $breakBefore = false, $breakAfter = true){

			//PRINT THE CONTENT OUT WITH LINE BREAKS
			self::uecho(($breakBefore ? "\n" : "").self::getIndent().$msg.($breakAfter ? "\n" : ""));

			//FLUSH THE BUFFER
			self::buffer()->flushBuffer(true);

			return static::$_instance;
		}

		//PRINT OUT CONTENT WITHOUT FORMATTING
		public static function uecho($content = null){ echo $content; }

		//GET THE INDENT LEVEL
		public static function getIndent($out = ""){

			//GENERATE SPACING FROM INDENT LEVEL
			if($spacing > 0) for($x = 0; self::$_indent*4; $x++) $out .= " ";

			//SEND BACK THE WHITE SPACE
			return $out;
		}

		//SET THE INDENT LEVEL
		public static function indent($amount){ 
		
			self::$_indent = $amount; 

			return static::$_instance;

		}

		//GENERATE A NEW PROGRESS BAR
		public static function createProgressBar($total = 0, $size = 60){ return new CLIProgressBar($total, $size); }

		//GENERATE A NEW STYLED OUTPUT
		public static function styled($text){ return CLIStyle::text($text); }

		//GENERATE A HUMAN READABLE SIZE
		public static function humanSize($size, $format = false) {

			//FIND THE SIZE TYPE
			foreach(['B','KB','MB','GB','TB'] as $val) if($size > 1024) $size = $size/1024; else break;

			//SEND BACK THE ROUNDED SIZE FORMATTED OR UNFORMATTED
			return ($format ? number_format(round($size, 2), 2, '.', '') :  round($size, 2))." ".$val;
		}

		//CALCULATE HUMAN TIME FROM A PHP UNIX EPOCH TIME
		public static function humanTime($time, $format = 'YMDHIS'){
			
			$time 	= is_numeric($time) ? date('Y-m-d H:i:s', $time) : $time;
			$ago 	= strtotime($time) >= time() ? '' : ' ago';
			$date1 	= new \DateTime($time);
			$date2 	= $date1->diff(new \DateTime);
			$parts 	= [];

			foreach(str_split($format) as $format_part){

				if($format_part == 'Y') $parts[] = $date2->y > 0 ? ($date2->y.' Year'.($date2->y > 1 ? 's' : '')) : '';
				elseif($format_part == 'y') $parts[] = $date2->y > 0 ? ($date2->y.'y'.($date2->y > 1 ? 'rs' : '')) : '';
				elseif($format_part == 'M') $parts[] = $date2->m > 0 ? ($date2->m.' Month'.($date2->m > 1 ? 's' : '')) : '';
				elseif($format_part == 'm') $parts[] = $date2->m > 0 ? ($date2->m.'mth'.($date2->m > 1 ? 's' : '')) : '';
				elseif($format_part == 'D') $parts[] = $date2->d > 0 ? ($date2->d.' Day'.($date2->d > 1 ? 's' : '')) : '';
				elseif($format_part == 'd') $parts[] = $date2->d > 0 ? ($date2->d.'d') : '';
				elseif($format_part == 'H') $parts[] = $date2->h > 0 ? ($date2->h.' Hour'.($date2->h > 1 ? 's' : '')) : '';
				elseif($format_part == 'h') $parts[] = $date2->h > 0 ? ($date2->h.'hr'.($date2->h > 1 ? 's' : '')) : '';
				elseif($format_part == 'I') $parts[] = $date2->i > 0 ? ($date2->i.' Minute'.($date2->i > 1 ? 's' : '')) : '';
				elseif($format_part == 'i') $parts[] = $date2->i > 0 ? ($date2->i.'min'.($date2->i > 1 ? 's' : '')) : '';
				elseif($format_part == 'S') $parts[] = $date2->s > 0 ? ($date2->s.' Second'.($date2->s > 1 ? 's' : '')) : '';
				elseif($format_part == 's') $parts[] = $date2->s > 0 ? ($date2->s.'s') : '';
			}

			return implode(' ',array_filter($parts));
		}
	}

	//HANDLE AUTOLOADING CLASS FILES
	spl_autoload_register(function ($class_name) { return CLIPrinter::_autoLoadClass($class_name); });
