<?php

class SqlGeo {
	protected $db;
	protected $table;
	protected $field_polygon;

	function __construct(PDO $db){
		$this->db=$db;
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
		foreach ($where as $key => $val){
			$where_prepare[]="$key = :$key";
			$where_data[':'.$key]=$val;
		}
		$select=$this->sql_select();
		$query="SELECT *,$select FROM {$this->table} WHERE ".implode(' and ',$where);
		$query=$this->prepare($query);
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
		return json_encode($json);
	}

	function search_json(Array $where){
		$rows=$this->get_rows(Array $where);
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