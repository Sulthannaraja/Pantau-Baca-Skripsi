<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'pantaubaca.index');
Route::view('/index.html', 'pantaubaca.index');
Route::view('/login', 'pantaubaca.login');
Route::view('/login.html', 'pantaubaca.login');
Route::view('/register', 'pantaubaca.register');
Route::view('/register.html', 'pantaubaca.register');
Route::view('/katalog', 'pantaubaca.katalog');
Route::view('/katalog.html', 'pantaubaca.katalog');
Route::view('/reader', 'pantaubaca.reader');
Route::view('/reader.html', 'pantaubaca.reader');
Route::view('/dashboard-siswa', 'pantaubaca.dashboard-siswa');
Route::view('/dashboard-siswa.html', 'pantaubaca.dashboard-siswa');
Route::view('/dashboard-guru', 'pantaubaca.dashboard-guru');
Route::view('/dashboard-guru.html', 'pantaubaca.dashboard-guru');
Route::view('/dashboard-admin', 'pantaubaca.dashboard-admin');
Route::view('/dashboard-admin.html', 'pantaubaca.dashboard-admin');

// Endpoint Ekspor / Backup Database Otomatis
Route::get('/backup-database-skripsi', function (\Illuminate\Http\Request $request) {
    if ($request->query('key') !== 'skripsi2026') {
        abort(403, 'Akses tidak diizinkan. Kunci keamanan salah.');
    }

    $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
    $tables = $pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(\PDO::FETCH_COLUMN);

    $sql = "-- Backup Database PantauBaca Skripsi\n";
    $sql .= "-- Waktu Ekspor: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
        $createSql = $createStmt['Create Table'] ?? '';

        $sql .= "-- --------------------------------------------------------\n";
        $sql .= "-- Struktur tabel `{$table}`\n";
        $sql .= "-- --------------------------------------------------------\n";
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sql .= $createSql . ";\n\n";

        $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $sql .= "-- Data tabel `{$table}`\n";
            $cols = array_keys($rows[0]);
            $colNames = '`' . implode('`, `', $cols) . '`';

            $chunks = array_chunk($rows, 100);
            foreach ($chunks as $chunk) {
                $valLines = [];
                foreach ($chunk as $row) {
                    $vals = [];
                    foreach ($row as $v) {
                        if (is_null($v)) {
                            $vals[] = 'NULL';
                        } else {
                            $vals[] = $pdo->quote($v);
                        }
                    }
                    $valLines[] = '(' . implode(', ', $vals) . ')';
                }
                $sql .= "INSERT INTO `{$table}` ({$colNames}) VALUES\n" . implode(",\n", $valLines) . ";\n";
            }
            $sql .= "\n";
        }
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

    $filename = 'backup_railway_pantaubaca_' . date('Y-m-d_His') . '.sql';

    return response($sql, 200, [
        'Content-Type' => 'application/sql',
        'Content-Disposition' => "attachment; filename=\"{$filename}\"",
    ]);
});

