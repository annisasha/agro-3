<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Monitoring</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>

    <div class="container mt-5">
        <h1 class="text-center">Dashboard Monitoring Lahan Pertanian</h1>

            <form action="/dashboard" method="GET"> Lokasi :
                <select name="site_id">
                    <option value="SITE001">SITE001 - Lahan Padi</option>
                    <option value="SITE002">SITE002 - Lahan Jagung</option>
                </select>
            </form>    
        <br>
        <h2>Informasi Tanaman</h2>
        @foreach ($plants as $plant)
            <p>Nama Tanaman: {{ $plant->pl_name }}</p>
            <p>Tanggal Tanam: {{ \Carbon\Carbon::parse($plant->pl_date_planting) }}</p>
            <p>Umur Tanam: {{ $plant->age() }} HST</p>
            <p>Fase Tanam: {{ $plant->phase() }}</p>
            <p>Waktu Menuju Panen: {{ $plant->timetoHarvest() }} hari</p>
        @endforeach
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h3>Data Suhu</h3>
                        <p class="card-text">Nilai: {{ $temperatureData['read_value'] }}</p>
                        <p class="card-text">Status: {{ $temperatureData['value_status'] }}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h3>Data Kelembapan</h3>
                        <p class="card-text">Nilai: {{ $humidityData['read_value'] }}</p>
                        <p class="card-text">Status: {{ $humidityData['value_status'] }}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h3>Data Kecepatan Angin</h3>
                        <p class="card-text">Nilai: {{ $windData['read_value'] }}</p>
                        <p class="card-text">Status: {{ $windData['value_status'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h3>Data Kecerahan</h3>
                        <p class="card-text">Nilai: {{ $luxData['read_value'] }}</p>
                        <p class="card-text">Status: {{ $luxData['value_status'] }}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h3>Data Curah Hujan</h3>
                        <p class="card-text">Nilai: {{ $rainData['read_value'] }}</p>
                        <p class="card-text">Status: {{ $rainData['value_status'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
