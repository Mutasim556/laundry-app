<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'item_category_id',
        'item_id',
        'name',
        'slug',
        'price',
        'icon',
        'image',
        'images',
        'description',
        'is_active',
    ];

    protected $appends = [
        'image_url',
        'gallery_urls',
        'all_image_urls',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'images' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Default unit for this service (e.g., Per Kg, Per Piece).
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /**
     * Optional item category this service applies to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    /**
     * Optional item this service applies to.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    /**
     * Get primary image URL.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!empty($this->image)) {
            if (Str::startsWith($this->image, ['http://', 'https://'])) {
                return $this->image;
            }
            return asset('storage/' . ltrim($this->image, '/'));
        }

        // Fallback to first image in gallery if main image is empty
        $gallery = $this->images;
        if (is_array($gallery) && count($gallery) > 0) {
            $first = $gallery[0];
            if (Str::startsWith($first, ['http://', 'https://'])) {
                return $first;
            }
            return asset('storage/' . ltrim($first, '/'));
        }

        return null;
    }

    /**
     * Get array of all gallery images URLs (from the images JSON column).
     */
    public function getGalleryUrlsAttribute(): array
    {
        $urls = [];
        $gallery = $this->images;

        if (is_array($gallery)) {
            foreach ($gallery as $img) {
                if (empty($img)) continue;
                if (Str::startsWith($img, ['http://', 'https://'])) {
                    $urls[] = $img;
                } else {
                    $urls[] = asset('storage/' . ltrim($img, '/'));
                }
            }
        }

        return $urls;
    }

    /**
     * Get combined array of main image + gallery images for carousel slider.
     */
    public function getAllImageUrlsAttribute(): array
    {
        $all = [];
        if ($this->image_url) {
            $all[] = $this->image_url;
        }

        foreach ($this->gallery_urls as $url) {
            if (!in_array($url, $all)) {
                $all[] = $url;
            }
        }

        return $all;
    }

    /**
     * Generate a unique slug based on service name and optional ignore ID.
     */
    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        if (empty($baseSlug)) {
            $baseSlug = 'service';
        }

        $slug = $baseSlug;
        $counter = 1;

        while (static::where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    protected static function booted(): void
    {
        static::saving(function ($service) {
            if (empty($service->slug)) {
                $service->slug = static::generateUniqueSlug($service->name, $service->id ?? null);
            }
        });

        static::deleted(function ($service) {
            if (!empty($service->image) && !Str::startsWith($service->image, ['http://', 'https://'])) {
                Storage::disk('public')->delete($service->image);
            }

            if (is_array($service->images)) {
                foreach ($service->images as $file) {
                    if (!empty($file) && !Str::startsWith($file, ['http://', 'https://'])) {
                        Storage::disk('public')->delete($file);
                    }
                }
            }
        });
    }
}
