<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Admin\Models\Item;
use Modules\Admin\Models\ItemCategory;

class ItemController extends Controller
{
    /**
     * Display a listing of items.
     */
    public function index(): View
    {
        $categories = ItemCategory::where('is_active', true)->orderBy('name')->get();
        return view('admin::services.items.index', compact('categories'));
    }

    /**
     * Return JSON data of items for AJAX DataTable.
     */
    public function data(Request $request): JsonResponse
    {
        $query = Item::with('category:id,name,slug,icon')->latest();

        if ($request->filled('category_id')) {
            $query->where('item_category_id', $request->input('category_id'));
        }

        $items = $query->get();

        $data = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'slug' => $item->slug,
                'category_id' => $item->item_category_id,
                'category_name' => $item->category ? $item->category->name : 'Uncategorized',
                'category_icon' => $item->category ? $item->category->icon : 'mdi-tag-text-outline',
                'icon' => $item->icon ?? 'mdi-tag-text-outline',
                'image' => $item->image,
                'image_url' => $item->image_url,
                'description' => $item->description ?? '-',
                'is_active' => (bool) $item->is_active,
                'created_at' => $item->created_at ? $item->created_at->format('M d, Y') : '-',
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Check or generate unique slug for an item via AJAX.
     */
    public function checkSlug(Request $request): JsonResponse
    {
        $name = (string) $request->input('name', '');
        $requestedSlug = (string) $request->input('slug', '');
        $ignoreId = $request->filled('id') ? (int) $request->input('id') : null;

        $sourceText = !empty($requestedSlug) ? $requestedSlug : $name;
        $uniqueSlug = Item::generateUniqueSlug($sourceText, $ignoreId);

        return response()->json([
            'slug' => $uniqueSlug,
        ]);
    }

    /**
     * Store a newly created item in storage via AJAX.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_category_id' => ['required', 'exists:item_categories,id'],
            'name' => ['required', 'string', 'max:100', 'unique:items,name'],
            'slug' => ['nullable', 'string', 'max:120'],
            'icon' => ['nullable', 'string', 'max:50'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $finalSlug = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Item::generateUniqueSlug($validated['name']);

        $finalSlug = Item::generateUniqueSlug($finalSlug);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('items', 'public');
        }

        $item = Item::create([
            'item_category_id' => $validated['item_category_id'],
            'name' => $validated['name'],
            'slug' => $finalSlug,
            'icon' => $validated['icon'] ?? 'mdi-tag-text-outline',
            'image' => $imagePath,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->has('is_active') ? (bool) $request->boolean('is_active') : true,
        ]);

        $item->load('category:id,name');

        return response()->json([
            'success' => true,
            'message' => "Item '{$item->name}' created successfully.",
            'item' => $item,
        ], 201);
    }

    /**
     * Update the specified item in storage via AJAX.
     */
    public function update(Request $request, Item $item): JsonResponse
    {
        $validated = $request->validate([
            'item_category_id' => ['required', 'exists:item_categories,id'],
            'name' => ['required', 'string', 'max:100', Rule::unique('items', 'name')->ignore($item->id)],
            'slug' => ['nullable', 'string', 'max:120'],
            'icon' => ['nullable', 'string', 'max:50'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $sourceSlug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        $finalSlug = Item::generateUniqueSlug($sourceSlug, $item->id);

        $imagePath = $item->image;

        if ($request->boolean('remove_image')) {
            if (!empty($imagePath) && !Str::startsWith($imagePath, ['http://', 'https://'])) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = null;
        } elseif ($request->hasFile('image')) {
            if (!empty($imagePath) && !Str::startsWith($imagePath, ['http://', 'https://'])) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('image')->store('items', 'public');
        }

        $item->update([
            'item_category_id' => $validated['item_category_id'],
            'name' => $validated['name'],
            'slug' => $finalSlug,
            'icon' => $validated['icon'] ?? $item->icon,
            'image' => $imagePath,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->has('is_active') ? (bool) $request->boolean('is_active') : $item->is_active,
        ]);

        $item->load('category:id,name');

        return response()->json([
            'success' => true,
            'message' => "Item '{$item->name}' updated successfully.",
            'item' => $item,
        ]);
    }

    /**
     * Toggle active/inactive status of an item.
     */
    public function toggleStatus(Item $item): JsonResponse
    {
        $item->update([
            'is_active' => ! $item->is_active,
        ]);

        $status = $item->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Item '{$item->name}' {$status} successfully.",
            'is_active' => (bool) $item->is_active,
        ]);
    }

    /**
     * Remove the specified item from storage via AJAX.
     */
    public function destroy(Item $item): JsonResponse
    {
        $name = $item->name;
        $item->delete();

        return response()->json([
            'success' => true,
            'message' => "Item '{$name}' deleted successfully.",
        ]);
    }
}
