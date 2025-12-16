/**
 * TTRPG Session Recap - Main JavaScript
 */

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', function () {
    // Add fade-in animations to cards
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.05}s`;
    });

    // Auto-close alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// Utility: Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Utility: Format duration
function formatDuration(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    if (hours > 0) {
        return `${hours}h ${minutes}m ${secs}s`;
    } else if (minutes > 0) {
        return `${minutes}m ${secs}s`;
    } else {
        return `${secs}s`;
    }
}

// Utility: Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Close modal on background click
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal-backdrop')) {
        const modals = document.querySelectorAll('[id$="Modal"]');
        modals.forEach(modal => modal.style.display = 'none');
    }
});

// Handle escape key to close modals
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('[id$="Modal"]');
        modals.forEach(modal => modal.style.display = 'none');
    }
});
