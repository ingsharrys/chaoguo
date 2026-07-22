<?php 

function obtener_coordenadas($direccion, $api_key) {
    $direccion = urlencode($direccion);
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address=$direccion&key=$api_key";
    
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if ($data['status'] == 'OK') {
        $location = $data['results'][0]['geometry']['location'];
        return ['lat' => $location['lat'], 'lng' => $location['lng']];
    } else {
        throw new Exception('Error al obtener las coordenadas: ' . $data['status']);
    }
}

function obtener_distancia_google_maps($direccion_origen, $direccion_destino, $api_key) {
    $direccion_origen = urlencode($direccion_origen);
    $direccion_destino = urlencode($direccion_destino);

    $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=$direccion_origen&destinations=$direccion_destino&key=$api_key";

    $response = file_get_contents($url);
    $data = json_decode($response, true);

    if ($data['status'] == 'OK') {
        $distance = $data['rows'][0]['elements'][0]['distance']['value']; // Distancia en metros
        return $distance / 1000; // Convertir a kilómetros
    } else {
        throw new Exception('Error al obtener la distancia: ' . $data['status']);
    }
}

function calcular_tarifa_domicilio($distancia) {
    if ($distancia <= 5) {
        return 5000; // Tarifa para distancia <= 5 km
    } elseif ($distancia > 5 && $distancia <= 20) {
        return 10000; // Tarifa para distancia entre 5 km y 20 km
    } else {
        return 15000; // Tarifa para distancia > 20 km
    }
}

$direccion_origen = "Calle 7 con Carrera 17, Neiva, Huila, Colombia"; // Dirección de origen fija
$direccion_destino = "Calle 41 sur #37-176 conjunto valle de Turín, Neiva, Huila, Colombia"; // Dirección de destino
$api_key = 'AIzaSyA2ZryJyH97lyWO3D6gTA2Ny4owUKOlK18'; // Reemplaza con tu clave de API de Google Maps

try {
    $coordenadas_origen = obtener_coordenadas($direccion_origen, $api_key);
    $coordenadas_destino = obtener_coordenadas($direccion_destino, $api_key);
    $distancia = obtener_distancia_google_maps($direccion_origen, $direccion_destino, $api_key);
    $tarifa = calcular_tarifa_domicilio($distancia);
    echo "La distancia recorrida para la dirección '$direccion_destino' es: $distancia km\n";
    echo "La tarifa del domicilio para la dirección '$direccion_destino' es: $$tarifa";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Mapa de la Distancia Recorrida</title>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo $api_key; ?>&libraries=places"></script>
    <script>
        function initMap() {
            var origen = {lat: <?php echo $coordenadas_origen['lat']; ?>, lng: <?php echo $coordenadas_origen['lng']; ?>};
            var destino = {lat: <?php echo $coordenadas_destino['lat']; ?>, lng: <?php echo $coordenadas_destino['lng']; ?>};

            var map = new google.maps.Map(document.getElementById('map'), {
                center: origen,
                zoom: 13
            });

            var directionsService = new google.maps.DirectionsService();
            var directionsRenderer = new google.maps.DirectionsRenderer();
            directionsRenderer.setMap(map);

            var request = {
                origin: origen,
                destination: destino,
                travelMode: 'DRIVING'
            };

            directionsService.route(request, function(result, status) {
                if (status == 'OK') {
                    directionsRenderer.setDirections(result);
                }
            });
        }
    </script>
</head>
<body onload="initMap()">
    <h3>Mapa de la Distancia Recorrida</h3>
    <div id="map" style="height: 500px; width: 100%;"></div>
    <p>La distancia recorrida para la dirección '<?php echo $direccion_destino; ?>' es: <?php echo $distancia; ?> km</p>
    <p>La tarifa del domicilio para la dirección '<?php echo $direccion_destino; ?>' es: $<?php echo $tarifa; ?></p>
</body>
</html>