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
		$subpart =  isset($parts[$key+1]) ? $parts[$key+1]: '';
		switch ($partMod) {
			case 'SELECT': parseSelect($sqlBuilder, $subpart); break; 
			case 'FROM': $sqlBuilder->from = $subpart; break; 
			case 'WHERE': $sqlBuilder->where = $subpart; break; 
			case 'GROUP BY': $sqlBuilder->groupby = $subpart; break; 
			case 'HAVING': $sqlBuilder->having = $subpart; break; 
			case 'ORDER BY': $sqlBuilder->orderby = $subpart; break; 
			case 'LIMIT': $sqlBuilder->limit = $subpart; break; 
		}
	}
	return $sqlBuilder;
}

/** SELECT [option], [(column, alias)] */
function parseSelect(&$sqlBuilder, $selectString) {

	// If something matches the pattern /whitespace,KEYWORD,whitespace/ where keyword is in UPPER CASE, 
	// and it exists in the $selectOpions array,then is should be handled as a MySQL keyword.
	$options = findKeywords($selectString, sqlSelect::$selectOptions, function($e) {return '\s' . $e . '\s';});
	$options = array_map('trim', $options);
	$sqlBuilder->select = array_merge($sqlBuilder->select, array('options' => $options));
	
	$parts = str_replace($options, '', $selectString);
	$columnParts = explode(',', $parts);
	// (?:) is a non-capturing group. The syntax says that AS is optional. 
	// whitespace (columnname) (AS) (aliasname) whitespace
	// columnname and aliasname could be `here be dragons` or $total.
	// Really it should be separated by whitespace or the backtick, if present.
	// For now, we use an mandatory AS. 
	// $regex1 = /(\S+)\s+(?:AS\s)?(\S+)/  // with whitespace separated
	// $regex2 = /`([^`]+)`\s+(?:AS\s)?`([^`]+)`/  // with back-tick separated
	// $columnPart = "(`(?:[^`]+)`|(?:\S+))";
	// $aliasPart = $columnPart;
	// $optionalAliasKeyword = (?:AS\s)?
	// Combined? : "/" . $columnPart . "\s+" . $optionalAliasKeyword. "\s*" . $aliasPart . "/";
	foreach ($columnParts as $columnPart) {
		$t = explode(' AS ', $columnPart);
		# If there is no alias, just assign a number to it.
		if (isset($t[1])) {
			$alias = trim($t[1]);
			$columns[$alias] = trim($t[0]);
		} else {
			$columns[] = trim($t[0]);
		}
	}
	$sqlBuilder->select = array_merge($sqlBuilder->select, array('columns' => $columns));
}

// Helper function. 
function findKeywords($string, $keywordsArray, $preprocessFunction = null) {
	if ($preprocessFunction === null) {
		$preparedKeywords = $keywordsArray;
	} else {
		$preparedKeywords = array_map($preprocessFunction, $keywordsArray);
	}
	$regexSplitBy = '/' . implode('|', $preparedKeywords) . '/';
	$matches = array();
	preg_match_all($regexSplitBy, $string, $matches);
	return $matches[0];
}
