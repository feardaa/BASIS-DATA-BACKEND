<?php

namespace App\Http\Controllers;

use App\Models\LaundryService; // Pastikan menggunakan Model
use Illuminate\Http\Request;

class LaundryServiceController extends Controller
{
    /**
     * Mengambil daftar semua layanan cucian.
     */
    public function index()
    {
        try {
            // Menggunakan Eloquent Model untuk mengambil semua data
            $services = LaundryService::all();

            return response()->json([
                'success' => true,
                'message' => 'Daftar layanan cucian berhasil diambil',
                'data' => $services
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar layanan cucian',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menampilkan detail satu layanan cucian berdasarkan ID.
     */
    public function show($id)
    {
        try {
            // Menggunakan Eloquent Model dan Primary Key yang benar: 'id_laundry_service'
            // findOrFail akan otomatis melempar 404 jika tidak ditemukan
            $service = LaundryService::find($id);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Layanan cucian tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail layanan berhasil diambil',
                'data' => $service
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail layanan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}