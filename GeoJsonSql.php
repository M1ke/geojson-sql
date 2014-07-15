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

	protected function process_coordinates(){
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

	protected function sql_field_polygon($coordinates){
		$polygon=implode(',',$coordinates);
		$polygon="POLYGON( ($polygon) )";
		return $polygon;
	}

	function sql_query_polygon(PDO $db,$table,$name){
		return $db->prepare("INSERT INTO `$table` (`$name`) VALUES (PolyFromText( :polygon ))");
	}

	function process_coordinates_sql(){
		$coordinates=$this->process_coordinates();
		return $this->sql_field_polygon($coordinates);
	}

	protected function properties_as_input(Array $input){
		$properties=$this->data['properties'];
		foreach ($input as $key => $field){
			$property=!isset($properties[$field]) ? strtoupper($field) : $field;
			$output[$key]=isset($properties[$property]) ? $properties[$property] : $field;
		}
		return $output;
	}

	function process_and_save(PDOStatement $query,Array $input=[]){
		$polygon=$this->process_coordinates_sql();
		if (!empty($input)){
			$input=$this->properties_as_input($input);
		}

		$input=array_merge($input,[
			':polygon'=>$polygon,
		]);
		if (!$query->execute($input)){
			throw new Exception('Database error: '.print_r($query->errorInfo(),true));
		}
		return true;
	}

	function process_with_query(PDO $db,$table,$name){
		$query=$this->sql_query_polygon($db,$table,$name);
		$this->process_and_save($query);
		return true;
	}

	function list_properties($output=true){
		$keys=array_keys($this->data['properties']);
		return print_r($keys,!$output);
	}
}