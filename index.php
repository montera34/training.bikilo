<?php include "header.php";

// This page
// VARS
////
$tit = "Listado de usuarios";
$ths = array('username','Nombre y apellidos');
$tds = array(
	array(
		'username' => 'skotperez',
		'realname' => 'Alfonso Sánchez Uzábal'
	)
);

$ths_out = '<tr><th></th>';
foreach ( $ths as $th ) {
	$ths_out .= '<th>'.$th.'</th>';
}
$ths_out .= '</tr>';

$td_count = 0;
$tds_out = '';
foreach ( $tds as $td ) {
	$count++;
	$tds_out .= '<tr>
		<th scope="row">'.$count.'</th>
		<td>'.$td['username'].'</td>
		<td>'.$td['realname'].'</td>
	</tr>';
}
?>


<main class="container">
<header class="row"><h1 class="col-md-12"><?php echo $tit; ?></h1></header>

	<div class="table-responsive">
	<table class="table table-condensed">
	<thead>
		<?php echo $ths_out; ?>
	</thead>
	<tbody>
		<?php echo $tds_out; ?>
	</tbody>
	</table>
	</div>
</main>

<?php include "footer.php"; ?>
