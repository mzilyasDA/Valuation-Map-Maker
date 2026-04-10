<!DOCTYPE html>
<html>
<head>
    <title>Valuation Map Maker</title>

    <!-- LOGO FAVICON -->
    <link rel="icon" type="image/png" href="{{ asset('maps.png') }}">


    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dom-to-image/2.6.0/dom-to-image.min.js"></script>

    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            display: flex;
            flex-direction: column;
            background-color: #0f1115;
            height: 100vh;
            color: white;
        }
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
        }

        .header-title {
            padding: 5px 20px;
            margin: 0;
            background: #0f1115;
            font-size: 24px;
            font-weight: bold;
            border-bottom: 1px solid #333;
        }

        .main-container {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        .sidebar {
            width: 30%;
            padding: 20px 25px;
            background: #111;
            box-sizing: border-box;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.5);
            z-index: 10;
        }

        .sidebar h2 { font-size: 1.1rem; margin-top: 20px; color: white; }
        .sidebar h3 { font-size: 0.9rem; color: #bbb; margin-top: 25px; }

        .sidebar input[type="text"], 
        .sidebar textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 6px;
            border: 1px solid #333;
            background: #222;
            color: white;
            box-sizing: border-box;
            font-family: inherit;
        }

        .basemap-option {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            cursor: pointer;
            font-size: 14px;
            color: #ddd;
        }

        .basemap-option input[type="radio"] {
            margin-right: 12px;
            accent-color: #28a745;
        }

        .btn-main {
            width: 100%;
            padding: 12px;
            background: #28a745;
            border: none;
            border-radius: 6px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            margin-top: 15px;
        }

        .btn-outline {
            width: 100%;
            padding: 10px;
            background: transparent;
            border: 1px solid #444;
            border-radius: 6px;
            color: #ccc;
            font-weight: normal;
            cursor: pointer;
            margin-top: 10px;
            font-size: 0.9rem;
        }

        .btn-outline:hover {
            background: #222;
            color: white;
            border-color: #666;
        }

        #map { width: 70%; height: 100%; background: #000; }

        /* --- Style Label Marker --- */
        .custom-label {
            background: white;
            border: 1px solid #333;
            border-radius: 3px;
            display: inline-block;
            width: auto;
            padding: 1px 5px;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
            color: black;
            box-shadow: 1px 1px 3px rgba(0,0,0,0.2);
            text-align: center;
            position: relative;
        }

        .custom-label::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 15px 4px 0 4px;
            border-style: solid;
            border-color: #333 transparent transparent transparent;
        }

        .label-subject { border-color: #d32f2f; color: #d32f2f; }
        .label-subject::after { border-color: #d32f2f transparent transparent transparent; }

        .label-data { border-color: #0000FF; color: #0000FF; }
        .label-data::after { border-color: #0000FF transparent transparent transparent; }
    </style>
</head>
<body>

<h1 class="header-title" style="display:flex;align-items:center;gap:1px;">
    <img src="{{ asset('maps.png') }}" width="70">
    Valuation Map Appraisal / Peta Penilaian
</h1>

<div class="main-container">
    <div class="sidebar">
        <h2>Subject Property</h2>
        <input type="text" id="subject" placeholder="-6.872775, 109.137690">

        <h2>Comparables</h2>
        <textarea id="comparables" rows="5" placeholder="-6.871224, 109.137241&#10;-6.869772, 109.135494"></textarea>

        <h3>Basemap</h3>
        <label class="basemap-option">
            <input type="radio" name="basemap" value="osm" onchange="gantiBasemap()"> OpenStreetMap
        </label>
        <label class="basemap-option">
            <input type="radio" name="basemap" value="google" checked onchange="gantiBasemap()"> Google Maps
        </label>
        <label class="basemap-option">
            <input type="radio" name="basemap" value="satellite" onchange="gantiBasemap()"> Google Satellite
        </label>

        <button class="btn-main" onclick="tampilkanPeta()">Tampilkan Peta</button>
        
        <hr style="border: 0; border-top: 1px solid #333; margin: 25px 0 10px 0;">
        
        <button class="btn-outline" onclick="unduhHTML()">Unduh Peta (HTML)</button>
        <button class="btn-outline" onclick="unduhJPEG()">Unduh Peta (JPEG HD)</button>
    </div>

    <div id="map"></div>
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
var map = L.map('map').setView([-6.87, 109.13], 13);

// ===== BASEMAP =====
var osm = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    maxZoom: 19
});
var google = L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
    subdomains:['mt0','mt1','mt2','mt3'], maxZoom: 20
});
var googleSat = L.tileLayer('https://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
    subdomains:['mt0','mt1','mt2','mt3'], maxZoom: 20
});

var currentLayer = google;
currentLayer.addTo(map);

// ===== GROUP (INI KUNCI FIX DOUBLE MARKER) =====
var markerGroup = L.layerGroup().addTo(map);

