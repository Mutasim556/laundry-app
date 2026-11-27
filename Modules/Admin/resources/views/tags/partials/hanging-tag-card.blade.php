@php
    /** @var \App\Models\TagPrinterProfile $profile */
    $slotData = $slot ?? null;
    $forPdf = $forPdf ?? false;
    $tagW = $profile->tag_width_mm;
    $tagH = $profile->tag_height_mm;
    $qrMm = min($profile->qr_size_mm, max(12, $tagW - 6));
    $logoMm = min($profile->logo_size_mm, max(8, $tagW - 10));
    $bgStyle = $profile->background_printing
        ? 'background: #FFFFFF;'
        : 'background: transparent;';
    $borderStyle = $profile->show_cut_guides
        ? 'border: 0.35mm dashed #94A3B8;'
        : 'border: 0.2mm solid #E2E8F0;';
@endphp

@if($slotData === null)
    <div style="width: {{ $tagW }}mm; height: {{ $tagH }}mm; display: inline-block; vertical-align: top; box-sizing: border-box;"></div>
@else
    <div class="fg-hang-tag" style="width: {{ $tagW }}mm; height: {{ $tagH }}mm; {{ $bgStyle }} {{ $borderStyle }} border-radius: 5mm; box-sizing: border-box; padding: 3mm 2.5mm; display: inline-block; vertical-align: top; position: relative; font-family: 'DejaVu Sans', Arial, sans-serif; color: #0F172A; overflow: hidden;">
        {{-- Top Collar Punch Hole & Vertical Hanging Slot --}}
        @if($profile->show_hole_guide)
            <div style="text-align: center; margin-bottom: 1.5mm;">
                <div style="width: 11mm; height: 11mm; border-radius: 999px; border: 0.45mm solid #0284C7; background: #F8FAFC; margin: 0 auto; box-sizing: border-box;"></div>
                <div style="width: 2.2mm; height: 11mm; border-radius: 1.2mm; border: 0.4mm solid #0284C7; background: #F8FAFC; margin: -0.6mm auto 0 auto; box-sizing: border-box;"></div>
            </div>
        @else
            <div style="height: 22mm;"></div>
        @endif

        {{-- Fresh Go Brand Logo & Name --}}
        <div style="text-align: center; margin-top: 1.5mm; margin-bottom: 2.5mm;">
            <div style="display: inline-block; width: {{ max(5, round($logoMm * 0.42, 1)) }}mm; height: {{ max(4, round($logoMm * 0.32, 1)) }}mm; background: #0284C7; border-radius: 1.2mm; vertical-align: middle;"></div>
            @if($profile->show_brand_name)
                <div style="font-size: {{ $profile->font_size_pt + 2.5 }}pt; font-weight: 700; letter-spacing: 0.2pt; color: #0284C7; line-height: 1.15; margin-top: 0.8mm;">
                    Fresh <span style="color: #0F172A;">Go</span>
                </div>
                <div style="font-size: {{ max(5, $profile->font_size_pt - 2) }}pt; color: #64748B; text-transform: uppercase; letter-spacing: 0.6pt;">
                    Laundry &amp; Dry Cleaning
                </div>
            @endif
        </div>

        {{-- Narrow Collar Visual Accent Divider --}}
        <div style="width: 16mm; height: 0.5mm; background: #FFC629; margin: 0 auto 2.5mm auto; border-radius: 1mm;"></div>

        {{-- Two Writable/Reference Information Fields --}}
        @if($profile->show_booking_field)
            <div style="border: 0.35mm solid #0284C7; border-radius: 1.2mm; height: 6.2mm; margin-bottom: 1.6mm; padding: 0.8mm 1.5mm; text-align: left; font-size: {{ $profile->font_size_pt }}pt; color: #0284C7; box-sizing: border-box;">
                <span style="font-weight: 700;">REF:</span>
            </div>
        @endif

        @if($profile->show_package_field)
            <div style="border: 0.35mm solid #0284C7; border-radius: 1.2mm; height: 6.2mm; margin-bottom: 2.8mm; padding: 0.8mm 1.5mm; text-align: left; font-size: {{ $profile->font_size_pt }}pt; color: #0284C7; box-sizing: border-box;">
                <span style="font-weight: 700;">#</span> <span style="color: #94A3B8; font-size: {{ max(5, $profile->font_size_pt - 1) }}pt;">Bag / Pkg</span>
            </div>
        @endif

        {{-- High-Contrast Scannable QR Code --}}
        <div style="text-align: center; background: #FFFFFF; padding: 1mm; border-radius: 1.5mm; margin: 0 auto 2mm auto; width: {{ $qrMm + 2 }}mm;">
            <img src="{{ $slotData['qr_data_uri'] }}" alt="{{ $slotData['tag_code'] }}" style="width: {{ $qrMm }}mm; height: {{ $qrMm }}mm; display: block; margin: 0 auto;" />
        </div>

        {{-- Unique Human-Readable Tag Code Underneath --}}
        <div style="text-align: center; font-family: 'DejaVu Sans Mono', monospace; font-size: {{ $profile->code_font_size_pt }}pt; font-weight: 700; letter-spacing: 0.5pt; color: #0284C7; margin-top: 0.5mm;">
            {{ $slotData['tag_code'] }}
        </div>
    </div>
@endif
