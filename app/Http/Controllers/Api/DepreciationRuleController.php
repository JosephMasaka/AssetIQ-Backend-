<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DepreciationRule;
use App\Traits\ApiResponser;

class DepreciationRuleController extends Controller
{
    use ApiResponser;

    public function index($key_id)
    {
        $rules = DepreciationRule::where('company_id', auth()->user()->getCompany())->where('depreciation_key_id', $key_id)->get();
        return $this->successResponse($rules);
    }

    public function store(Request $request)
    {
        $validate = $request->validate([
            'name'         => 'required',
            'method'       => 'required',
            'useful_life'  => 'required|numeric',
        ]);

        $rule = DepreciationRule::create(array_merge(
            $validate,
            ['company_id' => auth()->user()->getCompany()]
        ));

        return $this->successResponse($rule, 'Rule created successfully', 201);
    }
}
