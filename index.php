<!DOCTYPE html>
<html lang="pt">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Detector de Localização de Fotos</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css"></script>
    <style>
      #map {
        height: 100vh;
      }
      .form-container {
        position: absolute;
        top: 15%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        width: 90%;
        max-width: 350px;
        text-align: center;
      }
      .form-container input,
      .form-container button {
        width: 100%;
        margin-top: 10px;
      }
      .popup-button {
        background-color: #1d4ed8;
        color: white !important;
        padding: 10px 15px;
        border-radius: 5px;
        text-decoration: none;
        display: inline-block;
        margin-top: 10px;
      }
      #map {
        width: 100%;
      }
    </style>
  </head>
  <body class="bg-gray-100 font-sans">
    <div class="form-container">
      <input
        type="file"
        id="fileInput"
        accept="image/*"
        class="border p-2 rounded"
      />
      <button
        onclick="uploadImage()"
        class="bg-blue-500 text-white py-2 rounded hover:bg-blue-600"
      >
        Enviar
      </button>
    </div>

    <div id="map"></div>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/exif-js/2.3.0/exif.min.js"></script>
    <script>
      // Criando as camadas do mapa
      const osmLayer = L.tileLayer(
        "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
        {}
      );

      const satelliteLayer = L.tileLayer(
        "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
        {
          attribution:
            "&copy; Esri, Maxar, Earthstar Geographics, and the GIS User Community",
        }
      );

      // Criando o mapa e adicionando a camada padrão (OpenStreetMap)
      let map = L.map("map", {
        center: [0, 0],
        zoom: 2,
        layers: [osmLayer], // Camada inicial
      });

      // Criando um controle de camadas para alternar entre Padrão e Satélite
      const baseMaps = {
        Padrão: osmLayer,
        Satélite: satelliteLayer,
      };

      L.control.layers(baseMaps).addTo(map);

      let marker;

      function uploadImage() {
        const fileInput = document.getElementById("fileInput");
        if (!fileInput.files.length) {
          alert("Selecione uma imagem primeiro!");
          return;
        }

        const formData = new FormData();
        formData.append("image", fileInput.files[0]);

        const file = fileInput.files[0];

        // Lê a imagem e extrai os dados EXIF
        readExifData(file);
      }

      // Função para extrair as coordenadas EXIF e converter para formato decimal
      function readExifData(file) {
        EXIF.getData(file, function () {
          // Pegando as coordenadas de latitude e longitude
          const latitude = EXIF.getTag(this, "GPSLatitude");
          const longitude = EXIF.getTag(this, "GPSLongitude");

          // Pegando as referências (S ou N para latitude, W ou E para longitude)
          const latitudeRef = EXIF.getTag(this, "GPSLatitudeRef");
          const longitudeRef = EXIF.getTag(this, "GPSLongitudeRef");

          if (latitude && longitude && latitudeRef && longitudeRef) {
            console.log("Latitude antes de converter:", latitude);
            console.log("Longitude antes de converter:", longitude);

            // Convertendo as coordenadas de DMS para decimal
            const latitudeDecimal = convertToDecimal(latitude, latitudeRef);
            const longitudeDecimal = convertToDecimal(longitude, longitudeRef);

            console.log("Latitude convertida:", latitudeDecimal);
            console.log("Longitude convertida:", longitudeDecimal);

            // Passar as coordenadas para o mapa
            if (!isNaN(latitudeDecimal) && !isNaN(longitudeDecimal)) {
              map.setView([latitudeDecimal, longitudeDecimal], 15);

              if (marker) marker.remove();
              marker = L.marker([latitudeDecimal, longitudeDecimal])
                .addTo(map)
                .bindPopup(
                  `<b>Localização da Foto</b><br><br>Lat: ${latitudeDecimal}, <br>Lng: ${longitudeDecimal}<br><br><a href="https://www.google.com/maps/search/?api=1&query=${latitudeDecimal},${longitudeDecimal}" target="_blank" class="popup-button">Ver no Google Maps</a>`
                )
                .openPopup();
            } else {
              alert("A imagem não possui coordenadas GPS válidas.");
            }
          } else {
            alert("Esta imagem não contém coordenadas EXIF.");
          }
        });
      }

      // Função para converter de DMS para decimal
      function convertToDecimal(degreesMinutesSeconds, reference) {
        const degrees = degreesMinutesSeconds[0];
        const minutes = degreesMinutesSeconds[1];
        const seconds = degreesMinutesSeconds[2];

        let decimal = degrees + minutes / 60 + seconds / 3600;

        // Se a referência for 'S' ou 'W', o valor é negativo
        if (reference === "S" || reference === "W") {
          decimal = -decimal;
        }

        return decimal;
      }
    </script>
  </body>
</html>
