# UI Design System & Template Guidelines

This document provides comprehensive guidelines for developing admin pages using our modern, professional design system. Follow these patterns to ensure consistency across all admin interfaces.

## 🎨 Design Philosophy

Our design system emphasizes:
- **Modern & Professional**: Clean, contemporary aesthetics worthy of enterprise software
- **Color-Coded Organization**: Different sections use distinct color themes for easy visual navigation
- **Interactive & Responsive**: Smooth animations, hover effects, and mobile-friendly layouts
- **Information Density**: Sticky sidebars show real-time status while maintaining clean content areas
- **Accessibility**: High contrast, clear typography, and semantic HTML

---

## 🌈 Color Palette & Gradients

### Primary Gradient Patterns

Each admin section should use a themed gradient header:

**OTP/Security Features** - Purple/Blue/Indigo
```html
<div class="bg-gradient-to-br from-purple-600 via-blue-600 to-indigo-700">
```

**LINE OA/Social Integration** - Green/Emerald/Teal
```html
<div class="bg-gradient-to-br from-green-500 via-emerald-600 to-teal-700">
```

**User Management** - Blue/Cyan
```html
<div class="bg-gradient-to-br from-blue-500 via-cyan-600 to-sky-700">
```

**Payment/Finance** - Orange/Amber/Yellow
```html
<div class="bg-gradient-to-br from-orange-500 via-amber-600 to-yellow-700">
```

**Analytics/Reports** - Violet/Purple/Fuchsia
```html
<div class="bg-gradient-to-br from-violet-500 via-purple-600 to-fuchsia-700">
```

**Settings/System** - Gray/Slate/Zinc
```html
<div class="bg-gradient-to-br from-gray-600 via-slate-700 to-zinc-800">
```

### Section Color Coding

Use distinct gradients for different sections within a page:

```html
<!-- Primary Action Section -->
<div class="bg-gradient-to-r from-green-500 to-emerald-600">

<!-- Configuration Section -->
<div class="bg-gradient-to-r from-blue-500 to-cyan-600">

<!-- Advanced Settings -->
<div class="bg-gradient-to-r from-purple-500 to-pink-600">

<!-- Danger Zone -->
<div class="bg-gradient-to-r from-orange-500 to-red-600">
```

---

## 📐 Layout Patterns

### Standard Admin Page Layout

```blade
@extends('admin.layouts.app')

@section('content')
<div class="p-6 space-y-6">
    <!-- Page Header with Gradient -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-[color-1] via-[color-2] to-[color-3] p-8 shadow-2xl">
        <!-- Pattern Background -->
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxwYXRoIGQ9Ik0zNiAxOGMzLjMxNCAwIDYgMi42ODYgNiA2cy0yLjY4NiA2LTYgNi02LTIuNjg2LTYtNiAyLjY4Ni02IDYtNiIgc3Ryb2tlPSIjZmZmIiBzdHJva2Utd2lkdGg9IjIiIG9wYWNpdHk9Ii4xIi8+PC9nPjwvc3ZnPg==')] opacity-30"></div>

        <!-- Header Content -->
        <div class="relative z-10">
            <div class="flex items-center gap-4 mb-4">
                <!-- Icon (SVG or Font Awesome) -->
                <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                    <i class="fas fa-[icon-name] text-3xl text-white"></i>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">Page Title</h1>
                    <p class="text-white/90 text-lg">Brief description of this admin section</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content Area (Left) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Content sections here -->
        </div>

        <!-- Sidebar (Right) -->
        <div class="space-y-6">
            <!-- Status sidebar, guides, etc. -->
        </div>
    </div>
</div>
@endsection
```

---

## 🧩 Component Library

### 1. Modern Toggle Switch

Replace old checkboxes with modern toggle switches:

```blade
<div class="flex items-center justify-between p-4 bg-gradient-to-r from-[color]-50 to-[color]-100 rounded-xl border border-[color]-200">
    <div class="flex items-center gap-3">
        <i class="fas fa-[icon] text-[color]-500 text-xl"></i>
        <div>
            <p class="font-semibold text-gray-900">Feature Name</p>
            <p class="text-sm text-gray-600">Feature description</p>
        </div>
    </div>
    <label class="relative inline-flex items-center cursor-pointer">
        <input type="checkbox" name="field_name" value="1" class="sr-only peer" x-model="variableName">
        <div class="w-16 h-8 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[color]-300 rounded-full peer peer-checked:after:translate-x-8 peer-checked:bg-gradient-to-r peer-checked:from-[color]-500 peer-checked:to-[color]-600 after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all shadow-inner"></div>
    </label>
</div>
```

