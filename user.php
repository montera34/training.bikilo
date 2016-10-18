<?php include "header.php";

// $_GET VARS
$id = ( array_key_exists('id',$_GET) ) ? $_GET['id'] : '';
$what_print = ( array_key_exists('whatprint',$_GET) ) ? $_GET['whatprint'] : '';
$date_from = ( array_key_exists('datefrom',$_GET) ) ? $_GET['datefrom'] : '';
$date_to = ( array_key_exists('dateto',$_GET) ) ? $_GET['dateto'] : '';


// DEFAULTS
$what_print_default = 'trainings';
$fromat_default = 'csv';
$date_from_default = date('Y-m-d', strtotime("-1 week") );
$date_to_default = date('Y-m-d', strtotime("now") );


// API connection
// User data
// @endpoint entrenamientosporusuarios
// @endpoint entrenamientosporgrupos
// URL parts (mandatory)
// @param whatprint	"trainings", "leveltests", "fbrunning"
// @param format	"csv", "csv2" -> csv2 son los entrenamientos + los ejercicios
// @param fromdate	aaaa-mm-dd
// @param todate	aaaa-mm-dd
// URL params (optional)
// @param locked	bool
// @param rate		ids de las tarifas separados por comas
// @param gender	"Hombre", "Mujer"
// @param usertype	ids de los tipos de usuario separados por comas
// @param users		ids de los usuarios serparados por comas

$endpoint = 'entrenamientosporusuarios';

$urlparts_array = array();
$urlparts_array[] = ( $what_print != '' ) ? $what_print : $what_print_default;
$urlparts_array[] = $fromat_default;
$urlparts_array[] = ( $date_from != '' ) ? $date_from : $date_from_default;
$urlparts_array[] = ( $date_to != '' ) ? $date_to : $date_to_default;
$urlparts = join('/',$urlparts_array);

$params_array = array();
$params_array[] = ( $id != '' ) ? 'users='.$id : false;
$params = join('&',$params_array);
$params = ( $params != '' ) ? '?'.$params : '';
$data_url = $api_url.$api_key.'/'.$endpoint.'/'.$urlparts.$params;


$line_length = "4096";	 // max line lengh (increase in case you have longer lines than 1024 characters)
$delimiter = ";";	 // field delimiter character
$enclosure = '';	 // field enclosure character

// open the CSV file
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
				if ( $f != 'Usuario' && $f != 'Nombre Usuario' ) {
					$ths_out .= '<th>'.$f.'</th>';
				}
			}
			$ths_out .= '<th></th>';
		}

		// LIST GENERATION
		else {
			if ( $line == 1 ) {
				$id = $fp_csv[0];
				$name = $fp_csv[1];
			}
//			$lastname = $fp_csv[2];
//			$email = $fp_csv[3];
			$tds_out .= '<tr><th scope="row">'.$line.'</th>';
			$f_count = 0;
			foreach ( $fp_csv as $f ) {
				if ( $f_count != 0 && $f_count != 1 ) {
					$tds_out .= '<td>'.$f.'</td>';
				}
				$f_count++;
			}

			// links to access user data
			$tds_out .= '</tr>';
		}
	}
	$feedback_out = '
	<div class="row">
		<div class="col-md-8">
			<div class="alert alert-info" role="alert">Mytrainik API URL consulted:<br><code>'.$data_url.'</code></p></div>
		</div>
		<div class="col-md-4">
			<a class="btn btn-info" href="'.$data_url.'">Download CSV with this data</a>
		</div>
	</div>';
} else { // if there is a problem

	$tds_out = ''; $ths_out = '';
	$feedback_out = '<div class="row"><div class="col-md-12"><div class="alert alert-danger" role="alert">There is a problem with Mytrainik API.<br>We cannot access Mytrainik data.</div></div></div>';
}


$tit = $name // Page title
//$ths = array('Fecha','Entrenamiento','Nombre','Dist. requerida','Dist. realizada','% distancias','Tiempo','Ritmo objetivo','Ritno real','% ritmos');
?>


<main class="container">
<header class="row"><h1 class="col-md-12"><?php echo $tit; ?></h1></header>

	<?php echo $feedback_out; ?>
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
