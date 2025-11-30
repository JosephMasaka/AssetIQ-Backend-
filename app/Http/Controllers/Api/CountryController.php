<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Traits\ApiResponser;

class CountryController extends Controller
{
    use ApiResponser;

    public function index()
    {
        // Option 1: Return only active countries
        $countries = Country::where('active', true)
            ->orderBy('name')
            ->get();

        return $this->successResponse($countries, 'Countries retrieved successfully.');
    }
}
