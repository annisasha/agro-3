<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Data Sensor</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        h1 {
            text-align: center;
        }

        .sensor-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }

        .sensor-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
            width: 300px;
            text-align: center;
        }

        .sensor-card h2 {
            margin-bottom: 10px;
            font-size: 1.5em;
        }

        .sensor-card p {
            margin: 5px 0;
            font-size: 1.1em;
        }

        .sensor-card span {
            font-weight: bold;
        }
    </style>
</head>
<body>

    <h1>Riwayat Data Sensor</h1>

    <div class="sensor-container" id="sensor-container">
        <!-- Data sensor akan ditampilkan di sini -->
    </div>

    <script>
        async function fetchRiwayatData(areaId, startDate, endDate) {
            try {
                const response = await fetch(`http://127.0.0.1:8000/api/riwayat`);
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Error fetching data');
                }

                displaySensorData(data);
            } catch (error) {
                console.error('Error:', error.message);
            }
        }

        function displaySensorData(sensorData) {
            const container = document.getElementById('sensor-container');
            container.innerHTML = ''; // Kosongkan kontainer sebelum memasukkan data baru

            sensorData.forEach(sensor => {
                const card = document.createElement('div');
                card.classList.add('sensor-card');

                card.innerHTML = `
                    <h2>Sensor ID: ${sensor.ds_id}</h2>
                    <p><span>Tanggal:</span> ${sensor.read_date}</p>
                    <p><span>Nilai:</span> ${sensor.read_value}</p>
                `;

                container.appendChild(card);
            });
        }

        // Memanggil data riwayat dari area dan rentang waktu tertentu
        fetchRiwayatData('AREA001', '2024-10-13 15:00:00', '2024-10-14 15:00:00');
    </script>

</body>
</html>
