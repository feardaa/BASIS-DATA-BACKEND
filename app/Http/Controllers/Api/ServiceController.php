<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LaundryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    /**
     * Mendapatkan daftar semua layanan.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            // Ambil semua layanan
            $services = LaundryService::all();

            return response()->json([
                'success' => true,
                'message' => 'Daftar layanan berhasil diambil',
                'data' => $services
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar layanan',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Menampilkan detail satu layanan.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            // Cari layanan berdasarkan Primary Key (id_service)
            $service = LaundryService::find($id);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Layanan tidak ditemukan'
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
     * Membuat layanan baru (STORE).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:laundry_services,name',
            'price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'description' => 'nullable|string',
            'duration_hours' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // 2. Buat Layanan
            $service = LaundryService::create($request->only([
                'name',
                'price',
                'unit',
                'description',
                'duration_hours',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Layanan berhasil dibuat',
                'data' => $service
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat layanan',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Memperbarui layanan yang sudah ada (UPDATE).
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        // 1. Cari Layanan
        $service = LaundryService::find($id);

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Layanan tidak ditemukan'
            ], 404);
        }

        // 2. Validasi Input
        $validator = Validator::make($request->all(), [
            // Pastikan nama unik kecuali untuk dirinya sendiri
            'name' => 'nullable|string|max:255|unique:laundry_services,name,' . $id . ',id_service',
            'price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'duration_hours' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // 3. Perbarui Layanan
            $service->update($request->only([
                'name',
                'price',
                'unit',
                'description',
                'duration_hours',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Layanan berhasil diperbarui',
                'data' => $service
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui layanan',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Menghapus layanan (DESTROY).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $service = LaundryService::find($id);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Layanan tidak ditemukan'
                ], 404);
            }

            $service->delete();

            return response()->json([
                'success' => true,
                'message' => 'Layanan berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus layanan',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }
}