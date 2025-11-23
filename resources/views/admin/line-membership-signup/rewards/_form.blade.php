{{-- Reward Form Component --}}
<div class="space-y-6">
    {{-- Basic Information --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            ข้อมูลพื้นฐาน
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Name --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ชื่อรางวัล <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name', $reward->name ?? '') }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500"
                       required>
                @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    คำอธิบาย
                </label>
                <textarea name="description" rows="3"
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">{{ old('description', $reward->description ?? '') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Signup Type --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ประเภทการสมัคร <span class="text-red-500">*</span>
                </label>
                <select name="signup_type" id="signup_type"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500"
                        required>
                    <option value="free" {{ old('signup_type', $reward->signup_type ?? '') == 'free' ? 'selected' : '' }}>ฟรี</option>
                    <option value="package" {{ old('signup_type', $reward->signup_type ?? '') == 'package' ? 'selected' : '' }}>แพคเกจ</option>
                    <option value="both" {{ old('signup_type', $reward->signup_type ?? '') == 'both' ? 'selected' : '' }}>ทั้งสอง</option>
                </select>
                @error('signup_type')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Reward Type --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ประเภทรางวัล <span class="text-red-500">*</span>
                </label>
                <select name="reward_type" id="reward_type"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500"
                        required>
                    <option value="wallet_points" {{ old('reward_type', $reward->reward_type ?? '') == 'wallet_points' ? 'selected' : '' }}>แต้ม Wallet</option>
                    <option value="tpix_tokens" {{ old('reward_type', $reward->reward_type ?? '') == 'tpix_tokens' ? 'selected' : '' }}>เหรียญ TPIX</option>
                    <option value="coupon" {{ old('reward_type', $reward->reward_type ?? '') == 'coupon' ? 'selected' : '' }}>คูปอง</option>
                    <option value="rank_points" {{ old('reward_type', $reward->reward_type ?? '') == 'rank_points' ? 'selected' : '' }}>คะแนน Rank</option>
                    <option value="special_benefit" {{ old('reward_type', $reward->reward_type ?? '') == 'special_benefit' ? 'selected' : '' }}>สิทธิพิเศษ</option>
                    <option value="bonus_item" {{ old('reward_type', $reward->reward_type ?? '') == 'bonus_item' ? 'selected' : '' }}>ของแถม</option>
                    <option value="free_downlines" {{ old('reward_type', $reward->reward_type ?? '') == 'free_downlines' ? 'selected' : '' }}>ดาวน์ไลน์ฟรี</option>
                    <option value="experience_points" {{ old('reward_type', $reward->reward_type ?? '') == 'experience_points' ? 'selected' : '' }}>คะแนนประสบการณ์</option>
                </select>
                @error('reward_type')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Amount --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    จำนวน <span class="text-red-500">*</span>
                </label>
                <input type="number" name="amount" value="{{ old('amount', $reward->amount ?? 0) }}" step="0.01" min="0"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500"
                       required>
                @error('amount')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Display Order --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ลำดับการแสดงผล <span class="text-red-500">*</span>
                </label>
                <input type="number" name="display_order" value="{{ old('display_order', $reward->display_order ?? 0) }}" min="0"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500"
                       required>
                @error('display_order')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Coupon Template (for coupon type) --}}
            <div id="coupon_template_field" class="md:col-span-2" style="display: none;">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Coupon Template <span class="text-red-500">*</span>
                </label>
                <select name="coupon_template_id"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    <option value="">เลือก Template</option>
                    @foreach($couponTemplates as $template)
                        <option value="{{ $template->id }}" {{ old('coupon_template_id', $reward->coupon_template_id ?? '') == $template->id ? 'selected' : '' }}>
                            {{ $template->name }} ({{ $template->getDiscountText() }})
                        </option>
                    @endforeach
                </select>
                @error('coupon_template_id')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Product (for bonus_item type) --}}
            <div id="product_field" class="md:col-span-2" style="display: none;">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    สินค้าที่แถม <span class="text-red-500">*</span>
                </label>
                <select name="product_id"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    <option value="">เลือกสินค้า</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ old('product_id', $reward->product_id ?? '') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
                @error('product_id')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Package IDs (for package signup) --}}
            <div id="package_ids_field" class="md:col-span-2" style="display: none;">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    แพคเกจที่ใช้ได้ (เว้นว่าง = ทุกแพคเกจ)
                </label>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    @foreach($packages as $package)
                        <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                            <input type="checkbox" name="package_ids[]" value="{{ $package->id }}"
                                   {{ in_array($package->id, old('package_ids', $reward->package_ids ?? [])) ? 'checked' : '' }}
                                   class="mr-2">
                            <span class="text-sm text-gray-900 dark:text-gray-100">{{ $package->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('package_ids')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Display Settings --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            การแสดงผล
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Icon --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Icon (FontAwesome class)
                </label>
                <input type="text" name="icon" value="{{ old('icon', $reward->icon ?? '') }}" placeholder="fa-gift"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    ตัวอย่าง: fa-gift, fa-coins, fa-wallet
                </p>
                @error('icon')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Badge Color --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    สี Badge <span class="text-red-500">*</span>
                </label>
                <input type="color" name="badge_color" value="{{ old('badge_color', $reward->badge_color ?? '#3B82F6') }}"
                       class="w-full h-10 px-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-blue-500"
                       required>
                @error('badge_color')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Time Settings --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            การตั้งเวลา
        </h3>

        <div class="space-y-4">
            {{-- Is Time Limited --}}
            <div>
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="is_time_limited" value="1" id="is_time_limited"
                           {{ old('is_time_limited', $reward->is_time_limited ?? false) ? 'checked' : '' }}
                           class="mr-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        จำกัดช่วงเวลา
                    </span>
                </label>
            </div>

            <div id="time_fields" class="grid grid-cols-1 md:grid-cols-2 gap-6" style="display: none;">
                {{-- Start Date --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        วันเริ่มต้น
                    </label>
                    <input type="datetime-local" name="start_date" value="{{ old('start_date', $reward->start_date?->format('Y-m-d\TH:i') ?? '') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    @error('start_date')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- End Date --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        วันสิ้นสุด
                    </label>
                    <input type="datetime-local" name="end_date" value="{{ old('end_date', $reward->end_date?->format('Y-m-d\TH:i') ?? '') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    @error('end_date')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Max Claims --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    จำนวนสูงสุดที่รับได้ (เว้นว่าง = ไม่จำกัด)
                </label>
                <input type="number" name="max_claims" value="{{ old('max_claims', $reward->max_claims ?? '') }}" min="1"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                @error('max_claims')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Notification Settings --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            การแจ้งเตือน
        </h3>

        <div class="space-y-4">
            {{-- Notify User --}}
            <div>
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="notify_user" value="1"
                           {{ old('notify_user', $reward->notify_user ?? true) ? 'checked' : '' }}
                           class="mr-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        แจ้งเตือนผู้ใช้
                    </span>
                </label>
            </div>

            {{-- Notification Message --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ข้อความแจ้งเตือน
                </label>
                <textarea name="notification_message" rows="2"
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">{{ old('notification_message', $reward->notification_message ?? '') }}</textarea>
                @error('notification_message')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Status Settings --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            สถานะ
        </h3>

        <div class="space-y-4">
            {{-- Is Active --}}
            <div>
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" value="1"
                           {{ old('is_active', $reward->is_active ?? true) ? 'checked' : '' }}
                           class="mr-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        เปิดใช้งาน
                    </span>
                </label>
            </div>

            {{-- Is Stackable --}}
            <div>
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="is_stackable" value="1"
                           {{ old('is_stackable', $reward->is_stackable ?? true) ? 'checked' : '' }}
                           class="mr-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        สามารถรับได้หลายรางวัล
                    </span>
                </label>
            </div>
        </div>
    </div>

    {{-- Submit Buttons --}}
    <div class="flex justify-end gap-4">
        <a href="{{ route('admin.line-membership-signup.rewards.index') }}"
           class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            ยกเลิก
        </a>
        <button type="submit"
                class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition shadow-lg hover:shadow-xl">
            {{ isset($reward) && $reward->exists ? 'บันทึกการแก้ไข' : 'สร้างรางวัล' }}
        </button>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle reward type change
    const rewardTypeSelect = document.getElementById('reward_type');
    const couponTemplateField = document.getElementById('coupon_template_field');
    const productField = document.getElementById('product_field');

    function updateRewardTypeFields() {
        const rewardType = rewardTypeSelect.value;

        // Hide all conditional fields
        couponTemplateField.style.display = 'none';
        productField.style.display = 'none';

        // Show relevant fields
        if (rewardType === 'coupon') {
            couponTemplateField.style.display = 'block';
        } else if (rewardType === 'bonus_item') {
            productField.style.display = 'block';
        }
    }

    rewardTypeSelect.addEventListener('change', updateRewardTypeFields);
    updateRewardTypeFields(); // Initial call

    // Handle signup type change
    const signupTypeSelect = document.getElementById('signup_type');
    const packageIdsField = document.getElementById('package_ids_field');

    function updateSignupTypeFields() {
        const signupType = signupTypeSelect.value;
        packageIdsField.style.display = (signupType === 'package' || signupType === 'both') ? 'block' : 'none';
    }

    signupTypeSelect.addEventListener('change', updateSignupTypeFields);
    updateSignupTypeFields(); // Initial call

    // Handle time limited checkbox
    const isTimeLimitedCheckbox = document.getElementById('is_time_limited');
    const timeFields = document.getElementById('time_fields');

    function updateTimeFields() {
        timeFields.style.display = isTimeLimitedCheckbox.checked ? 'grid' : 'none';
    }

    isTimeLimitedCheckbox.addEventListener('change', updateTimeFields);
    updateTimeFields(); // Initial call
});
</script>
@endpush