**Alpine.js Integration:**
```blade
<div x-data="{
    enabled: {{ old('enabled', $settings->enabled) ? 'true' : 'false' }},
    requireFeature: {{ old('require_feature', $settings->require_feature) ? 'true' : 'false' }}
}">
    <!-- Toggle switches here -->
</div>
```

### 2. Selection Cards (Radio Buttons)

For provider selection or option cards:

```blade
<div class="grid grid-cols-1 md:grid-cols-3 gap-6" x-data="{ provider: '{{ old('provider', $settings->provider) }}' }">
    @foreach(['option1' => ['name' => 'Option 1', 'color' => 'red', 'icon' => 'fa-icon'], ...] as $key => $option)
    <label class="relative cursor-pointer group">
        <input type="radio" name="provider" value="{{ $key }}" class="sr-only" x-model="provider">
        <div class="h-full p-6 border-2 rounded-2xl transition-all duration-300"
             :class="provider === '{{ $key }}' ? 'border-{{ $option['color'] }}-500 bg-{{ $option['color'] }}-50 shadow-lg transform scale-105' : 'border-gray-200 bg-gray-50 hover:border-{{ $option['color'] }}-300 hover:shadow-md'">
            <div class="flex items-center gap-3 mb-3">
                <i class="fas {{ $option['icon'] }} text-2xl"
                   :class="provider === '{{ $key }}' ? 'text-{{ $option['color'] }}-500' : 'text-gray-400'"></i>
                <h3 class="font-bold text-lg"
                    :class="provider === '{{ $key }}' ? 'text-{{ $option['color'] }}-700' : 'text-gray-700'">
                    {{ $option['name'] }}
                </h3>
            </div>
            <p class="text-sm text-gray-600">Description of this option</p>

            <!-- Selected Indicator -->
            <div class="absolute top-4 right-4">
                <div class="w-6 h-6 rounded-full border-2 transition-all"
                     :class="provider === '{{ $key }}' ? 'border-{{ $option['color'] }}-500 bg-{{ $option['color'] }}-500' : 'border-gray-300'">
                    <i class="fas fa-check text-white text-xs absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2"
                       x-show="provider === '{{ $key }}'"></i>
                </div>
            </div>
        </div>
    </label>
    @endforeach
</div>
```

### 3. Form Section Card

Consistent card style for form sections:

```blade
<div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100 animate-fade-in">
    <!-- Section Header -->
    <div class="bg-gradient-to-r from-[color-1] to-[color-2] px-6 py-4">
        <div class="flex items-center gap-3">
            <i class="fas fa-[icon] text-2xl text-white"></i>
            <h2 class="text-xl font-bold text-white">Section Title</h2>
        </div>
    </div>

    <!-- Section Content -->
    <div class="p-6 space-y-4">
        <!-- Form fields here -->
    </div>
</div>
```

### 4. Input Field with Icon

```blade
<div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
        <i class="fas fa-[icon] text-[color]-500 mr-2"></i>
        Field Label
    </label>
    <div class="relative">
        <input type="text"
               name="field_name"
               value="{{ old('field_name', $settings->field_name) }}"
               class="w-full px-4 py-3 pl-12 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[color]-500 focus:border-[color]-500 transition-all"
               placeholder="Enter value...">
        <i class="fas fa-[icon] absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
    </div>
    <p class="mt-1 text-sm text-gray-500">Helpful description or hint</p>
</div>
```

### 5. Status Sidebar (Sticky)

```blade
<div class="bg-gradient-to-br from-[color-1] to-[color-2] rounded-2xl shadow-2xl p-6 text-white sticky top-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold">Current Status</h3>
        @if($settings->is_active)
            <span class="px-3 py-1 bg-green-400 rounded-full text-xs font-bold text-green-900">Active</span>
        @else
            <span class="px-3 py-1 bg-red-500 rounded-full text-xs font-bold">Inactive</span>
        @endif
    </div>

    <div class="space-y-4">
        <!-- Status Items -->
        <div class="flex items-center justify-between py-2 border-b border-white/20">
            <span class="text-white/80">Setting Name:</span>
            <span class="font-semibold">Value</span>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="mt-6 space-y-3">
        <button type="button" class="w-full py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-xl font-semibold transition-all">
            Action Button
        </button>
    </div>
</div>
```

