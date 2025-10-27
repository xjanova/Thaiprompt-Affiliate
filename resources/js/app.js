import './bootstrap';
import Alpine from 'alpinejs';
import Swal from 'sweetalert2';
import Chart from 'chart.js/auto';

// Make Alpine available globally
window.Alpine = Alpine;
Alpine.start();

// Make SweetAlert2 available globally
window.Swal = Swal;

// Make Chart.js available globally
window.Chart = Chart;

// Configure SweetAlert2 defaults
Swal.mixin({
    customClass: {
        confirmButton: 'btn-primary',
        cancelButton: 'btn-secondary',
    },
    buttonsStyling: false,
});

// Initialize components
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips if using any
    initializeTooltips();

    // Initialize any charts
    initializeCharts();
});

function initializeTooltips() {
    // Add tooltip initialization if needed
}

function initializeCharts() {
    // Charts will be initialized by specific pages
}

// Export utilities
export { Swal, Chart, Alpine };
