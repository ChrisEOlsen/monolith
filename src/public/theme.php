<?php
/**
 * theme.php - Include in every page's <head> immediately before the Tailwind
 * CSS block (CDN script or compiled link). Provides: anti-flash script +
 * CSS custom properties for light/dark themes.
 * Note: theme-sun/moon classes are CSS-driven, not Tailwind-driven, to avoid flash.
 */
?>
<script>
  (function() {
    if (localStorage.getItem('theme') === 'light') {
      document.documentElement.classList.add('light');
    }
  })();
</script>
<style>
  html {
    --color-bg: #0d1117;
    --color-surface: #161b22;
    --color-surface-2: #21262d;
    --color-text: #f0f6fc;
    --color-text-muted: #8b949e;
    --color-border: #30363d;
    --color-hover: #21262d;
  }
  html.light {
    --color-bg: #fafafa;
    --color-surface: #ffffff;
    --color-surface-2: #f1f5f9;
    --color-text: #0f172a;
    --color-text-muted: #64748b;
    --color-border: #e2e8f0;
    --color-hover: #f8fafc;
  }
  html.light .theme-sun { display: none; }
  html.light .theme-moon { display: inline; }
  .theme-moon { display: none; }
</style>
