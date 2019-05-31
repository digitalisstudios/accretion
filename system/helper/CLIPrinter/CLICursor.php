<?php
	
	class CLICursor extends CLIPrinter {

		public static $_cursorPosition;
		private static $_instance;

		public function __construct(){
			self::$_instance = $this;
		}

		public static function setDefaults(){

			if(is_null(self::$_instance)) self::$_instance = new CLICursor;

			self::$_cursorPosition = 0;

			return self::$_instance;
		}

		public static function position(){

			//FLUSH AND COMPILE THE BUFFER
			self::buffer()->flushBuffer(true);

			//SEND BACK THE CURSOR POSITION
			return self::$_cursorPosition;
		}

		public static function moveTo($lineNumber){

			//GET THE CURRENT LINE
			$current_line = self::position();

			//CHECK IF THE CURRENT LINE IS BEFORE THE TARGE LINE
			if($current_line < $lineNumber){

				//CALCULATE THE NUMBER OF LINES TO JUMP
				$lines = $lineNumber-$current_line;

				//JUMP THE CURSOR UP BY THE CALCULATED LINES
				self::uecho("\033[{$lines}B\r");

				//CALCULATE THE NEW CURSOR POSITION
				self::$_cursorPosition += $lines;
			}

			//CHECK IF THE CURRENT LINE IS AFTER THE TARGET LINE
			elseif($current_line > $lineNumber){

				//CALCULATE THE NUMBER OF LINES TO MOVE
				$lines = $current_line-$lineNumber;

				//JUMP THE CURSOR DOWN BY THE CALCULATED LINE AMOUNT
				self::uecho("\033[{$lines}A\r");

				//CALCULATE THE NEW CURSOR POSITION
				self::$_cursorPosition -= $lines;
			}

			//FLUSH THE BUFFER
			self::buffer()->flushBuffer();

		}

		//MOVE THE CURSOR TO THE END
		public static function moveToEnd(){

			//FLUSH THE BUFFER
			self::buffer()->flushBuffer(true);

			//MOVE THE CURSOR DOWN BY THE DIFFERENCE IT IS FROM THE BOTTOM 
			if(self::$_cursorPosition > 0){ self::moveDown(self::buffer()->lineCount()-self::$_cursorPosition); }
		}

		//MOVE THE CURSOR UP
		public static function moveUp($lines = 1){ self::moveTo(self::position()-$lines); }

		//MOVE THE CURSOR DOWN
		public function moveDown($lines = 1){ self::moveTo(self::position()+$lines); }

	}