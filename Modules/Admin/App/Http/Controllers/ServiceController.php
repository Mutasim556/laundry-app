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
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Unit;

class ServiceController extends Controller
{
    /**
     * Display a listing of laundry services.
     */
    public function index(): View
    {
        $units = Unit::where('is_active', true)->orderBy('name')->get();
        $categories = ItemCategory::where('is_active', true)->orderBy('name')->get();
        $items = Item::where('is_active', true)->orderBy('name')->get();

        return view('admin::services.services.index', compact('units', 'categories', 'items'));
    }

    /**
     * Return JSON data of laundry services for AJAX DataTable.
     */
    public function data(Request $request): JsonResponse
    {
        $query = Service::with([
            'unit:id,name,short_name',
            'category:id,name,slug,icon',
            'item:id,name,slug,icon'
        ])->latest();

        if ($request->filled('unit_id')) {
            $query->where('unit_id', $request->input('unit_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('item_category_id', $request->input('category_id'));
        }

        $services = $query->get();

        $data = $services->map(function ($service) {
            $allImages = $service->all_image_urls;

            return [
                'id' => $service->id,
                'name' => $service->name,
                'slug' => $service->slug,
                'price' => number_format((float) $service->price, 2, '.', ''),
                'unit_id' => $service->unit_id,
                'unit_name' => $service->unit ? $service->unit->name : 'No Unit',
                'unit_short_name' => $service->unit ? $service->unit->short_name : '-',
                'item_category_id' => $service->item_category_id,
                'category_name' => $service->category ? $service->category->name : null,
                'item_id' => $service->item_id,
                'item_name' => $service->item ? $service->item->name : null,
                'icon' => $service->icon ?? 'mdi-cube-outline',
                'image' => $service->image,
                'image_url' => $service->image_url,
                'gallery_urls' => $service->gallery_urls,
                'all_images' => $allImages,
                'images_count' => count($allImages),
                'description' => $service->description ?? '-',
                'is_active' => (bool) $service->is_active,
                'created_at' => $service->created_at ? $service->created_at->format('M d, Y') : '-',
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Return items belonging to a category (or all if category is omitted) for dynamic dropdowns.
     */
    public function getItemsByCategory(Request $request): JsonResponse
    {
        $categoryId = $request->input('category_id');
        $query = Item::where('is_active', true)->orderBy('name');

        if (!empty($categoryId)) {
            $query->where('item_category_id', $categoryId);
        }

        $items = $query->get(['id', 'name', 'item_category_id']);

        return response()->json(['items' => $items]);
    }

    /**
     * Check or generate unique slug for a service via AJAX.
     */
    public function checkSlug(Request $request): JsonResponse
    {
        $name = (string) $request->input('name', '');
        $requestedSlug = (string) $request->input('slug', '');
        $ignoreId = $request->filled('id') ? (int) $request->input('id') : null;

        $sourceText = !empty($requestedSlug) ? $requestedSlug : $name;
        $uniqueSlug = Service::generateUniqueSlug($sourceText, $ignoreId);

        return response()->json([
            'slug' => $uniqueSlug,
        ]);
    }

    /**
     * Store a newly created service in storage via AJAX.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'unit_id' => ['required', 'exists:units,id'],
            'item_category_id' => ['nullable', 'exists:item_categories,id'],
            'item_id' => ['nullable', 'exists:items,id'],
            'name' => ['required', 'string', 'max:100', 'unique:services,name'],
            'slug' => ['nullable', 'string', 'max:120'],
            'price' => ['required', 'numeric', 'min:0'],
            'icon' => ['nullable', 'string', 'max:50'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'images.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $finalSlug = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Service::generateUniqueSlug($validated['name']);

        $finalSlug = Service::generateUniqueSlug($finalSlug);

        // Upload primary cover image if provided
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('services', 'public');
        }

        // Upload multiple additional gallery images
        $galleryPaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                if ($file->isValid()) {
                    $galleryPaths[] = $file->store('services', 'public');
                }
            }
        }

        // If no main image but gallery has images, assign first gallery image as primary
        if (empty($imagePath) && count($galleryPaths) > 0) {
            $imagePath = $galleryPaths[0];
        }

        $service = Service::create([
            'unit_id' => $validated['unit_id'],
            'item_category_id' => $validated['item_category_id'] ?? null,
            'item_id' => $validated['item_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $finalSlug,
            'price' => $validated['price'],
            'icon' => $validated['icon'] ?? 'mdi-cube-outline',
            'image' => $imagePath,
            'images' => count($galleryPaths) > 0 ? $galleryPaths : null,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->has('is_active') ? (bool) $request->boolean('is_active') : true,
        ]);

        $service->load(['unit:id,name,short_name', 'category:id,name', 'item:id,name']);

        return response()->json([
            'success' => true,
            'message' => "Service '{$service->name}' created successfully.",
            'service' => $service,
        ], 201);
    }

    /**
     * Update the specified service in storage via AJAX.
     */
    public function update(Request $request, Service $service): JsonResponse
    {
        $validated = $request->validate([
            'unit_id' => ['required', 'exists:units,id'],
            'item_category_id' => ['nullable', 'exists:item_categories,id'],
            'item_id' => ['nullable', 'exists:items,id'],
            'name' => ['required', 'string', 'max:100', Rule::unique('services', 'name')->ignore($service->id)],
            'slug' => ['nullable', 'string', 'max:120'],
            'price' => ['required', 'numeric', 'min:0'],
            'icon' => ['nullable', 'string', 'max:50'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'images.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'remove_gallery_images' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $sourceSlug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        $finalSlug = Service::generateUniqueSlug($sourceSlug, $service->id);

        $imagePath = $service->image;
        $galleryPaths = is_array($service->images) ? $service->images : [];

        // Handle cover image
        if ($request->boolean('remove_image')) {
            if (!empty($imagePath) && !Str::startsWith($imagePath, ['http://', 'https://'])) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = null;
        } elseif ($request->hasFile('image')) {
            if (!empty($imagePath) && !Str::startsWith($imagePath, ['http://', 'https://'])) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('image')->store('services', 'public');
        }

        // Handle gallery images
        if ($request->boolean('remove_gallery_images')) {
            foreach ($galleryPaths as $f) {
                if (!empty($f) && !Str::startsWith($f, ['http://', 'https://'])) {
                    Storage::disk('public')->delete($f);
                }
            }
            $galleryPaths = [];
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                if ($file->isValid()) {
                    $galleryPaths[] = $file->store('services', 'public');
                }
            }
        }

        // If main image became empty but gallery has images, take the first one
        if (empty($imagePath) && count($galleryPaths) > 0) {
            $imagePath = $galleryPaths[0];
        }

        $service->update([
            'unit_id' => $validated['unit_id'],
            'item_category_id' => $validated['item_category_id'] ?? null,
            'item_id' => $validated['item_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $finalSlug,
            'price' => $validated['price'],
            'icon' => $validated['icon'] ?? $service->icon,
            'image' => $imagePath,
            'images' => count($galleryPaths) > 0 ? array_values($galleryPaths) : null,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->has('is_active') ? (bool) $request->boolean('is_active') : $service->is_active,
        ]);

        $service->load(['unit:id,name,short_name', 'category:id,name', 'item:id,name']);

        return response()->json([
            'success' => true,
            'message' => "Service '{$service->name}' updated successfully.",
            'service' => $service,
        ]);
    }

    /**
     * Toggle active/inactive status of a service.
     */
    public function toggleStatus(Service $service): JsonResponse
    {
        $service->update([
            'is_active' => ! $service->is_active,
        ]);

        $status = $service->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Service '{$service->name}' {$status} successfully.",
            'is_active' => (bool) $service->is_active,
        ]);
    }

    /**
     * Remove the specified service from storage via AJAX.
     */
    public function destroy(Service $service): JsonResponse
    {
        $name = $service->name;
        $service->delete();

        return response()->json([
            'success' => true,
            'message' => "Service '{$name}' deleted successfully.",
        ]);
    }
}
