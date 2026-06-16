/**
 * MediBook — Dark Mode Script
 * Include this on EVERY page: <script src="../assets/js/dark-mode.js"></script>
 * For root pages (index, 404): <script src="assets/js/dark-mode.js"></script>
 *
 * This script:
 *  1. Applies saved theme INSTANTLY before page renders (no flash)
 *  2. Wires up ALL .theme-toggle buttons on the page
 *  3. Saves preference to localStorage
 */

(function () {
    var THEME_KEY = 'medibook_theme';

    // Apply theme instantly — runs before DOM is fully loaded
    // This prevents the white flash when dark mode is saved
    var saved = localStorage.getItem(THEME_KEY) || 'light';
    document.documentElement.setAttribute('data-theme', saved);

    function setIcon(theme) {
        // Wait for DOM then update all toggle buttons
        document.querySelectorAll('.theme-toggle').forEach(function (btn) {
            btn.textContent = theme === 'dark' ? '☀️' : '🌙';
            btn.title       = theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode';
        });
    }

    // Wire up buttons once DOM is ready
    document.addEventListener('DOMContentLoaded', function () {
        // Set correct icon based on current theme
        setIcon(document.documentElement.getAttribute('data-theme') || 'light');

        // Attach click to all toggle buttons
        document.querySelectorAll('.theme-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var current = document.documentElement.getAttribute('data-theme');
                var next    = current === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', next);
                localStorage.setItem(THEME_KEY, next);
                setIcon(next);
            });
        });
    });
})();