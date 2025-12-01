<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryZoneController extends Controller
{
    public function index()
    {
        try {
            $zones = DB::table('delivery_zones')->get();

            return response()->json([
                'success' => true,
                'message' => 'Delivery zones retrieved successfully',
                'data' => $zones
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve delivery zones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $zone = DB::table('delivery_zones')->where('id_zone', $id)->first();

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
                'error' => $e->getMessage()
            ], 500);
        }
    }
}