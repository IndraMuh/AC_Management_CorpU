@php
    $db_status = 'Gagal Terhubung ke Database (Cek .env dan phpMyAdmin)!';
    $db_success = false;
    $client_ip = request()->ip();

    try {
        DB::connection()->getPdo();
        $db_status = '✅ MySQL Connected! Database: ' . DB::getDatabaseName();
        $db_success = true;
    } catch (\Exception $e) {
        $db_status = '❌ Database Error: ' . $e->getMessage();
    }
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Server Connectivity Test</title>
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f4f4f4; }
        .container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .success { color: green; font-weight: bold; }
        .failure { color: red; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Laravel Server Test (AC-Management)</h1>
        
        <table>
            <tr>
                <th>Tes Komponen</th>
                <th>Status</th>
                <th>Detail</th>
            </tr>
            <tr>
                <td>Koneksi Jaringan</td>
                <td class="{{ $db_success ? 'success' : 'failure' }}">OK</td>
                <td>Berhasil dimuat dari IP: {{ $client_ip }}</td>
            </tr>
            <tr>
                <td>Koneksi Database</td>
                <td class="{{ $db_success ? 'success' : 'failure' }}">{{ $db_success ? 'BERHASIL' : 'GAGAL' }}</td>
                <td>{{ $db_status }}</td>
            </tr>
            <tr>
                <td>Versi PHP & Laravel</td>
                <td class="success">OK</td>
                <td>PHP v{{ PHP_VERSION }} / Laravel v{{ Illuminate\Foundation\Application::VERSION }}</td>
            </tr>
            <tr>
                <td>App Environment</td>
                <td class="success">OK</td>
                <td>{{ config('app.env') }}</td>
            </tr>
        </table>
        
        <p style="margin-top: 30px;">Halaman ini berhasil dimuat melalui Server Apache/Port Forwarding kamu.</p>
    </div>
</body>
</html>