<?php
	class Validate_Helper extends Helper {

		private $_data;
		private $_prefix;		
		public $rules;						// Rule storage
		public $error;						// Error storage
		public $print_errors = true;		// Toggle using $this->print_errors(bool);
		public $print_field_title = true;	// Toggle using $this->print_titles(bool);

		public function __construct(){

		}

		/**
		 * Set validation rules
		 * Will merge over previously set rules
		 * @param array $rules
		 * @return bool
		 */
		public function add_rules($rules)
		{
			if(is_array($rules))
			{
				if(is_array($this->rules))
				{
					$this->rules = array_merge($this->rules, $rules);
				}else{
					$this->rules = $rules;
				}
			}else{
				return FALSE;
			}
			return TRUE;
		}

		/**
		 * Run validation
		 * @param array $data
		 * @param array $rules
		 * @return bool
		 */
		public function run($data, $rules = NULL)
		{
			// Optional rules
			if($rules !== NULL)
			{
				// Add rules
				if($this->add_rules($rules) === FALSE)
				{
					// Could not add rules
					return FALSE;
				}
			}
			// Set data to validate
			$this->_data = $data;
			// Rules are set
			if(is_array($this->rules))
			{
				// Check fields
				foreach($this->rules as $k => $v)
				{
					$this->check_field($k);
				}
			}

			// Check if any errors were set
			return empty($this->error);
		}



		/**
		 * Run field validation
		 * @param string $field_name
		 * @return bool
		 */
		private function check_field($field_name)
		{
			$this->data_copy = $this->_data;

			$field = $this->get_value($field_name);

			$this->data_copy[$field_name] = $field;

			$field = trim($this->data_copy[$field_name]);
			if(is_array($this->data_copy[$field_name]))
			{
				// If it is a checkbox array
				foreach($this->data_copy[$field_name] as $k=>$v)
				{
					if(!empty($v))
					{
						$this->data_copy[$field_name] = trim($v);
					}
				}
			}else{
				// Regular string data
				$this->data_copy[$field_name] = trim($this->data_copy[$field_name]);
			}

			// Loop through each rule
			foreach($this->rules[$field_name] as $type => $error)
			{
				// Required
				if($type == 'reqd' && empty($field) && strlen($field) == 0)
				{
					$this->error[$field_name][] = $error;
				}
				// Max length
				if(preg_match('/max\[(\d+)\]/i', $type, $m))
				{

				if(strlen($field) > $m[1])
					{
						$this->error[$field_name][] = $error;
					}
				}
				// Min length
				if(preg_match('/min\[(\d+)\]/i', $type, $m))
				{
					if(strlen($field) < $m[1])
					{
						$this->error[$field_name][] = $error;
					}
				}
				// Exact length
				if(preg_match('/exact\[(\d+)\]/i', $type, $m))
				{
					if(strlen($field) != $m[1])
					{
						$this->error[$field_name][] = $error;
					}
				}
				// Confirm
				if(preg_match('/match\[(.*?)\]/i', $type, $m))
				{
					if($field != $this->data[$m[1]])
					{
						$this->error[$field_name][] = $error;
					}
				}

				//VALIDATE IN ARRAY
				if(preg_match('/in_array\[(.*?)\]/i', $type, $m)){
					preg_match('/\[(.*?)\]/i', $type, $m);
					$check_arr = json_decode($m[0]);
					if(!in_array($field, $check_arr)){
						$this->error[$field_name][] = $error;
					}
				}

				//VALIDATE MIN DATE
				if(preg_match('/min_date\[(.*?)\]/i', $type, $m)){
					if(date('Y-m-d', strtotime($m[1])) > date('Y-m-d', strtotime($field))){
						$this->error[$field_name][] = $error;
					}
				}

				//VALIDATE MIN DATE
				if(preg_match('/min_date\[(.*?)\]/i', $type, $m)){
					if(date('Y-m-d', strtotime($m[1])) > date('Y-m-d', strtotime($field))){
						$this->error[$field_name][] = $error;
					}
				}

				if($type == 'force'){
					$this->error[$field_name][] = $error;
				}

				// Alpha
				if($type == 'alpha' && !ctype_alpha(str_replace(' ', '', $field)))
				{
					$this->error[$field_name][] = $error;
				}
				// Numeric
				if($type == 'numeric' && !ctype_digit(str_replace('.', '', $field)))
				{
					$this->error[$field_name][] = $error;
				}
				// Money
				if($type == 'money' && !ctype_digit(str_replace('$', '', str_replace(',', '', str_replace('.', '', $field)))))
				{
					$this->error[$field_name][] = $error;
				}
				// Numeric with thousands
				if($type == 'numeric_thousands' && !ctype_digit(str_replace(',', '', $field)))
				{
					$this->error[$field_name][] = $error;
				}
				// Alphanumeric
				if($type == 'alphanumeric' && !ctype_alnum(str_replace(' ', '', $field)))
				{
					$this->error[$field_name][] = $error;
				}
				// No spaces
				if($type == 'no_space' && $field != str_replace(' ', '', $field))
				{
					$this->error[$field_name][] = $error;
				}
				// Email
				if($type == 'email' && !eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $field))
				{
					$this->error[$field_name][] = $error;
				}
				// Date (5/10/09)(05/10/2009)
				if($type == 'date' && strtotime($field) === FALSE)
				{
					$this->error[$field_name][] = $error;
				}
				// Time
				if($type == 'time' && !eregi('^([0-1]{1})?([0-9]{1}):([0-5]{1})([0-9]{1})( )?([AaPp][Mm])$', $field))
				{
					$this->error[$field_name][] = $error;
				}
				// Conditional
				if(substr($type, 0, 4) == 'cond' && empty($field))
				{
					if($type == 'cond')
					{
						$conditional = true;
					}else{
						$cond_statement = preg_match('/cond\[(.*)=(.*)\]/i', $type, $m);
						if($cond_statement)
						{
							if($this->data[$m['1']] != $m['2'])
							{
								$conditional = true;
							}else{
								$this->error[$field_name][] = $error;
							}
						}
					}
				}
			}

			// Conditional rules
			if($conditional == true)
			{
				unset($this->error[$field_name]);
			}
			return TRUE;
		}

		public function has_error($field_name){
			if(isset($this->error[$field_name])){
				return $this->error[$field_name];
			}
			return false;
		}



		public function get_value($field_name, $arr = false){

			if($arr === false){
				$arr = $this->_data;
			}

			if(strpos($field_name, '.') !== false){
				$parts = explode('.', $field_name);
				$first_part = $parts[0];
				unset($parts[0]);
				$string = implode('.', $parts);

				if(isset($arr[$first_part])){
					return $this->get_value($string, $arr[$first_part]);
				}
			}

			$value = "";
			if(isset($arr[$field_name])){
				$value = $arr[$field_name];
			}
			
			if(isset($this->_extra_prefix)){
				$extra = str_replace(']', '', str_replace('[', '', $this->_extra_prefix));
				$value = "";
				if(isset($arr[$extra][$field_name])){
					$value = $arr[$extra][$field_name];
				}
				
				unset($this->_extra_prefix);

			}
			elseif(isset($this->_temp_val)){
				$value = $this->_temp_val;
				unset($this->_temp_val);
			}
			
			if(is_null($value) || in_array(strtolower($value), array('no', 'disabled', 'false', '0'))){
				return '';
			}
			return $value;
		}

		

		public function text_field($field_name, $attributes = array()){
			?>
				<input type="text" name="<?=$this->field_name($field_name)?>" <?=$this->parse_attributes($attributes)?> value="<?=$this->get_value($field_name)?>">
				<? if(isset($this->error[$field_name])): ?>
					<span class="help-block">
						<?=$this->error[$field_name][0]?>
					</span>
				<? endif; ?>
			<?

		}

		public function email($field_name, $attributes = array()){
			?>
				<input type="email" name="<?=$this->field_name($field_name)?>" <?=$this->parse_attributes($attributes)?> value="<?=$this->get_value($field_name)?>">
				<? if(isset($this->error[$field_name])): ?>
					<span class="help-block">
						<?=$this->error[$field_name][0]?>
					</span>
				<? endif; ?>
			<?

		}

		public function number($field_name, $attributes = array()){
			?>
				<input type="number" name="<?=$this->field_name($field_name)?>" <?=$this->parse_attributes($attributes)?> value="<?=$this->get_value($field_name)?>">
				<? if(isset($this->error[$field_name])): ?>
					<span class="help-block">
						<?=$this->error[$field_name][0]?>
					</span>
				<? endif; ?>
			<?

		}

		public function hidden($field_name, $attributes = array()){

			?>
				<input type="hidden" name="<?=$this->field_name($field_name)?>" <?=$this->parse_attributes($attributes)?> value="<?=$this->get_value($field_name)?>">
			<?

		}

		public function textarea($field_name, $attributes = array()){
			?>
				<textarea name="<?=$this->field_name($field_name)?>" <?=$this->parse_attributes($attributes)?>><?=$this->get_value($field_name)?></textarea>
			<?
		}



		public function select($field_name, $options = array(), $attributes = array(), $use_key = false){
			
			$actual_field_name = $this->field_name($field_name);
			$val = $this->get_value($field_name);				
			?>
				<select name="<?=$actual_field_name?>" <?=$this->parse_attributes($attributes)?>>
					<? foreach($options as $k => $v): ?>
						<? !$use_key ? $k = $v : $k = $k; ?>
						<? if($use_key && $k == 'false' && $val == '') $val = 'false' ?> 
						<option value="<?=$k?>" <?=$k == $val ? 'selected' : ''?> ><?=$v?></option>
					<? endforeach; ?>
				</select>

				<? if(isset($this->error[$field_name])): ?>
					<span class="help-block">
						<?=$this->error[$field_name][0]?>
					</span>
				<? endif; ?>
			<?


		}

		public function checkbox($field_name, $value = 'true', $attributes = array()){

		
			$actual_field_name = $this->field_name($field_name);
			
			$found = false;
			if($this->get_value($field_name) !== ''){
				$found = true;
			}
			
			
			?>
				<input type="checkbox" name="<?=$actual_field_name?>" <?=$this->parse_attributes($attributes)?> value="<?=$value?>" <?= $found ? 'checked' : ''?>>

				<? if(isset($this->error[$field_name])): ?>
					<span class="help-block">
						<?=$this->error[$field_name][0]?>
					</span>
				<? endif; ?>
			<?
		}

		public function radio($field_name, $field_value, $attributes = array()){
			?>
				<input type="radio" name="<?=$this->field_name($field_name)?>" <?=$this->parse_attributes($attributes)?> value="<?=$field_value?>" <?=$this->get_value($field_name) == $field_value ? 'checked' : ''?>>

				<? if(isset($this->error[$field_name])): ?>
					<span class="help-block">
						<?=$this->error[$field_name][0]?>
					</span>
				<? endif; ?>
			<?
		}

		public function file($field_name, $attributes = array()){

		}

		public function prefix($str = ""){
			$this->_prefix = $str;
			return $this;
		}

		public function extra_prefix($str = ""){
			$this->_extra_prefix = $str;
			return $this;
		}

		public function set($data){
			$this->_data = $data;
			return $this;
		}

		public function add_data($data){
			foreach($data as $k => $v){
				$this->_data[$k] = $v;
			}
			return $this;
		}

		public function set_value($key, $data){
			$this->_data[$key] = $data;
			return $this;
		}

		public function get_data($key = null, $encode = false, $d = false){
			if(!$d){
				$d = $this->_data;
			}

			if(is_object($d)){
				$d = json_decode(json_encode($d), true);
			}

			if(!is_null($key)){
				if(strpos($key, '.') !== false){
					$keys = array_values(array_filter(explode('.', $key)));
					if(isset($d[$keys[0]])){

						$d = $d[$keys[0]];
						unset($keys[0]);
						$keys = array_values(array_filter($keys));

						if(!empty($keys)){
							return $this->get_data(0, $encode, $keys);
						}						
					}
				}
				else{
					$d = $d[$key];
				}
			}

			if($encode){
				if(is_array($d)){
					$d = json_decode(json_encode($d));
				}
			}
			return $d;
		}



		public function field_name($field_name){

			

			if(strpos($field_name, '.') !== false){
				
				$parts = explode('.', $field_name);

				if(isset($this->_prefix)){
					array_unshift($parts, $this->_prefix);
				}

				$str = "";
				foreach($parts as $k => $v){
					if($str == ""){
						$str = $v;
						continue;
					}

					$str .= '['.$v.']';
				}

				return $str;
			}
			else{
				
				$prefix = "";

				if(isset($this->_prefix)){
					$prefix = $this->_prefix;
					if(isset($this->_extra_prefix)){
						$prefix .= $this->_extra_prefix;
						//unset($this->_extra_prefix);
					}
					return $prefix.'['.$field_name.']';
				}
				
				return $prefix.$field_name;
			}
		
			
		}

		public function parse_attributes($attributes){
			$res = "";
			foreach($attributes as $k => $v){
				$res .= ' '.$k.'="'.$v.'" ';
			}
			return $res;
		}

		public function number_range($start, $end, $show_blank = TRUE)
		{
			if($show_blank)
			{
				$ret[''] = '';
			}
			for($x = $start; $x <= $end; $x++)
			{
				$ret[$x] = $x;
			}
			return $ret;
		}

		public function country(){
			return array("United States", "Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", "Anguilla", "Antarctica", "Antigua and Barbuda", "Argentina", "Armenia", "Aruba", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia", "Bosnia and Herzegowina", "Botswana", "Bouvet Island", "Brazil", "British Indian Ocean Territory", "Brunei Darussalam", "Bulgaria", "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada", "Cape Verde", "Cayman Islands", "Central African Republic", "Chad", "Chile", "China", "Christmas Island", "Cocos (Keeling) Islands", "Colombia", "Comoros", "Congo", "Congo, the Democratic Republic of the", "Cook Islands", "Costa Rica", "Cote d'Ivoire", "Croatia (Hrvatska)", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "East Timor", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Falkland Islands (Malvinas)", "Faroe Islands", "Fiji", "Finland", "France", "France Metropolitan", "French Guiana", "French Polynesia", "French Southern Territories", "Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Gibraltar", "Greece", "Greenland", "Grenada", "Guadeloupe", "Guam", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana", "Haiti", "Heard and Mc Donald Islands", "Holy See (Vatican City State)", "Honduras", "Hong Kong", "Hungary", "Iceland", "India", "Indonesia", "Iran (Islamic Republic of)", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Korea, Democratic People's Republic of", "Korea, Republic of", "Kuwait", "Kyrgyzstan", "Lao, People's Democratic Republic", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libyan Arab Jamahiriya", "Liechtenstein", "Lithuania", "Luxembourg", "Macau", "Macedonia, The Former Yugoslav Republic of", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Martinique", "Mauritania", "Mauritius", "Mayotte", "Mexico", "Micronesia, Federated States of", "Moldova, Republic of", "Monaco", "Mongolia", "Montserrat", "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepal", "Netherlands", "Netherlands Antilles", "New Caledonia", "New Zealand", "Nicaragua", "Niger", "Nigeria", "Niue", "Norfolk Island", "Northern Mariana Islands", "Norway", "Oman", "Pakistan", "Palau", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Pitcairn", "Poland", "Portugal", "Puerto Rico", "Qatar", "Reunion", "Romania", "Russian Federation", "Rwanda", "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent and the Grenadines", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Seychelles", "Sierra Leone", "Singapore", "Slovakia (Slovak Republic)", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "South Georgia and the South Sandwich Islands", "Spain", "Sri Lanka", "St. Helena", "St. Pierre and Miquelon", "Sudan", "Suriname", "Svalbard and Jan Mayen Islands", "Swaziland", "Sweden", "Switzerland", "Syrian Arab Republic", "Taiwan, Province of China", "Tajikistan", "Tanzania, United Republic of", "Thailand", "Togo", "Tokelau", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Turks and Caicos Islands", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States Minor Outlying Islands", "Uruguay", "Uzbekistan", "Vanuatu", "Venezuela", "Vietnam", "Virgin Islands (British)", "Virgin Islands (U.S.)", "Wallis and Futuna Islands", "Western Sahara", "Yemen", "Yugoslavia", "Zambia", "Zimbabwe");

		}

		public function state($show_blank = TRUE, $use_key = false)
		{
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

			foreach($states as $k => $state){
				$states[$k] = ucwords(strtolower($state));
			}

			if($use_key){
				$new_states = array();
				foreach($states as $k => $state){
					$new_states[] = $k;
				}

				$states = $new_states;
			}

			

			if($show_blank)
			{
				$ret = array('' => ' ');
				return array_merge($ret, $states);
			}else{
				return $states;
			}
		}
	}
?>