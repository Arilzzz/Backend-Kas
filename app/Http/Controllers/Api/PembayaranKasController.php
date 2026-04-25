<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PembayaranKas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PembayaranKasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pembayaranKas = PembayaranKas::all();
        return response()->json([
            'Success' => true,
            'Data' => $pembayaranKas
        ]);
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
            'data_student_id' => 'required|integer',
            'tanggal_pemasukkan' => 'required|date',
            'jumlah_pemasukkan' => 'required|integer',
            'keterangan' => 'required|string'
        ]);

        $pembayaran = PembayaranKas::create($request->all());
        return response()->json([
            'Success' => true,
            'Data' => $pembayaran
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pembayaran = PembayaranKas::find($id);

        return response()->json([
            'Success' => true,
            'Data' => $pembayaran
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PembayaranKas $pembayaranKas)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'data_student_id' => 'required|integer',
            'user_id' => 'required|integer',
            'tanggal_pemasukkan' => 'required|date',
            'jumlah_pemasukkan' => 'required|integer',
            'keterangan' => 'required|text'
        ]);
        $pembayaran = PembayaranKas::find($id);
        $pembayaran->update($request->all());
        return response()->json([
            'Success' => true,
            'Data' => $pembayaran
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $pembayaran = PembayaranKas::find($id);

        $pembayaran->delete();
        return response()->json([
            'Success' => true,
            'Message' => 'Data berhasil dihapus'
        ]);
    }

}
