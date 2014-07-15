<?php

class SqlGeo {
	protected $db;
	protected $table;
	protected $field_polygon;

	function __construct(PDO $db=null,$table='',$field=''){
		$this->set_db($db);
		$this->set_table($table);
		$this->set_field($field);
		return $this;
	}

	function set_db($db){
		$this->db=$db;
		return $this;
	}

	function set_table($table){
		$this->table=$table;
		return $this;
	}

	function set_field($field){
		$this->field_polygon=$field;
		return $this;
	}

	function get_rows(Array $where){
		if (!$this->db){
			throw new Exception('You must setup a database connection to get rows.');
		}
		if (empty($this->table)){
			throw new Exception('You must set a database table name to get rows.');
		}
		foreach ($where as $key => $val){
			$where_prepare[]="$key = :$key";
			$where_data[':'.$key]=$val;
		}
		$select=$this->sql_select();
		$query="SELECT *,$select FROM {$this->table}".(!empty($where_prepare)? " WHERE ".implode(' and ',$where_prepare) : " LIMIT 10");
		$query=$this->db->prepare($query);
		$query->execute($where_data);
		return $query->fetchAll(PDO::FETCH_ASSOC);
	}

	function sql_select(){
		return "astext({$this->field_polygon}) as {$this->field_polygon}";
	}

	function output_json(Array $rows){
		foreach ($rows as $row){
			$json[]=$this->geo_json_structure($row);
		}
		return json_encode(count($json)>1 ? $json : $json[0],JSON_PRETTY_PRINT);
	}

	function search_json(Array $where){
		$rows=$this->get_rows($where);
		return $this->output_json($rows);
	}

	protected function polygon_to_array($polygon){
		$polygon=str_replace(['POLYGON','(',')'],'',$polygon);
		$polygon=explode(',',$polygon);
		foreach ($polygon as &$coords){
			$coords=explode(' ',$coords);
			$coords=[$coords[1],$coords[0]];
		}
		return $polygon;
	}

	function geo_json($record){
		$arr=$this->geo_json_structure($record);
		return json_encode($arr);
	}

	function geo_json_structure($record){
		$structure=[
			'type'=>'Feature',
			'properties'=>[],
			'geometry'=>[
				'type'=>'Polygon',
				'coordinates'=>[
					0=>[],
				]
			]
		];
		$structure['geometry']['coordinates'][0]=$this->polygon_to_array($record[$this->field_polygon]);
		unset($record[$this->field_polygon]);

		foreach ($record as $field => $val){
			$structure['properties'][$field]=$val;
		}
		return $structure;
	}
}