### 6. Setup Guide Card

```blade
<div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
        <i class="fas fa-book-open text-[color]-500"></i>
        Setup Guide
    </h3>
    <ol class="space-y-3">
        <li class="flex gap-3">
            <span class="flex-shrink-0 w-6 h-6 bg-gradient-to-br from-[color-1] to-[color-2] text-white rounded-full flex items-center justify-center text-sm font-bold">1</span>
            <span class="text-gray-700">Step description with <a href="#" class="text-[color]-500 hover:underline">link</a></span>
        </li>
        <!-- More steps... -->
    </ol>
</div>
```

### 7. Info/Warning/Tip Boxes

```blade
<!-- Info Box -->
<div class="p-4 bg-gradient-to-r from-blue-50 to-cyan-50 rounded-xl border border-blue-200">
    <div class="flex items-start gap-3">
        <i class="fas fa-info-circle text-blue-500 text-xl mt-0.5"></i>
        <div class="text-sm">
            <p class="font-semibold text-blue-900 mb-1">Info Title</p>
            <p class="text-blue-700">Information content here</p>
        </div>
    </div>
</div>

<!-- Warning Box -->
<div class="p-4 bg-gradient-to-r from-yellow-50 to-amber-50 rounded-xl border border-yellow-200">
    <div class="flex items-start gap-3">
        <i class="fas fa-exclamation-triangle text-yellow-600 text-xl mt-0.5"></i>
        <div class="text-sm">
            <p class="font-semibold text-yellow-900 mb-1">Warning Title</p>
            <p class="text-yellow-700">Warning content here</p>
        </div>
    </div>
</div>

<!-- Tip Box -->
<div class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border border-green-200">
    <div class="flex items-start gap-3">
        <i class="fas fa-lightbulb text-green-500 text-xl mt-0.5"></i>
        <div class="text-sm">
            <p class="font-semibold text-green-900 mb-1">Pro Tip</p>
            <p class="text-green-700">Tip content here</p>
        </div>
    </div>
</div>
```

### 8. Action Buttons

```blade
<!-- Primary Button -->
<button type="submit" class="px-6 py-3 bg-gradient-to-r from-[color-1] to-[color-2] text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
    <i class="fas fa-save mr-2"></i>
    Save Changes
</button>

<!-- Secondary Button -->
<button type="button" class="px-6 py-3 bg-white border-2 border-[color]-500 text-[color]-700 rounded-xl font-semibold hover:bg-[color]-50 transition-all duration-200">
    <i class="fas fa-undo mr-2"></i>
    Reset
</button>

<!-- Danger Button -->
<button type="button" class="px-6 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
    <i class="fas fa-trash mr-2"></i>
    Delete
</button>

<!-- Glass Button (for sidebars) -->
<button type="button" class="w-full py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-xl font-semibold transition-all text-white">
    <i class="fas fa-cog mr-2"></i>
    Configure
</button>
```

### 9. Modal Design

```blade
<div id="modalId" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-[color-1] to-[color-2] px-6 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-white">Modal Title</h3>
                <button onclick="closeModal()" class="text-white hover:text-white/80 transition-colors">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
        </div>

        <!-- Modal Content -->
        <div class="p-6">
            <!-- Modal content here -->
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
            <button onclick="closeModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                Cancel
            </button>
            <button class="px-4 py-2 bg-gradient-to-r from-[color-1] to-[color-2] text-white rounded-lg hover:shadow-lg transition-all">
                Confirm
            </button>
        </div>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('modalId').classList.remove('hidden');
    document.getElementById('modalId').classList.add('flex');
}

function closeModal() {
    document.getElementById('modalId').classList.add('hidden');
    document.getElementById('modalId').classList.remove('flex');
}
</script>
```

---

## 🎬 Animations & Transitions

### CSS Animations

Add to your layout file or create a separate CSS file:

```css
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.5s ease-out;
}

@keyframes slide-in-right {
    from {
        opacity: 0;
        transform: translateX(50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.animate-slide-in-right {
    animation: slide-in-right 0.6s ease-out;
}
```

### Transition Classes

