<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>test</title>
</head>
<body>
    <form id="siteForm">
        <label for="site">Pilih Lokasi:</label>
        <select name="site_id" id="site" onchange="fetchData()">
            <option value="SITE000" {{ $siteId == 'SITE000' ? 'selected' : '' }}>SITE000 - </option>
            <option value="SITE001" {{ $siteId == 'SITE001' ? 'selected' : '' }}>SITE001 - Lahan Padi</option>
        </select>
    </form>

    <h2>Informasi Tanaman</h2>
        <div id="plantInfo"></div>

@foreach ($sensors as $sensor)
    <div>
        <p>Sensor ID: {{ $sensor->ds_id }}</p> <!-- Menggunakan ds_id sebagai pengidentifikasi sensor -->
        <p>Nilai Sensor: {{ $sensor->read_value }}</p> <!-- Pastikan read_value adalah kolom yang ada -->
    </div>
@endforeach
</body>
</html>




{{-- <!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Monitoring Site</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>

    <div class="container mt-5">
        <h1 class="text-center">Dashboard Monitoring Berdasarkan Lokasi</h1>

        <form id="siteForm">
            <label for="site">Pilih Lokasi:</label>
            <select name="site_id" id="site" onchange="fetchData()">
                <option value="SITE000" {{ $siteId == 'SITE000' ? 'selected' : '' }}>SITE000 - </option>
                <option value="SITE001" {{ $siteId == 'SITE001' ? 'selected' : '' }}>SITE001 - Lahan Padi</option>
            </select>
        </form>
        
        <h3 id="dashboardHeader">Dashboard for Site: {{ $siteId }}</h3>

        <h2>Informasi Tanaman</h2>
        <div id="plantInfo"></div>

        <div class="row mt-4" id="sensorData">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h3>Data Suhu</h3>
                        <p class="card-text" id="temperatureData">Nilai: N/A</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h3>Data Kelembapan</h3>
                        <p class="card-text" id="humidityData">Nilai: N/A</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h3>Data Kecepatan Angin</h3>
                        <p class="card-text" id="windData">Nilai: N/A</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h3>Data Kecerahan</h3>
                        <p class="card-text" id="luxData">Nilai: N/A</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h3>Data Curah Hujan</h3>
                        <p class="card-text" id="rainData">Nilai: N/A</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        function fetchData() {
            const siteId = $('#site').val();
            $.ajax({
                url: "{{ route('dashboard2.index') }}",
                method: "GET",
                data: { site_id: siteId },
                success: function(response) {
                    // Update the dashboard
                    $('#dashboardHeader').text(`Dashboard for Site: ${response.site_info.site_id}`);
                    $('#temperatureData').text(`Nilai: ${response.temperature.read_value} | Status: ${response.temperature.value_status}`);
                    $('#humidityData').text(`Nilai: ${response.humidity.read_value} | Status: ${response.humidity.value_status}`);
                    $('#windData').text(`Nilai: ${response.wind.read_value} | Status: ${response.wind.value_status}`);
                    $('#luxData').text(`Nilai: ${response.lux.read_value} | Status: ${response.lux.value_status}`);
                    $('#rainData').text(`Nilai: ${response.rain.read_value} | Status: ${response.rain.value_status}`);
                    
                    // Update plant information
                    const plantInfoDiv = $('#plantInfo');
                    plantInfoDiv.empty();
                    response.plant_info.forEach(plant => {
                        plantInfoDiv.append(`
                            <p>Nama Tanaman: ${plant.pl_name}</p>
                            <p>Tanggal Tanam: ${plant.pl_date_planting}</p>
                            <p>Umur Tanam: ${plant.age()} HST</p>
                            <p>Fase Tanam: ${plant.phase()}</p>
                            <p>Waktu Menuju Panen: ${plant.timetoHarvest()} hari</p>
                        `);
                    });
                },
                error: function() {
                    alert('Data tidak tersedia.');
                }
            });
        }
    </script>
</body>

</html>

 --}}
