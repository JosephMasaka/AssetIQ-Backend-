<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Module;

class ModuleController extends Controller
{
    public function index()
    {
        $modules = Module::orderBy('id', 'asc')->get();

        return response()->json($modules);
    }
}
