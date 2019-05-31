<?php

require_once "Model_Structure.php";

abstract class IDX_TYPES
{
	const FTEXT = "FULLTEXT INDEX";
	const IDX = "INDEX";
	const UNIQ  = "UNIQUE INDEX";
	const SPAT  = "SPATIAL INDEX";
}

function index_exists($query_log_fd, $table_name, $index_name)
{
	$sql = sprintf("
			SELECT DISTINCT
    		TABLE_NAME,
    		INDEX_NAME
			FROM INFORMATION_SCHEMA.STATISTICS
			WHERE TABLE_NAME = '%s'
			and INDEX_NAME = '%s'
		", $table_name, $index_name);
	$index_exists = \DB::get_row($sql);
	return $index_exists;
}

function add_index($query_log_fd, $model_structure_helper, $indices, $index_type)
{
	if ($indices) {
		$model = $model_structure_helper->model;
		$idx_count = count($indices);
		for ($i = 0; $i < $idx_count; $i++) {
			if (is_array($indices[$i])) {
				$index_name = "idx_" . implode("_", $indices[$i]);
				$indices[$i] = implode(",", $indices[$i]);
			} else {
				$index_name = $indices[$i];
			}
			if (!index_exists($query_log_fd, $model->_table, $index_name)) {
				$is_valid = 1;
				$sql = sprintf(
					"SELECT DATA_TYPE 
					  	FROM INFORMATION_SCHEMA.COLUMNS 
						WHERE table_name = '%s' 
						AND COLUMN_NAME = '%s'",
					$model->_table,
					$indices[$i]
				);
				$data_type = \DB::get_row($sql)->DATA_TYPE;
				$is_valid = $is_valid && (
						$data_type == "char"
						|| $data_type == "varchar"
						|| $data_type == "text"
					);
				if ($is_valid) {
					$exception_msg = sprintf("char/varchar/text columns only COLUMN: %s INDEX: %s",
						implode(",", $indices[$i]),
						$index_name
					);
					//echo $exception_msg;
					//exit(0);
				}
				switch ($index_type) {
					case IDX_TYPES::UNIQ:
						/*
							unique requirements:
							only add to columns with unique fields
						*/
						$distinct_count_sql = sprintf("select * from %s group by concat(%s)",
							$model->_table,
							$indices[$i]
						);
						$distinct_count = \DB::get_rows($distinct_count_sql);
						$total_count_sql = sprintf("select * from %s",
							$model->_table
						);
						$total_count = \DB::get_rows($total_count_sql);
						$column_isnt_unique = $total_count > $distinct_count;
						if ($column_isnt_unique) {
							pr($sql);
							$exception_msg = sprintf(
								"(%s) is not unique",
								implode(",", $model_structure_helper->columns)
							);
							echo $exception_msg;
							//exit(0);
						}
						break;
					default:
						break;
				}

				$sql = sprintf("CREATE %s %s on %s (%s)",
					$index_type,
					$index_name,
					$model->_table,
					$indices[$i]
				);
				
				//fputs($query_log_fd, $sql . "\n");
				\DB::set($model_structure_helper->db_name)->query($sql);
			}
		}
	}
}

class Model_Structure_Index
{

	public function update_indices(Model_Structure_Helper $model_structure_helper = NULL)
	{
		if (!$model_structure_helper)
			return 0;

		//$this->update_removed_indices($current_index_names, $model_structure_helper);
		/* 1. Loop through all indices defined in the model
		 * 2. Check to see if the table_name is empty (for some reason this occurs at times)
		 * 3. Stop execution if the user tries to add an index to an invalid column
		 * 4. Add the index
		 * */

		
		$f = fopen("query_log", "w");
		add_index($f, $model_structure_helper, $model_structure_helper->model->idx, IDX_TYPES::IDX);
		add_index($f, $model_structure_helper, $model_structure_helper->model->uniq_idx, IDX_TYPES::UNIQ);
		add_index($f, $model_structure_helper, $model_structure_helper->model->ftxt_idx, IDX_TYPES::FTEXT);
		add_index($f, $model_structure_helper, $model_structure_helper->model->spat_idx, IDX_TYPES::SPAT);
	}

	public function update_foreign_keys(Model_Structure_Helper $model_structure_helper)
	{
		$f = fopen("query_log", "a+");
		$current_key_names = [];
		foreach ($model_structure_helper->model->foreign_keys as $key)
			array_push($current_key_names, "fk_" . implode("_", $key[COLUMNS]));
		foreach ($model_structure_helper->model->foreign_keys as $key) {
			$on_update = "";
			if ($key[\fk_struct::ON_UPDATE])
				$on_update = sprintf("ON UPDATE %s", $key[\fk_struct::ON_UPDATE]);
			$on_delete = "";
			if ($key[\fk_struct::ON_DELETE])
				$on_update = sprintf("ON DELETE %s", $key[\fk_struct::ON_DELETE]);
			$sql = sprintf("ALTER TABLE %s ADD FOREIGN KEY %s (%s) REFERENCES %s(%s) %s %s",
				$model_structure_helper->model->_table,
				"fk_" . implode("_", $key[\fk_struct::COLUMNS]),
				implode("_", $key[\fk_struct::COLUMNS]),
				$key[\fk_struct::REF_TABLE],
				$key[\fk_struct::REF_COL],
				$on_update,
				$on_delete
			);
			//fputs($f, $sql . "\n");
			\DB::set($model_structure_helper->db_name)->query($sql);
		}
	}

	public function update_removed_indices(Model_Structure_Helper $model_structure_helper)
	{
		$f = fopen("query_log", "a+");
		$sql = sprintf("show index from %s", $model_structure_helper->model->_table);
		$indices_in_db = \DB::get_rows($sql);
		$indices_in_db_count = count($indices_in_db);
		for ($i = 0; $i < $indices_in_db_count; $i++) {
			if (!empty($model_structure_helper->model->_table)) {
				$index_in_db = $indices_in_db[$i]["Key_name"];
				$index_removed = $index_in_db != "PRIMARY";
				if ($index_removed) {
					$sql = sprintf("ALTER TABLE %s DROP INDEX %s", 
						$model_structure_helper->model->_table, 
						$index_in_db);
					//fputs($f, $sql . "\n");
					\DB::set($model_structure_helper->db_name)->query($sql);
				}
			}
		}
	}

	public function update_removed_keys(Model_Structure_Helper $model_structure_helper)
	{
		$f = fopen("query_log", "a+");
		$sql = sprintf(
			"SELECT * FROM information_schema.TABLE_CONSTRAINTS 
			WHERE information_schema.TABLE_CONSTRAINTS.CONSTRAINT_TYPE = 'FOREIGN KEY' 
			AND information_schema.TABLE_CONSTRAINTS.TABLE_NAME = '%s'", 
			$model_structure_helper->model->_table
		);
		$keys_in_db = \DB::get_rows($sql);
		$keys_in_db_count = count($keys_in_db);
		for ($i = 0; $i < $keys_in_db_count; $i++) {
			if (!empty($model_structure_helper->model->_table)) {
				$key_in_db = $keys_in_db[$i]->CONSTRAINT_NAME;
				$sql = sprintf("ALTER TABLE %s DROP INDEX %s", $model_structure_helper->model->_table, $key_in_db);
				//fputs($f, $sql . "\n");
				\DB::set($model_structure_helper->db_name)->query($sql);
			}
		}
	}
}