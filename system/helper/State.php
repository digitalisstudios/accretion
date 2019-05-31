<?php

	class State_Helper extends Helper{

		public $_short_name = null;
		public $_long_name = null;
		public $_timezone;

		public function __construct($name){

			if(strlen($name) == 2){
				$this->_short_name = $name;
			}
			else{
				$this->_long_name = $name;
			}

			$this->set_names();
			
			return $this;
		}

		public function set_timezones(){

			$state_timezones = array(
			    'AK' => array(
			        'name' => 'ALASKA',
			        'timezone' => 'AKST',
			        'timediff' => '-9',
			    ),
			    'AL' => array(
			        'name' => 'ALABAMA',
			        'timezone' => 'CDT',
			        'timediff' => '-6',
			    ),
			    'AR' => array(
			        'name' => 'ARKANSAS',
			        'timezone' => 'CDT',
			        'timediff' => '-6',
			    ),
			    'AZ' => array(
			        'name' => 'ARIZONA',
			        'timezone' => 'MST',
			        'timediff' => '-7',
			    ),
			    'CA' => array(
			        'name' => 'CALIFORNIA',
			        'timezone' => 'PDT',
			        'timediff' => '-8',
			    ),
			    'CO' => array(
			        'name' => 'COLORADO',
			        'timezone' => 'MST',
			        'timediff' => '-7',
			    ),
			    'CT' => array(
			        'name' => 'CONNECTICUT',
			        'timezone' => 'EDT',
			        'timediff' => '-5',
			    ),
			    'DC' => array(
			        'name' => 'DISTRICT OF COLUMBIA',
			        'timezone' => 'EDT',
			        'timediff' => '-5',
			    ),
			    'DE' => array(
			        'name' => 'DELAWARE',
			        'timezone' => 'EDT',
			        'timediff' => '-5',
			    ),
			    'FL' => array(
			        'name' => 'FLORIDA',
			        'timezone' => 'EDT',
			        'timediff' => '-5',
			    ),
			    'GA' => array(
			        'name' => 'GEORGIA',
			        'timezone' => 'EDT',
			        'timediff' => '-5',
			    ),
			    'GU' => array(
			        'name' => 'GUAM GU',
			        'timezone' => 'ChST',
			        'timediff' => '-10',
			    ),
			    'HI' => array(
			        'name' => 'HAWAII',
			        'timezone' => 'HST',
			        'timediff' => '-10',
			    ),
			    'IA' => array(
			        'name' => 'IOWA',
			        'timezone' => 'CDT',
			        'timediff' => '-6',
			    ),
			    'ID' => array(
			        'name' => 'IDAHO',
			        'timezone' => 'MDT',
			        'timediff' => '-7',
			    ),
			    'IL' => array(
			        'name' => 'ILLINOIS',
			        'timezone' => 'CDT',
			        'timediff' => '-6',
			    ),
			    'IN' => array(
			        'name' => 'INDIANA',
			        'timezone' => 'EST',
			        'timediff' => '-5',
			    ),
			    'KS' => array(
			        'name' => 'KANSAS',
			        'timezone' => 'CDT',
			        'timediff' => '-6',
			    ),
			    'KY' => array(
			        'name' => 'KENTUCKY',
			        'timezone' => 'EST',
			        'timediff' => '-6',
			    ),
			    'LA' => array(
			        'name' => 'LOUISIANA',
			        'timezone' => 'CDT',
			        'timediff' => '-6',
			    ),
			    'MA' => array(
			        'name' => 'MASSACHUSETTS',
			        'timezone' => 'EDT',
			        'timediff' => '-5',
			    ),
			    'MD' => array(
			        'name' => 'MARYLAND',
			        'timezone' => 'EDT',
			        'timediff' => '-5',
			    ),
			    'ME' => array(
			        'name' => 'MAINE',
			        'timezone' => 'EDT',
			        'timediff' => '-5',
			    ),
			    'MI' => array(
			        'name' => 'MICHIGAN',
			        'timezone' => 'America/Detroit',
			        'timediff' => '-5',
			    ),
			    'MN' => array(
			        'name' => 'MINNESOTA',
			        'timezone' => 'CDT',
			        'timediff' => '-6',
			    ),
			    'MO' => array(
			        'name' => 'MISSOURI',
			        'timezone' => 'CDT',
			        'timediff' => '-6',
			    ),
			    'MS' => array(
			        'name' => 'MISSISSIPPI',
			        'timezone' => 'CDT',
			        'timediff' => '-6',
			    ),
			    'MT' => array(
			        'name' => 'MONTANA',
			        'timezone' => 'MST',
			        'timediff' => '-7',
			    ),
			    'NC' => array(
			        'name' => 'NORTH CAROLINA',
			        'timezone' => 'EDT',
			        'timediff' => '-5',
			    ),
			    'ND' => array(
			        'name' => 'NORTH DAKOTA',
			        'timezone' => 'CST',
			        'timediff' => '-7',
			    ),
			    'NE' => array(
			        'name' => 'NEBRASKA',
			        'timezone' => 'CDT',
			        'timediff' => '-6',
			    ),
			    'NH' => array(
			        'name' => 'NEW HAMPSHIRE',
			        'timezone' => 'EDT',
			        'timediff' => '-5',
			    ),
			    'NJ' => array(
			        'name' => 'NEW JERSEY',
			        'timezone' => 'EDT',
			        'timediff' => '-5',
			    ),
			    'NM' => array(
			        'name' => 'NEW MEXICO',
			        'timezone' => 'MST',
			        'timediff' => '-7',
			    ),
			    'NV' => array(
			        'name' => 'NEVADA',
			        'timezone' => 'PDT',
			        'timediff' => '-8',
			    ),
			    'NY' => array(
			        'name' => 'NEW YORK',
			        'timezone' => 'EDT',
			        'timediff' => '-5',
			    ),
			    'OH' => array(
			        'name' => 'OHIO',
			        'timezone' => 'EDT',
			        'timediff' => '-5',
			    ),
			    'OK' => array(
			        'name' => 'OKLAHOMA',
			        'timezone' => 'CDT',
			        'timediff' => '-6',
			    ),
			    'OR' => array(
			        'name' => 'OREGON',
			        'timezone' => 'PDT',
			        'timediff' => '-8',
			    ),
			    'PA' => array(
			        'name' => 'PENNSYLVANIA',
			        'timezone' => 'EDT',
			        'timediff' => '-5',
			    ),
			    'PR' => array(
			        'name' => 'PUERTO RICO',
			        'timezone' => 'AST',
			        'timediff' => '-4',
			    ),
			    'RI' => array(
			        'name' => 'RHODE ISLAND',
			        'timezone' => 'EDT',
			        'timediff' => '-5',
			    ),
			    'SC' => array(
			        'name' => 'SOUTH CAROLINA',
			        'timezone' => 'EDT',
			        'timediff' => '-5',
			    ),
			    'SD' => array(
			        'name' => 'SOUTH DAKOTA',
			        'timezone' => 'CDT',
			        'timediff' => '-6',
			    ),
			    'TN' => array(
			        'name' => 'TENNESSEE',
			        'timezone' => 'CDT',
			        'timediff' => '-5',
			    ),
			    'TX' => array(
			        'name' => 'TEXAS',
			        'timezone' => 'CDT',
			        'timediff' => '-6',
			    ),
			    'UT' => array(
			        'name' => 'UTAH',
			        'timezone' => 'MST',
			        'timediff' => '-7',
			    ),
			    'VA' => array(
			        'name' => 'VIRGINIA',
			        'timezone' => 'EDT',
			        'timediff' => '-5',
			    ),
			    'VI' => array(
			        'name' => 'VIRGIN ISLANDS',
			        'timezone' => 'AST',
			        'timediff' => '-4',
			    ),
			    'VT' => array(
			        'name' => 'VERMONT',
			        'timezone' => 'EDT',
			        'timediff' => '-5',
			    ),
			    'WA' => array(
			        'name' => 'WASHINGTON',
			        'timezone' => 'PDT',
			        'timediff' => '-8',
			    ),
			    'WI' => array(
			        'name' => 'WISCONSIN',
			        'timezone' => 'CDT',
			        'timediff' => '-6',
			    ),
			    'WV' => array(
			        'name' => 'WEST VIRGINIA',
			        'timezone' => 'EDT',
			        'timediff' => '-5',
			    ),
			    'WY' => array(
			        'name' => 'WYOMING',
			        'timezone' => 'MST',
			        'timediff' => '-7',
			    ),
			);

			$tz = $state_timezones[$this->_short_name];

			$this->_timezone = timezone_name_from_abbr($tz['timezone']);
		}

		public function set_names(){
			
			$states = array (
				'AL' => 'ALABAMA',
				'AK' => 'ALASKA',
				'AZ' => 'ARIZONA',
				'AR' => 'ARKANSAS',
				'CA' => 'CALIFORNIA',
				'CO' => 'COLORADO',
				'CT' => 'CONNECTICUT',
				'DC' => 'DISTRICT OF COLUMBIA',
				'DE' => 'DELAWARE',
				'FL' => 'FLORIDA',
				'GA' => 'GEORGIA',
				'GU' => 'GUAM',
				'HI' => 'HAWAII',
				'ID' => 'IDAHO',
				'IL' => 'ILLINOIS',
				'IN' => 'INDIANA',
				'IA' => 'IOWA',
				'KS' => 'KANSAS',
				'KY' => 'KENTUCKY',
				'LA' => 'LOUISIANA',
				'ME' => 'MAINE',
				'MD' => 'MARYLAND',
				'MA' => 'MASSACHUSETTS',
				'MI' => 'MICHIGAN',
				'MN' => 'MINNESOTA',
				'MS' => 'MISSISSIPPI',
				'MO' => 'MISSOURI',
				'MT' => 'MONTANA',
				'NE' => 'NEBRASKA',
				'NV' => 'NEVADA',
				'NH' => 'NEW HAMPSHIRE',
				'NJ' => 'NEW JERSEY',
				'NM' => 'NEW MEXICO',
				'NY' => 'NEW YORK',
				'NC' => 'NORTH CAROLINA',
				'ND' => 'NORTH DAKOTA',
				'OH' => 'OHIO',
				'OK' => 'OKLAHOMA',
				'OR' => 'OREGON',
				'PW' => 'PALAU',
				'PA' => 'PENNSYLVANIA',
				'PR' => 'PUERTO RICO',
				'RI' => 'RHODE ISLAND',
				'SC' => 'SOUTH CAROLINA',
				'SD' => 'SOUTH DAKOTA',
				'TN' => 'TENNESSEE',
				'TX' => 'TEXAS',
				'UT' => 'UTAH',
				'VT' => 'VERMONT',
				'VI' => 'VIRGIN ISLANDS',
				'VA' => 'VIRGINIA',
				'WA' => 'WASHINGTON',
				'WV' => 'WEST VIRGINIA',
				'WI' => 'WISCONSIN',
				'WY' => 'WYOMING',
			);

			if(is_null($this->_long_name)){
				$this->_long_name = ucwords(strtolower($states[$this->_short_name]));
			}
			if(is_null($this->_short_name)){
				$this->_short_name = array_search(strtoupper($this->_long_name), $states);
			}

			$this->set_timezones();

			return $this;
		}

		public function short_name(){
			return $this->_short_name;
		}

		public function long_name(){
			return ucwords(strtolower($this->_long_name));
		}

	}