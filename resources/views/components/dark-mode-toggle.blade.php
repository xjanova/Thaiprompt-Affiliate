<!-- Simple Dark Mode Toggle -->
<button
    type="button"
    id="darkModeToggle"
    class="flex items-center justify-center w-10 h-10 rounded-lg transition-all duration-200 hover:scale-110"
    aria-label="Toggle dark mode"
>
    <!-- Sun Icon (Light Mode) -->
    <svg id="sunIcon" class="w-5 h-5 text-gray-700 dark:text-gray-300 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
    </svg>

    <!-- Moon Icon (Dark Mode) -->
    <svg id="moonIcon" class="w-5 h-5 text-gray-700 dark:text-yellow-400 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
    </svg>
</button>

<script>
(function() {
    const toggle = document.getElementById('darkModeToggle');
    const sunIcon = document.getElementById('sunIcon');
    const moonIcon = document.getElementById('moonIcon');

    function updateIcons() {
        const isDark = document.documentElement.classList.contains('dark');
        if (isDark) {
            sunIcon.classList.remove('hidden');
            moonIcon.classList.add('hidden');
        } else {
            sunIcon.classList.add('hidden');
            moonIcon.classList.remove('hidden');
        }
    }

    toggle.addEventListener('click', function() {
        const isDark = document.documentElement.classList.contains('dark');

        if (isDark) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('darkMode', 'light');
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('darkMode', 'dark');
        }

        updateIcons();
    });

    // Initial icon state
    updateIcons();
})();
</script>
