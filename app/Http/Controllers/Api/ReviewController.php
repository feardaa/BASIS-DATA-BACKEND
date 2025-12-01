<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function index()
    {
        try {
            $reviews = DB::table('reviews')
                ->leftJoin('orders', 'reviews.id_order', '=', 'orders.id_order')
                ->select('reviews.*', 'orders.id_user')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Reviews retrieved successfully',
                'data' => $reviews
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reviews',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $review = DB::table('reviews')
                ->leftJoin('orders', 'reviews.id_order', '=', 'orders.id_order')
                ->select('reviews.*', 'orders.id_user')
                ->where('reviews.id_review', $id)
                ->first();

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Review not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Review retrieved successfully',
                'data' => $review
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve review',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}