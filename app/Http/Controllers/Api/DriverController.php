<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Driver; // Model yang diperbarui
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class DriverController extends Controller
{
    /**
     * Get all drivers (INDEX)
     */
    public function index(): JsonResponse
    {
        try {
            $drivers = Driver::all();

            return response()->json([
                'success' => true,
                'message' => 'Drivers retrieved successfully',
                'count' => $drivers->count(),
                'data' => $drivers
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve drivers',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Create a new driver (STORE)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validasi Input disesuaikan dengan Model Driver terbaru
            $validator = Validator::make($request->all(), [
                'nama' => 'required|string|max:100',
                // Kolom 'email' dihapus dari validasi
                'no_handphone' => 'required|string|max:15|unique:drivers,no_handphone',
                'plat_nomor' => 'required|string|max:10|unique:drivers,plat_nomor',
                'status_aktif' => 'required|in:0,1', // Menggunakan status_aktif (boolean)
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Buat driver baru
            $driver = Driver::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Driver created successfully',
                'data' => $driver
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create driver',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Get single driver by ID (SHOW)
     */
    public function show($id_driver): JsonResponse
    {
        try {
            // Mencari driver berdasarkan primary key (id_driver)
            $driver = Driver::find($id_driver);

            if (!$driver) {
                return response()->json([
                    'success' => false,
                    'message' => 'Driver not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Driver retrieved successfully',
                'data' => $driver
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve driver',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }
    
    /**
     * Update driver (UPDATE)
     */
    public function update(Request $request, $id_driver): JsonResponse
    {
        try {
            $driver = Driver::find($id_driver); // Menggunakan id_driver

            if (!$driver) {
                return response()->json([
                    'success' => false,
                    'message' => 'Driver not found'
                ], 404);
            }
            
            // Validasi Input
            $validator = Validator::make($request->all(), [
                'nama' => 'sometimes|string|max:100',
                // 'email' dihapus
                // Cek unik no_handphone, dan plat_nomor, kecuali untuk ID saat ini
                'no_handphone' => 'sometimes|string|max:15|unique:drivers,no_handphone,' . $id_driver . ',id_driver',
                'plat_nomor' => 'sometimes|string|max:10|unique:drivers,plat_nomor,' . $id_driver . ',id_driver',
                'status_aktif' => 'sometimes|in:0,1', // Menggunakan status_aktif
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Update data driver
            $driver->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Driver updated successfully',
                'data' => $driver
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update driver',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Delete driver (DESTROY)
     */
    public function destroy($id_driver): JsonResponse
    {
        try {
            $driver = Driver::find($id_driver); // Menggunakan id_driver

            if (!$driver) {
                return response()->json([
                    'success' => false,
                    'message' => 'Driver not found'
                ], 404);
            }

            $driver->delete();

            return response()->json([
                'success' => true,
                'message' => 'Driver deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete driver',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }
}