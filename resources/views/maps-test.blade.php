<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pipeline Maps & Charts Test</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        #map { height: 500px; margin-bottom: 20px; }
        .chart-container { width: 90%; margin: auto; }
    </style>
</head>
<body>
    <h2 style="text-align:center;">Pipeline Map & Charts</h2>

    <div id="map"></div>

    <div class="chart-container">
        <h3>MW Breakdown by Project Stage</h3>
        <canvas id="stageChart"></canvas>
    </div>

    <div class="chart-container">
        <h3>MW Breakdown by COD Year</h3>
        <canvas id="yearChart"></canvas>
    </div>

    <!-- Leaflet.js -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
    // Peta awal Kanada
    var map = L.map('map').setView([56.1304, -106.3468], 4);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Ambil data pipeline dari API Laravel
    fetch('/pipeline')
        .then(res => res.json())
        .then(data => {
            // ====== TAMPILKAN MARKER DI MAP ======
            data.locations.forEach(loc => {
                if (loc.location) {
                    let [lat, lng] = loc.location.split(',');
                    L.marker([lat, lng]).addTo(map)
                        .bindPopup(`<b>${loc.name}</b><br>${loc.size} (${loc.type_id})`);
                }
            });

            // ====== CHART: MW BY PROJECT STAGE ======
            const stageLabels = data.breakdown_by_stage.map(s => s.stage);
            const stageDatasets = [];

            if (data.breakdown_by_stage.length > 0) {
                const firstRow = data.breakdown_by_stage[0];
                const typeKeys = Object.keys(firstRow).filter(k => k !== 'stage');

                typeKeys.forEach(typeKey => {
                    stageDatasets.push({
                        label: typeKey.toUpperCase(),
                        data: data.breakdown_by_stage.map(s => s[typeKey]),
                        backgroundColor: getRandomColor()
                    });
                });
            }

            new Chart(document.getElementById('stageChart'), {
                type: 'bar',
                data: {
                    labels: stageLabels,
                    datasets: stageDatasets
                },
                options: { responsive: true, plugins: { legend: { position: 'top' } } }
            });

            // ====== CHART: MW BY COD YEAR ======
            const yearLabels = data.breakdown_by_cod_year.map(y => y.year);
            const yearDatasets = [];

            if (data.breakdown_by_cod_year.length > 0) {
                const firstRowY = data.breakdown_by_cod_year[0];
                const typeKeysY = Object.keys(firstRowY).filter(k => k !== 'year');

                typeKeysY.forEach(typeKey => {
                    yearDatasets.push({
                        label: typeKey.toUpperCase(),
                        data: data.breakdown_by_cod_year.map(y => y[typeKey]),
                        backgroundColor: getRandomColor()
                    });
                });
            }

            new Chart(document.getElementById('yearChart'), {
                type: 'bar',
                data: {
                    labels: yearLabels,
                    datasets: yearDatasets
                },
                options: { responsive: true, plugins: { legend: { position: 'top' } } }
            });
        });

    // ====== FUNGSI WARNA RANDOM ======
    function getRandomColor() {
        const colors = ['#4E79A7', '#F28E2B', '#E15759', '#76B7B2', '#59A14F', '#EDC949'];
        return colors[Math.floor(Math.random() * colors.length)];
    }
    </script>
</body>
</html>
