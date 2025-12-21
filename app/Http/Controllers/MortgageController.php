<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMortgageRequest;
use App\Http\Requests\UpdateMortgageRequest;
use App\Models\Mortgage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MortgageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $mortgages = $request->user()->mortgages()
            ->orderBy('updated_at', 'desc')
            ->get(['id', 'name', 'property_value', 'created_at', 'updated_at']);

        return response()->json($mortgages);
    }

    public function store(StoreMortgageRequest $request): JsonResponse
    {
        $mortgage = $request->user()->mortgages()->create($request->validated());

        return response()->json($mortgage, 201);
    }

    public function show(Request $request, Mortgage $mortgage): JsonResponse
    {
        if ($mortgage->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json($mortgage);
    }

    public function update(UpdateMortgageRequest $request, Mortgage $mortgage): JsonResponse
    {
        if ($mortgage->user_id !== $request->user()->id) {
            abort(403);
        }

        $mortgage->update($request->validated());

        return response()->json($mortgage);
    }

    public function destroy(Request $request, Mortgage $mortgage): JsonResponse
    {
        if ($mortgage->user_id !== $request->user()->id) {
            abort(403);
        }

        $mortgage->delete();

        return response()->json(null, 204);
    }
}
