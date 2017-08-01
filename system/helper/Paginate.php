<?php
	class Paginate_Helper extends Helper {

		public $_order 					= array();
		public $_where 					= array();
		public $_limit 					= 10;
		public $_model 					= false;
		public $_table 					= false;
		public $_custom_query;
		public $_data 					= array();
		public $_total;
		public $_page_count;
		public $_ajax_page 				= false;
		public $_ajax_load_container 	= false;
		public $_page 					= 1;


		public function __construct(){

		}

		public function model($name){

			$this->_model 	= !is_object($name) ? Model::get($name) : $name;
			$name 			= $this->_model->model_name();
			$this->_table 	= $this->_model->table_name();
			$this->_model 	= $name;
			return $this;
		}

		public function order($query){
			$this->_order[] = $query;
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
			if(Request::get('rpp')){
				$this->_limit = Request::get('rpp');
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

		//SET THE CURRENT PAGE
		public function page($number){
			$this->_page = $number;
		}

		public function render_pagination(){

			$this->_page_count = 1;

			$this->new_url = array_values(array_filter(explode('/', WEB_APP.Accretion::$controller->get_controller_template_web_path().Accretion::$template_name)));
			$this->url_without_rpp = $this->new_url;
			$vars = \Request::get_vars();
			foreach($vars as $k => $v){
				if($k == 'page') continue;
				$this->new_url[] = $k.'='.$v;
				if($k !== 'rpp'){
					$this->url_without_rpp[] = $k.'='.$v;
				}
			}

			$this->new_url = '/'.implode('/', $this->new_url).'/';
			$this->url_without_rpp = '/'.implode('/', $this->url_without_rpp).'/';

			if(Request::get('rpp')){
				$this->rpp = Request::get('rpp');
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
			if(Request::get('page')){
				$this->_page = Request::get('page');
			}

			//SET THE CURRENT PAGE
			if(!$this->_page){
				$this->_page = 1;
			}
			
			//ONLY SHOW 10 PAGES AT A TIME
			$this->start_page = floor($this->_page/10)*10;
			$this->end_page 	= floor($this->_page/10)*10+10;		


			//FIND THE ENDING PAGE
			if($this->end_page >= $this->_page_count){
				$this->end_page = $this->_page_count;
			}

			//ONLY RENDER IF THERE IS MORE THAN 1 PAGE
			if($this->_page_count > 1):
				$this->next_group = $this->end_page;
				for($x = 10; $x >= 1; $x--){
					if($this->_page+$x <= $this->_page_count){
						$this->next_group = $this->_page+$x;
						break;
					}
				}
				$this->prev_group = 1;
				for($x = 10; $x >= 1; $x--){
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

			//$this->url_without_rpp = $url_without_rpp;
			//$this->rpp = $rpp;

			View::get('navigation', 'Paginate', false, false);
		}

		public function generate(){			

			//CHECK FOR REQUESTS PER PAGE LIMITS
			if(Request::get('rpp')){
				$this->limit(Request::get('rpp'));
			}

			//SET LIMITS
			$limit_start = 0;
			if(Request::get('page')){
				$limit_start = (Request::get('page')*$this->_limit)-$this->_limit;
			}
			$limit_end = $limit_start+$this->_limit;	

			//ALLOW FOR PAGINATED ARRAYS WITHOUT SQL QUERIES
			if(!$this->_table){
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
			if(isset($this->_custom_where)){
				$data_sql = "SELECT d.* FROM ({$this->_custom_where}) AS d {$order} LIMIT {$limit_start}, {$this->_limit}";
				$count_sql = "SELECT COUNT(*) AS count FROM ({$this->_custom_where}) AS d";				
			}

			//GET RESULTS
			$data_res 	= DB::get_rows($data_sql);
			$count_res 	= DB::get_row($count_sql);
			$this->_total = $count_res['count'];


			if($data_res){
				foreach($data_res as $data){
					$this->_data[] = Model::get($this->_model)->set($data);
				}
			}
			
			return $this;
		}
	}		
?>