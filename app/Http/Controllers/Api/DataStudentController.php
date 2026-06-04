<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DataStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, ['csv', 'txt'])) {
            return response()->json([
                'Success' => false,
                'Message' => 'File harus berformat CSV',
            ], 422);
        }

        try {

            $content = file_get_contents($file->getRealPath());

            // Hilangkan BOM Excel
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

            // Samakan line ending
            $content = str_replace(["\r\n", "\r"], "\n", $content);

            $lines = array_filter(explode("\n", $content));

            $rows = [];
            $errors = [];
            $lineNumber = 0;

            foreach ($lines as $line) {

                $lineNumber++;

                $data = str_getcsv($line);

                // Skip header
                if (
                    $lineNumber === 1 &&
                    !is_numeric(trim($data[0] ?? ''))
                ) {
                    continue;
                }

                if (count($data) < 2) {
                    $errors[] = "Baris {$lineNumber}: Format tidak valid";
                    continue;
                }

                $nis = trim($data[0]);
                $nama_siswa = trim($data[1]);

                if (!is_numeric($nis)) {
                    $errors[] = "Baris {$lineNumber}: NIS harus berupa angka";
                    continue;
                }

                if (empty($nama_siswa)) {
                    $errors[] = "Baris {$lineNumber}: Nama siswa kosong";
                    continue;
                }

                $rows[] = [
                    'nis' => $nis,
                    'nama_siswa' => $nama_siswa,
                ];
            }

            if (empty($rows)) {
                return response()->json([
                    'Success' => false,
                    'Message' => 'Tidak ada data valid dalam CSV',
                    'errors' => $errors,
                ], 422);
            }

           // Cek duplikat dalam file CSV
            $nisList = array_column($rows, 'nis');

            $duplicate = array_unique(
                array_diff_assoc(
                    $nisList,
                    array_unique($nisList)
                )
            );

            if (!empty($duplicate)) {
                return response()->json([
                    'Success' => false,
                    'Message' => 'CSV mengandung NIS duplikat',
                    'duplicates' => array_values($duplicate),
                ], 422);
            }

            $imported = 0;
            $skipped = 0;

            if ($mode === 'replace') {

                DB::statement('SET FOREIGN_KEY_CHECKS=0');

                DB::table('pembayaran_kas')->truncate();
                DB::table('data_students')->truncate();

                DB::statement('SET FOREIGN_KEY_CHECKS=1');

                foreach ($rows as $row) {

                    DataStudent::create([
                        'nis' => $row['nis'],
                        'nama_siswa' => $row['nama_siswa'],
                    ]);

                    $imported++;
                }
            }

            else {

                foreach ($rows as $row) {

                    if (
                        DataStudent::where('nis', $row['nis'])->exists()
                    ) {
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
                'Message' => "Import berhasil",
                'mode' => $mode,
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {

        return response()->json([
            'Success' => false,
            'Message' => $e->getMessage(),
            'Line' => $e->getLine(),
            'File' => $e->getFile(),
        ], 500);
    }
    }
}
