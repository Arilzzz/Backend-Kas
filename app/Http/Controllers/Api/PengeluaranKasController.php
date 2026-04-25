<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengeluaranKas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PengeluaranKasController extends Controller
{
/**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pengeluaran = PengeluaranKas::all();
        return response()->json([
            'Success' => true,
            'Data' => $pengeluaran
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
            'tanggal_pengeluaran' => 'required|date',
            'jumlah_pengeluaran' => 'required|integer',
            'keterangan' => 'required|text',
            'bukti_foto' => 'required|image|mines:jpg,jpeg,png|max:2048'
        ]);

        $path = null;

        if ($request->hasFile('bukti_foto')) {
            $file = $request->file('bukti_foto');
            $filename = time() . '_' . $file->getClientOriginalName(); // Adding timestamp to prevent override while keeping original name
            $path = $file->storeAs('bukti', $filename, 'public');
        }

        $pengeluaran = PengeluaranKas::create([
            'user_id' => $request->user_id,
            'tanggal_pengeluaran' => $request->tanggal_pengeluaran,
            'jumlah_pengeluaran' => $request->jumlah_pengeluaran,
            'keterangan' => $request->keterangan,
            'bukti_foto' => $path
        ]);
        return response()->json([
            'Success' => true,
            'Data' => $pengeluaran
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pengeluaran = PengeluaranKas::find($id);

        return response()->json([
            'Success' => true,
            'Data' => $pengeluaran
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PengeluaranKas $pengeluaranKas)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'tanggal_pengeluaran' => 'required|date',
            'jumlah_pengeluaran' => 'required|integer',
            'keterangan' => 'required|text',
            'bukti_foto' => 'required|image|mines:jpg,jpeg,png|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                $validator->errors()
            ], 422);
        }

        $pengeluaran = PengeluaranKas::find($id);
        if(!$pengeluaran) return response()->json([
            'message' => 'Data tidak ditemukan'
        ], 404);
        return response()->json([
            'success' => true,
            'Data' => $pengeluaran
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $pengeluaran =  PengeluaranKas::find($id);

        $pengeluaran->delete();
        return response()->json([
            'Success' => true,
            'Message' => 'Data berhasil Dihapus'
        ]);
    }

}
