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
$view = ( array_key_exists('view',$_GET) ) ? $_GET['view'] : 'grouped';
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
	$sum_pulse = 0;
	$sum_pulse_count = 0;

	$th_class = '';
	$td_class = '';

	$filter_count = 0;

	$date_old = '';
	$grouped_count = 0;
	$grouped_quantity = -1;
	$distance_objetive_total = 0;
	$name_total = array();
	$distance_real_total = 0;
	$distance_percentage_total = 0;
	$time_total = 0;
	$ritme_objetive_total = 0;
	$ritme_real_total = 0;
	$ritme_percentage_total = 0;
	$ritme_objetive_total_count = 0;
	$ritme_real_total_count = 0;
	$pulse_total = 0;
	$pulse_total_count = 0;

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
			// fields
			//  3 Fecha
			//  4 Entrenamiento
			//  5 Nombre
			//  6 Dist. Objetiva
			//  7 Dist. Real
			//  8 Tiempo
			//  9 Ritmo Objetivo
			// 10 Ritmo Real
			// 11 Pulsaciones
			
			// run filters
			$fp_csv[4] = trim( $fp_csv[4] );
			$fp_csv[5] = trim( preg_replace('/[0-9].*$/', '', $fp_csv[5]) );
			$output = 1;
			if ( $ftype != '' && $ftype != strtolower($fp_csv[4]) ) { $output = 0; }
			if ( $fname != '' && $fname != strtolower($fp_csv[5]) ) { $output = 0; }

			if ( $output == 1 ) {
				$filter_count++;

				// prepare fields: unit conversion
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
				} else {
					$fp_csv[6] = $fp_csv[6];
					$fp_csv[7] = $fp_csv[7];
				}
				settype($fp_csv[6],'float');
				settype($fp_csv[7],'float');
				for ( $i = 8; $i <= 10; $i++ ) {
					$t = explode(':',$fp_csv[$i]);
					if ( count($t) == 2 ) $fp_csv[$i] = ( $t[0] * 60 ) + $t[1];
					elseif ( count($t) == 1 ) $fp_csv[$i] = ( $t[0] * 60 );
					else $fp_csv[$i] = 0;
					if ( !is_int($fp_csv[$i]) ) $fp_csv[$i] = 0;
				}
				$distance_objetive = $fp_csv[6];
				$distance_real = $fp_csv[7];
				$time = $fp_csv[8];
				$ritme_objetive = $fp_csv[9];
				$ritme_real = $fp_csv[10];
				$pulse = ( $fp_csv[11] != '' ) ? $fp_csv[11] : 0;

				// calculate distances
				if ( $time !== 0 ) {
					// distance_objetive
					if ( $ritme_objetive != 0 ) {
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
				}

				// calculate time
				else {
					if ( $distance_real != '' && $ritme_real != '' ) {
						$time = round( $ritme_real * $distance_real * ( 1 / 1000 ) );
					}
				}

				// OUTPUT
				// if view grouped
				if ( $view == 'grouped' ) {
					$grouped_count++;
					$grouped_quantity++;
					if ( $fp_csv[3] == $date_old || $grouped_count === 1 ) { // if same date and same training type, then sum the partials
						$date_out = $fp_csv[3];
						$training_out = $fp_csv[4];
						$name_total[] = trim($fp_csv[5]);
						if ( is_numeric($distance_objetive) ) $distance_objetive_total += $distance_objetive;
						if ( is_numeric($distance_real) ) $distance_real_total += $distance_real;
						if ( is_numeric($time) ) $time_total += $time;
						if ( is_numeric($ritme_objetive) ) { $ritme_objetive_total += $ritme_objetive; $ritme_objetive_total_count++; }
						if ( is_numeric($ritme_real) ) { $ritme_real_total += $ritme_real; $ritme_real_total_count++; }
						if ( is_numeric($pulse) ) { $pulse_total += $pulse; $pulse_total_count++; }
						$time_prev = $fp_csv[8];

					} else { // if not the same date and training type, then output total
						$tds_out .= '<tr><th scope="row">'.$grouped_quantity.'</th>';
						$grouped_quantity = 0;
						$f_count = 0;
		
						// get distance and ritme relations
						$distance_percentage_total = ( $distance_objetive_total != 0 ) ? round( ( $distance_real_total * 100 ) / $distance_objetive_total ) : 0;
						$distance_percentage_out = '<span class="text-success">'.$distance_percentage_total.'</span>';
						$ritme_percentage_total = ( $ritme_real_total != 0 ) ? round( ( $ritme_objetive_total * 100 ) / $ritme_real_total ) : 0;
						$ritme_percentage_out = '<span class="text-success">'.$ritme_percentage_total.'</span>';

						foreach ( $fp_csv as $f ) {
							if ( $f_count == 6 ) { $td_class = ' class="text-right"'; }
							if ( $f_count != 0 && $f_count != 1 && $f_count != 2 ) {
								if ( $f_count == 3 ) { $f = $date_out; }
								elseif ( $f_count == 4 ) { $f = $training_out; }
								elseif ( $f_count == 5 ) { $f = implode(', ',$name_total); }
								elseif ( $f_count == 6 ) { $f = $distance_objetive_total; }
								elseif ( $f_count == 7 ) { $f = $distance_real_total; }
								elseif ( $f_count == 8 ) {
									$f = ( is_numeric($time_total) ) ? gmdate("H:i:s", $time_total) : gmdate("H:i:s", 0);
									$f = ( $time_prev == 0 ) ? '<span class="text-info">'.$f.'</span>' : $f;
									$tds_out .= '<td'.$td_class.'>'.$distance_percentage_out.'</td>'; }
								elseif ( $f_count == 9 ) { $f = ( is_numeric($ritme_objetive_total) ) ? gmdate("i:s", $ritme_objetive_total / $ritme_objetive_total_count ) : gmdate("i:s", 0); }
								elseif ( $f_count == 10 ) { $f = ( is_numeric($ritme_real_total) ) ? gmdate("i:s", $ritme_real_total / $ritme_real_total_count ) : gmdate("i:s", 0); }
								elseif ( $f_count == 11 ) { $f = round($pulse_total / $pulse_total_count); $tds_out .= '<td'.$td_class.'>'.$ritme_percentage_out.'</td>'; }
								$tds_out .= '<td'.$td_class.'>'.$f.'</td>';
							}
							$f_count++;
						}

						// calcule sum and average distances
						$sum_dist_objective += $distance_objetive_total;
						$sum_dist_real += $distance_real_total;
						if ( $distance_percentage_total != 0 ) { $sum_dist_percent += $distance_percentage_total; $sum_dist_percent_count++; }
						// calcule sum and average ritmes, time and pulse
						$sum_time += $time_total;
						if ( $ritme_objetive_total != 0 ) { $sum_ritme_objetive += $ritme_objetive_total / $ritme_objetive_total_count; $sum_ritme_objetive_count++; }
						if ( $ritme_real_total != 0 ) { $sum_ritme_real += $ritme_real_total / $ritme_real_total_count; $sum_ritme_real_count++; }
						if ( $ritme_percentage_total != 0 ) { $sum_ritme_percent += $ritme_percentage_total; $sum_ritme_percent_count++; }
						if ( $pulse_total != 0 ) { $sum_pulse += $pulse_total / $pulse_total_count; $sum_pulse_count++; }

						$date_out = $fp_csv[3];
						$training_out = $fp_csv[4];
						$name_total = array(); $name_total[] = trim($fp_csv[5]);
						$distance_objetive_total = ( is_numeric($distance_objetive) ) ? $distance_objetive : 0;
						$distance_real_total = ( is_numeric($distance_real) ) ? $distance_real : 0;
						$time_total = ( is_numeric($time) ) ? $time : 0;
						if ( is_numeric($ritme_objetive) ) { $ritme_objetive_total =  $ritme_objetive; } else { $ritme_objetive_total = 0; }
						$ritme_objetive_total_count = 1;
						if ( is_numeric($ritme_real) ) { $ritme_real_total = $ritme_real; } else { $ritme_real_total = 0; }
						$ritme_real_total_count = 1;
						if ( is_numeric($pulse) ) { $pulse_total = $pulse; } else { $pulse_total = 0; }
						$pulse_total_count = 1; 

					}
					$date_old = $fp_csv[3];

				// if view all
				} else {

					// get distance and ritme relations
					$distance_percentage = ( $distance_objetive != 0 ) ? round( ( $distance_real * 100 ) / $distance_objetive ) : 0;
					$distance_percentage_out = '<span class="text-success">'.$distance_percentage.'</span>';
					$ritme_percentage = ( $ritme_real != 0 ) ? round( ( $ritme_objetive * 100 ) / $ritme_real ) : 0;
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
					if ( $pulse != 0 ) { $sum_pulse += $pulse; $sum_pulse_count++; }

					$tds_out .= '<tr><th scope="row">'.$filter_count.'</th>';
					$f_count = 0;

					foreach ( $fp_csv as $f ) {
						if ( $f_count != 0 && $f_count != 1 && $f_count != 2 ) {
							if ( $f_count == 6 ) { $td_class = ' class="text-right"'; }
							if ( $f_count == 8 ) {
								$f = ( is_numeric($time) ) ? gmdate("H:i:s",$time) : gmdate("H:i:s",0);
								$f = ( $fp_csv[8] == 0 ) ? '<span class="text-info">'.$f.'</span>' : $f;
								$tds_out .= '<td'.$td_class.'>'.$distance_percentage_out.'</td>'; }
							elseif ( $f_count == 9 || $f_count == 10 ) {
								$f = ( is_numeric($f) ) ? gmdate("i:s",$f) : gmdate("i:s",0); }
							elseif ( $f_count == 11 ) {
								$tds_out .= '<td'.$td_class.'>'.$ritme_percentage_out.'</td>'; }
							$tds_out .= '<td'.$td_class.'>'.$f.'</td>';
						}
						$f_count++;
					}

				} // VIEW: if all or grouped

			} // FILTERS: if output true

			$tds_out .= '</tr>';
			$td_class = '';

		} // LINE: if header or data row

	} // END WHILE: Main loop
	
	// if view is grouped, output last data row
	if ( $view == 'grouped' ) {
		$tds_out .= '<tr><th scope="row">'.$grouped_quantity.'</th>';
		$f_count = 0;

		// get distance and ritme relations
		$distance_percentage_total = ( $distance_objetive_total != 0 ) ? round( ( $distance_real_total * 100 ) / $distance_objetive_total ) : 0;
		$distance_percentage_out = '<span class="text-success">'.$distance_percentage_total.'</span>';
		$ritme_percentage_total = ( $ritme_real_total != 0 ) ? round( ( $ritme_objetive_total * 100 ) / $ritme_real_total ) : 0;
		$ritme_percentage_out = '<span class="text-success">'.$ritme_percentage_total.'</span>';

		for ( $f_count = 3; $f_count <= 11; $f_count++ ) {
			if ( $f_count == 6 ) { $td_class = ' class="text-right"'; }
			if ( $f_count == 3 ) { $f = $date_out; }
			elseif ( $f_count == 4 ) { $f = $training_out; }
			elseif ( $f_count == 5 ) { $f = implode(', ',$name_total); }
			elseif ( $f_count == 6 ) { $f = $distance_objetive_total; }
			elseif ( $f_count == 7 ) { $f = $distance_real_total; }
			elseif ( $f_count == 8 ) {
				$f = ( is_numeric($time_total) ) ? gmdate("H:i:s", $time_total) : gmdate("H:i:s", 0);
				$f = ( $time_prev == 0 ) ? '<span class="text-info">'.$f.'</span>' : $f;
				$tds_out .= '<td'.$td_class.'>'.$distance_percentage_out.'</td>'; }
			elseif ( $f_count == 9 ) { $f = ( is_numeric($ritme_objetive_total) ) ? gmdate("i:s", $ritme_objetive_total / $ritme_objetive_total_count ) : gmdate("i:s", 0); }
			elseif ( $f_count == 10 ) { $f = ( is_numeric($ritme_real_total) ) ? gmdate("i:s", $ritme_real_total / $ritme_real_total_count ) : gmdate("i:s", 0); }
			elseif ( $f_count == 11 ) { $f = round($pulse_total / $pulse_total_count); $tds_out .= '<td'.$td_class.'>'.$ritme_percentage_out.'</td>'; }
			$tds_out .= '<td'.$td_class.'>'.$f.'</td>';
		}

		// calcule sum and average distances
		$sum_dist_objective += $distance_objetive_total;
		$sum_dist_real += $distance_real_total;
		if ( $distance_percentage_total != 0 ) { $sum_dist_percent += $distance_percentage_total; $sum_dist_percent_count++; }
		// calcule sum and average ritmes and time
		$sum_time += $time_total;
		if ( $ritme_objetive_total != 0 ) { $sum_ritme_objetive += $ritme_objetive_total / $ritme_objetive_total_count; $sum_ritme_objetive_count++; }
		if ( $ritme_real_total != 0 ) { $sum_ritme_real += $ritme_real_total / $ritme_real_total_count; $sum_ritme_real_count++; }
		if ( $ritme_percentage_total != 0 ) { $sum_ritme_percent += $ritme_percentage_total; $sum_ritme_percent_count++; }
		if ( $pulse_total != 0 ) { $sum_pulse += $pulse_total / $pulse_total_count; $sum_pulse_count++; }

	} // if view is grouped, output last data row

	$tds_out .= '<tr>';
	$sum_count = 0;
	$sum_time_out = ( $sum_time <= 86400 ) ? gmdate("H:i:s", $sum_time) : round($sum_time / 60) .' *';
	$sum_dist_percent_count = ( $sum_dist_percent_count === 0 ) ? 1 : $sum_dist_percent_count;
	$sum_ritme_objetive_count = ( $sum_ritme_objetive_count === 0 ) ? 1 : $sum_ritme_objetive_count;
	$sum_ritme_real_count = ( $sum_ritme_real_count === 0 ) ? 1 : $sum_ritme_real_count;
	$sum_ritme_percent_count = ( $sum_ritme_percent_count === 0 ) ? 1 : $sum_ritme_percent_count;
	$sum_pulse_count = ( $sum_pulse_count === 0 ) ? 1 : $sum_pulse_count;
	while ( $sum_count <= 11 ) {
		if ( $sum_count == 4 ) $tds_out .= '<td class="text-info text-right"><strong>'. round($sum_dist_objective,2) .'</strong></td>';
		elseif ( $sum_count == 5 ) $tds_out .= '<td class="text-info text-right"><strong>'. round($sum_dist_real,2) .'</strong></td>';
		elseif ( $sum_count == 6 ) $tds_out .= '<td class="text-success text-right"><strong>'. round($sum_dist_percent / $sum_dist_percent_count) .'</strong></td>';
		elseif ( $sum_count == 7 ) $tds_out .= '<td class="text-info text-right"><strong>'.$sum_time_out.'</strong></td>';
		elseif ( $sum_count == 8 ) $tds_out .= '<td class="text-info text-right"><strong>'.gmdate("i:s",$sum_ritme_objetive / $sum_ritme_objetive_count).'</strong></td>';
		elseif ( $sum_count == 9 ) $tds_out .= '<td class="text-info text-right"><strong>'.gmdate("i:s",$sum_ritme_real / $sum_ritme_real_count).'</strong></td>';
		elseif ( $sum_count == 10 ) $tds_out .= '<td class="text-success text-right"><strong>'.round($sum_ritme_percent / $sum_ritme_percent_count).'</strong></td>';
		elseif ( $sum_count == 11 ) $tds_out .= '<td class="text-right"><strong>'.round($sum_pulse / $sum_pulse_count).'</strong></td>';
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

// VIEWS
$get_params = '?id='.$id.'&name='.$name.'&whatprint='.$what_print.'&fdatefrom='.$date_from.'&fdateto='.$date_to.'&ftype='.$ftype.'&fname='.$fname;
$grouped_disabled = ( $view == 'grouped' ) ? ' class="btn btn-warning"' : ' class="btn btn-default"';
$all_disabled = ( $view == 'all' ) ? ' class="btn btn-warning"' : ' class="btn btn-default"';
$views_out = '
<div class="row"><div class="col-md-12 bspace">
	<div id="views" class="btn-group" role="group">
		<a'.$grouped_disabled.' href="user.php'.$get_params.'&view=grouped">Entrenamientos agrupados</a>
		<a'.$all_disabled.' href="user.php'.$get_params.'&view=all">Todos los entrenamientos</a>
	</div>
</div></div>';

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
	<input type="hidden" name="view" value="'.$view.'" />
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

	<?php 	echo $views_out;
	 	echo $filters_out;
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