```html
<!-- Hover Transform -->
<div class="transition-all duration-300 hover:scale-105 hover:shadow-xl">

<!-- Color Transition -->
<button class="transition-colors duration-200 hover:bg-blue-600">

<!-- All Properties -->
<div class="transition-all duration-300 ease-in-out">
```

---

## 📱 Responsive Design

### Grid Breakpoints

```html
<!-- Mobile First Approach -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

<!-- Responsive Padding -->
<div class="p-4 md:p-6 lg:p-8">

<!-- Hide on Mobile -->
<div class="hidden md:block">

<!-- Show Only on Mobile -->
<div class="block md:hidden">
```

### Sidebar Stacking

```html
<!-- Desktop: 2/3 content + 1/3 sidebar, Mobile: Stack -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <!-- Main content -->
    </div>
    <div>
        <!-- Sidebar -->
    </div>
</div>
```

---

## 🔧 Alpine.js Patterns

### State Management

```blade
<div x-data="{
    // Boolean states
    isActive: {{ old('is_active', $settings->is_active) ? 'true' : 'false' }},

    // String states
    provider: '{{ old('provider', $settings->provider) }}',

    // Show/hide
    showAdvanced: false,

    // Modal state
    modalOpen: false
}">
```

### Conditional Classes

```html
<div :class="isActive ? 'bg-green-100 border-green-500' : 'bg-gray-100 border-gray-300'">

<button :class="{ 'opacity-50 cursor-not-allowed': !isActive }">
```

### Show/Hide Elements

```html
<div x-show="showAdvanced" x-transition>
    <!-- Advanced settings -->
</div>

<button @click="showAdvanced = !showAdvanced">
    <span x-text="showAdvanced ? 'Hide' : 'Show'"></span> Advanced
</button>
```

---

## 📋 Complete Page Template

Here's a complete example combining all elements:

```blade
@extends('admin.layouts.app')

@section('content')
<div class="p-6 space-y-6">

    <!-- Animated Page Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 via-cyan-600 to-sky-700 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxwYXRoIGQ9Ik0zNiAxOGMzLjMxNCAwIDYgMi42ODYgNiA2cy0yLjY4NiA2LTYgNi02LTIuNjg2LTYtNiAyLjY4Ni02IDYtNiIgc3Ryb2tlPSIjZmZmIiBzdHJva2Utd2lkdGg9IjIiIG9wYWNpdHk9Ii4xIi8+PC9nPjwvc3ZnPg==')] opacity-30"></div>

        <div class="relative z-10">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                    <i class="fas fa-cog text-3xl text-white"></i>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">Feature Settings</h1>
                    <p class="text-white/90 text-lg">Configure your feature settings and preferences</p>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.feature.update') }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">

                <!-- System Control Section -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100 animate-fade-in">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-power-off text-2xl text-white"></i>
                            <h2 class="text-xl font-bold text-white">System Control</h2>
                        </div>
                    </div>

                    <div class="p-6 space-y-4" x-data="{ isActive: {{ old('is_active', $settings->is_active ?? false) ? 'true' : 'false' }} }">

                        <!-- Toggle Switch -->
                        <div class="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-emerald-100 rounded-xl border border-green-200">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-toggle-on text-green-500 text-xl"></i>
                                <div>
                                    <p class="font-semibold text-gray-900">Enable Feature</p>
                                    <p class="text-sm text-gray-600">Turn this feature on or off</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" class="sr-only peer" x-model="isActive">
                                <div class="w-16 h-8 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-8 peer-checked:bg-gradient-to-r peer-checked:from-green-500 peer-checked:to-emerald-600 after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all shadow-inner"></div>
                            </label>
                        </div>

                    </div>
                </div>

                <!-- Configuration Section -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100 animate-fade-in">
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-600 px-6 py-4">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-sliders-h text-2xl text-white"></i>
                            <h2 class="text-xl font-bold text-white">Configuration</h2>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">

                        <!-- Input Field -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-key text-blue-500 mr-2"></i>
                                API Key
                            </label>
                            <div class="relative">
                                <input type="text"
                                       name="api_key"
                                       value="{{ old('api_key', $settings->api_key ?? '') }}"
                                       class="w-full px-4 py-3 pl-12 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                       placeholder="Enter your API key...">
                                <i class="fas fa-key absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Your API credentials for this feature</p>
                        </div>

                    </div>
                </div>

                <!-- Submit Section -->
                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.dashboard') }}" class="px-6 py-3 bg-white border-2 border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition-all duration-200">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-500 to-cyan-600 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                        <i class="fas fa-save mr-2"></i>
                        Save Changes
                    </button>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="space-y-6">

                <!-- Status Card -->
                <div class="bg-gradient-to-br from-blue-600 to-cyan-700 rounded-2xl shadow-2xl p-6 text-white sticky top-6 animate-slide-in-right">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold">Current Status</h3>
                        @if($settings->is_active ?? false)
                            <span class="px-3 py-1 bg-green-400 rounded-full text-xs font-bold text-green-900">Active</span>
                        @else
                            <span class="px-3 py-1 bg-red-500 rounded-full text-xs font-bold">Inactive</span>
                        @endif
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between py-2 border-b border-white/20">
                            <span class="text-white/80">Status:</span>
                            <span class="font-semibold">{{ $settings->is_active ? 'Enabled' : 'Disabled' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Setup Guide -->
                <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-book-open text-blue-500"></i>
                        Setup Guide
                    </h3>
                    <ol class="space-y-3">
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 bg-gradient-to-br from-blue-500 to-cyan-600 text-white rounded-full flex items-center justify-center text-sm font-bold">1</span>
                            <span class="text-gray-700">Enable the feature using the toggle above</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 bg-gradient-to-br from-blue-500 to-cyan-600 text-white rounded-full flex items-center justify-center text-sm font-bold">2</span>
                            <span class="text-gray-700">Configure your API credentials</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 bg-gradient-to-br from-blue-500 to-cyan-600 text-white rounded-full flex items-center justify-center text-sm font-bold">3</span>
                            <span class="text-gray-700">Save and test your configuration</span>
                        </li>
                    </ol>
                </div>

            </div>

        </div>
    </form>

</div>

<!-- Add fade-in animation -->
<style>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.5s ease-out;
}

@keyframes slide-in-right {
    from {
        opacity: 0;
        transform: translateX(50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.animate-slide-in-right {
    animation: slide-in-right 0.6s ease-out;
}
</style>
@endsection
```

