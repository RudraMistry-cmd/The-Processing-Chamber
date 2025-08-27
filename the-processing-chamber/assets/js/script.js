// Main script
document.addEventListener('DOMContentLoaded', function() {
    const navToggle = document.querySelector('.nav-toggle');
    const sidebar = document.querySelector('.sidebar');
    const body = document.body;

    // Ensure overlay exists only once
    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }

    function toggleSidebar() {
        if (!sidebar) return;
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        body.classList.toggle('sidebar-active');
    }

    // Sidebar collapse/expand for all resolutions (persistent)
    const sidebarCollapseToggle = document.getElementById('sidebarCollapseToggle');
    const savedCollapsed = localStorage.getItem('sidebarCollapsed') === '1';
    if (savedCollapsed) {
        document.body.classList.add('sidebar-collapsed');
    }
    if (sidebarCollapseToggle) {
        sidebarCollapseToggle.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-collapsed');
            const collapsed = document.body.classList.contains('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0');
            // toggle icon direction
            this.querySelector('i').classList.toggle('fa-angle-double-left', !collapsed);
            this.querySelector('i').classList.toggle('fa-angle-double-right', collapsed);
        });
    }

    if (navToggle && sidebar) {
        navToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleSidebar();
        });
    }

    overlay.addEventListener('click', function() { toggleSidebar(); });

    document.addEventListener('click', function(e) {
        if (sidebar && sidebar.classList.contains('active') && !sidebar.contains(e.target) && e.target !== navToggle) {
            toggleSidebar();
        }
    });

    document.querySelectorAll('.nav-menu a').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 992) toggleSidebar();
        });
    });

    // Theme initialization and toggles
    const themeToggle = document.getElementById('themeToggle');
    const headerThemeToggle = document.getElementById('headerThemeToggle');

    // Initialize theme from localStorage regardless of which toggle exists
    const initialTheme = localStorage.getItem('theme');
    if (initialTheme === 'dark-mode') {
        document.body.classList.add('dark-mode');
    }
    // Update both buttons to reflect saved theme
    updateThemeButton(initialTheme === 'dark-mode' ? 'dark-mode' : '');

    // Shared toggle handler
    function toggleTheme() {
        document.body.classList.toggle('dark-mode');
        if (document.body.classList.contains('dark-mode')) {
            localStorage.setItem('theme', 'dark-mode');
            updateThemeButton('dark-mode');
        } else {
            localStorage.removeItem('theme');
            updateThemeButton('');
        }
    }

    if (themeToggle) themeToggle.addEventListener('click', toggleTheme);
    if (headerThemeToggle) headerThemeToggle.addEventListener('click', toggleTheme);

    function updateThemeButton(theme) {
        if (themeToggle) {
            if (theme === 'dark-mode') {
                themeToggle.innerHTML = '<i class="fas fa-sun"></i> <span>Light Mode</span>';
            } else {
                themeToggle.innerHTML = '<i class="fas fa-moon"></i> <span>Dark Mode</span>';
            }
        }
        if (headerThemeToggle) {
            if (theme === 'dark-mode') {
                headerThemeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            } else {
                headerThemeToggle.innerHTML = '<i class="fas fa-moon"></i>';
            }
        }
    }

    // Add to cart animation (links that include action=add)
    const addToCartButtons = document.querySelectorAll('.btn[href*="action=add"]');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            this.style.opacity = '0.7';
            this.disabled = true;
            setTimeout(() => {
                window.location.href = this.href;
                this.innerHTML = originalText;
                this.style.opacity = '1';
                this.disabled = false;
            }, 700);
        });
    });

    // Smooth anchor scrolling
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
});

// Initialize tooltips
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(el => {
        el.addEventListener('mouseenter', showTooltip);
        el.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(e) {
    const tooltipText = this.getAttribute('data-tooltip');
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = tooltipText;
    document.body.appendChild(tooltip);
    
    const rect = this.getBoundingClientRect();
    tooltip.style.left = rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
}

function hideTooltip() {
    const tooltip = document.querySelector('.tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}
