<?php
// training types array
$trainings = array(
array('type' => 'Fartlek','unit' => 'm','name' => 'Fartlek','name_for_ritme'=>'Fartlek'),
array('type' => 'Series_200','unit' => 'm','name' => 'Serie','name_for_ritme'=>'Serie'),
array('type' => 'Series_400','unit' => 'm','name' => 'Serie','name_for_ritme'=>'Serie'),
array('type' => 'Series_1000','unit' => 'm',   'name' => 'Serie','name_for_ritme'=>'Serie'),
array('type' => 'Series_2000','unit' => 'm',   'name' => 'Serie','name_for_ritme'=>'Serie'),
array('type' => 'Series_4000','unit' => 'm',   'name' => 'Serie','name_for_ritme'=>'Serie'),
array('type' => 'Series_3000','unit' => 'm',   'name' => 'Serie','name_for_ritme'=>'Serie'),
array('type' => 'Series_5000','unit' => 'm',   'name' => 'Serie','name_for_ritme'=>'Serie'),
array('type' => 'Series_6000','unit' => 'm',   'name' => 'Serie','name_for_ritme'=>'Serie'),
array('type' => 'Series', 'unit' => 'm',   'name' => 'Serie','name_for_ritme'=>'Serie'),
array('type' => 'Cuestas_100','unit' => 'm',   'name' => 'Cuesta','name_for_ritme'=>'Cuesta'),
array('type' => 'Cuestas_200','unit' => 'm',   'name' => 'Cuesta','name_for_ritme'=>'Cuesta'),
array('type' => 'Cuestas_300','unit' => 'm',   'name' => 'Cuesta','name_for_ritme'=>'Cuesta'),
array('type' => 'Cuestas_400','unit' => 'm',   'name' => 'Cuesta','name_for_ritme'=>'Cuesta'),
array('type' => 'Cuestas_1000',   'unit' => 'm',   'name' => 'Cuesta','name_for_ritme'=>'Cuesta'),
array('type' => 'Estructura mixta',   'unit' => 'km',  'name' => 'Rodaje K','name_for_ritme'=>'Rodaje K'),
array('type' => 'Rodaje', 'unit' => 'km',  'name' => 'Rodaje K','name_for_ritme'=>'Rodaje K'),
array('type' => 'Regeneración',  'unit' => 'km',  'name' => 'Regeneración','name_for_ritme'=>'Regeneración'),
array('type' => 'Tirada larga',   'unit' => 'km',  'name' => 'Tirada larga','name_for_ritme'=>'Tirada larga'),
array('type' => '',   'unit' => 'km',  'name' => 'Carrera continua','name_for_ritme'=>'Carrera continua'),
array('type' => '',   'unit' => 'km',  'name' => 'Enfriamiento','name_for_ritme'=>'Enfriamiento'),
array('type' => 'Preparación física',   'unit' => 'km',  'name' => 'PF','name_for_ritme'=>'Rodaje K'),
array('type' => '',   'unit' => 'ud',  'name' => 'Progresivos','name_for_ritme'=>'Progresivos'),
array('type' => 'Competición',   'unit' => 'km',  'name' => 'Competición','name_for_ritme'=>'Competición'),
array('type' => 'Trabajo en arena',   'unit' => 'km','name' => 'Trabajo en arena','name_for_ritme' => 'Trabajo en arena')
);
$trainings_names = array('Fartlek','Serie','Cuesta','Rodaje K','Regeneración','Tirada larga','Carrera continua','Enfriamiento','PF','Progresivos','Competición','Trabajo en arena');

// to produce an array of types
function get_training_types() {
	global $trainings;
	$types = array();
	foreach ( $trainings as $t ) {
		if ( $t['type'] != '' ) $types[] = $t['type'];
	}
	return $types;
}
?>