---

## ✅ Best Practices Checklist

When creating a new admin page, ensure:

- [ ] Use appropriate color gradient theme for the feature area
- [ ] Include animated page header with icon and description
- [ ] Implement sticky status sidebar showing current configuration
- [ ] Use modern toggle switches instead of checkboxes
- [ ] Add color-coded sections with gradient headers
- [ ] Include setup guide or quick start instructions
- [ ] Add info/warning boxes where helpful
- [ ] Implement proper Alpine.js state management
- [ ] Use consistent button styles (primary, secondary, danger)
- [ ] Add smooth animations and transitions
- [ ] Ensure responsive layout (mobile-friendly)
- [ ] Include helpful icons from Font Awesome
- [ ] Add form validation and error messages
- [ ] Use descriptive placeholders in input fields
- [ ] Include helpful hints/descriptions under fields
- [ ] Implement proper spacing with space-y-* classes
- [ ] Add shadow and border effects consistently
- [ ] Use rounded-xl or rounded-2xl for modern corners
- [ ] Ensure high contrast for accessibility
- [ ] Test on different screen sizes

---

## 🎯 Quick Start Example

To apply this template to a new admin page:

1. **Choose a color theme** from the palette based on feature category
2. **Copy the Complete Page Template** above
3. **Replace placeholders**:
   - `[color-1]`, `[color-2]`, `[color-3]` → your chosen colors
   - `[icon-name]` → appropriate Font Awesome icon
   - Route names and model references
4. **Add your specific form fields** using the component examples
5. **Customize the sidebar** with relevant status information
6. **Add your setup guide steps**
7. **Test responsiveness** and animations

---

## 📚 Reference Files

See these files for complete working examples:

- `/resources/views/admin/otp/settings.blade.php` - Multi-provider selection, toggle switches
- `/resources/views/admin/line-oa/index.blade.php` - Complete feature page with all elements

---

## 🔄 Continuous Improvement

This design system is living documentation. When you create new patterns or improvements:

1. Document them in this file
2. Update existing examples if better patterns emerge
3. Maintain consistency across all admin pages
4. Share new component discoveries with the team

---

**Last Updated**: 2025-11-02
**Version**: 1.0.0
**Maintainer**: Development Team
