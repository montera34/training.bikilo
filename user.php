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
$ftype = ( array_key_exists('ftype',$_GET) ) ? trim(strtolower($_GET['ftype'])) : trim(strtolower($ftype_default));
$fname = ( array_key_exists('fname',$_GET) ) ? trim(strtolower($_GET['fname'])) : trim(strtolower($fname_default));

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

	$sum_dist_objective = 0;
	$sum_dist_real = 0;
	$sum_dist_percent = 0;
	$sum_dist_percent_count = 0;
	$sum_time = 0;
	$sum_ritme_objetive = 0;
	$sum_ritme_objetive_count = 0;
	$sum_ritme_real = 0;
	$sum_ritme_real_count = 0;
	$sum_ritme_percent = 0;
	$sum_ritme_percent_count = 0;

	$th_class = '';
	$td_class = '';

	$line = -1;
	while ( ($fp_csv = fgetcsv($fp,$line_length,$delimiter)) !== FALSE ) { // begin main loop

		$line++;

		// HEADERS GENERATION
		if ( $line == 0 ) {
			foreach ( $fp_csv as $f ) {
				if ( $f != 'Usuario' && $f != 'Nombre Usuario' && $f != 'Id' && $f != '' ) {
					if ( strpos($f, 'Cantidad' ) !== FALSE ) { $f = str_replace( 'Cantidad', 'Dist.', $f ); $f .= '<br><small>metros</small>'; $th_class = ' class="text-right"'; }
					elseif ( $f == 'Tiempo' ) { $f .= '<br><small>H:m:s</small>'; $extra = true; $extra_label = 'Dist.'; }
					elseif ( strpos($f, 'Ritmo' ) !== FALSE ) { $f .= '<br><small>m:s</small>'; }
					elseif ( $f == 'Pulsaciones' ) { $extra = true; $extra_label = 'Ritmos'; }
					if ( isset($extra) ) { $ths_out .= '<th'.$th_class.'><span class="text-success">'.$extra_label.'<br><small>%</small></span></th>'; unset($extra); }
					$ths_out .= '<th'.$th_class.'>'.$f.'</th>';
				}
			}
			$th_class = '';
		}

		// LIST GENERATION
		else {
			// run filters
			$fp_csv[5] = preg_replace('/[0-9].*$/', '', $fp_csv[5]);
			$output = 1;
			if ( $ftype != '' && $ftype != trim(strtolower($fp_csv[4])) ) { $output = 0; }
			if ( $fname != '' && $fname != trim(strtolower($fp_csv[5])) ) { $output = 0;}

			if ( $output == 1 ) {
	
				// get time or distance
				if (
					trim($fp_csv[5]) == 'Rodaje K' ||
					trim($fp_csv[5]) == 'Regeneración' ||
					trim($fp_csv[5]) == 'Carrera continua' ||
					trim($fp_csv[5]) == 'Enfriamiento' ||
					trim($fp_csv[5]) == 'PF' ||
					trim($fp_csv[5]) == 'Competición' ||
					trim($fp_csv[5]) == 'Trabajo en arena'
				) {
					$fp_csv[6] = $fp_csv[6] * 1000;
					$fp_csv[7] = $fp_csv[7] * 1000;
				}
				$distance_objetive = $fp_csv[6];
				settype($distance_objetive,'float');
				$distance_real = $fp_csv[7];
				settype($distance_real,'float');
				$times = array(
					'time' => $fp_csv[8],
					'ritme_objetive' => $fp_csv[9],
					'ritme_real' => $fp_csv[10]
				);
				foreach ( $times as $k => $t ) {
					$t = explode(':',$t);
					if ( count($t) == 2 ) $$k = ( $t[0] * 60 ) + $t[1];
					elseif ( count($t) == 1 ) $$k = ( $t[0] * 60 );
					else $$k = 0;
					if ( !is_int($$k) ) $$k = 0;
				}
				$fp_csv[9] = gmdate("i:s", $ritme_objetive);
				$fp_csv[10] = gmdate("i:s", $ritme_real);
				if ( $times['time'] != '' ) { // calculate distances
					// distance_objetive
					if ( $time != 0 && $ritme_objetive != 0 ) {
						$distance_objetive = round( ( $time / $ritme_objetive ) * 1000,2 );
						$fp_csv[6] = '<span class="text-info">'.$distance_objetive.'</span>';
					} else {
						$distance_objetive = 0;
						$fp_csv[6] = '<span class="text-danger">faltan datos</span>';
					}
					// distance_real
					if ( $time != 0 && $ritme_real != 0 ) {
						$distance_real = round( ( $time / $ritme_real ) * 1000,2 );
						$fp_csv[7] = '<span class="text-info">'.$distance_real.'</span>';
					} else {
						$distance_real = 0;
						$fp_csv[7] = '<span class="text-danger">faltan datos</span>';
					}
					$fp_csv[8] = gmdate("H:i:s", $time);
				}
				elseif ( $time == '' ) { // calculate time
					if ( $distance_real != '' && $ritme_real != '' ) {
						$time = round( $ritme_real * $distance_real * ( 1 / 1000 ) );
						$fp_csv[8] = '<span class="text-info">'.gmdate("H:i:s", $time).'</span>';
					}
				}
				// get distance and ritme relations
				$distance_percentage = ( $distance_objetive != 0 ) ? round( ( $distance_real * 100 ) / $distance_objetive ) : 0;
				$distance_percentage_out = '<span class="text-success">'.$distance_percentage.'</span>';
				$ritme_percentage = ( $ritme_objetive != 0 ) ? round( ( $ritme_real * 100 ) / $ritme_objetive ) : 0;
				$ritme_percentage_out = '<span class="text-success">'.$ritme_percentage.'</span>';
	
				// calcule sum and average distances
				$sum_dist_objective += $distance_objetive;
				$sum_dist_real += $distance_real;
				if ( $distance_percentage != 0 ) { $sum_dist_percent += $distance_percentage; $sum_dist_percent_count++; }
				// calcule sum and average ritmes and time
				$sum_time += $time;
				if ( $ritme_objetive != 0 ) { $sum_ritme_objetive += $ritme_objetive; $sum_ritme_objetive_count++; }
				if ( $ritme_real != 0 ) { $sum_ritme_real += $ritme_real; $sum_ritme_real_count++; }
				if ( $ritme_percentage != 0 ) { $sum_ritme_percent += $ritme_percentage; $sum_ritme_percent_count++; }

				// generate output
				$tds_out .= '<tr><th scope="row">'.$line.'</th>';
				$f_count = 0;
				foreach ( $fp_csv as $f ) {
					if ( $f_count == 6 ) { $td_class = ' class="text-right"'; }
					if ( $f_count != 0 && $f_count != 1 && $f_count != 2 ) {
						if ( $f_count == 8 ) $tds_out .= '<td'.$td_class.'>'.$distance_percentage_out.'</td>';
						if ( $f_count == 11 ) $tds_out .= '<td'.$td_class.'>'.$ritme_percentage_out.'</td>';
						$tds_out .= '<td'.$td_class.'>'.$f.'</td>';
					}
					$f_count++;
				}
			}

			$tds_out .= '</tr>';
			$td_class = '';
		}
	}

	$tds_out .= '<tr>';
	$sum_count = 0;
	$sum_time_out = ( $sum_time <= 86400 ) ? gmdate("H:i:s", $sum_time) : round($sum_time / 60) .' *';
	while ( $sum_count <= 11 ) {
		if ( $sum_count == 4 ) $tds_out .= '<td class="text-info text-right"><strong>'. round($sum_dist_objective,2) .'</strong></td>';
		elseif ( $sum_count == 5 ) $tds_out .= '<td class="text-info text-right"><strong>'. round($sum_dist_real,2) .'</strong></td>';
		elseif ( $sum_count == 6 ) $tds_out .= '<td class="text-success text-right"><strong>'. round($sum_dist_percent / $sum_dist_percent_count) .'</strong></td>';
		elseif ( $sum_count == 7 ) $tds_out .= '<td class="text-info text-right"><strong>'.$sum_time_out.'</strong></td>';
		elseif ( $sum_count == 8 ) $tds_out .= '<td class="text-info text-right"><strong>'.gmdate("i:s",$sum_ritme_objetive / $sum_ritme_objetive_count).'</strong></td>';
		elseif ( $sum_count == 9 ) $tds_out .= '<td class="text-info text-right"><strong>'.gmdate("i:s",$sum_ritme_real / $sum_ritme_real_count).'</strong></td>';
		elseif ( $sum_count == 10 ) $tds_out .= '<td class="text-success text-right"><strong>'.round($sum_ritme_percent / $sum_ritme_percent_count).'</strong></td>';
		else { $tds_out .= '<td class="active"></td>'; }
		$sum_count++;
	}
	$tds_out .= '</tr>';
	if ( $sum_time >= 86400 ) {
		$tds_out .= '
		<tr>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td colspan="4">* <small>Si el tiempo suma más de 24 horas, aparece en minutos.</small></td>
		</tr>';
	}

	$feedback_out = '
	<div class="row">
		<div class="col-md-12 bspace">
			<a class="btn btn-sm btn-info" href="'.$data_url.'">Descargar CSV con los datos de este usuario</a>
		</div>
	</div>';

} else { // if there is a problem with Mytrainik API
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


// PAGE TITLE
$tit = $name; 

// FILTERS
$ftypes_out = '<option value=""></option>';
foreach ( $ftypes as $t ) {
	$selected = ( strtolower($t) == $ftype ) ? ' selected' : '';
	$ftypes_out .= '<option value="'.$t.'"'.$selected.'>'.$t.'</option>';
}
$fnames_out = '<option value=""></option>';
foreach ( $fnames as $t ) {
	$selected = ( strtolower($t) == $fname ) ? ' selected' : '';
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

// LEGEND
$legend_out = '
<div class="row"><div class="col-md-12 bspace">
	<strong>Leyenda de datos: </strong>
	<ul class="legend list-inline">
		<li>Importado de Mytrainik</li>
		<li class="text-info">Calculado</li>
		<li class="text-danger">Error en el cálculo</li>
		<li class="text-success">Relación de valores</li>
	</ul>
</div></div>';
?>


<main class="container">
<header class="row"><h1 class="col-md-12"><?php echo $tit; ?></h1></header>

	<?php 	echo $filters_out;
		echo $legend_out; ?>
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
