<?php
	class CSV_Helper extends Helper {

		public function __construct(){

		}

		/**
			* Convert a csv to a php array
			* @param 	string 	$filename 		The full path of the csv to read into an array
			* @param 	string 	$delimiter 		The character used to split into the array
			* @return 	mixed 					If the file is readable this method returns the parsed $data array else it returns false
		*/
		public function to_array($filename='', $delimiter=','){

			//
			if(!file_exists($filename) || !is_readable($filename))
				return FALSE;
			
			$header = NULL;
			$data = array();
			if (($handle = fopen($filename, 'r')) !== FALSE){
				while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE){
					if(!$header)
						$header = $row;
					else
						$data[] = array_combine($header, $row);
				}
				fclose($handle);
			}
			return $data;
		}

		/**
		 * Generates a csv from an associative array
		 * @param 		array 	$assocDataArray 	The array to convert to a csv
		 * @param 		string 	$fileName 			The filename of the csv to be exported
		 * @return 		This method will always return null. It will always attempt to download the buffer as a file
		 */
		public function generate($assocDataArray, $fileName = 'export.csv'){
			error_reporting(0);
			foreach($assocDataArray as $keys){
				$header = array_keys($keys);
				break;
			}

			ob_clean();
			header('Pragma: public');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Cache-Control: private', false);
			header('Content-Type: text/csv');
			header('Content-Disposition: attachment;filename=' . $fileName);    
			
			$fp = fopen('php://output', 'w');
			fputcsv($fp, $header);
			foreach($assocDataArray AS $values){
				fputcsv($fp, $values);
			}
			fclose($fp);		   
			ob_flush();
			exit;
		}
	}
?>