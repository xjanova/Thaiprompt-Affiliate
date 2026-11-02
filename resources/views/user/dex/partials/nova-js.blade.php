{{--
    ตัวช่วย JS ที่หน้า DEX/Staking ธีมโนวาใช้ร่วมกัน (โหลดครั้งเดียวต่อหน้า)
    - nvmDxPost: ส่ง JSON ไป route เว็บของสมาชิก (ใช้ session + CSRF) — ไม่เรียก /api/v1 ตรงๆ
      เพราะกลุ่ม api ไม่มี session → หน้าเว็บโดน 401 ทุกครั้ง
    - ข้อความผิดพลาดจากเซิร์ฟเวอร์แปลงเป็นภาษาไทย · ข้อความที่ไม่รู้จักแสดงเป็นข้อความกลาง
      (ไม่โชว์ข้อความ exception ดิบให้สมาชิกเห็น)
--}}
@once
<script>
(function () {
    var known = {
        'Insufficient balance': 'ยอดคงเหลือไม่พอ',
        'Slippage tolerance exceeded': 'ราคาเปลี่ยนเกินค่าที่ยอมรับได้ ลองเพิ่ม Slippage แล้วทำรายการใหม่',
        'Insufficient LP tokens': 'LP Token ไม่พอสำหรับจำนวนที่เลือก',
        'No rewards to claim': 'ยังไม่มีรางวัลให้รับ',
        'Cannot unstake yet - still locked': 'ยังอยู่ในช่วงล็อก ถอนไม่ได้',
        'Unauthenticated.': 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่'
    };

    window.nvmDxError = function (data, status) {
        if (data && typeof data.message === 'string' && /[฀-๿]/.test(data.message)) return data.message;
        var raw = data && (data.error || data.message);
        if (raw && known[raw]) return known[raw];
        if (data && data.errors) return 'ข้อมูลไม่ถูกต้อง กรุณาตรวจสอบจำนวนและตัวเลือกอีกครั้ง';
        if (status === 419) return 'หน้านี้เปิดค้างไว้นานเกินไป กรุณารีเฟรชแล้วลองใหม่';
        if (status === 429) return 'ทำรายการถี่เกินไป กรุณารอสักครู่';
        return 'ทำรายการไม่สำเร็จ กรุณาลองใหม่อีกครั้ง';
    };

    window.nvmDxPost = async function (url, body) {
        var token = document.querySelector('meta[name="csrf-token"]');
        var res;
        try {
            res = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token ? token.content : ''
                },
                body: JSON.stringify(body || {})
            });
        } catch (e) {
            throw new Error('เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ กรุณาตรวจสอบอินเทอร์เน็ต');
        }
        var data = {};
        try { data = await res.json(); } catch (e) { data = {}; }
        if (! res.ok || data.success === false) throw new Error(window.nvmDxError(data, res.status));
        return data;
    };

    // ตัวเลขแบบไทย ตัดศูนย์ท้าย (ใช้แสดงผลเท่านั้น ไม่ใช้คำนวณเงินจริง)
    window.nvmDxFmt = function (n, digits) {
        var x = parseFloat(n);
        if (! isFinite(x)) return '0';
        return x.toLocaleString('th-TH', { maximumFractionDigits: digits === undefined ? 6 : digits });
    };
})();
</script>
@endonce
