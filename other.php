<?php
// Este archivo PHP genera una página HTML con un mapa interactivo y muestra información del clima usando OpenWeather
$apiKey = '01df15662db5b727c862a7c8e35dd27f'; // Reemplaza esto con tu propia clave de API de OpenWeather
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa con Información Climática</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            display: flex;
        }

        #map {
            width: 100%;
            height: 100vh;
        }

        aside {
            width: 300px;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.8);
            border-left: 1px solid #ccc;
            position: relative;
        }

        h2, h3 {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div id="map"></div>
    <aside id="coordinates">
        <h2>Coordenadas del Marcador</h2>
        <p id="coordOutput">Coloca un marcador en el mapa.</p>
        <h3>Información Climática</h3>
        <p id="cityOutput">Ciudad: --</p>
        <p id="precipitationOutput">Precipitación: --</p>
        <p id="humidityOutput">Humedad: --</p>
        <p id="maxTempOutput">Temperatura Máxima Pronosticada: --</p>
        <p id="minTempOutput">Temperatura Mínima Pronosticada: --</p>
        <p id="droughtIndexOutput">Índice de Sequía: --</p>
        <p id="frostIndexOutput">Índice de Helada: --</p>
        <p id="floodIndexOutput">Índice de Inundaciones: --</p>
    </aside>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        // Inicializa el mapa
        const map = L.map('map').setView([0, 0], 2); // Cambia la vista inicial si es necesario

        // Carga el mapa desde OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
        }).addTo(map);

        // Variable para almacenar el marcador
        let marker = null;

        // Función para manejar el clic en el mapa
        map.on('click', function(e) {
            // Elimina el marcador anterior si existe
            if (marker) {
                map.removeLayer(marker);
            }
            
            // Coloca un nuevo marcador
            marker = L.marker(e.latlng).addTo(map);

            // Muestra las coordenadas en el aside
            const coordOutput = document.getElementById('coordOutput');
            coordOutput.innerHTML = `Latitud: ${e.latlng.lat.toFixed(5)} <br> Longitud: ${e.latlng.lng.toFixed(5)}`;

            // Llama a la función para obtener la información del clima
            getWeatherInfo(e.latlng.lat, e.latlng.lng);
        });

        // Función para obtener la información del clima (pronóstico)
        function getWeatherInfo(lat, lon) {
            const apiKey = '<?php echo $apiKey; ?>'; // Obtiene la clave de la API de PHP
            const apiUrl = `https://api.openweathermap.org/data/2.5/forecast?lat=${lat}&lon=${lon}&appid=${apiKey}&units=metric`; // Cambia a 'imperial' para Fahrenheit

            fetch(apiUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error al obtener datos del clima');
                    }
                    return response.json();
                })
                .then(data => {
                    const cityOutput = document.getElementById('cityOutput');
                    const precipitationOutput = document.getElementById('precipitationOutput');
                    const humidityOutput = document.getElementById('humidityOutput');
                    const maxTempOutput = document.getElementById('maxTempOutput');
                    const minTempOutput = document.getElementById('minTempOutput');
                    const droughtIndexOutput = document.getElementById('droughtIndexOutput');
                    const frostIndexOutput = document.getElementById('frostIndexOutput');
                    const floodIndexOutput = document.getElementById('floodIndexOutput');

                    const cityName = data.city.name;
                    const humidity = data.list[0].main.humidity;

                    // Calcular la temperatura máxima y mínima del día
                    let maxTemp = -Infinity;
                    let minTemp = Infinity;
                    let totalRain = 0;
                    data.list.forEach((entry) => {
                        const hour = new Date(entry.dt_txt).getHours();
                        const temp = entry.main.temp;
                        const rain = entry.rain ? entry.rain['3h'] || 0 : 0;
                        if (hour >= 6 && hour <= 18) {
                            if (temp > maxTemp) maxTemp = entry.main.temp_max;
                        } else {
                            if (temp < minTemp) minTemp = entry.main.temp_min;
                        }
                        totalRain += rain;
                    });

                    const precipitation = totalRain; // Total de lluvia pronosticada

                    // Mostrar datos en el aside
                    cityOutput.innerHTML = `Ciudad: ${cityName}`;
                    precipitationOutput.innerHTML = `Precipitación: ${precipitation} mm`;
                    humidityOutput.innerHTML = `Humedad: ${humidity}%`;
                    maxTempOutput.innerHTML = `Temperatura Máxima Pronosticada: ${maxTemp.toFixed(2)} °C`;
                    minTempOutput.innerHTML = `Temperatura Mínima Pronosticada: ${minTemp.toFixed(2)} °C`;

                    // Calcular índices usando las funciones proporcionadas
                    const droughtIndex = calculateDroughtIndex(precipitation, maxTemp);
                    const frostIndex = calculateFrostRisk(minTemp);
                    const floodIndex = calculateFloodRisk(precipitation);

                    // Mostrar índices en el aside
                    droughtIndexOutput.innerHTML = `Índice de Sequía: ${droughtIndex}`;
                    frostIndexOutput.innerHTML = `Índice de Helada: ${frostIndex}`;
                    floodIndexOutput.innerHTML = `Índice de Inundaciones: ${floodIndex}`;
                                    })
                .catch(error => {
                    const errorOutput = document.getElementById('cityOutput');
                    errorOutput.innerHTML = `Error: ${error.message}`;
                });
        }

        // Cálculo del índice de sequía
        function calculateDroughtIndex(rain, tempDay) {
            if (rain < 1 && tempDay > 30) {
                return 'Alta';
            } else if (rain >= 1 && tempDay <= 30) {
                return 'Baja';
            } else {
                return 'Moderada';
            }
        }

        // Cálculo del riesgo de helada
        function calculateFrostRisk(tempNight) {
            if (tempNight <= 0) {
                return 'Alto';
            } else if (tempNight > 0 && tempNight <= 5) {
                return 'Moderado';
            } else {
                return 'Bajo';
            }
        }

        // Cálculo del riesgo de inundación
        function calculateFloodRisk(rain) {
            if (rain > 30) {
                return 'Alto';
            } else if (rain > 10 && rain <= 30) {
                return 'Moderado';
            } else {
                return 'Bajo';
            }
        }
    </script>
</body>
</html>
