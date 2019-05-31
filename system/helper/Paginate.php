<?php
	class Paginate_Helper extends Helper implements Iterator {

		public $_order 					= array();
		public $_where 					= array();
		public $_limit 					= 10;
		public $_model 					= false;
		public $_table 					= false;
		public $_db_name 				= false;
		public $_custom_query;
		public $_custom_where;
		public $_data 					= false;
		public $_total;
		public $_page_count;
		public $_ajax_page 				= false;
		public $_ajax_load_container 	= false;
		public $_page 					= 1;
		public $_page_name 				= 'page';
		public $_per_page_name 			= 'rpp';
		public $_name 					= '';
		public $_page_limit				= 5;
		public $_custom_url 			= false;
		public $_first 					= 0;
		public $_last 					= 0;
		public $_position 				= 0;


		public function __construct(){

		}

		public function __toString(){
			return \Buffer::start(function(){
				$this->render();
			});
		}

		public function valid(){
			return isset($this->_data[$this->_position]);
		}

		public function rewind(){
			$this->_position = 0;
			return $this;
		}

		public function current(){
			return $this->_data[$this->_position];
		}

		public function key(){
			return $this->_position;
		}

		public function nth($offset){
			return $this->_data($offset);
		}

		public function next(){
			$this->_position ++;
			return $this;
		}

		public function previous(){
			if($this->_position > 0){
				$this->_position --;
			}
			return $this;
		}

		public function first(){
			foreach($this->_data as $item){
				return $item;
			}
		}

		public function last(){

			$keys = $this->_keys;
			$last_key = array_pop($keys);
			return $this->_data[$last_key];
			
		}

		public function total(){
			return $this->_total;
		}

		public function url($url = null){
			if(!is_null($url)){
				$this->_custom_url = $url;
				return $this;
			}

			return $this->_custom_url;
		}

		public function page_limit($val = null){
			if(!is_null($val)){
				$this->_page_limit = $val;
				return $this;
			}
			return $this->_page_limit;
		}

		public function model($name){

			$this->_model 	= !is_object($name) ? Model::get($name) : $name;
			$name 			= $this->_model->model_name();
			$this->_table 	= $this->_model->table_name();
			$this->_db_name = $this->_model->db_name();
			$this->_model 	= $name;
			return $this;
		}

		public function order($query){
			if(is_array($query)){
				foreach($query as $q) $this->order($q);
			}
			else{
				$this->_order[] = $query;
			}
			
			return $this;
		}

		public function where($query){
			$this->_where[] = $query;
			return $this;
		}

		public function custom_where($sql){
			$this->_custom_where = $sql;
			return $this;
		}

		public function limit($query){

			//CHECK FOR RECORDS PER PAGE OVERRIDE
			if(Request::get($this->_per_page_name)){
				$this->_limit = Request::get($this->_per_page_name);
				return $this;
			}
			$this->_limit = $query;
			return $this;
		}

		public function data($data = false){

			if($data === false){
				return $this->_data;
			}

			if(is_object($data) && get_class($data) == 'ORM_Wrapper'){
				$this->_data = $data->to_array();
			}
			else{
				$this->_data = $data;
			}

			return $this;
		}

		public function count(){
			return count($this->_data);
		}

		public function name($name = null){
			if(!is_null($name)){
				$this->_name = $name;
				$this->per_page_name($name.'_rpp');
				$this->page_name($name.'_page');
				return $this;
			}

			return $this->_name;
		}

		public function per_page_name($name = null){
			if(!is_null($name)){
				$this->_per_page_name = $name;
				return $this;
			}

			return $this->_per_page_name;
		}

		public function page_name($name = null){
			if(!is_null($name)){
				$this->_page_name = $name;
				return $this;
			}

			return $this->_page_name;
		}

		//SET THE CURRENT PAGE
		public function page($number = null){
			if(!is_null($number)){
				$this->_page = $number;
				return $this;
			}

			return $this->_page;
			
		}

		public function render(){

			if($this->total()){
				$this->new_url = array_merge(array_filter(explode('/', WEB_APP)), array_values(array_filter(explode('/', Accretion::$controller->get_controller_template_web_path().Accretion::$template_name))));

				if(!$this->_custom_url){
					

					$this->url_without_rpp = $this->new_url;

					$vars = \Request::get_vars();
					foreach($vars as $k => $v){
						if($k === $this->_page_name) continue;
						if(is_numeric($k)){
							$this->new_url[] = $v;					
						}
						else{
							$this->new_url[] = $k.'='.$v;
						}
						
						if($k !== $this->_per_page_name){
							if(is_numeric($k)){
								$this->url_without_rpp[] = $v;	
							}
							else{
								$this->url_without_rpp[] = $k.'='.$v;
							}
							
						}
					}

					$this->new_url = '/'.implode('/', $this->new_url).'/';
					$this->url_without_rpp = '/'.implode('/', $this->url_without_rpp).'/';
				}
				else{
					$this->new_url =$this->_custom_url;
					$this->url_without_rpp = $this->new_url;
					if(\Request::get('rpp')){
						$this->new_url = rtrim($this->new_url, '/').'/rpp='.\Request::get('rpp').'/';
					}
				}

				if(Request::get($this->_per_page_name)){
					$this->rpp = Request::get($this->_per_page_name);
					$this->_limit = $this->rpp;
				}

				$this->rpp = $this->_limit;

				//FIND THE NUMBER OF PAGES
				if($this->_total < $this->_limit){
					$this->_page_count = 1;
				}
				else{
					$this->_page_count = ceil($this->_total/$this->_limit);
				}

				//CHECK THE GET PAGE NUMBER
				if(Request::get($this->_page_name)){
					$this->_page = Request::get($this->_page_name);
				}

				//SET THE CURRENT PAGE
				if(!$this->_page){
					$this->_page = 1;
				}
				
				//ONLY SHOW 10 PAGES AT A TIME
				$this->start_page = (floor($this->_page/$this->_page_limit)*$this->_page_limit)+1;
				if($this->start_page > $this->_page){
					$this->start_page = $this->start_page-$this->_page_limit;
				}
				$this->end_page = $this->start_page+($this->_page_limit-1);

				//$this->end_page 	= floor($this->_page/$this->_page_limit)*$this->_page_limit+$this->_page_limit;		


				//FIND THE ENDING PAGE
				if($this->end_page >= $this->_page_count){
					$this->end_page = $this->_page_count;
				}

				//ONLY RENDER IF THERE IS MORE THAN 1 PAGE
				if($this->_page_count > 1):
					$this->next_group = $this->end_page;
					for($x = $this->_page_limit; $x >= 1; $x--){
						if($this->_page+$x <= $this->_page_count){
							$this->next_group = $this->_page+$x;
							break;
						}
					}
					$this->prev_group = 1;
					for($x = $this->_page_limit; $x >= 1; $x--){
						if($this->_page-$x >= 1){
							$this->prev_group = $this->_page-$x;
							break;
						}
					}

				endif;

				$rpp_array 		= array('10','30','50','100','200','500','1000');
				$rpp_array[] 	= $this->rpp;
				$rpp_array 		= array_unique($rpp_array);
				$new_rpp 		= array();

				foreach($rpp_array as $v){
					$new_rpp[$v] = $v;
				}
				ksort($new_rpp);
				$this->rpp_array = $new_rpp;
				
				$this->load_helper_view('navigation');

				//View::get('navigation', 'Paginate', false, false);
			}
			else{
				echo ' ';
			}

			

			
		}

		public function render_pagination(){
			$this->render();
			
		}

		public function generate(){	

			//CHECK FOR REQUESTS PER PAGE LIMITS
			if(Request::get($this->_per_page_name)){
				$this->limit(Request::get($this->_per_page_name));
			}

			//SET LIMITS
			$limit_start = 0;
			if(Request::get($this->_page_name)){
				$limit_start = (Request::get($this->_page_name)*$this->_limit)-$this->_limit;
			}
			$limit_end = $limit_start+$this->_limit;	

			//ALLOW FOR PAGINATED ARRAYS WITHOUT SQL QUERIES
			if(!$this->_table && $this->_data){
				$this->_total = count($this->_data);
				$this->_data = array_slice($this->_data, $limit_start, $this->_limit);
				return $this;
			}

			//SET WHERE STATEMENTS
			$where = "WHERE 1=1 ";
			if(!empty($this->_where)){
				foreach($this->_where as $q){
					$where .= "AND ({$q}) ";
				}
			}

			//SET ORDER STATEMENT
			$order = "";
			if(!empty($this->_order)){
				$order = "ORDER BY ";
				$order .= implode(', ', $this->_order);
			}

			//GENERATE THE DATA QUERY
			$data_sql = "
				SELECT
					*
				FROM
					`{$this->_table}`
				{$where}
				{$order}
				LIMIT {$limit_start}, {$this->_limit}
			";			

			//GENERATE THE COUNT QUERY
			$count_sql = "
				SELECT
					COUNT(*) AS count
				FROM
					`{$this->_table}`
				{$where}
				{$order}				
			";

			

			//GENERATE QUERIES FOR CUSTOM SQL
			if($this->_custom_where){
				$data_sql = "SELECT d.* FROM ({$this->_custom_where}) AS d {$order} LIMIT {$limit_start}, {$this->_limit}";
				$count_sql = "SELECT COUNT(*) AS count FROM ({$this->_custom_where}) AS d";				
			}

			if($this->_db_name){

				//GET RESULTS
				$data_res 	= DB::set($this->_db_name)->get_rows($data_sql);
				$count_res 	= DB::set($this->_db_name)->get_row($count_sql);
			}
			else{
				//GET RESULTS
				$data_res 	= DB::get_rows($data_sql);
				$count_res 	= DB::get_row($count_sql);
			}
			
			$this->_total = $count_res['count'];
			$this->_first = $this->_total ? $limit_start+1 : 0;
			$this->_last = $this->_first-1 + $this->_limit;

			if($this->_last > $this->_total) $this->_last = $this->_total;

			if($data_res){
				if($this->_model){
					$this->_data = [];
					foreach($data_res as $data) $this->_data[] = Model::get($this->_model)->set($data);
				}
				else{
					$this->_data = $data_res;
				}
			}
			
			return $this;
		}
	}		
?>