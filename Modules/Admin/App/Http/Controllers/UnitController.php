<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Admin\Models\Unit;

class UnitController extends Controller
{
    /**
     * Display a listing of the units.
     */
    public function index(): View
    {
        return view('admin::services.units.index');
    }

    /**
     * Return JSON data of units for real-time AJAX DataTable.
     */
    public function data(): JsonResponse
    {
        $units = Unit::latest()->get();

        $data = $units->map(function ($unit) {
            return [
                'id' => $unit->id,
                'name' => $unit->name,
                'short_name' => $unit->short_name ?? '-',
                'description' => $unit->description ?? '-',
                'is_active' => (bool) $unit->is_active,
                'created_at' => $unit->created_at ? $unit->created_at->format('M d, Y') : '-',
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Store a newly created unit in storage via AJAX.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:units,name'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $unit = Unit::create([
            'name' => $validated['name'],
            'short_name' => $validated['short_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->has('is_active') ? (bool) $request->boolean('is_active') : true,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Unit '{$unit->name}' created successfully.",
            'unit' => $unit,
        ], 201);
    }

    /**
     * Update the specified unit in storage via AJAX.
     */
    public function update(Request $request, Unit $unit): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('units', 'name')->ignore($unit->id)],
            'short_name' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $unit->update([
            'name' => $validated['name'],
            'short_name' => $validated['short_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->has('is_active') ? (bool) $request->boolean('is_active') : $unit->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Unit '{$unit->name}' updated successfully.",
            'unit' => $unit,
        ]);
    }

    /**
     * Toggle active/inactive status of a unit.
     */
    public function toggleStatus(Unit $unit): JsonResponse
    {
        $unit->update([
            'is_active' => ! $unit->is_active,
        ]);

        $status = $unit->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Unit '{$unit->name}' {$status} successfully.",
            'is_active' => (bool) $unit->is_active,
        ]);
    }

    /**
     * Remove the specified unit from storage via AJAX.
     */
    public function destroy(Unit $unit): JsonResponse
    {
        $name = $unit->name;
        $unit->delete();

        return response()->json([
            'success' => true,
            'message' => "Unit '{$name}' deleted successfully.",
        ]);
    }
}
