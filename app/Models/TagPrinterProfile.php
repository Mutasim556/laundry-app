<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Models\Admin;

class TagPrinterProfile extends Model
{
    public const PRESET_A4 = 'a4';
    public const PRESET_LETTER = 'letter';
    public const PRESET_THERMAL = 'roll';
    public const PRESET_CUSTOM = 'custom';

    protected $fillable = [
        'name',
        'is_default',
        'preset',
        'orientation',
        'paper_width_mm',
        'paper_height_mm',
        'tag_width_mm',
        'tag_height_mm',
        'margin_top_mm',
        'margin_bottom_mm',
        'margin_left_mm',
        'margin_right_mm',
        'gap_horizontal_mm',
        'gap_vertical_mm',
        'tags_per_row',
        'tags_per_column',
        'qr_size_mm',
        'logo_size_mm',
        'font_size_pt',
        'code_font_size_pt',
        'show_brand_name',
        'show_booking_field',
        'show_package_field',
        'show_cut_guides',
        'show_hole_guide',
        'background_printing',
        'created_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'paper_width_mm' => 'float',
            'paper_height_mm' => 'float',
            'tag_width_mm' => 'float',
            'tag_height_mm' => 'float',
            'margin_top_mm' => 'float',
            'margin_bottom_mm' => 'float',
            'margin_left_mm' => 'float',
            'margin_right_mm' => 'float',
            'gap_horizontal_mm' => 'float',
            'gap_vertical_mm' => 'float',
            'tags_per_row' => 'integer',
            'tags_per_column' => 'integer',
            'qr_size_mm' => 'float',
            'logo_size_mm' => 'float',
            'font_size_pt' => 'float',
            'code_font_size_pt' => 'float',
            'show_brand_name' => 'boolean',
            'show_booking_field' => 'boolean',
            'show_package_field' => 'boolean',
            'show_cut_guides' => 'boolean',
            'show_hole_guide' => 'boolean',
            'background_printing' => 'boolean',
        ];
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function getPerPageCapacityAttribute(): int
    {
        return max(1, (int) $this->tags_per_row) * max(1, (int) $this->tags_per_column);
    }
}
