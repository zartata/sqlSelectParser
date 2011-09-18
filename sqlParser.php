<?php
include 'sqlSelect.class.php';
include 'countParensEnd.php';

function sqlParser($sqlString) {
	$sql = trim($sqlString);
	#$sql = $sqlString;
	$sqlLength = strlen($sql);
	$mainKeywords = array('SELECT', 'FROM', 'WHERE', 'GROUP BY', 'HAVING', 'ORDER BY', 'LIMIT');
	# $mainKeywords = array_values(sqlSelect::$mainKeywords);
	
	// Check if there is 0, 1, or >1 SELECT. >1 means we have subqueries.
	// Stores the position of the subquery if it exists.
	# Beware the 0/false return value from strpos!
	$selectPosition = strpos($sql, $mainKeywords[0]);
	if ($selectPosition === false) {
		# Invalid select query without the select keyword.
		return false;
	}
	
	$sqlBuilder = new sqlSelect();
	
	$subqueryStart = strpos($sql, $mainKeywords[0], $selectPosition + 1);
	
	if ($subqueryStart !== false) {
		$index = 0;
		do {
			// Find the start and end '(' for this subquery.
			# Note: strrpos has a offset parameter, might work with a negative offset?
			$parensStart = strrpos(substr($sql, 0, $subqueryStart), '(');
			#$parensStart = strrpos($sql, '(', $subqueryStart - $sqlLength);
			$count = countParensEnd($sql, $parensStart);
			$parensEnd = $parensStart + $count;
			
			// Add it to the end of the subquery array recursively,
			# Without the parentesis.
			$sqlBuilder->subqueries[$index] = sqlParser(substr($sql, $parensStart + 1, $count)); 
			
			// and replace it with a \n where n is the last position of said array.
			# Needs to call itself afterwards.
			$sql = substr($sql, 0, $parensStart + 1) . "\\$index" . substr($sql, $parensEnd + 1);
			
			$subqueryStart = strpos($sql, $mainKeywords[0], $subqueryStart + 1);
			$index++;
		} while ($subqueryStart !== false);
	}
	
	// Split the query in its main parts. (It should no longer contain subqueries.)
	// Notice that we keep the Keyword (Delimiter) in the parts array, 
	// since some keywords are optional, and therefore we want to know which keyword it actually was.
	# {'SELECT', 'FROM'} -> '/(SELECT)|(FROM)/i'
	$parentesizedMainKeywords = array_map(function($e) {return '(' . $e . ')';}, $mainKeywords);
	$regexSplitBy = '/' . implode('|', $parentesizedMainKeywords) . '/i';
	$parts = preg_split($regexSplitBy, $sql, NULL, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
	# print_r($parts);
	
	# Should divide it further than the main parts...
	foreach ($parts as $key => $part) {
		$partMod = strtoupper($part);
		switch ($partMod) {
			case 'SELECT': $sqlBuilder->select = $parts[$key+1]; break; 
			case 'FROM': $sqlBuilder->from = $parts[$key+1]; break; 
			case 'WHERE': $sqlBuilder->where = $parts[$key+1]; break; 
			case 'GROUP BY': $sqlBuilder->groupby = $parts[$key+1]; break; 
			case 'HAVING': $sqlBuilder->having = $parts[$key+1]; break; 
			case 'ORDER BY': $sqlBuilder->orderby = $parts[$key+1]; break; 
			case 'LIMIT': $sqlBuilder->limit = $parts[$key+1]; break; 
		}
	}
	return $sqlBuilder;
}
