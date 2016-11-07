<?php include "header.php";

// API connection
// Users list
//  @endpoint usuarios
//  @param locked	bool
//  @param rate		string ids de las tarifas separados por comas
//  @param gender	string "Hombre", "Mujer"
//  @param usertype	string ids de los tipos de usuario separados por comas
//  @param users	string ids de los usuarios serparados por comas

$endpoint = 'usuarios';
$params = '';
$data_url = $api_url.$api_key.'/'.$endpoint.'?'.$params;


// OPEN CSV FILE
$line_length = "4096";	 // max line lengh (increase in case you have longer lines than 1024 characters)
$delimiter = ";";	 // field delimiter character
$enclosure = '';	 // field enclosure character

$fp = fopen($data_url,'r');
if ( $fp !== FALSE ) { // if the file exists and is readable
	
	$ths_out = '<tr><th></th>'; // headers container
	$tds_out = ''; // list container
	$line = -1;
	while ( ($fp_csv = fgetcsv($fp,$line_length,$delimiter)) !== FALSE ) { // begin main loop

		$line++;

		// HEADERS GENERATION
		if ( $line == 0 ) {
			foreach ( $fp_csv as $f ) {
				$ths_out .= '<th>'.$f.'</th>';
			}
			$ths_out .= '<th></th>';
		}

		// LIST GENERATION
		else {
			$id = $fp_csv[0];
			$firstname = $fp_csv[1];
			$lastname = $fp_csv[2];
			$name = $firstname. ' ' .$lastname;
//			$email = $fp_csv[3];
			$tds_out .= '<tr><th scope="row">'.$line.'</th>';
			foreach ( $fp_csv as $f ) {
				$tds_out .= '<td>'.$f.'</td>';
			}

			// links to access user data
			$tds_out .= '<td>
				<a class="btn btn-xs btn-success" href="/user.php?id='.$id.'&name='.urlencode($name).'&whatprint=fbrunning&view=grouped"><span class="glyphicon glyphicon-menu-right" aria-hidden="true"></span></a>
				</td>
			</tr>';
		}
	}
	$feedback_out = '
	<div class="row">
		<div class="col-md-12 bspace">
			<a class="btn btn-sm btn-info" href="'.$data_url.'">Descargar CSV con la lista de usuarios</a>
		</div>
	</div>';
} else { // if there is a problem

	$tds_out = ''; $ths_out = '';
	$feedback_out = '
	<div class="row">
		<div class="col-md-12 bspace">
			<div class="alert alert-danger" role="alert">Ha habido un problema al conectar con el servidor de Mytrainik.<br>No es posible obtener los datos de este momento.</div>
		</div>
	</div>';
}

$tit = "Listado de usuarios"; // Page title
?>

<main class="container">
<header class="row"><h1 class="col-md-12"><?php echo $tit; ?></h1></header>

	<div class="table-responsive">
	<table class="table table-condensed table-hover">
	<thead>
		<?php echo $ths_out; ?>
	</thead>
	<tbody>
		<?php echo $tds_out; ?>
	</tbody>
	</table>
	</div>
	<?php echo $feedback_out; ?>
</main>

<?php include "footer.php"; ?>
