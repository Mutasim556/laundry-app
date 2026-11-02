<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Admin\Models\ItemCategory;

class ItemCategoryController extends Controller
{
    /**
     * Display a listing of the item categories.
     */
    public function index(): View
    {
        return view('admin::services.categories.index');
    }

    /**
     * Return JSON data of item categories for AJAX DataTable.
     */
    public function data(): JsonResponse
    {
        $categories = ItemCategory::latest()->get();

        $data = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'icon' => $category->icon ?? 'mdi-tag-text-outline',
                'image' => $category->image,
                'image_url' => $category->image_url,
                'description' => $category->description ?? '-',
                'is_active' => (bool) $category->is_active,
                'created_at' => $category->created_at ? $category->created_at->format('M d, Y') : '-',
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Generate or verify a unique slug on the fly via AJAX.
     */
    public function checkSlug(Request $request): JsonResponse
    {
        $name = (string) $request->input('name', '');
        $requestedSlug = (string) $request->input('slug', '');
        $ignoreId = $request->filled('id') ? (int) $request->input('id') : null;

        $sourceText = !empty($requestedSlug) ? $requestedSlug : $name;
        $uniqueSlug = ItemCategory::generateUniqueSlug($sourceText, $ignoreId);

        return response()->json([
            'slug' => $uniqueSlug,
        ]);
    }

    /**
     * Store a newly created item category in storage via AJAX.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:item_categories,name'],
            'slug' => ['nullable', 'string', 'max:120'],
            'icon' => ['nullable', 'string', 'max:50'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $finalSlug = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : ItemCategory::generateUniqueSlug($validated['name']);

        // Guarantees uniqueness by generating suffix (-1, -2, etc.) if collision exists
        $finalSlug = ItemCategory::generateUniqueSlug($finalSlug);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('categories', 'public');
        }

        $category = ItemCategory::create([
            'name' => $validated['name'],
            'slug' => $finalSlug,
            'icon' => $validated['icon'] ?? 'mdi-tag-text-outline',
            'image' => $imagePath,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->has('is_active') ? (bool) $request->boolean('is_active') : true,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Item category '{$category->name}' created successfully.",
            'category' => $category,
        ], 201);
    }

    /**
     * Update the specified item category in storage via AJAX.
     */
    public function update(Request $request, ItemCategory $itemCategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('item_categories', 'name')->ignore($itemCategory->id)],
            'slug' => ['nullable', 'string', 'max:120'],
            'icon' => ['nullable', 'string', 'max:50'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $sourceSlug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        $finalSlug = ItemCategory::generateUniqueSlug($sourceSlug, $itemCategory->id);

        $imagePath = $itemCategory->image;

        if ($request->boolean('remove_image')) {
            if (!empty($imagePath) && !Str::startsWith($imagePath, ['http://', 'https://'])) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = null;
        } elseif ($request->hasFile('image')) {
            if (!empty($imagePath) && !Str::startsWith($imagePath, ['http://', 'https://'])) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('image')->store('categories', 'public');
        }

        $itemCategory->update([
            'name' => $validated['name'],
            'slug' => $finalSlug,
            'icon' => $validated['icon'] ?? $itemCategory->icon,
            'image' => $imagePath,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->has('is_active') ? (bool) $request->boolean('is_active') : $itemCategory->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Item category '{$itemCategory->name}' updated successfully.",
            'category' => $itemCategory,
        ]);
    }

    /**
     * Toggle active/inactive status of an item category.
     */
    public function toggleStatus(ItemCategory $itemCategory): JsonResponse
    {
        $itemCategory->update([
            'is_active' => ! $itemCategory->is_active,
        ]);

        $status = $itemCategory->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Category '{$itemCategory->name}' {$status} successfully.",
            'is_active' => (bool) $itemCategory->is_active,
        ]);
    }

    /**
     * Remove the specified item category from storage via AJAX.
     */
    public function destroy(ItemCategory $itemCategory): JsonResponse
    {
        $name = $itemCategory->name;
        $itemCategory->delete();

        return response()->json([
            'success' => true,
            'message' => "Category '{$name}' deleted successfully.",
        ]);
    }
}
