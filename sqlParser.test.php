<?php
include 'sqlParser.php';

// TESTS
$sql = '
	SELECT 
		t1.col1 AS `1`,
		t2.col2 AS `2`
	FROM 
		table1 AS `t1`,
		table2 AS `t2`,
		(
			SELECT Count(`id`) AS `num`
			FROM table3 AS `t3`
			GROUP BY rand()
		) AS `subquery1`
	WHERE t1.id = t2.id OR t1.id = (
		SELECT 1337
		FROM DUAL
	)
	ORDER BY t1.col1 DESC
';
# $sql = 'SELECT t1.col1 AS `1`, t2.col2 AS `2` FROM table1 AS `t1`, table2 AS `t2`, (SELECT Count(`id`) AS `num` FROM table3 AS `t3` GROUP BY rand()) AS `subquery1`WHERE t1.id = t2.id OR t1.id = (SELECT 1337 FROM DUAL) ORDER BY t1.col1 DESC';
$newSql = sqlParser($sql);
?>

<pre>before: <?php echo $sql;?></pre>
<br />
<!--<pre>after: <?php //print_r($newSql);?></pre>-->
<pre>after: <?php echo $newSql;?></pre>