<?php

namespace App\Http\Controllers\Api; 
use App\Http\Controllers\Controller;
use App\Models\LaundryService; // Pastikan Model sudah di-import
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class LaundryServiceController extends Controller
{
    /**
     * Mengambil daftar semua layanan cucian (INDEX).
     * Method: GET
     */
    public function index(): JsonResponse
    {
        try {
            // Menggunakan Eloquent Model untuk mengambil semua data
            $services = LaundryService::all();

            return response()->json([
                'success' => true,
                'message' => 'Daftar layanan cucian berhasil diambil',
                'count' => $services->count(),
                'data' => $services
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar layanan cucian',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Membuat layanan cucian baru (STORE).
     * Method: POST
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validasi Input disesuaikan dengan Model terbaru
            $validator = Validator::make($request->all(), [
                'nama_service' => 'required|string|max:150|unique:laundry_services,nama_service', 
                'kategori' => 'required|string|max:50', 
                'harga' => 'required|numeric|min:0', 
                'tipe_harga' => 'required|string|max:20|in:per_kg,per_pcs,per_item,per_unit', // Asumsi beberapa tipe harga
                'estimasi_hari' => 'required|integer|min:1', 
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Buat layanan baru
            $service = LaundryService::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Layanan cucian berhasil ditambahkan',
                'data' => $service
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan layanan cucian',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Menampilkan detail satu layanan cucian berdasarkan ID (SHOW).
     * Parameter disesuaikan dengan Primary Key Model: id_service.
     * Method: GET {id_service}
     */
    public function show($id_service): JsonResponse
    {
        try {
            // Mencari berdasarkan primary key (id_service)
            $service = LaundryService::find($id_service);

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
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Memperbarui layanan cucian (UPDATE).
     * Parameter disesuaikan dengan Primary Key Model: id_service.
     * Method: PUT/PATCH {id_service}
     */
    public function update(Request $request, $id_service): JsonResponse
    {
        try {
            $service = LaundryService::find($id_service); // Menggunakan id_service

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Layanan cucian tidak ditemukan'
                ], 404);
            }

            // Validasi Input
            $validator = Validator::make($request->all(), [
                // Pastikan nama_service unik kecuali untuk ID layanan saat ini
                'nama_service' => 'sometimes|string|max:150|unique:laundry_services,nama_service,' . $id_service . ',id_service',
                'kategori' => 'sometimes|string|max:50', 
                'harga' => 'sometimes|numeric|min:0',
                'tipe_harga' => 'sometimes|string|max:20|in:per_kg,per_pcs,per_item,per_unit',
                'estimasi_hari' => 'sometimes|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update data layanan
            $service->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Layanan cucian berhasil diperbarui',
                'data' => $service
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui layanan cucian',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Menghapus layanan cucian (DESTROY).
     * Parameter disesuaikan dengan Primary Key Model: id_service.
     * Method: DELETE {id_service}
     */
    public function destroy($id_service): JsonResponse
    {
        try {
            $service = LaundryService::find($id_service); // Menggunakan id_service

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Layanan cucian tidak ditemukan'
                ], 404);
            }

            $service->delete();

            return response()->json([
                'success' => true,
                'message' => 'Layanan cucian berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus layanan cucian',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }
}