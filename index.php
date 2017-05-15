<?php
require __DIR__ . '/vendor/autoload.php';


// if category was sumbitted, run readability test
if (isset($_POST['cat_name']) && (! empty($_POST['cat_name']))) {
	$wiki_test = new \mason88\WikiTest\WikiTest();
	$readibility = $wiki_test->get_readability($_POST['cat_name'], 50);
}
else
	$readibility = array();


// local callback function to be used for displaying rows of articles and scores
function display_article_row($row_result, $row) {
	$row_result .= "<tr>
		<td>{$row['pageid']}</td>
		<td>{$row['title']}</td>
		<td>{$row['paragraph']}</td>
		<td>{$row['fk_score']}</td>
		<td>{$row['ari_score']}</td>
		</tr>";
	return($row_result);
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset='utf-8'>
<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.15/css/jquery.dataTables.css">
<script src="https://code.jquery.com/jquery-2.2.4.min.js" integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44=" crossorigin="anonymous"></script>
<script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.15/js/jquery.dataTables.js"></script>
<script type="text/javascript" charset="utf-8">
	$(document).ready(function() {
		article_tabular_obj = $('#article_tabular').dataTable({
			"sPaginationType": "full_numbers",
			"aoColumnDefs": [
				{ "aTargets":[4], "bSearchable":false }
			],
			"aaSorting": [[3, "desc"]]
		});
	});
</script>
</head>

<body>
<h1>Wikipedia Readability Scores</h1>
<form method="post" style="margin-bottom:60px">
	<label for="cat_name">Category:</label>
	<input type="text" id="cat_name" name="cat_name" maxlength="70" />
	<input type="submit" />
</form>

<p>Please click on the score headers to sort by that score type. </p>

<h2>Article Scores</h2>
<table id="article_tabular" class="display">
	<thead>
		<tr>
			<th>ID</th><th>NAME</th><th>PARAGRAPH</th><th>FLESCH KINCAID READING EASE SCORE</th><th>AUTOMATED REABILITY INDEX</th>
		</tr>
	</thead>
	<tbody>
		<?= array_reduce($readibility, 'display_article_row'); ?>
	</tbody>
</table>
</body>
</html>
