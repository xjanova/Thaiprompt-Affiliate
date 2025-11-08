<!-- Dark Mode Initialization Script -->
<!-- Place this in <head> for no-flash dark mode -->
<script>
(function() {
    // Check localStorage first, then system preference
    const darkMode = localStorage.getItem('darkMode');
    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (darkMode === 'dark' || (!darkMode && systemPrefersDark)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
})();
</script>
