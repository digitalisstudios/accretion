<?php

	class CLIStyle {

		public static $_isCli;
		protected $_text;
		protected $_bold 		= false;
		protected $_italic 		= false;
		protected $_underline 	= false;
		protected $_blink 		= false;
		protected $_invert 		= false;
		protected $_color;
		protected $_background;

		//RUN WHEN A NEW NODE IS INSTANTIATED
		public function __construct($text){

			//SET IS CLI
			self::$_isCli = php_sapi_name() == 'cli';

			$this->_text = $text;
		}

		//RUN WHEN THIS TEXT NODE IS RENDERED
		public function __toString(){
			return self::$_isCli ? $this->toCliString() : $this->toBrowserString();
		}

		//CREATE A NEW CLI STYLE TEXT NODE
		public static function text($text){
			return new CLIStyle($text);
		}

		//SET THIS TEXT NODE TO BE BOLD
		public function bold($val = true){
			$this->_bold = $val;
			return $this;
		}

		//SET THIS TEXT NODE TO BE ITALIC
		public function italic($val = true){
			$this->_italic = $val;
			return $this;
		}

		//SET THIS TEXT NODE TO BE UNDERLINED
		public function underline($val = true){
			$this->_underline = $val;
			return $this;
		}

		//SET THIS TEXT NODE TO BLINK
		public function blink($val = true){
			$this->_blink = $val;
			return $this;
		}

		//SET THIS TEXT NODE TO HAVE INVERTED COLORS
		public function invert($val = true){
			$this->_invert = $val;
			return $this;
		}

		//SET THE COLOR FOR THIS TEXT NODE
		public function color($val = null){
			$this->_color = $val;
			return $this;
		}

		//SET THE BACKGROUND FOR THIS TEXT NODE
		public function background($val = null){
			$this->_background = $val;
			return $this;
		}

		//GENERATE THE OUTPUT WHEN THE USER IS USING A BROWSER
		public function toBrowserString(){

			$styles = [];

			if($this->_background) 	$styles['background'] 		= '#'.$this->_background;
			if($this->_color) 		$styles['color'] 			= '#'.$this->_color;
			if($this->_bold) 		$styles['font-weight'] 		= 'bold';
			if($this->_italic) 		$styles['font-style'] 		= 'italic';
			if($this->_underline) 	$styles['text-decoration'] 	= 'underline';

			$str = '<span style="';
			foreach($styles as $k => $v) $str .= $k.':'.$v.'; ';
			$str .= '">';
			$str .= $this->_text;
			$str .= '</span>';

			return $str;
		}

		//GENERATE THE OUTPUT WHEN THE USER IS USING A CLI
		public function toCliString(){

			$str = "";

			if($this->_background) 	$str .= "\033[48;5;".$this->getColor($this->_background)."m";
			if($this->_color) 		$str .= "\033[38;5;".$this->getColor($this->_color)."m";
			if($this->_bold) 		$str .= "\033[1m";
			if($this->_italic) 		$str .= "\033[3m";
			if($this->_underline)	$str .= "\033[4m";
			if($this->_bilnk)		$str .= "\033[5m";
			if($this->_invert)		$str .= "\033[7m";

			$str .= $this->_text;
			$str .= "\033[0m";

			return $str;
		}

		//GET A COLOR ANSI CODE BY HEX KEY
		public function getColor($val = null){
			return \CLIColor::getColor($val);
		}
	}