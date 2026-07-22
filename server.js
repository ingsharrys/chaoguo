const express = require('express');
const path = require('path');

const app = express();
const PORT = 3000;

// Servir archivos estáticos de la carpeta 'public'
app.use(express.static(path.join(__dirname, 'print')));

app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'print', 'index.html'));
});

app.listen(PORT, () => {
    console.log(`Servidor escuchando en http://localhost:${PORT}`);
});
