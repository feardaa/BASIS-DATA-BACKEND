<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DeliveryZone; // Model yang diperbarui
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class DeliveryZoneController extends Controller
{
    /**
     * Get all delivery zones (INDEX)
     */
    public function index(): JsonResponse
    {
        try {
            // Mengambil semua zona pengiriman menggunakan Eloquent
            $zones = DeliveryZone::all();

            return response()->json([
                'success' => true,
                'message' => 'Delivery zones retrieved successfully',
                'count' => $zones->count(),
                'data' => $zones
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve delivery zones',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Create a new delivery zone (STORE)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validasi Input disesuaikan dengan Model DeliveryZone terbaru
            $validator = Validator::make($request->all(), [
                'nama_zone' => 'required|string|max:100|unique:delivery_zones,nama_zone',
                'ongkir' => 'required|numeric|min:0', // Menggunakan 'ongkir'
                'estimasi_jam' => 'required|string|max:50', // Field 'estimasi_jam'
                'status' => 'required|in:active,inactive', // Field 'status'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Buat zona baru
            $zone = DeliveryZone::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Delivery zone created successfully',
                'data' => $zone
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create delivery zone',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Get single delivery zone by ID (SHOW)
     */
    public function show($id): JsonResponse
    {
        try {
            // Mencari zona berdasarkan primary key (id_zone)
            $zone = DeliveryZone::find($id);

            if (!$zone) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zone not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Zone retrieved successfully',
                'data' => $zone
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve zone',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }
    
    /**
     * Update delivery zone (UPDATE)
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $zone = DeliveryZone::find($id);

            if (!$zone) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zone not found'
                ], 404);
            }
            
            // Validasi Input disesuaikan dengan Model DeliveryZone terbaru
            $validator = Validator::make($request->all(), [
                // Pastikan nama_zone unik kecuali untuk ID zona saat ini
                'nama_zone' => 'sometimes|string|max:100|unique:delivery_zones,nama_zone,' . $id . ',id_zone',
                'ongkir' => 'sometimes|numeric|min:0', // Menggunakan 'ongkir'
                'estimasi_jam' => 'sometimes|string|max:50', // Field 'estimasi_jam'
                'status' => 'sometimes|in:active,inactive', // Field 'status'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Update data zona
            $zone->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Delivery zone updated successfully',
                'data' => $zone
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update delivery zone',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Delete delivery zone (DESTROY)
     */
    public function destroy($id): JsonResponse
    {
        try {
            $zone = DeliveryZone::find($id);

            if (!$zone) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zone not found'
                ], 404);
            }

            $zone->delete();

            return response()->json([
                'success' => true,
                'message' => 'Delivery zone deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete delivery zone',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }
}