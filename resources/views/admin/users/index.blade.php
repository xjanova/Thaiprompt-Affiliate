@extends('layouts.admin-v4')

@section('title', 'จัดการผู้ใช้')

@php
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Route;

    $roleColors = ['super_admin' => '#d9534f', 'admin' => '#5689b8', 'seller' => '#e0a52e', 'user' => '#5aa07e'];

    // สถิติ (total = ทั้งระบบ, ใช้งาน/ยืนยัน = หน้าปัจจุบัน ตามพฤติกรรมเดิม)
    $stTotal    = $users->total();
    $stActive   = $users->getCollection()->filter(fn ($u) => ! $u->blocked_at)->count();
    $stVerified = $users->getCollection()->filter(fn ($u) => $u->email_verified_at)->count();
    $stRoles    = $roles->count();

    $hasPerm = Route::has('admin.users.permissions');
    $hasDash = Route::has('admin.users.dashboard');

    $userData = $users->getCollection()->map(function ($u) use ($roleColors, $hasPerm, $hasDash) {
        $rn = $u->roleModel->name ?? ($u->role ?? 'user');
        return [
            'id'           => $u->id,
            'name'         => $u->name,
            'initial'      => mb_strtoupper(mb_substr($u->name ?: '?', 0, 1)),
            'email'        => $u->email,
            'verified'     => (bool) $u->email_verified_at,
            'role'         => $u->roleModel->display_name ?? $u->getRoleDisplayName(),
            'roleColor'    => $roleColors[$rn] ?? '#5aa07e',
            'memberNo'     => $u->member_number,
            'status'       => $u->blocked_at ? 'blocked' : 'active',
            'isSuperAdmin' => (bool) $u->is_super_admin,
            'created'      => optional($u->created_at)->format('d/m/Y'),
            'showUrl'      => route('admin.users.show', $u),
            'editUrl'      => route('admin.users.edit', $u),
            'permUrl'      => $hasPerm ? route('admin.users.permissions', $u) : null,
            'dashUrl'      => $hasDash ? route('admin.users.dashboard', $u) : null,
            'deleteUrl'    => route('admin.users.destroy', $u),
            'genUrl'       => route('admin.users.generate-member-number', $u),
        ];
    })->values();
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;" x-data="tpUsers(@js($userData))">

    {{-- หัวข้อ --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ผู้ใช้งานระบบ</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">จัดการผู้ใช้งาน</h1>
        </div>
        <div style="display:flex; align-items:center; gap:9px;">
            <a href="{{ route('admin.roles.index') }}" class="tp-btn"><i class="fas fa-user-shield"></i> บทบาท</a>
            <a href="{{ route('admin.users.create') }}" class="tp-btn tp-btn-primary"><i class="fas fa-user-plus"></i> เพิ่มผู้ใช้</a>
        </div>
    </div>

    {{-- KPI --}}
    @php
        $cards = [
            ['ผู้ใช้ทั้งหมด', 'Total Users', number_format($stTotal), 'fa-users'],
            ['ใช้งาน (หน้านี้)', 'Active', number_format($stActive), 'fa-circle-check'],
            ['ยืนยันอีเมล (หน้านี้)', 'Verified', number_format($stVerified), 'fa-envelope-circle-check'],
            ['บทบาททั้งหมด', 'Roles', number_format($stRoles), 'fa-user-shield'],
        ];
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px;">
        @foreach($cards as [$label, $en, $val, $icon])
            <div class="tp-card" style="padding:18px; display:flex; align-items:center; gap:14px;">
                <span class="tp-tile" style="width:46px; height:46px; border-radius:14px; font-size:18px;"><i class="fas {{ $icon }}"></i></span>
                <div style="min-width:0;">
                    <div style="font-size:12px; color:var(--ink2); font-weight:600;">{{ $label }}</div>
                    <div class="tp-num" style="font-size:24px; font-weight:800; line-height:1.1;">{{ $val }}</div>
                    <div style="font-size:10px; color:var(--ink2); opacity:.8;">{{ $en }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- master-detail --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(330px,1fr)); gap:16px; align-items:start;">

        {{-- ซ้าย --}}
        <div class="tp-card" style="padding:16px; display:flex; flex-direction:column; gap:14px;">
            <form method="GET" action="{{ route('admin.users.index') }}"
                  onsubmit="for (const el of this.elements) { if (el.name && !el.value) el.disabled = true; }">
                <div class="tp-well" style="display:flex; align-items:center; gap:10px; padding:11px 15px;">
                    <i class="fas fa-magnifying-glass" style="color:var(--ink2); font-size:13px;"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหาชื่อ · อีเมล · เลขสมาชิก…" class="tp-input" style="box-shadow:none; background:transparent; padding:0;">
                </div>
                <div style="display:flex; gap:8px; margin-top:10px;">
                    <select name="role" class="tp-well tp-input" style="flex:1; padding:9px 12px; font-size:12.5px;">
                        <option value="">ทุกบทบาท</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" @selected(request('role') == $role->id)>{{ $role->display_name }}</option>
                        @endforeach
                    </select>
                    <select name="per_page" class="tp-well tp-input" style="width:74px; padding:9px 8px; font-size:12.5px;">
                        @foreach([10, 25, 50, 100] as $pp)
                            <option value="{{ $pp }}" @selected(request('per_page', 10) == $pp)>{{ $pp }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="tp-btn tp-btn-primary tp-btn-sm" style="flex:none;">กรอง</button>
                </div>
            </form>

            <div style="display:flex; flex-direction:column; gap:7px; max-height:560px; overflow-y:auto; padding-right:2px;">
                @forelse($userData as $i => $u)
                    <button type="button" @click="select({{ $i }})"
                            class="tp-card" :style="sel.id === {{ $u['id'] }} ? 'box-shadow:var(--inset);' : 'box-shadow:var(--raise);'"
                            style="cursor:pointer; text-align:left; display:flex; align-items:center; gap:11px; padding:10px 12px; border-radius:15px;">
                        <span class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:15px;">{{ $u['initial'] }}</span>
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:13px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $u['name'] }}</div>
                            <div style="font-size:11px; color:var(--ink2); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $u['email'] }}</div>
                        </div>
                        <div style="text-align:right; flex:none;">
                            <span class="tp-pill" style="font-size:9px; color:#fff; background:{{ $u['roleColor'] }};">{{ Str::limit($u['role'], 10) }}</span>
                            <div style="display:flex; align-items:center; gap:5px; justify-content:flex-end; margin-top:5px;">
                                <span style="width:7px; height:7px; border-radius:50%; background:{{ $u['status'] === 'active' ? '#5aa07e' : '#d9534f' }};"></span>
                                <span style="font-size:10px; color:var(--ink2);">{{ $u['status'] === 'active' ? 'ปกติ' : 'ระงับ' }}</span>
                            </div>
                        </div>
                    </button>
                @empty
                    <div style="text-align:center; color:var(--ink2); padding:40px 0; font-size:13px;">
                        <i class="fas fa-inbox" style="font-size:32px; display:block; margin-bottom:8px; opacity:.5;"></i>
                        ไม่พบผู้ใช้
                    </div>
                @endforelse
            </div>

            @if($users->hasPages())
                <div class="tp-num" style="display:flex; justify-content:center;">{{ $users->withQueryString()->links() }}</div>
            @endif
        </div>

        {{-- ขวา --}}
        <div class="tp-card" style="padding:22px;" x-show="sel" x-cloak>
            <div style="display:flex; align-items:center; gap:14px;">
                <span class="tp-tile" style="width:64px; height:64px; border-radius:18px; font-size:26px;" x-text="sel.initial"></span>
                <div style="flex:1; min-width:0;">
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <span style="font-size:19px; font-weight:800;" x-text="sel.name"></span>
                        <span class="tp-pill" style="color:#fff;" :style="'background:' + sel.roleColor" x-text="sel.role"></span>
                        <span class="tp-pill" style="color:#fff; background:linear-gradient(135deg,#e6b347,#d98e3f);" x-show="sel.isSuperAdmin" x-cloak><i class="fas fa-crown"></i> Super</span>
                    </div>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:3px; overflow:hidden; text-overflow:ellipsis;" x-text="sel.email"></div>
                </div>
            </div>

            <div style="display:flex; gap:9px; margin-top:16px; flex-wrap:wrap; align-items:center;">
                <a :href="sel.showUrl" class="tp-btn tp-btn-primary tp-btn-sm"><i class="fas fa-id-card"></i> ดู</a>
                <a :href="sel.editUrl" class="tp-btn tp-btn-sm"><i class="fas fa-pen"></i> แก้ไข</a>
                <template x-if="sel.permUrl"><a :href="sel.permUrl" class="tp-btn tp-btn-sm"><i class="fas fa-key"></i> สิทธิ์</a></template>
                <template x-if="sel.dashUrl"><a :href="sel.dashUrl" class="tp-btn tp-btn-sm"><i class="fas fa-gauge"></i> แดชบอร์ด</a></template>
                <form :action="sel.deleteUrl" method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบผู้ใช้นี้?');" x-show="!sel.isSuperAdmin" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="tp-btn tp-btn-sm" style="color:#d9534f;"><i class="fas fa-trash"></i> ลบ</button>
                </form>
            </div>

            <div class="tp-divider" style="margin:18px 0;"></div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px;">
                <div>
                    <div style="font-size:10.5px; color:var(--ink2); font-weight:600;"><i class="fas fa-envelope" style="margin-right:5px;"></i>อีเมล</div>
                    <div style="font-size:13px; font-weight:600; margin-top:3px; overflow:hidden; text-overflow:ellipsis;">
                        <span x-text="sel.email"></span>
                        <span style="font-size:11px; font-weight:700; margin-left:4px;" :style="'color:' + (sel.verified ? '#5aa07e' : '#e0a52e')" x-text="sel.verified ? '✓ ยืนยันแล้ว' : '⏱ รอยืนยัน'"></span>
                    </div>
                </div>
                <div>
                    <div style="font-size:10.5px; color:var(--ink2); font-weight:600;"><i class="fas fa-hashtag" style="margin-right:5px;"></i>เลขสมาชิก</div>
                    <div class="tp-num" style="font-size:13.5px; font-weight:700; margin-top:3px;">
                        <span x-show="sel.memberNo" x-text="sel.memberNo"></span>
                        <form x-show="!sel.memberNo" :action="sel.genUrl" method="POST" onsubmit="return confirm('สร้างเลขสมาชิกให้ผู้ใช้นี้?');" style="display:inline;">
                            @csrf
                            <button type="submit" class="tp-btn tp-btn-sm" style="height:28px; padding:0 10px; font-size:11px;"><i class="fas fa-wand-magic-sparkles"></i> สร้างเลข</button>
                        </form>
                    </div>
                </div>
                <div>
                    <div style="font-size:10.5px; color:var(--ink2); font-weight:600;"><i class="fas fa-user-shield" style="margin-right:5px;"></i>บทบาท</div>
                    <div style="font-size:13.5px; font-weight:700; margin-top:3px;" :style="'color:' + sel.roleColor" x-text="sel.role"></div>
                </div>
                <div>
                    <div style="font-size:10.5px; color:var(--ink2); font-weight:600;"><i class="fas fa-calendar" style="margin-right:5px;"></i>สมัครเมื่อ</div>
                    <div class="tp-num" style="font-size:13.5px; font-weight:600; margin-top:3px;" x-text="sel.created"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function tpUsers(list) {
        return {
            members: list,
            sel: list.length ? list[0] : null,
            select(i) { this.sel = this.members[i]; },
        };
    }
</script>
@endpush
@endsection
