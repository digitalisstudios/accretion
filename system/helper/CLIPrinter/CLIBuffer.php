<?php

	class CLIBuffer extends CLIPrinter{

		public static $_outputLines;
		public static $_lastBufferCompiled;		
		public static $_needsCompile;
		public static $_lineCount;
		private static $_instance;

		public function __construct(){
			self::$_instance = $this;
		}

		//SET THE DEFAULTS FOR THIS CLASS
		public static function setDefaults(){

			if(is_null(self::$_instance)) self::$_instance = new CLIBuffer;

			//SET THE DEFAULTS
			self::$_outputLines 			= [];
			self::$_lastBufferCompiled 		= "";
			self::$_needsCompile 			= true;
			self::$_lineCount 				= 0;

			//SEND BACK THAT DEFAULTS WERE SUCESSFULLY SET
			return self::$_instance;
		}

		//CHECK IF A FLUSH IS NEEDED
		public static function needsFlush(){

			return ob_get_length();
		}

		//PRINT THE BUFFER TO THE SCREEN IF NEEDED
		public static function flushBuffer($compile = false){

			if(!self::isCli()){
				//pr('not cli');
				//exit;
			}

			//ASSUME THAT WE HAVE NOT FLUSHED THE BUFFER
			$flushed = false;

			//CHECK IF THERE IS ANYTHING IN THE BUFFER
			if(ob_get_length()){

				//STORE THE CURRENT OUTPUT BUFFER
				self::$_outputLines[] = ob_get_contents();

				if(!self::isCli()){

					ob_clean();
					$scriptId 			= md5(microtime(true)).'-'.rand(0, 1000);
					$formattedBuffer 	= addslashes(str_replace("\n", "<br>", self::$_lastBufferCompiled));

					?>
						<script id="<?=$scriptId?>">

							var cliWrap 		= document.getElementById("cli-helper-console-text");
							var formattedBuffer = '<?=$formattedBuffer?>';
							if(cliWrap.innerHTML.length <= formattedBuffer.length){
								currentScrollHeight = cliWrap.scrollHeight;
								cliWrap.innerHTML 	= formattedBuffer;
								if(cliWrap.scrollHeight > currentScrollHeight) cliWrap.scrollTop = cliWrap.scrollHeight;
							}
							

							document.getElementById("<?=$scriptId?>").outerHTML = "";
						</script>
					<?

				}

				//TURN COMPILING ON
				self::$_needsCompile = true;

				//FLUSH THE BUFFER				
				ob_flush();

				//FLAG AS FLUSHED
				$flushed = true;
			}

			//COMPILE IF NEEDED AND FLUSHED
			if($compile === true && $flushed === true) self::compileBuffer(false);

			return static::$_instance;
		}

		//COMPILE THE BUFFER
		public static function compileBuffer($flush = true){

			//CHECK IF WE NEED TO FLUSH AND BUFFER
			if($flush === true && self::needsFlush()) self::$_needsCompile = true;

			//CHECK IF WE NEED TO COMPILE THE BUFFER
			if(self::$_needsCompile){

				//FLUSH THE BUFFER IF NEEDED
				if($flush === true) self::flushBuffer();

				//COLLAPSE THE OUTPUT LINES
				self::$_outputLines 			= [implode("", self::$_outputLines)];

				//COMPILE THE BUFFER AND PARSE IT
				self::parseCompiled();
			}			

			//RETURN THE COMPILED BUFFER
			return self::$_lastBufferCompiled;
		}

		//CLEAR THE SCREEN AND RESET DEFAULTS
		public static function clearScreen(){

			//STOP OUTPUT BUFFERING IF WEVE ALREADY INSTANTIATED
			if(CLIPrinter::$_instantiated === true) ob_end_clean();

			if(!self::isCli()){
				
				?>
					<script id="cli-script-clear-screen">

						try{
							document.body.innerHTML = "";
						}
						catch(e){

						}
						document.getElementById("cli-script-clear-screen").outerHTML = "";
					</script>

					<div id="cli-helper-console-text" style="position:fixed; top:0; left:0; right:0; bottom:0; overflow:auto; padding: 30px;"></div>
				<?

			}
			else{

				//CLEAR THE SCREEN AND RETURN TO 0
				echo "\r\n\033[H\033[J\033[r";
			}

			

			//START OUTPUT BUFFERING
			ob_start();

			//SET THE DEFAULT PROPERTIES
			CLIPrinter::setDefaults();

			//RETURN THE CALLING INSTANCE
			return static::$_instance;
			
		}

		//RETRIEVE THE LINE COUNT FROM THE BUFFER
		public static function lineCount(){

			//COMPILE THE BUFFER IF NEEDED
			self::compileBuffer(true);

			//SEND BACK THE CALCULATED LINE COUNT
			return self::$_lineCount;
		}

		public static function parseCompiled(){
		
			//PARSE THE CURRENT BUFFER
			$parsed 						= self::parse();
			
			//SET THE PARSED VALUES
			self::$_lastBufferCompiled 		= $parsed->newLines;
			self::$_needsCompile 			= false;
			self::$_lineCount 				= $parsed->lineCount;
			CLICursor::$_cursorPosition 	= $parsed->cursor;	

			//SEND BACK THE PARSED OBJECTS
			return $parsed;		
		}

		public static function parse($data = null){

			//SET THE DEFAULTS
			$newLines 		= [];
			$cursor 		= 0;
			$cursorPattern 	= "/\\033\[([0-9])+(A|B)/";
			$data 			= is_null($data) ? implode("",self::$_outputLines) : $data;

			//SPLIT THE DATA BY LINE CLEARS AND LOOP THROUGH THE PARTS
			foreach(explode("\033[J",$data) as $lineClearKey => $lineClears){

				//FORCE MIN CURSOR TO BE 0
				if($cursor < 0) $cursor = 0; 

				//RESET NEW LINES IF THE CURSOR IS 0
				if($cursor <= 0) $newLines 	= [];
				
				//SLICE OUT ONLY THE LINES WE WANT TO PROCESS
				if($cursor < count($newLines)) $newLines = array_slice($newLines, 0,  $cursor);

				//DECREMENT THE CURSOR
				$cursor--;
				
				//LOOP THROUGH EACH LINE
				foreach(explode("\n", $lineClears) as $lineKey => $line){

					//INCREMENT THE CURSOR
					$cursor++;

					//CHECK IF THERE IS A CURSOR MOVE
					if(preg_match_all($cursorPattern, $line, $matches)){

						//SPLIT UP THE LINE BY CURSOR MOVEMENT
						foreach(preg_split($cursorPattern, $line) as $linePartKey => $linePart){

							//ADD THE PART TO THE LINES
							$newLines[$cursor] .= "\r".$linePart;

							//CHECK IF WE NEED TO MOVE THE CURSOR
							if(isset($matches[2][$linePartKey])){

								//GET THE CURSOR VALUES
								$matchType 	= $matches[2][$linePartKey];
								$matchVal 	= $matches[1][$linePartKey];

								//WE NEED TO MOVE THE CURSOR UP
								if($matchType == 'A'){
									$cursor -= $matchVal;
								}

								//WE NEED TO MOVE THE CURSOR DOWN
								elseif($matchType == 'B'){
									$cursor += $matchVal;
								}
							}
						}
					}

					//NO CURSOR MOVEMENT SO JUST ADD THE LINE
					else{						
						$newLines[$cursor] .= "\r".$line;
					}
				}
			}

			//CLEAN UP LINES
			foreach($newLines as $k => $line) $newLines[$k] = end(array_filter(explode("\r", $line)));

			//BUILD THE RETURN OBJECT
			return (object)[
				'lineCount' 	=> count($newLines),
				'cursor' 		=> $cursor,
				'cursorBottom' 	=> (count($newLines)-$cursor),
				'newLines' 		=> implode("\n", $newLines),
				'originalData' 	=> $data,
			];
		}
	}