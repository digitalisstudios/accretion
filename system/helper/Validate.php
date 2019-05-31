<?php
	class Validate_Helper extends Helper {

		private $_data;
		private $_prefix;		
		public $rules;							// Rule storage
		public $error;							// Error storage
		public $print_errors 		= true;		// Toggle using $this->print_errors(bool);
		public $print_field_title 	= true;		// Toggle using $this->print_titles(bool);
		public $error_class 		= 'is-invalid';
		public $force_false 		= false;
		public $label 				= false;
		public $_validators 		= [
			'_validate_reqd',
			'_validate_setreqd',
			'_validate_reqfield',
			'_validate_max',
			'_validate_min',
			'_validate_exact',
			'_validate_match',
			'_validate_in_array',
			'_validate_min_date',
			'_validate_max_date',
			'_validate_force',
			'_validate_alpha',
			'_validate_numeric',
			'_validate_money',
			'_validate_numeric_thousands',
			'_validate_alphanumeric',
			'_validate_no_space',
			'_validate_email',
			'_validate_phone',
			'_validate_date',
			'_validate_time',
			'_validate_cond',
			'_validate_has_symbol',
			'_validate_has_caps',
			'_validate_has_number',
			'_validate_has_letter',
			'_validate_unique',
			'_validate_ssn',
		];

		public function __construct(){

		}

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

		public function register_validators($validators = []){

			if(is_string($validators) && method_exists($this, $validators)){
				$this->_validators[$validator] = $validator;
			}
			elseif(is_array($validators)){
				foreach($validators as $validator){
					$this->register_validators($validator);
				}
			}

			return $this;
			
		}

		private function check_field($field_name){

			$this->data_copy = $this->_data;

			$field = $this->get_value($field_name);

			$this->data_copy[$field_name] = $field;

			$field = trim($this->data_copy[$field_name]);
			if(is_array($this->data_copy[$field_name])){
				
				// If it is a checkbox array
				foreach($this->data_copy[$field_name] as $k=>$v){
					if(!empty($v)){
						$this->data_copy[$field_name] = trim($v);
					}
				}
			}
			else{
				// Regular string data
				$this->data_copy[$field_name] = trim($this->data_copy[$field_name]);
			}

			// Loop through each rule
			foreach($this->rules[$field_name] as $type => $error){
				
				foreach($this->_validators as $validator){
					if(method_exists($this, $validator)){
						

						if(!$this->$validator($field, $field_name, $type)){
							
							$this->error[$field_name][] = $error;
						}
					}
				}
			}

			return TRUE;
		}

		public function _validate_reqd($field, $field_name, $type){

			// Required
			if($type == 'reqd' && empty($field) && strlen($field) == 0){
				return false;
			}

			return true;

		}

		public function _validate_setreqd($field, $field_name, $type){
			
			// Required Only if isset
			if($type == 'setreqd' && empty($field) && strlen($field) == 0){
				if(isset($this->_data[$field_name])){				
					return false;
				}				
			}

			return true;

		}

		public function _validate_reqfield($field, $field_name, $type){

			if(preg_match('/reqfield\[(.*)\]/i', $type, $m)){

				if(isset($this->rules[$field_name]['setreqd'])){
					if(!isset($this->_data[$field_name])){
						return true;
					}
				}
				
				return $this->_validate_reqd($this->_data[$m[1]], $m[1], 'reqd');				
			}

			return true;

		}

		public function _validate_max($field, $field_name, $type){

			// Max length
			if(preg_match('/max\[(\d+)\]/i', $type, $m)){

				if(strlen($field) > $m[1]){
					return false;
				}
			}

			return true;
		}

		public function _validate_min($field, $field_name, $type){

			// Min length
			if(preg_match('/min\[(\d+)\]/i', $type, $m)){
				if(strlen($field) < $m[1]){
					return false;
				}
			}

			return true;
		}

		public function _validate_exact($field, $field_name, $type){

			// Exact length
			if(preg_match('/exact\[(\d+)\]/i', $type, $m)){
				if(strlen($field) != $m[1]){
					return false;
				}
			}

			return true;
		}

		public function _validate_match($field, $field_name, $type){

			// Confirm
			if(preg_match('/match\[(.*?)\]/i', $type, $m)){
				if($field != $this->_data[$m[1]]){
					return false;
				}
			}

			return true;

		}

		public function _validate_in_array($field, $field_name, $type){

			//VALIDATE IN ARRAY
			if(preg_match('/in_array\[(.*?)\]/i', $type, $m)){
				preg_match('/\[(.*?)\]/i', $type, $m);
				$check_arr = json_decode($m[0]);
				if(!in_array($field, $check_arr)){
					if(strlen($field) > 0){
						return false;
					}
				}
			}

			return true;

		}



		public function _validate_min_date($field, $field_name, $type){

			//VALIDATE MIN DATE
			if(preg_match('/min_date\[(.*?)\]/i', $type, $m)){
				if(date('Y-m-d', strtotime($m[1])) >= date('Y-m-d', strtotime($field))){
					if(strlen($field) > 0){
						return false;
					}
				}
			}

			return true;
		}

		public function _validate_max_date($field, $field_name, $type){

			//VALIDATE MIN DATE
			if(preg_match('/max_date\[(.*?)\]/i', $type, $m)){
				if(date('Y-m-d', strtotime($m[1])) <= date('Y-m-d', strtotime($field))){
					if(strlen($field) > 0){
						return false;
					}
				}
			}
			return true;
		}

		public function _validate_force($field, $field_name, $type){

			//FORCE AN ERROR
			if($type == 'force'){
				return false;
			}

			return true;

		}

		public function _validate_alpha($field, $field_name, $type){

			// Alpha
			if($type == 'alpha' && !ctype_alpha(str_replace(' ', '', $field))){
				if(strlen($field) > 0){
					return false;
				}
			}

			return true;

		}

		public function _validate_numeric($field, $field_name, $type){

			// Numeric
			if($type == 'numeric' && !ctype_digit(str_replace('.', '', $field))){
				if(strlen($field) > 0){
					return false;
				}
			}

			return true;

		}

		public function _validate_money($field, $field_name, $type){

			// Money
			if($type == 'money' && !ctype_digit(str_replace('$', '', str_replace(',', '', str_replace('.', '', $field))))){
				if(strlen($field) > 0){
					return false;
				}
			}

			return true;

		}

		public function _validate_numeric_thousands($field, $field_name, $type){

			// Numeric with thousands
			if($type == 'numeric_thousands' && !ctype_digit(str_replace(',', '', $field))){
				if(strlen($field) > 0){
					return false;
				}
			}

			return true;

		}

		public function _validate_alphanumeric($field, $field_name, $type){

			// Alphanumeric
			if($type == 'alphanumeric' && !ctype_alnum(str_replace(' ', '', $field))){
				if(strlen($field) > 0){
					return false;
				}
			}

			return true;

		}

		public function _validate_no_space($field, $field_name, $type){

			// No spaces
			if($type == 'no_space' && $field != str_replace(' ', '', $field)){

				if(strlen($field) > 0){
					return false;
				}
			}

			return true;

		}

		public function _validate_email($field, $field_name, $type){

			// Email
			//if($type == 'email' && !preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/", $field)){
			if($type == 'email' && !filter_var($field, FILTER_VALIDATE_EMAIL)){
				//if(strlen($field) > 0){
					return false;
				//}
			}

			return true;

		}

		public function _validate_ssn($field, $field_name, $type){

			$field = trim($field);

			if($type == 'ssn' && !preg_match("/^(?!219-09-9999|078-05-1120)(?!666|000|9\\d{2})\\d{3}-(?!00)\\d{2}-(?!0{4})\\d{4}$/", $field)){
				if(strlen($field) > 0){
					return false;
				}
			}

			return true;
			
		}

		public function _validate_phone($field, $field_name, $type){

			$field = trim($field);

			//return true;

			//VALIDATE A PHONE NUMBER
			if($type == 'phone' && !preg_match("/^(?:(?:\+?[0-9]|[0-9][0-9]|1[0-9][0-9]|2[0-9][0-9]|3[0-9][0-9]|4[0-9][0-9]|5[0-9][0-9]|6[0-9][0-9]|7[0-9][0-9]|8[0-9][0-9]|9[0-9][0-9]\s*(?:[.-]\s*)?)?(?:\(\s*([0-9]1[0-9]|[0-9][0-9]1|[0-9][0-9][0-9])\s*\)|([0-9]1[0-9]|[0-9][0-9]1|[0-9][0-9][0-9]))\s*(?:[.-]\s*)?)?([0-9]1[0-9]|[0-9][0-9]1|[0-9][0-9]{2})\s*(?:[.-]\s*)?([0-9]{4})(?:\s*(?:#|x\.?|ext\.?|extension)\s*(\d+))?$/", $field)){
				if(strlen($field) > 0){
					return false;
				}
			}

			return true;

		}

		public function _validate_date($field, $field_name, $type){

			// Date (5/10/09)(05/10/2009)
			if($type == 'date' && strtotime($field) === FALSE){

				if(strlen($field) > 0){
					return false;
				}
			}

			return true;

		}

		public function _validate_time($field, $field_name, $type){

			// Time
			if($type == 'time' && !preg_match('/^([0-1]{1})?([0-9]{1}):([0-5]{1})([0-9]{1})( )?([AaPp][Mm])$/', $field)){
				if(strlen($field) > 0){
					return false;
				}
			}

			return true;

		}

		public function _validate_cond($field, $field_name, $type){



			// Conditional
			if(substr($type, 0, 4) == 'cond' && empty($field)){

				//pr($this->rules[$field_name]['cond']);

				$conditions = $this->rules[$field_name]['cond'];
				unset($this->rules[$field_name]['cond']);
				$this->rules[$field_name]['temp_cond'] = $conditions;

				foreach($conditions as $condition => $condition_statements){

					$cond_statement = preg_match('/cond\[(.*)=(.*)\]/i', $condition, $m);

					if($cond_statement){

						if($this->_data[$m[1]] == $m[2]){
							foreach($condition_statements as $statement_type => $statement){
								$this->rules[$field_name][$statement_type] = $statement;
							}
						}
					}
				}

				return $this->check_field($field_name);
			}

			return true;
		}

		public function _validate_has_symbol($field, $field_name, $type){
			$val_type = 'has_symbol';
			$val_match = "#\W+#";
			return (($type == $val_type && preg_match($val_match, $field)) || $type !== $val_type) ? true : false;
		}

		public function _validate_has_caps($field, $field_name, $type){
			$val_type = 'has_caps';
			$val_match = "#[A-Z]+#";
			return (($type == $val_type && preg_match($val_match, $field)) || $type !== $val_type) ? true : false;
		}

		public function _validate_has_number($field, $field_name, $type){
			$val_type = 'has_number';
			$val_match = "#[0-9]+#";
			return (($type == $val_type && preg_match($val_match, $field)) || $type !== $val_type) ? true : false;
		}

		public function _validate_has_letter($field, $field_name, $type){
			$val_type = 'has_letter';
			$val_match = "#[a-z]+#";
			return (($type == $val_type && preg_match($val_match, $field)) || $type !== $val_type) ? true : false;
		}

		public function _validate_unique($field, $field_name, $type){

			$reg = "/unique\[(.*?):(.*?)(?:(\|(.*?):(.*?)))?\]/i";
			//$reg = '/unique\[(.*?)\|(.*?)\]/i';

			

			if(preg_match($reg, $type, $m)){
				$check_table = $m[1];
				$check_field = $m[2];

				

				$query = "SELECT count(*) AS c FROM `{$check_table}` WHERE `{$check_field}` = '{$field}'";

				if(isset($m[4])) $query .= " AND `{$m[4]}` != '{$m[5]}'";

				if(\DB::get_row($query)['c'] > 0) return false;
			}

			return true;
		}


		public function set_error_class($name){
			$this->error_class = $name;
			return $this;
		}

		public function error_class($field_name){
			if($this->has_error($field_name)){
				return $this->error_class;
			}
			return '';
		}

		public function has_error($field_name){

			

			if(isset($this->error[$field_name])){
				return $this->error[$field_name];
			}
			return false;
		}

		public function has_errors($field_name){
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

		public function label($label){
			$this->label = $label;
			return $this;
		}

		public function start_wrap($field_name){
			$this->current_field_name = $field_name;
			if($this->label !== false){
				?>
					<div class="form-group <?=$this->error_class($field_name)?>">
						<label class="control-label"><?=$this->label?></label>
				<?
			}
		}

		public function end_wrap(){
			//$this->render_error($this->current_field_name);
			if($this->label !== false){
				?>
					</div>
				<?
			}

			$this->label = false;
			return $this;
		}

		public function temp_val($val){
			$this->_temp_val = $val;
			return $this;
		}

		public function parse_field_name($field_name){
			$field_name = str_replace(']', '', str_replace('][', '.', $field_name));

			if($this->_prefix !== ""){
				$field_name = str_replace($this->_prefix."[", "", $field_name);
			}

			return $field_name;
		}

		public function render_error($field_name){
			
			/*
			if(strpos($field_name, "[")){
				$field_name = $this->parse_field_name($field_name);
			}
			*/

			if($this->has_error($field_name)): ?>
				<div class="invalid-feedback">
					<?=$this->has_error($field_name)[0]?>
				</div>
			<? endif;
		}

		public function render_field($field_name, $attributes = [], $callback){

			$this->start_wrap($field_name);
			

			if($this->has_error($field_name)){
				if(!isset($attributes['class'])){
					$attributes['class'] = "";
				}
				$attributes['class'] .= " is-invalid ";
			}

			$multi = '';
			if(isset($attributes['multiple']) && $attributes['multiple'] == 'true'){
				$multi = '[]';	
			}

			$actual_field_name 	= $field_name;
			$field_name 		= $this->field_name($field_name);
			$parsed_field_name 	= $this->parse_field_name($field_name);
			$attributes 		= $this->parse_attributes($attributes);
			$value 				= $this->get_value($actual_field_name);
			$meta 				= " name=".$field_name.$multi." ".$attributes;

			$callback($meta, $value);

			$this->render_error($actual_field_name);

			$this->end_wrap();
		}

		public function input($type, $field_name, $attributes = [], $value = null){
			$data = ['type' => $type, 'value' => $value, 'field_name' => $field_name];
			$this->render_field($field_name, $attributes, function($meta, $value) use ($data) {
				?><input type="<?=$data['type']?>" <?=$meta?> value="<?=!is_null($data['value']) ? $data['value'] : $value?>" <?=in_array($data['type'], ['checkbox','radio']) && $data['value'] == $this->get_value($data['field_name']) ? 'checked' : '' ?>><?
			});
		}

		public function text_field($field_name, $attributes = []){

			$this->render_field($field_name, $attributes, function($meta, $value){
				?><input type="text" <?=$meta?> value="<?=$value?>"><?
			});
		}

		public function password($field_name, $attributes = []){

			$this->render_field($field_name, $attributes, function($meta, $value){
				?><input type="password" <?=$meta?> value="<?=$value?>"><?
			});
		}

		public function email($field_name, $attributes = array()){

			$this->render_field($field_name, $attributes, function($meta, $value){
				?><input type="email" <?=$meta?> value="<?=$value?>"><?
			});
		}

		public function number($field_name, $attributes = array(), $default = null){

			$this->render_field($field_name, $attributes, function($meta, $value) use($default){
				$value = !$value && !is_null($default) ? $default : $value;
				?><input type="number" <?=$meta?> value="<?=$value?>"><?
			});
		}

		public function hidden($field_name, $attributes = array()){

			$this->render_field($field_name, $attributes, function($meta, $value){
				?><input type="hidden" <?=$meta?> value="<?=$value?>"><?
			});
		}

		public function textarea($field_name, $attributes = array()){

			$this->render_field($field_name, $attributes, function($meta, $value){
				?><textarea <?=$meta?>><?=$value?></textarea><?
			});
		}

		public function multi_select($field_name, $options = [], $attributes = [], $use_key = false){
			$attributes['multiple'] = 'true';
			$data = [
				'options' => $options,
				'use_key' => $use_key
			];

			//$this->_extra_prefix = "[]";

			$this->render_field($field_name, $attributes, function($meta, $value) use ($data) {

				$options 	= $data['options'];
				$use_key 	= $data['use_key'];
				$vals 		= $value;
				?>
					<select <?=$meta?>>
						<? foreach($options as $k => $v): ?>
							<? !$use_key ? $k = $v : $k = $k; ?>
							<? if($use_key): ?>
								<option value="<?=$k?>" <?=isset($vals[$k]) ? 'selected' : ''?> ><?=$v?></option>	
							<? else: ?>
								<option value="<?=$k?>" <?=in_array($k, $vals) ? 'selected' : ''?> ><?=$v?></option>
							<? endif; ?>							
						<? endforeach; ?>
					</select>
				<?
			});
		}

		public function selectGroup($options, $use_key, $val, &$selected, &$select_string, &$depth = 0){

			foreach($options as $k => $v){
				if(is_array($v)){
					?>
						<optgroup label="<?=$k?>" style="padding-left:15px;">
							<? $this->selectGroup($v, $use_key, $val, $selected, $select_string) ?>
						</optgroup>
					<?
				}
				else{
					?>
						<? !$use_key ? $k = $v : $k = $k; ?>
						<? if($use_key && $k == 'false' && $val === '') $val = 'false' ?> 
						<? if(!$selected && $k == $val){
							$select_string = 'selected';
							$selected = true;
							if($k === '' && $val === ''){
								$select_string = '';
							}
						}?>
						<option value="<?=$k?>" <?=$select_string?> ><?=$v?></option>
						<? $select_string = ''; ?>
					<?
				}
			}

		}

		public function select($field_name, $options = array(), $attributes = array(), $use_key = false){

			$this->temp_options = $options;
			$this->temp_use_key = $use_key;

			$this->render_field($field_name, $attributes, function($meta, $value){

				$options 	= $this->temp_options;
				$use_key 	= $this->temp_use_key;
				$val 		= $value;
				$selected 	= false;
				$select_string = '';

				?>
					<select <?=$meta?>>

						<? $this->selectGroup($options, $use_key, $val, $selected, $select_string)?>;

						<? /*
						<? foreach($options as $k => $v): ?>
							<? if(is_array($v)): ?>
								?>
									<optgroup label="<?=$k?>">
										<? foreach($v as $vk => $vv): ?>
											<? !$use_key ? $vk = $vv : $vk = $vk; ?>
											<? if($use_key && $vk == 'false' && $val === '') $val = 'false' ?> 
											<? if(!$selected && $vk == $val){
												$select_string = 'selected';
												$selected = true;
												if($vk === '' && $val === ''){
													$select_string = '';
												}
											}?>
											<option value="<?=$vk?>" <?=$select_string?> ><?=$vv?></option>
											<? $select_string = ''; ?>
										<? endforeach; ?>
									</optgroup>
							<? else: ?>
								<? !$use_key ? $k = $v : $k = $k; ?>
								<? if($use_key && $k == 'false' && $val === '') $val = 'false' ?> 
								<? if(!$selected && $k == $val){
									$select_string = 'selected';
									$selected = true;
									if($k === '' && $val === ''){
										$select_string = '';
									}
								}?>
								<option value="<?=$k?>" <?=$select_string?> ><?=$v?></option>
								<? $select_string = ''; ?>
							<? endif; ?>
							
						<? endforeach; ?>

						*/ ?>
					</select>
				<?

				unset($this->temp_options);
				unset($this->temp_use_key);
			});
		}

		/*

		public function checkbox($field_name, $value = 'true', $attributes = array(), $default = null, $label = ''){

		
			$actual_field_name = $this->field_name($field_name);
			
			$found = false;
			if($this->get_value($field_name) !== ''){
				$found = true;
			}
			
			
			?>
				<? if(!is_null($default)): ?>
					<input type="hidden" name="<?=$actual_field_name?>" value="<?=$default?>">
				<? endif; ?>

				<? $this->render_field($field_name, $attributes, function($meta, $v) use($value,$found,$label){
					?><div class="checkbox"><label><input type="checkbox" <?=$meta?> value="<?=$value?>" <?=$found ? 'checked' : ''?>> <?=$label?></label></div><?
				}); ?>

				
				
			
			<?
		}
		*/

		public function checkbox($field_name, $value = 'true', $attributes = array(), $default = null, $label = ''){

		
			$actual_field_name = $this->field_name($field_name);
			
			$found = false;
			if($this->get_value($field_name) !== ''){
				$found = true;
			}
			
			
			?>
				<? if(!is_null($default)): ?>
					<input type="hidden" name="<?=$actual_field_name?>" value="<?=$default?>">
				<? endif; ?>

				<? if($label != ''): ?>
					<? $this->render_field($field_name, $attributes, function($meta, $v) use($value,$found,$label){
						?><div class="checkbox"><label><input type="checkbox" <?=$meta?> value="<?=$value?>" <?=$found ? 'checked' : ''?>> <?=$label?></label></div><?
					}); ?>
				<? else: ?>
				
				
					<input type="checkbox" name="<?=$actual_field_name?>" <?=$this->parse_attributes($attributes)?> value="<?=$value?>" <?= $found ? 'checked' : ''?>>

					<? if(isset($this->error[$field_name])): ?>
						<span class="help-block invalid-feedback d-block">
							<?=$this->error[$field_name][0]?>
						</span>
					<? endif; ?>

				<? endif; ?>
			<?
		}

		public function radio($field_name, $field_value, $attributes = array()){
			?>
				<input type="radio" name="<?=$this->field_name($field_name)?>" <?=$this->parse_attributes($attributes)?> value="<?=$field_value?>" <?=$this->get_value($field_name) == $field_value ? 'checked' : ''?>>

				<!--
				<? if(isset($this->error[$field_name])): ?>
					<span class="help-block">
						<?=$this->error[$field_name][0]?>
					</span>
				<? endif; ?>
			-->
			<?
		}

		public function file($field_name, $attributes = []){

			$this->render_field($field_name, $attributes, function($meta, $value){
				?><input type="file" <?=$meta?> value="<?=$value?>"><?
			});
		}

		public function prefix($str = ""){
			$this->_prefix = $str;
			if($this->_prefix == ""){
				unset($this->_prefix);
			}
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

		public function data(){
			return $this->_data;
		}

		public function force_false(){
			$this->force_false = true;
			return $this;
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

				if($k == 'json'){
					foreach($v as $k2 => $v2){
						$res .= ' '.$k2."='".json_encode($v2)."' ";
					}
				}
				else{
					$res .= ' '.$k.'="'.$v.'" ';
				}

				
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

		public function form_group($field_name, $attributes = array()){
			return new Validate_Form_Group($this, $field_name, $attributes);
		}

		public function country(){
			return array("United States", "Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", "Anguilla", "Antarctica", "Antigua and Barbuda", "Argentina", "Armenia", "Aruba", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia", "Bosnia and Herzegowina", "Botswana", "Bouvet Island", "Brazil", "British Indian Ocean Territory", "Brunei Darussalam", "Bulgaria", "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada", "Cape Verde", "Cayman Islands", "Central African Republic", "Chad", "Chile", "China", "Christmas Island", "Cocos (Keeling) Islands", "Colombia", "Comoros", "Congo", "Congo, the Democratic Republic of the", "Cook Islands", "Costa Rica", "Cote d'Ivoire", "Croatia (Hrvatska)", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "East Timor", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Falkland Islands (Malvinas)", "Faroe Islands", "Fiji", "Finland", "France", "France Metropolitan", "French Guiana", "French Polynesia", "French Southern Territories", "Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Gibraltar", "Greece", "Greenland", "Grenada", "Guadeloupe", "Guam", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana", "Haiti", "Heard and Mc Donald Islands", "Holy See (Vatican City State)", "Honduras", "Hong Kong", "Hungary", "Iceland", "India", "Indonesia", "Iran (Islamic Republic of)", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Korea, Democratic People's Republic of", "Korea, Republic of", "Kuwait", "Kyrgyzstan", "Lao, People's Democratic Republic", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libyan Arab Jamahiriya", "Liechtenstein", "Lithuania", "Luxembourg", "Macau", "Macedonia, The Former Yugoslav Republic of", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Martinique", "Mauritania", "Mauritius", "Mayotte", "Mexico", "Micronesia, Federated States of", "Moldova, Republic of", "Monaco", "Mongolia", "Montserrat", "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepal", "Netherlands", "Netherlands Antilles", "New Caledonia", "New Zealand", "Nicaragua", "Niger", "Nigeria", "Niue", "Norfolk Island", "Northern Mariana Islands", "Norway", "Oman", "Pakistan", "Palau", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Pitcairn", "Poland", "Portugal", "Puerto Rico", "Qatar", "Reunion", "Romania", "Russian Federation", "Rwanda", "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent and the Grenadines", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Seychelles", "Sierra Leone", "Singapore", "Slovakia (Slovak Republic)", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "South Georgia and the South Sandwich Islands", "Spain", "Sri Lanka", "St. Helena", "St. Pierre and Miquelon", "Sudan", "Suriname", "Svalbard and Jan Mayen Islands", "Swaziland", "Sweden", "Switzerland", "Syrian Arab Republic", "Taiwan, Province of China", "Tajikistan", "Tanzania, United Republic of", "Thailand", "Togo", "Tokelau", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Turks and Caicos Islands", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States Minor Outlying Islands", "Uruguay", "Uzbekistan", "Vanuatu", "Venezuela", "Vietnam", "Virgin Islands (British)", "Virgin Islands (U.S.)", "Wallis and Futuna Islands", "Western Sahara", "Yemen", "Yugoslavia", "Zambia", "Zimbabwe");

		}

		public function states($show_blank = TRUE, $use_key = false){
			return $this->state($show_blank, $use_key);
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

	class Validate_Form_Group extends Validate_Helper {
		
		public $validator;
		public $field_name;
		
		public function __construct($validator, $field_name, $attributes = array()){
			$this->validator = $validator;
			$this->field_name = $field_name;
			if(isset($attributes['class'])){
				$attributes['class'] = trim($attributes['class'].' form-group');
			}
			else{
				$attributes['class'] = "form-group";
			}

			if($this->validator->has_error($field_name)){
				$attributes['class'] = trim($attributes['class'].' has-error');
			}
			?>
				<div <?=$this->parse_attributes($attributes)?>>
			<?
		}

		

		public function label($label, $attributes = array()){
			?>
				<label <?=$this->parse_attributes($attributes)?>>
				<?=$label?>
				</label>
			<?
			return $this;
		}

		public function div($attributes = array(), $callback){
			?>
				<div <?=$this->parse_attributes($attributes)?>>
					<? $callback($this) ?>
				</div>
				</div>
			<?

			return $this;
		}

		public function text_field($attributes = array()){
			return $this->validator->text_field($this->field_name, $attributes);
		}

		public function textarea($attributes = array()){
			return $this->validator->textarea($this->field_name, $attributes);
		}

		public function select($options = array(), $attributes = array(), $use_key = false){
			return $this->validator->select($this->field_name, $options, $attributes, $use_key);
		}

		public function radio($field_value, $attributes = array()){
			return $this->validator->radio($this->field_name, $field_value, $attributes);
		}

		public function password($attributes = array()){
			return $this->validator->password($this->field_name, $attributes);
		}

		public function hidden($attributes = array()){
			return $this->validator->hidden($this->field_name, $attributes);
		}

		public function checkbox($value = 'true', $attributes = array()){
			return $this->validator->checkbox($this->field_name, $value, $attributes);
		}
	}
?>