// ===== GANTI BASEMAP =====
function gantiBasemap() {
    let pilihan = document.querySelector('input[name="basemap"]:checked').value;
    map.removeLayer(currentLayer);

    if(pilihan === "osm") currentLayer = osm;
    else if(pilihan === "google") currentLayer = google;
    else currentLayer = googleSat;

    currentLayer.addTo(map);
}

// ===== PARSE =====
function parseLatLng(text) {
    let parts = text.split(',');
    return [parseFloat(parts[0]), parseFloat(parts[1])];
}

// ===== CLEAR =====
function clearMarkers() {
    markerGroup.clearLayers();
}

// ===== JARAK =====
function hitungJarak(a, b) {
    return map.distance(a, b);
}

function formatJarak(meter) {
    if (meter < 1000) return meter.toFixed(0) + " m";
    return (meter / 1000).toFixed(2) + " km";
}

// ===== MAIN =====
function tampilkanPeta() {

    clearMarkers();

    let subjectText = document.getElementById('subject').value;
    if(!subjectText) return;

    let subjectCoord = parseLatLng(subjectText);

    // SUBJECT
    var subjectLabel = L.divIcon({
        className: 'custom-div-icon',
        html: `<div class="custom-label label-subject">LOKASI</div>`,
        iconSize: null,
        iconAnchor: [30, 45]
    });

    L.marker(subjectCoord, {icon: subjectLabel}).addTo(markerGroup);

    let compText = document.getElementById('comparables').value;
    let lines = compText.split('\n');

    lines.forEach((line, index) => {

        if(line.trim() !== "") {

            let coord = parseLatLng(line);

            // MARKER DATA
            var dataLabel = L.divIcon({
                className: 'custom-div-icon',
                html: `<div class="custom-label label-data">DATA ${index + 1}</div>`,
                iconSize: null,
                iconAnchor: [30, 45]
            });

            L.marker(coord, {icon: dataLabel}).addTo(markerGroup);

            // GARIS
            L.polyline([subjectCoord, coord], {
                color: ['black','black','black','black'][index % 4],
                weight: 1,
                dashArray: '5,5'
            }).addTo(markerGroup);

            // JARAK
            var jarak = hitungJarak(subjectCoord, coord);

            var midPoint = [
                (subjectCoord[0] + coord[0]) / 2,
                (subjectCoord[1] + coord[1]) / 2
            ];

        }

    });

    map.setView(subjectCoord, 15);
}

// ===== UNDUH HTML =====
function unduhHTML() {

const subjectVal = document.getElementById('subject').value;
const compVal = document.getElementById('comparables').value;
const basemap = document.querySelector('input[name="basemap"]:checked').value;

const html = document.documentElement.outerHTML;

const blob = new Blob([html], { type: 'text/html' });
const url = URL.createObjectURL(blob);

const win = window.open(url, '_blank');
win.document.querySelector('.sidebar')?.remove();
win.document.querySelector('.header-title')?.remove();

// 🔥 inject ulang data setelah dibuka
setTimeout(() => {

    win.document.getElementById('subject').value = subjectVal;
    win.document.getElementById('comparables').value = compVal;

    let radio = win.document.querySelector('input[name="basemap"][value="'+basemap+'"]');
    if(radio) radio.checked = true;

    // jalankan ulang map
    win.gantiBasemap();
    win.tampilkanPeta();

    // full screen paksa
    win.document.body.style.margin = "0";
    win.document.getElementById('map').style.height = "100vh";

    setTimeout(() => {
        win.map.invalidateSize();
    }, 500);

}, 1000);
}
// ===== UNDUH JPEG =====
function unduhJPEG() {
    const node = document.getElementById('map');
    const btn = event.target;
    btn.innerText = "Memproses HD...";
    
    // Meningkatkan resolusi dengan properti scale
    const scale = 2; // Ganti ke 3 untuk resolusi lebih tinggi lagi
    
    domtoimage.toJpeg(node, { 
        quality: 1.0, // Kualitas maksimum
        bgcolor: '#0f1115',
        width: node.clientWidth * scale,
        height: node.clientHeight * scale,
        style: {
            transform: 'scale(' + scale + ')',
            transformOrigin: 'top left',
            width: node.clientWidth + 'px',
            height: node.clientHeight + 'px'
        }
    })
    .then(function (dataUrl) {
        const link = document.createElement('a');
        link.download = 'Peta_Penilaian_HD.jpeg';
        link.href = dataUrl;
        link.click();
        btn.innerText = "Unduh Peta (JPEG)";
    })
    .catch(function (error) {
        alert('Gagal mengunduh gambar HD.');
        console.error('Error:', error);
        btn.innerText = "Unduh Peta (JPEG)";
    });
}

// ===== LOAD =====
window.onload = function() {
    gantiBasemap();
};
</script>