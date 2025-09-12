// Placeholder for future interactivity and form validation enhancements
document.addEventListener('DOMContentLoaded', () => {
    // Example: prevent multiple submits
    document.querySelectorAll('form').forEach(f => {
        f.addEventListener('submit', () => {
            const btn = f.querySelector('button[type="submit"]');
            if (btn) { btn.disabled = true; btn.textContent = 'Please wait…'; }
        });
    });
});


