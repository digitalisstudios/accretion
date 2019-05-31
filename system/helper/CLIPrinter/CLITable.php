<?php
	
	class CLITable extends CLIPrinter {

		public $_data;
		public $_rows = [];

		public function __construct(){

		}

		public function addRow(){

			$newRow = new CLITableRow($this);
			$this->_rows[] = $newRow;

			return $newRow;
		}

		public function parseData(){

			$cells = [];
			$rows = [];

			foreach($this->_rows as $rowKey => $row){

				$rows[$rowKey] = [
					'height' => 0,
					'row' => $row
				];

				foreach($row->cells() as $cellKey => $cell){

					if(!isset($cells[$cellKey]['width'])) $cells[$cellKey]['width'] = 0;
					if(!isset($cells[$cellKey]['height'])) $cells[$cellKey]['height'] = 0;

					$cells[$cellKey]['cells'][$rowKey] = $cell;
					$cellContentWidth = $cell->getContentWidth();
					$cellContentHeight = $cell->getContentHeight();
					$cells[$cellKey]['width'] = $cells[$cellKey]['width'] > $cellContentWidth ? $cells[$cellKey]['width'] : $cellContentWidth;

					$row->_height = $row->_height > $cellContentHeight ? $row->_height : $cellContentHeight;

					$rows[$rowKey]['height'] = $rows[$rowKey]['height'] > $cellContentHeight ? $rows[$rowKey]['height'] : $cellContentHeight;
				}
			}

			foreach($cells as $cellKey => $cellData){
				foreach($cellData['cells'] as $cell){
					$cell->_width = $cellData['width'];
					$cell->_height = $cellData['height'];
				}
			}

		}
	}

	class CLITableRow {

		public $_parent;
		public $_cells = [];
		public $_height = 0;

		public function __construct($parent){
			$this->_parent = $parent;
		}

		public function table(){
			return $this->_parent;
		}

		public function addCell($content = ''){
			$newCell = new CLITableCell($content, $this);
			$this->_cells[] = $newCell;

			return $newCell;
		}

		public function cells(){
			return $this->_cells;
		}
	}

	class CLITableCell {

		public $_content;
		public $_parent;
		public $_width = 0;
		public $_height = 1;

		public function __construct($content, $parent){
			$this->_content = $content;
			$this->_parent = $parent;
		}

		public function row(){
			return $this->_parent;
		}

		public function getContentWidth(){
			$rawContent = preg_replace('/#\\x1b[[][^A-Za-z]*[A-Za-z]#/', '', $this->_content);

			return strlen($rawContent);
		}

		public function getContentHeight(){

			$rawContent = preg_replace('/#\\x1b[[][^A-Za-z]*[A-Za-z]#/', '', $this->_content);

			return count(explode("\n", $rawContent));
		}
	}