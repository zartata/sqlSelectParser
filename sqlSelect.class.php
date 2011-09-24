<?php
/** */
class sqlSelect {
	public $members = array(
		'select' => array(),
		'from' => 'DUAL',
		'where' => '',
		'groupby' => '',
		'having' => '',
		'orderby' => '',
		'limit' => ''
	);
	public $subqueries = array();
	
	/** 
	 * Array with keys same as $members array, 
	 * and values are strings with MySQL SELECT main keywords (uppercase). 
	 */
	public static $mainKeywords = array(
		'select' => 'SELECT',
		'from' => 'FROM',
		'where' =>'WHERE',
		'groupby' => 'GROUP BY',
		'having' => 'HAVING',
		'orderby' => 'ORDER BY',
		'limit' => 'LIMIT'
	);
	
	/** Array containing mysql keywords normally found immediatly after the SELECT keyword. */
	public static $selectOptions = array('ALL', 'DISTINCT', 'DISTINCTROW', 'HIGH_PRIORITY', 
		'STRAIGHT_JOIN', 'SQL_SMALL_RESULT', 'SQL_BIG_RESULT', 'SQL_BUFFER_RESULT', 'SQL_CACHE',
		'SQL_NO_CACHE', 'SQL_CALC_FOUND_ROWS');
	
	/** magic setter. */
	public function __set($name, $value) {
		$this->members[$name] = $value;
	}
	
	/** magic getter. */
	public function __get($name) {
		if (array_key_exists($name, $this->members)) {
			return $this->members[$name];
		}
		return NULL;
	}
	
	function __toString() {
		$returnString = '';
		
		// Handle the main keywords.
		# A SELECT keyword is required. It is stored as an array.
		$returnString .= $this->selectToString();
		foreach ($this->members as $key => $member) {
			# Other keywords are optional, and are strings atm.
			if ($key !== 'select') {
				$returnString .= ($member !== '') ? ' ' . self::$mainKeywords[$key] . ' ' . $member : '';
			}
		}
		
		// Handle the subqueries.
		foreach ($this->subqueries as $index => $subquery) {
			$subString = $subquery->__toString();
			# search for example '(\13)', replace with '(subquery goes here)'.
			$search = '(\\' . $index . ')';
			$returnString = str_replace($search, '(' . $subString .')', $returnString);
		}
		
		return $returnString;
	}
	
	public function selectToString() {
		$select = $this->select;
		
		// Add select and select options to return string.
		$returnString = self::$mainKeywords['select'] . ' ' . implode(' ', $select['options']) . ' ';
		
		// Print out the columns with their alias, if they have any.
		// No alias means they are inserted with [] -> numeric index.
		foreach ($select['columns'] as $alias => $column) {
			if (is_numeric($alias)) {
				$columnArray[] = $column;
			} else {
				$columnArray[] = $column . ' AS ' . $alias;
			}
		}
		
		# Columns are comma-separated.
		$returnString .= implode(', ', $columnArray);
		
		return $returnString;
	}
}