{{--
 | สคริปต์เปลือก V4 (admin-v4 / seller-v4)
 | ตั้งโหมดมืดก่อน paint กันจอกะพริบ แล้วลงทะเบียน Alpine store 'tp' + component tpShell
 | สีทั้งหมดอยู่ใน resources/css/theme-v4.css — คีย์ tp_theme_v4 ตรงกับ layouts/user-nova
 --}}
<script>
(function () {
    const KEY = 'tp_theme_v4';
    const root = document.documentElement;
    function readDark() {
        try { return !!(JSON.parse(localStorage.getItem(KEY)) || {}).dark; } catch (e) { return false; }
    }
    root.classList.toggle('dark', readDark());

    // ลงทะเบียนหลัง alpine:init (ก่อน Alpine.start ใน app.js)
    document.addEventListener('alpine:init', () => {
        window.Alpine.store('tp', {
            dark: readDark(),
            // store 'theme' ใน app.js อาจลบ class dark ตอนโหลด จึงต้องตั้งซ้ำตรงนี้
            init() { root.classList.toggle('dark', this.dark); },
            toggleDark() {
                this.dark = !this.dark;
                root.classList.toggle('dark', this.dark);
                try { localStorage.setItem(KEY, JSON.stringify({ dark: this.dark })); } catch (e) {}
            }
        });

        window.Alpine.data('tpShell', () => ({
            vw: window.innerWidth,
            drawer: false,
            sidebarHidden: false,
            get isMobile() { return this.vw <= 860; },
            init() {
                window.addEventListener('resize', () => { this.vw = window.innerWidth; if (!this.isMobile) this.drawer = false; });
            },
            toggleSidebar() { if (this.isMobile) this.drawer = !this.drawer; else this.sidebarHidden = !this.sidebarHidden; },
            closeDrawer() { this.drawer = false; }
        }));
    });
})();
</script>
