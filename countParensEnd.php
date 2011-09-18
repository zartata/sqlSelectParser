<?php
/* Need to count parentesis when parsing sql subqueries. */

/**
 * Returns the COUNT of characters BETWEEN the delimiter at specified position,
 * and the corresponding end delimiter.
 * Returns false if there was no corresponding ending delimiter.
 * Valid delimiters are '(', '[' and '{', 
 * with their corresponding ending delimiters ')', ']', '}'.
 */
function countParensEnd($str, $pos) {
	$strLen = strlen($str);
	if (!is_numeric($pos) || $pos < 0 || $pos >= $strLen) {
		return false;
	}
	$delimiter = $str[$pos];
	$endDelimiter = getMatchingDelimiter($delimiter);
	if (!$endDelimiter) {
		return false;
	}
	$delimiters = $delimiter . $endDelimiter;
	$count = 0;
	$nextPos = $pos;
	do {
		$endPos = $nextPos;
		$count += parensCompare($str[$endPos], $delimiter, $endDelimiter);
		$nextPos += 1 + strcspn($str, $delimiters, $endPos + 1);
	} while ($count > 0 && ($nextPos < $strLen));
	return ($count == 0) ? $endPos - $pos - 1 : false;
}

function parensCompare($var, $addVar, $subVar) {
	if ($var === $addVar) {
		// echo "+";
		return 1;
	} else if ($var === $subVar) {
		// echo "-";
		return -1;
	} else {
		return 0;
	}
}

function getMatchingDelimiter($delimiter) {
	switch ($delimiter) {
		case '(': return ')';
		case '[': return ']';
		case '{': return '}';
		default: return false;
	}
}

# Tests
# echo "end parens count " . countParensEnd('        (()((  ))())              ))', 8);
# echo "end parens count " . countParensEnd('( )', 0);