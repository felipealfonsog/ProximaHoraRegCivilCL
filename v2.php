<?php 
/*
Initially createde by Sebastian Wilson
Modified by Felipe Alfonso González



---




  Ayuda a buscar la próxima hora disponible en el Registro Civil.

  Genera un archivo de texto plano donde las columnas son separadas usando $separator .

  Este archivo puede ser cargado posteriormente en Excel, y al ordenar por la columna 'fecha_hora'
  se podrá saber dónde conseguir la primera hora.

  Trámites ( tipo / código ): (lista actualizada en https://agenda.qa.registrocivil.cl/api/backend/getTiposTramite)

    2/4  = Identificación -> Primera Obtención - Chileno
    2/5  = Identificación -> Primera Obtención - Extranjero
    2/22 = Identificación -> Reimpresión de cédula
    2/6  = Identificación -> Renovación cédula - Chileno
    2/23 = Identificación -> Renovación cédula - Extranjero
    2/8  = Identificación -> Solicitud de Pasaporte

    3/11 = Matrimonio     -> Ceremonia Matrimonio Civil
    3/12 = Matrimonio     -> Inscripción Ceremonia Religiosa

    5/20 = Vehículos      -> Cambio de Modalidad
    5/18 = Vehículos      -> Duplicado Placa Patente
    5/16 = Vehículos      -> Inscripción de Vehículo
    5/21 = Vehículos      -> Otros
    5/19 = Vehículos      -> Retiro Placa Patente
    5/17 = Vehículos      -> Transferencia Vehículo

    6/2  = Rectificación  -> Orden de Apellido - Hijos Inscritos
    6/3  = Rectificación  -> Orden de Apellido - Mayor de Edad

    7/24 = Apostilla -> Solicitud de Apostilla
*/

$start          =   time();

$service_type   =   2; // Tipo de trámite ( 2 = Identificación)
$service_id     =   6; // Código del trámite ( 6 = Renovación cédula - Chileno )
$region         =   13; // Metropolitana
$base_url       =   'https://agenda.qa.registrocivil.cl/api/backend/';
$separator      =   '|';

$hour_structure =   array(
                        'codigo_oficina',
                        'nombre_oficina',
                        'direccion',
                        'codigo_oficina_horas',
                        'fecha_hora',
                        'fecha',
                        'hora',
                        'cantidad'
                    );

// Títulos
print 'comuna';
foreach( $hour_structure as $item_name ) print $separator . $item_name;

// Buscamos todas las comunas de la región
$cities_info    =   file_get_contents( $base_url . '/comunas/' . $region );
$json_cities    =   json_decode( $cities_info, true, 512, JSON_BIGINT_AS_STRING );

if( !$json_cities ) exit( 'No se pudo obtener la lista de comunas' );

foreach( $json_cities as $city ){
    $city_id        =   $city['codigo_comuna'];
    $city_name      =   $city['nombre_comuna'];

    // Buscamos las oficinas dentro de la comuna donde se puede realizar el trámite
    $offices_info   =   file_get_contents( $base_url . 'oficinas/' . $city_id . '/' . $service_id );
    $json_offices   =   json_decode( $offices_info, true, 512, JSON_BIGINT_AS_STRING );

    if( !$json_offices || $json_offices['code'] < 1 ) continue;

    foreach( $json_offices['oficinas'] as $office ){
        $office_id      =   $office['codigo_oficina'];

        // Revisamos las horas disponibles
        $hours_info     =   file_get_contents( $base_url . 'horas/' . $office_id . '/' . $service_type . '/' . $service_id );
        $json_hours     =   json_decode( $hours_info, true, 512, JSON_BIGINT_AS_STRING );

        if( !$json_hours || $json_hours['code'] < 1 ) continue;

        foreach( $json_hours['horas'] as $hour ){
            print PHP_EOL . $city_name;

            foreach( $hour_structure as $item_name ) print $separator . $hour[$item_name];
        }
    }
}

print PHP_EOL . 'FIN. Tiempo total: ' . ( time() - $start ) . ' segundos.' . PHP_EOL;

?>
