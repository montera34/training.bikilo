<?php include "header.php";

// TRAINING TYPES
include "data/training.type.php";

// DEFAULTS
$what_print_default = 'fbrunning';
$fromat_default = 'csv';
$date_from_default = date('Y-m-d', strtotime("-1 week") );
$date_to_default = date('Y-m-d', strtotime("now") );
$ftype_default = '';
$fname_default = '';
$ftypes = get_training_types();
$fnames = $trainings_names;

// $_GET VARS
$id = ( array_key_exists('id',$_GET) ) ? $_GET['id'] : '';
$name = ( array_key_exists('name',$_GET) ) ? urldecode($_GET['name']) : '';
$what_print = ( array_key_exists('whatprint',$_GET) ) ? $_GET['whatprint'] : $what_print_default;
$date_from = ( array_key_exists('fdatefrom',$_GET) ) ? $_GET['fdatefrom'] : $date_from_default;
$date_to = ( array_key_exists('fdateto',$_GET) ) ? $_GET['fdateto'] : $date_to_default;
$ftype = ( array_key_exists('ftype',$_GET) ) ? strtolower($_GET['ftype']) : strtolower($ftype_default);
$fname = ( array_key_exists('fname',$_GET) ) ? strtolower($_GET['fname']) : strtolower($fname_default);

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
$urlparts_array[] = $what_print;
$urlparts_array[] = $fromat_default;
$urlparts_array[] = $date_from;
$urlparts_array[] = $date_to;
$urlparts = join('/',$urlparts_array);

$params_array = array();
$params_array[] = ( $id != '' ) ? 'users='.$id : false;
$params = join('&',$params_array);
$params = ( $params != '' ) ? '?'.$params : '';
$data_url = $api_url.$api_key.'/'.$endpoint.'/'.$urlparts.$params;


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
				if ( $f != 'Usuario' && $f != 'Nombre Usuario' ) {
					$ths_out .= '<th>'.$f.'</th>';
				}
			}
			$ths_out .= '<th></th>';
		}

		// LIST GENERATION
		else {
			// run filters
			$output = 1;
			if ( $ftype != '' && $ftype != strtolower($fp_csv[4]) ) { $output = 0; }
			if ( $fname != '' && $fname != strtolower($fp_csv[5]) ) { $output = 0; }

			if ( $output == 1 ) {
				$tds_out .= '<tr><th scope="row">'.$line.'</th>';
				$f_count = 0;
				foreach ( $fp_csv as $f ) {
					if ( $f_count != 0 && $f_count != 1 ) {
						$tds_out .= '<td>'.$f.'</td>';
					}
					$f_count++;
				}
			}

			// links to access user data
			$tds_out .= '</tr>';
		}
	}
	$feedback_out = '
	<div class="row">
		<div class="col-md-12 bspace">
			<a class="btn btn-sm btn-info" href="'.$data_url.'">Descargar CSV con los datos de este usuario</a>
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

// NO DATA MESSAGE
if ( $tds_out == '' ) {
	// no data message
	$tds_out = '
	<tr><td colspan="9">
		<div class="alert alert-warning" role="alert">No hay datos de <strong>'.$name.'</strong> en las fechas seleccionadas: desde <em>'.$date_from.'</em> hasta <em>'.$date_to.'</em>.</div>
	</td></tr>';
	$feedback_out = '';

}


// FILTERS
$ftypes_out = '<option value=""></option>';
foreach ( $ftypes as $t ) {
	$selected = ( $t == $ftype ) ? ' selected' : '';
	$ftypes_out .= '<option value="'.$t.'"'.$selected.'>'.$t.'</option>';
}
$fnames_out = '<option value=""></option>';
foreach ( $fnames as $t ) {
	$selected = ( $t == $fname ) ? ' selected' : '';
	$fnames_out .= '<option value="'.$t.'"'.$selected.'>'.$t.'</option>';
}
$filters_out = '
<div class="row"><div class="col-md-12 bspace">
<form id="filters" class="form-inline" method="get">
	<div class="form-group">
		<label for="fdatefrom">Desde</label>
		<input type="text" class="filter-date form-control" id="fdatefrom" name="fdatefrom" value="'.$date_from.'" />
	</div>
	<div class="form-group">
		<label for="fdateto">Hasta</label>
		<input type="text" class="filter-date form-control" id="fdateto" name="fdateto" value="'.$date_to.'" />
	</div>
	<div class="form-group">
		<label for="ftype">Entrenamiento</label>
		<select class="form-control" id="ftype" name="ftype">
			'.$ftypes_out.'
		</select>
	</div>
	<div class="form-group">
		<label for="fname">Nombre</label>
		<select class="form-control" id="fname" name="fname">
			'.$fnames_out.'
		</select>
	</div>
	<input type="submit" class="btn btn-success" value="Filtrar" />
	<input type="hidden" name="id" value="'.$id.'" />
	<input type="hidden" name="name" value="'.urlencode($name).'" />
</form>
</div></div>';

$tit = $name; // Page title
?>


<main class="container">
<header class="row"><h1 class="col-md-12"><?php echo $tit; ?></h1></header>

	<?php echo $filters_out; ?>
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
