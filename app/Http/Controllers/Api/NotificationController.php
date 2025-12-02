<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification; // Import Model Notifikasi Anda
use Illuminate\Support\Facades\Auth; // Digunakan untuk mendapatkan ID user yang sedang login

class NotificationController extends Controller
{
    /**
     * Menampilkan daftar semua notifikasi untuk user yang sedang login.
     * Secara opsional, bisa difilter berdasarkan status (belum dibaca/sudah dibaca).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Pastikan user sudah login
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $userId = Auth::id(); // Ambil ID user yang sedang login
        
        $query = Notification::where('id_user', $userId)
            ->with('users'); // Memuat relasi user jika diperlukan

        // Filter opsional berdasarkan status (contoh: /api/notifications?status=0)
        if ($request->has('status') && in_array($request->status, [0, 1])) {
            $query->where('status', $request->status);
        }

        // Ambil notifikasi, urutkan dari yang terbaru
        $notifications = $query->orderBy('id_notif', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'message' => 'Daftar notifikasi berhasil diambil.'
        ]);
    }

    /**
     * Menampilkan detail satu notifikasi.
     *
     * @param  int  $id_notif
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id_notif)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $notification = Notification::where('id_notif', $id_notif)
                                    ->where('id_user', Auth::id()) // Pastikan hanya notifikasi miliknya
                                    ->first();

        if (!$notification) {
            return response()->json(['message' => 'Notifikasi tidak ditemukan.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $notification,
            'message' => 'Detail notifikasi berhasil diambil.'
        ]);
    }

    /**
     * Menandai notifikasi sebagai sudah dibaca (status = 1).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id_notif
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead($id_notif)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $notification = Notification::where('id_notif', $id_notif)
                                    ->where('id_user', Auth::id())
                                    ->first();

        if (!$notification) {
            return response()->json(['message' => 'Notifikasi tidak ditemukan.'], 404);
        }

        // Cek apakah status sudah 1
        if ($notification->status == 1) {
             return response()->json([
                'success' => true,
                'data' => $notification,
                'message' => 'Notifikasi sudah ditandai sebagai sudah dibaca.'
            ], 200);
        }


        // Update status menjadi sudah dibaca
        $notification->status = 1; 
        $notification->save();

        return response()->json([
            'success' => true,
            'data' => $notification,
            'message' => 'Notifikasi berhasil ditandai sebagai sudah dibaca.'
        ], 200);
    }
    
    /**
     * Menghapus satu notifikasi.
     *
     * @param  int  $id_notif
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id_notif)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $notification = Notification::where('id_notif', $id_notif)
                                    ->where('id_user', Auth::id())
                                    ->first();

        if (!$notification) {
            return response()->json(['message' => 'Notifikasi tidak ditemukan.'], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi berhasil dihapus.'
        ], 200);
    }
}