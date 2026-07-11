@php
    $imageWidth = (int) data_get($attachment?->meta, 'image.width', 0);
    $imageHeight = (int) data_get($attachment?->meta, 'image.height', 0);
    $imageOrientation = data_get($attachment?->meta, 'image.orientation');

    if (! in_array($imageOrientation, ['portrait', 'landscape'], true)) {
        $imageOrientation = $imageHeight > $imageWidth ? 'portrait' : 'landscape';
    }
@endphp

<span @class([
    'inline-flex max-w-full overflow-hidden rounded-xl bg-black/5 dark:bg-black/20',
    'h-[24rem]' => $imageOrientation === 'portrait',
    'h-[14.625rem] sm:max-w-[26rem]' => $imageOrientation !== 'portrait',
])>
    <img
        class="block h-full w-auto max-w-full shrink-0 object-contain"
        loading="lazy"
        @if ($imageWidth > 0) width="{{ $imageWidth }}" @endif
        @if ($imageHeight > 0) height="{{ $imageHeight }}" @endif
        src="{{ $attachment?->url }}"
        alt="{{ __('wirechat::chat.labels.attachment') }}"
    >
</span>
