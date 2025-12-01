<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index()
    {
        try {
            $payments = DB::table('payments')
                ->leftJoin('orders', 'payments.id_order', '=', 'orders.id_order')
                ->select('payments.*', 'orders.id_user')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Payments retrieved successfully',
                'data' => $payments
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $payment = DB::table('payments')
                ->leftJoin('orders', 'payments.id_order', '=', 'orders.id_order')
                ->select('payments.*', 'orders.id_user')
                ->where('payments.id_payment', $id)
                ->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment retrieved successfully',
                'data' => $payment
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}