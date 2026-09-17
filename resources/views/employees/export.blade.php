<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Master Karyawan</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        h1 { text-align: center; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 5px; text-align: left; }
        th { background: #eee; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Cetak / Simpan PDF</button>
    <h1>Master Karyawan</h1>
    <table>
        <thead><tr><th>No</th><th>ID</th><th>Nama</th><th>Jabatan</th><th>Golongan</th><th>Lokasi</th><th>Status</th></tr></thead>
        <tbody>
        @foreach($employees as $index => $employee)
            <tr>
                <td>{{ $index + 1 }}</td><td>{{ $employee->employee_id }}</td><td>{{ $employee->name }}</td>
                <td>{{ $employee->jabatan?->name ?? '-' }}</td><td>{{ $employee->golongan?->name ?? '-' }}</td>
                <td>{{ $employee->lokasi?->name ?? '-' }}</td><td>{{ ucfirst($employee->status) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
