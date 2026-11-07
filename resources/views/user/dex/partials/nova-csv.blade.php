{{--
    ส่งออก CSV จากแถวที่หน้าเรนเดอร์ไว้ (ใช้กับประวัติ DEX/Staking) — โหลดครั้งเดียวต่อหน้า
    ใส่ BOM ให้ Excel อ่านภาษาไทยถูก · ครอบทุกช่องด้วย " และกันสูตร Excel (ค่าที่ขึ้นต้นด้วย = + - @)
--}}
@once
<script>
function nvmDxCsv(rows, filename, header) {
    return {
        download() {
            const cell = (v) => {
                let s = v === null || v === undefined ? '' : String(v);
                if (/^[=+\-@]/.test(s) && ! /^-?\d/.test(s)) s = "'" + s;
                return '"' + s.replace(/"/g, '""') + '"';
            };
            const lines = [header].concat(rows).map(r => r.map(cell).join(','));
            const blob = new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            setTimeout(() => { URL.revokeObjectURL(a.href); a.remove(); }, 0);
        }
    };
}
</script>
@endonce
