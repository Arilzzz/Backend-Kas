<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DataStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DataStudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $dataStudent = DataStudent::orderBy('nis', 'asc')->get();

        return response()->json([
            'Success' => true,
            'Data' => $dataStudent,
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_siswa' => 'required|string|max: 255',
            'nis' => 'required|integer|min:5',
        ]);

        if ($validator->fails()) {
            return response()->json(
                $validator->errors(), 422
            );
        }
        $dataStudent = DataStudent::create($request->all());

        return response()->json([
            'Succcess' => true,
            'Data' => $dataStudent,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $dataStudent = DataStudent::find($id);

        return response()->json([
            'Success' => true,
            'Data' => $dataStudent,
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DataStudent $dataStudent)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'nama_siswa' => 'required|string|max:255',
            'nis' => 'required|integer|min:5',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $dataStudent = DataStudent::find($id);
        if (! $dataStudent) {
            return response()->json([
                'Message' => 'Data tidak ditemukan',
            ], 404);
        }
        $dataStudent->update($request->all());

        return response()->json([
            'Success' => true,
            'Data' => $dataStudent,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $dataStudent = DataStudent::find($id);
        if (! $dataStudent) {
            return response()->json([
                'Message' => 'Data tidak ditemukan',
            ], 404);
        }
        $dataStudent->delete();

        return response()->json([
            'Success' => true,
            'Message' => 'Data berhasil Dihapus',
        ], 200);
    }

    /**
     * Import students from CSV file.
     * Format: nis,nama_siswa
     * Modes: "replace" (delete all old data, insert new) or "append" (add new, skip existing NIS)
     */
    public function importCSV(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:2048',
            'mode' => 'required|in:replace,append',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'Success' => false,
                'Message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $file = $request->file('file');
        $mode = $request->input('mode');

        try {
            $handle = fopen($file->getRealPath(), 'r');
            if (! $handle) {
                return response()->json([
                    'Success' => false,
                    'Message' => 'Gagal membaca file CSV',
                ], 500);
            }

            $rows = [];
            $lineNumber = 0;
            $errors = [];
            $skipped = 0;

            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $lineNumber++;

                // Skip empty lines
                if (empty(array_filter($data))) {
                    continue;
                }

                // Skip header row (if first row contains non-numeric NIS)
                if ($lineNumber === 1 && ! is_numeric(trim($data[0]))) {
                    continue;
                }

                // Validate row has at least 2 columns
                if (count($data) < 2) {
                    $errors[] = "Baris {$lineNumber}: Format tidak valid (butuh minimal 2 kolom)";

                    continue;
                }

                $nis = trim($data[0]);
                $nama_siswa = trim($data[1]);

                // Validate NIS is numeric
                if (! is_numeric($nis)) {
                    $errors[] = "Baris {$lineNumber}: NIS '{$nis}' harus berupa angka";

                    continue;
                }

                // Validate nama_siswa is not empty
                if (empty($nama_siswa)) {
                    $errors[] = "Baris {$lineNumber}: Nama siswa tidak boleh kosong";

                    continue;
                }

                $rows[] = [
                    'nis' => $nis,
                    'nama_siswa' => $nama_siswa,
                ];
            }
            fclose($handle);

            if (empty($rows)) {
                return response()->json([
                    'Success' => false,
                    'Message' => 'Tidak ada data valid dalam file CSV',
                    'errors' => $errors,
                ], 422);
            }

            $imported = 0;

            if ($mode === 'replace') {
                // Replace mode: delete all existing, insert all new
                DB::transaction(function () use ($rows, &$imported) {
                    // Temporarily disable foreign key checks to allow deleting
                    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

                    // Delete records from both tables to ensure a clean slate
                    DB::table('pembayaran_kas')->delete();
                    DB::table('data_students')->delete();

                    // Reset auto-increment IDs
                    DB::statement('ALTER TABLE pembayaran_kas AUTO_INCREMENT = 1;');
                    DB::statement('ALTER TABLE data_students AUTO_INCREMENT = 1;');

                    // Re-enable foreign key checks
                    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

                    foreach ($rows as $row) {
                        DataStudent::create([
                            'nis' => $row['nis'],
                            'nama_siswa' => $row['nama_siswa'],
                        ]);
                        $imported++;
                    }
                });
            } else {
                // Append mode: insert new ones, skip if NIS already exists
                foreach ($rows as $row) {
                    $exists = DataStudent::where('nis', $row['nis'])->exists();
                    if ($exists) {
                        $skipped++;

                        continue;
                    }
                    DataStudent::create([
                        'nis' => $row['nis'],
                        'nama_siswa' => $row['nama_siswa'],
                    ]);
                    $imported++;
                }
            }

            return response()->json([
                'Success' => true,
                'Message' => "Import berhasil! {$imported} data diimport.",
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
                'mode' => $mode,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'Success' => false,
                'Message' => 'Terjadi kesalahan saat import: '.$e->getMessage(),
            ], 500);
        }
    }
}
