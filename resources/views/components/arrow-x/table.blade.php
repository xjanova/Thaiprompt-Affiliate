{{--
    Arrow X Table Component

    Data table แบบ modern responsive พร้อม sorting และ actions

    @props
    - headers: array - หัวตาราง ['ชื่อ', 'อีเมล', 'สถานะ', 'จัดการ']
    - striped: bool - แถบสลับสี [default: false]
    - hover: bool - Hover effect [default: true]
    - bordered: bool - มีขอบ [default: false]
    - compact: bool - ระยะห่างน้อย [default: false]

    @slots
    - default: <tbody> rows
    - caption: หัวข้อตาราง

    @example
    <x-arrow-x.table :headers="['ชื่อ', 'อีเมล', 'สถานะ']">
        <tr>
            <td>John Doe</td>
            <td>john@example.com</td>
            <td><x-arrow-x.badge>Active</x-arrow-x.badge></td>
        </tr>
    </x-arrow-x.table>
--}}

@props([
    'headers' => [],
    'striped' => false,
    'hover' => true,
    'bordered' => false,
    'compact' => false,
])

@php
    // Table classes
    $tableClasses = 'min-w-full divide-y divide-gray-200 dark:divide-gray-700';

    // Wrapper classes
    $wrapperClasses = $bordered
        ? 'border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden'
        : 'rounded-xl overflow-hidden';
@endphp

<div {{ $attributes->merge(['class' => "bg-white dark:bg-gray-800 shadow-lg {$wrapperClasses}"]) }}>
    {{-- Caption (if provided) --}}
    @if(isset($caption))
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                {{ $caption }}
            </h3>
        </div>
    @endif

    {{-- Table wrapper for horizontal scroll --}}
    <div class="overflow-x-auto">
        <table class="{{ $tableClasses }}">
            {{-- Table Header --}}
            @if(!empty($headers))
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        @foreach($headers as $header)
                            <th scope="col" class="px-6 {{ $compact ? 'py-2' : 'py-4' }} text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                {{ $header }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
            @endif

            {{-- Table Body --}}
            <tbody class="bg-white dark:bg-gray-800 {{ $striped ? 'divide-y divide-gray-200 dark:divide-gray-700' : '' }}">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>

{{-- CSS สำหรับ table rows (ใช้ใน parent view) --}}
<style>
    /* Striped rows */
    @if($striped)
    .arrow-x-table tbody tr:nth-child(even) {
        background-color: rgba(0, 0, 0, 0.02);
    }
    .dark .arrow-x-table tbody tr:nth-child(even) {
        background-color: rgba(255, 255, 255, 0.02);
    }
    @endif

    /* Hover effect */
    @if($hover)
    .arrow-x-table tbody tr {
        transition: background-color 0.2s;
    }
    .arrow-x-table tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.03);
    }
    .dark .arrow-x-table tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.05);
    }
    @endif

    /* Table cell padding */
    .arrow-x-table tbody td {
        padding: {{ $compact ? '0.5rem' : '1rem' }} 1.5rem;
        font-size: 0.875rem;
        color: #374151;
    }
    .dark .arrow-x-table tbody td {
        color: #D1D5DB;
    }
</style>
