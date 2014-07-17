<?php

class SqlGeo {
	protected $db;
	protected $table;
	protected $field_polygon;
	protected $rows;
	protected $json;
	protected $kml;

	const INLINE = true;

	function __construct(PDO $db=null,$table='',$field=''){
		$this->set_db($db);
		$this->set_table($table);
		$this->set_field($field);
		return $this;
	}

	function __call($name,$args){
		throw new Exception('There is no handler for the output type '.$name);
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

	function get_rows(){
		return $this->rows;
	}

	function db_search(Array $where){
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
		$select=$this->sql_select_field();
		$query="SELECT *,$select FROM {$this->table}".(!empty($where_prepare)? " WHERE ".implode(' and ',$where_prepare) : " LIMIT 10");
		$query=$this->db->prepare($query);
		$query->execute($where_data);
		$this->rows=$query->fetchAll(PDO::FETCH_ASSOC);
		return $this;
	}

	private function search($type,Array $where,$inline=false){
		$generate='generate_'.$type;
		$return=$inline ? 'inline_'.$type : 'get_'.$type;
		return $this->db_search($where)->$generate()->$return();
	}

	protected function record_polygon($record,$arr=true){
		return $this->polygon_to_array($record[$this->field_polygon],$arr);
	}

	protected function polygon_to_array($polygon,$arr=true){
		$polygon=str_replace(['POLYGON','(',')'],'',$polygon);
		$polygon=explode(',',$polygon);
		foreach ($polygon as &$coords){
			$coords=explode(' ',$coords);
			$coords=$arr ? [$coords[1],$coords[0]] : $coords[1].','.$coords[0];
		}
		return $polygon;
	}

	function get_json(){
		return $this->json;
	}

	function inline_json(){
		return self::_inline($this->json,'json');
	}

	function search_json(Array $where,$inline=false){
		return $this->search('json',$where,$inline);
	}

	function generate_json(){
		$rows=$this->rows;
		foreach ($rows as $row){
			$json[]=$this->geo_json_structure($row);
		}
		$this->json=json_encode(count($json)>1 ? $json : $json[0],JSON_PRETTY_PRINT);
		return $this;
	}

	function geo_json($record){
		$arr=$this->geo_json_structure($record);
		return json_encode($arr);
	}

	static public function get_json_structure(){
		return [
			'type'=>'Feature',
			'properties'=>[],
			'geometry'=>[
				'type'=>'Polygon',
				'coordinates'=>[
					0=>[],
				]
			]
		];
	}

	static public function get_ignored_properties(){
		return [];
	}

	function geo_json_structure($record){
		$structure=self::get_json_structure();
		$structure['geometry']['coordinates'][0]=$this->record_polygon($record);
		unset($record[$this->field_polygon]);

		$ignore=self::get_ignored_properties();
		foreach ($record as $field => $val){
			if (!isset($ignore[$field])){
				$structure['properties'][$field]=$val;
			}
		}
		return $structure;
	}

	function get_kml(){
		return $this->kml;
	}

	function inline_kml(){
		return self::_inline($this->kml,'vnd.google-earth.kml+xml');
	}

	function search_kml(Array $where,$inline=false){
		return $this->search('kml',$where,$inline);
	}

	function generate_kml($name=''){
		$rows=$this->rows;
		$polygons=[];
		foreach ($rows as $row){
			if (isset($row[$name]) and !$name_set){
				$name=$row[$name];
				$name_set=true;
			}
			$polygons[]=$this->kml_polygon($row);
		}
		$this->kml=$this->kml_structure($polygons,$name);
		return $this;
	}

	function kml_polygon($record){
		$coordinates=$this->record_polygon($record,false);
		$coordinates=implode("\n\r\t\t\t\t\t\t",$coordinates);
		$polygon="
			<Polygon>
				<outerBoundaryIs>
					<LinearRing>
						<coordinates>
							$coordinates
						</coordinates>
					</LinearRing>
				</outerBoundaryIs>
			</Polygon>";
		return $polygon;
	}

	static private function sql_select($field){
		return "astext({$field}) as {$field}";
	}

	private function sql_select_field(){
		return self::sql_select($this->field_polygon);
	}

	function kml_structure($polygons,$name){
		$polygons=implode("\n",$polygons);
	$kml = <<< END
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
	<Placemark>
		<name>$name</name>
		<MultiGeometry>
$polygons
		</MultiGeometry>
	</Placemark>
</kml>
END;
		return $kml;
	}

	static private function _inline($content,$mime='json'){
		header('Content-type: application/'.$mime);
		header('Content-Disposition: inline');
		echo $content;
		return $this;
	}
}