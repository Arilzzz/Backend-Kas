<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DataStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DataStudentController extends Controller
{
/**
     * Display a listing of the resource.
     */
    public function index()
    {
        $dataStudent = DataStudent::all();

        return response()->json([
            'Success'=> true,
            'Data' => $dataStudent
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
            'status_aktif' =>'required|boolean'
        ]);

        if ($validator->fails()){
            return response()->json(
                $validator->errors(), 422
            );
        }
        $dataStudent = DataStudent::create($request->all());
        return response()->json([
            'Succcess' => true,
            'Data' => $dataStudent
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $dataStudent = DataStudent::find($id);

        return response()->json([
            'Success'=> true,
            'Data' => $dataStudent
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
            'status_aktif' => 'required|boolean'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 422);
        }

        $dataStudent = DataStudent::find($id);
        if (!$dataStudent) {
            return response()->json([
                'Message' => 'Data tidak ditemukan'
            ], 404);
        }
        $dataStudent->update($request->all());
        return response()->json([
            'Success' => true,
            'Data' => $dataStudent
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $dataStudent = DataStudent::find($id);
        if(!$dataStudent) return response()->json([
            'Message' => 'Data tidak ditemukan'
        ], 404);
        $dataStudent->delete();
        return response()->json([
            'Success' => true,
            'Message' => 'Data berhasil Dihapus'
        ], 200);
    }

}
