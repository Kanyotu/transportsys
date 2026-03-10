/* ================================
   DARK MODE TOGGLE - SafiriPay
   ================================ */

// Initialize dark mode from localStorage or system preference
(function() {
  const storedTheme = localStorage.getItem('theme');
  const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  
  // Determine initial theme
  const initialTheme = storedTheme || (systemPrefersDark ? 'dark' : 'light');
  
  // Set theme immediately to avoid flash
  document.documentElement.setAttribute('data-theme', initialTheme);
})();

// Dark mode toggle functionality
function initDarkMode() {
  // Create toggle button if it doesn't exist
  if (!document.querySelector('.dark-mode-toggle')) {
    const toggleBtn = document.createElement('button');
    toggleBtn.className = 'dark-mode-toggle';
    toggleBtn.setAttribute('aria-label', 'Toggle dark mode');
    toggleBtn.innerHTML = `
      <span class="sun-icon">☀️</span>
      <span class="moon-icon">🌙</span>
    `;
    document.body.appendChild(toggleBtn);
    
    // Add click event
    toggleBtn.addEventListener('click', toggleDarkMode);
  }
  
  // Update toggle button state
  updateToggleButton();
}

// Toggle between light and dark mode
function toggleDarkMode() {
  const currentTheme = document.documentElement.getAttribute('data-theme');
  const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
  
  // Apply new theme
  document.documentElement.setAttribute('data-theme', newTheme);
  
  // Save to localStorage
  localStorage.setItem('theme', newTheme);
  
  // Update button
  updateToggleButton();
  
  // Dispatch custom event for other scripts
  window.dispatchEvent(new CustomEvent('themeChange', { detail: { theme: newTheme } }));
  
  // Add a subtle animation effect
  document.body.style.transition = 'background-color 0.3s ease';
}

// Update toggle button appearance
function updateToggleButton() {
  const theme = document.documentElement.getAttribute('data-theme');
  const toggleBtn = document.querySelector('.dark-mode-toggle');
  
  if (toggleBtn) {
    if (theme === 'dark') {
      toggleBtn.setAttribute('aria-label', 'Switch to light mode');
      toggleBtn.title = 'Switch to light mode';
    } else {
      toggleBtn.setAttribute('aria-label', 'Switch to dark mode');
      toggleBtn.title = 'Switch to dark mode';
    }
  }
}

// Listen for system theme changes
if (window.matchMedia) {
  const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
  
  // Modern browsers
  if (darkModeQuery.addEventListener) {
    darkModeQuery.addEventListener('change', (e) => {
      // Only auto-switch if user hasn't manually set a preference
      if (!localStorage.getItem('theme')) {
        const newTheme = e.matches ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', newTheme);
        updateToggleButton();
      }
    });
  } 
  // Legacy browsers
  else if (darkModeQuery.addListener) {
    darkModeQuery.addListener((e) => {
      if (!localStorage.getItem('theme')) {
        const newTheme = e.matches ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', newTheme);
        updateToggleButton();
      }
    });
  }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initDarkMode);
} else {
  // DOMContentLoaded has already fired
  initDarkMode();
}

// Export functions for external use
window.darkMode = {
  toggle: toggleDarkMode,
  setTheme: function(theme) {
    if (theme === 'light' || theme === 'dark') {
      document.documentElement.setAttribute('data-theme', theme);
      localStorage.setItem('theme', theme);
      updateToggleButton();
    }
  },
  getTheme: function() {
    return document.documentElement.getAttribute('data-theme');
  },
  reset: function() {
    localStorage.removeItem('theme');
    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const theme = systemPrefersDark ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', theme);
    updateToggleButton();
  }
};

// Keyboard shortcut (Ctrl/Cmd + Shift + D)
document.addEventListener('keydown', function(e) {
  if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'D') {
    e.preventDefault();
    toggleDarkMode();
  }
});

// Add smooth transition class after initial load
window.addEventListener('load', function() {
  document.body.classList.add('theme-transition-ready');
});
