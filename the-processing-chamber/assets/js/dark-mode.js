// Enhanced dark mode functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize dark mode
    initDarkMode();
    
    // Add event listeners
    setupDarkModeToggle();
});

function initDarkMode() {
    // Check for saved theme preference or prefer OS setting
    const savedTheme = localStorage.getItem('theme');
    const osPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (savedTheme === 'dark-mode' || (!savedTheme && osPrefersDark)) {
        document.body.classList.add('dark-mode');
        updateThemeButton('dark-mode');
    }
}

function setupDarkModeToggle() {
    const themeToggle = document.getElementById('themeToggle');
    
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            
            if (document.body.classList.contains('dark-mode')) {
                localStorage.setItem('theme', 'dark-mode');
                updateThemeButton('dark-mode');
            } else {
                localStorage.removeItem('theme');
                updateThemeButton('light-mode');
            }
        });
    }
}

function updateThemeButton(theme) {
    const themeToggle = document.getElementById('themeToggle');
    
    if (themeToggle) {
        if (theme === 'dark-mode') {
            themeToggle.innerHTML = '<i class="fas fa-sun"></i> <span>Light Mode</span>';
        } else {
            themeToggle.innerHTML = '<i class="fas fa-moon"></i> <span>Dark Mode</span>';
        }
    }
}

// Listen for system theme changes
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
    if (!localStorage.getItem('theme')) {
        if (e.matches) {
            document.body.classList.add('dark-mode');
            updateThemeButton('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
            updateThemeButton('light-mode');
        }
    }
});