<?php
/** */
class sqlSelect {
	public $members = array(
		'select' => '*',
		'from' => 'DUAL',
		'where' => '',
		'groupby' => '',
		'having' => '',
		'orderby' => '',
		'limit' => ''
	);
	public $subqueries = array();
	
	public static $mainKeywords = array(
		'select' => 'SELECT',
		'from' => 'FROM',
		'where' =>'WHERE',
		'groupby' => 'GROUP BY',
		'having' => 'HAVING',
		'orderby' => 'ORDER BY',
		'limit' => 'LIMIT'
	);
	
	public function __set($name, $value) {
		$this->members[$name] = $value;
	}
	
	public function __get($name) {
		if (array_key_exists($name, $this->members)) {
			return $this->members[$name];
		}
		return NULL;
	}
	
	function __toString() {
		$returnString = '';
		foreach ($this->members as $key => $member) {
			$returnString .= ($member !== '') ? self::$mainKeywords[$key].$member : '';
		}
		foreach ($this->subqueries as $index => $subquery) {
			$subString = $subquery->__toString();
			# search for example '(\13)', replace with '(subquery goes here)'.
			$search = '(\\' . $index . ')';
			$returnString = str_replace($search, '(' . $subString .')', $returnString);
		}
		return $returnString;
	}
}