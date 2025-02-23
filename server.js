const express = require('express');
const fs = require('fs');
const { ExifTool } = require('exiftool-vendored');
const multer = require('multer');
const cors = require('cors');
const app = express();

const exiftool = new ExifTool();

const dmsToDecimal = (degrees, minutes, seconds) => {
    return degrees + (minutes / 60) + (seconds / 3600);
};

const roundCoordinates = (value) => {
    return parseFloat(value.toFixed(5)); // Arredonda para 5 casas decimais
};

// Configuração do multer para upload de arquivos
const upload = multer({ dest: 'uploads/' });

app.use(cors());

app.post("/upload", upload.single("image"), async (req, res) => {
    if (!req.file) {
        return res.status(400).json({ error: "Nenhuma imagem enviada." });
    }

    const filePath = req.file.path;

    try {
        const tags = await exiftool.read(filePath);
        fs.unlinkSync(filePath); // Remove o arquivo temporário

        console.log("Metadados EXIF extraídos:", tags);

        // Verifica se os dados de GPS estão presentes
        if (!tags.GPSLatitude || !tags.GPSLongitude || !tags.GPSLatitudeRef || !tags.GPSLongitudeRef) {
            return res.status(400).json({ error: "A imagem não contém informações de GPS." });
        }

        const latitude = tags.GPSLatitude;
        const longitude = tags.GPSLongitude;
        const latitudeRef = tags.GPSLatitudeRef;  // N ou S
        const longitudeRef = tags.GPSLongitudeRef;  // E ou W

        console.log("Latitude antes de converter:", latitude);
        console.log("Longitude antes de converter:", longitude);
        console.log("LatitudeRef:", latitudeRef);
        console.log("LongitudeRef:", longitudeRef);

        // Verifica se os valores de latitude e longitude estão definidos corretamente
        if (!Array.isArray(latitude) || !Array.isArray(longitude)) {
            return res.status(400).json({ error: "Coordenadas GPS inválidas." });
        }

        let latDecimal = dmsToDecimal(latitude[0], latitude[1], latitude[2]);
        let longDecimal = dmsToDecimal(longitude[0], longitude[1], longitude[2]);

        console.log("Latitude convertida (decimal):", latDecimal);
        console.log("Longitude convertida (decimal):", longDecimal);

        // Ajusta se estiver no hemisfério sul ou oeste
        if (latitudeRef === 'S') latDecimal = -latDecimal;
        if (longitudeRef === 'W') longDecimal = -longDecimal;

        // Arredonda as coordenadas para 5 casas decimais
        latDecimal = roundCoordinates(latDecimal);
        longDecimal = roundCoordinates(longDecimal);

        console.log("Latitude final (decimal):", latDecimal);
        console.log("Longitude final (decimal):", longDecimal);

        const googleMapsUrl = `https://www.google.com/maps?q=${latDecimal},${longDecimal}`;

        res.json({
            latitude: latDecimal,
            longitude: longDecimal,
            googleMapsUrl: googleMapsUrl
        });

    } catch (error) {
        console.error("Erro ao extrair EXIF:", error);
        res.status(500).json({ error: "Erro ao extrair metadados EXIF." });
    }
});

// Inicia o servidor
const port = 3000;
app.listen(port, () => {
    console.log(`Servidor rodando na porta ${port}`);
});
