<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;

class DriverController extends Controller
{
    public function index() { return response()->json(Driver::all()); }
    public function show($id) {
        $d = Driver::find($id);
        if(!$d) return response()->json(['message'=>'Not found'],404);
        return response()->json($d);
    }
}
