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
     * Modes: "replace" (delete all old data + payments, insert new) or "append" (add new, skip existing NIS)
     *
     * NOTE: MIME type validation is intentionally omitted because on Windows with
     * Microsoft Excel installed, CSV files are frequently uploaded with MIME types
     * such as "application/vnd.ms-excel" or "application/octet-stream" which do not
     * match Laravel's "mimes:csv,txt" rule. Extension validation is used instead.
     */
    public function importCSV(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:2048',
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

        // Validate by extension — MIME types are unreliable on Windows/Excel
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['csv', 'txt'])) {
            return response()->json([
                'Success' => false,
                'Message' => 'File harus berformat CSV (.csv atau .txt)',
            ], 422);
        }

        try {
            // Read raw content and strip UTF-8 BOM (\xEF\xBB\xBF) that Excel adds
            $content = file_get_contents($file->getRealPath());
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
            // Normalize all line endings to \n
            $content = str_replace(["\r\n", "\r"], "\n", $content);

            $lines = array_values(array_filter(
                explode("\n", $content),
                fn ($l) => trim($l) !== ''
            ));

            $rows = [];
            $lineNumber = 0;
            $errors = [];
            $skipped = 0;

            foreach ($lines as $line) {
                $lineNumber++;
                $data = str_getcsv($line, ',', '"');

                // Skip blank lines
                if (empty(array_filter($data, fn ($v) => trim($v) !== ''))) {
                    continue;
                }

                // Skip header row if first column is non-numeric
                if ($lineNumber === 1 && ! is_numeric(trim($data[0] ?? ''))) {
                    continue;
                }

                if (count($data) < 2) {
                    $errors[] = "Baris {$lineNumber}: Format tidak valid (butuh minimal 2 kolom)";

                    continue;
                }

                $nis = trim($data[0]);
                $nama_siswa = trim($data[1]);

                if (! is_numeric($nis)) {
                    $errors[] = "Baris {$lineNumber}: NIS '{$nis}' harus berupa angka";

                    continue;
                }

                if (empty($nama_siswa)) {
                    $errors[] = "Baris {$lineNumber}: Nama siswa tidak boleh kosong";

                    continue;
                }

                $rows[] = ['nis' => $nis, 'nama_siswa' => $nama_siswa];
            }

            if (empty($rows)) {
                return response()->json([
                    'Success' => false,
                    'Message' => 'Tidak ada data valid dalam file CSV',
                    'errors' => $errors,
                ], 422);
            }

            $imported = 0;

            if ($mode === 'replace') {
                DB::transaction(function () use ($rows, &$imported) {
                    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                    DB::table('pembayaran_kas')->delete();
                    DB::table('data_students')->delete();
                    DB::statement('ALTER TABLE pembayaran_kas AUTO_INCREMENT = 1;');
                    DB::statement('ALTER TABLE data_students AUTO_INCREMENT = 1;');
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
                // Append: insert new, skip existing NIS
                foreach ($rows as $row) {
                    if (DataStudent::where('nis', $row['nis'])->exists()) {
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
