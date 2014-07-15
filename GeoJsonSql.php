<?php

class GeoJsonSql {
	protected $data;

	function __construct($file_name){
		$json=file_get_contents($file_name);
		if (empty($json)){
			throw new Exception('No file ('.$file_name.')');
		}
		$this->data=json_decode($json,true);
		if (empty($this->data)){
			throw new Exception('JSON could not be parsed ('.$file_name.')');
		}
	}

	function process_coordinates(){
		$geometry=$this->data['geometry']['coordinates'][0];
		$coordinates=[];
		foreach ($geometry as $coords){
			$safety=0;
			// some coords end up translated to multiple dimensiona arrays - this fixes
			while (is_array($coords[0]) and $safety<10){
				$coords=$coords[0];
				$safety++;
			}
			$coordinates[]=$coords[1].' '.$coords[0];
		}
		if (!is_array($coordinates)){
			return false;
		}
		return $coordinates;
	}

	function sql_field_polygon($coordinates){
		$polygon=implode(',',$coordinates);
		$polygon="POLYGON( ($polygon) )";
	}

	function sql_query_polygon(PDO $db,$table,$name){
		return $db->prepare("INSERT INTO `$table` (`$name`) VALUES (PolyFromText( :polygon ))");
	}

	function process_and_save($query){
		$coordinates=$this->process_coordinates();

		$polygon=$this->sql_field_polygon($coordinates);

		if (isset($query)){
			if (!$query->execute([
				':polygon'=>$polygon,
				':name'=>$data['properties']['NAME'],
				':desc1'=>$data['properties']['DESCRIPT0'],
				':desc2'=>$data['properties']['DESCRIPTIO'],
				':file'=>$data['properties']['FILE_NAME'],
			])){
				throw new Exception('Database error: '.print_r($query->errorInfo(),true));
			}
		}
		return $polygon;
	}

	function process_with_query(PDO $db,$table,$name){
		$query=$this->sql_query_polygon($db,$table,$name);
		return $this->process_and_save($query);
	}
}