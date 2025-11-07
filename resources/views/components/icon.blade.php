@props([
    'name' => null,
    'category' => 'system',
    'type' => 'img', // img, inline, svg
    'size' => 'md', // xs, sm, md, lg, xl, 2xl
    'class' => '',
])

@php
use App\Helpers\IconHelper;

// Size mapping
$sizes = [
    'xs' => 'w-3 h-3',
    'sm' => 'w-4 h-4',
    'md' => 'w-5 h-5',
    'lg' => 'w-6 h-6',
    'xl' => 'w-8 h-8',
    '2xl' => 'w-10 h-10',
];

$sizeClass = $sizes[$size] ?? $sizes['md'];
$classes = $sizeClass . ' ' . $class;

// Get icon
if (!$name) {
    $iconUrl = IconHelper::placeholder();
    $type = 'img';
}
@endphp

@if($type === 'inline' && $name)
    {!! IconHelper::inline($name, $category, ['class' => $classes]) !!}
@elseif($type === 'svg' && $name)
    @php
        $svgContent = IconHelper::svg($name, $category);
    @endphp
    @if($svgContent)
        {!! $svgContent !!}
    @else
        <img src="{{ IconHelper::placeholder() }}" {{ $attributes->merge(['class' => $classes, 'alt' => $name]) }}>
    @endif
@else
    <img src="{{ $name ? IconHelper::url($name, $category) : IconHelper::placeholder() }}" {{ $attributes->merge(['class' => $classes, 'alt' => $name ?? 'icon']) }}>
@endif
