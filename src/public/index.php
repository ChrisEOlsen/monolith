<?php
require_once 'db.php';
require_once 'csrf.php';

// Authentication Check (Optional - Toggle true if you want index to be private)
$auth_required = false;

if ($auth_required && !isset($_SESSION['user_id'])) {
    $_SESSION['auth_redirect_to'] = $_SERVER['REQUEST_URI'];
    header("Location: /login.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monolith - Home</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <script src="//unpkg.com/alpinejs" defer></script>
    <?php include 'theme.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-[var(--color-bg)] text-[var(--color-text)] font-sans min-h-screen flex flex-col selection:bg-[#58a6ff]/30">

    <?php include 'navbar.php'; ?>

    <main class="flex-1 max-w-4xl mx-auto w-full px-4 py-16 space-y-8">
        
        <!-- Header -->
        <div class="text-center pt-12">
            <h1 class="text-4xl font-bold text-[var(--color-text)] mb-4">Welcome to Monolith</h1>
            <p class="text-[var(--color-text-muted)] text-lg">Your self-replicating, AI-first PHP template is ready.</p>
        </div>

        <!-- Getting Started -->
        <section class="mt-12 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-lg p-8 shadow-sm">
            <h2 class="text-xl font-semibold mb-6">Getting Started</h2>
            
            <div class="grid gap-6 md:grid-cols-2">
                <div class="space-y-2">
                    <h3 class="text-sm font-bold text-[#58a6ff] uppercase tracking-wider">Step 1: Database</h3>
                    <p class="text-sm text-[var(--color-text-muted)]">Use <code>execute_sql</code> to create your tables. Every table should have an <code>id</code> primary key.</p>
                </div>

                <div class="space-y-2">
                    <h3 class="text-sm font-bold text-[#58a6ff] uppercase tracking-wider">Step 2: Scaffold</h3>
                    <p class="text-sm text-[var(--color-text-muted)]">Use <code>scaffold_crud</code> or <code>create_model</code> to generate the core logic for your entities.</p>
                </div>

                <div class="space-y-2">
                    <h3 class="text-sm font-bold text-[#58a6ff] uppercase tracking-wider">Step 3: Interface</h3>
                    <p class="text-sm text-[var(--color-text-muted)]">Add HTMX forms or custom pages using <code>add_htmx_form</code> and <code>create_page</code>.</p>
                </div>

                <div class="space-y-2">
                    <h3 class="text-sm font-bold text-[#58a6ff] uppercase tracking-wider">Step 4: Polish</h3>
                    <p class="text-sm text-[var(--color-text-muted)]">Compile your styles with <code>build_css</code> and you're ready for deployment.</p>
                </div>
            </div>
        </section>

        <!-- Useful Links -->
        <div class="flex justify-center gap-6 mt-12">
            <a href="https://htmx.org" target="_blank" class="text-xs font-medium text-[var(--color-text-muted)] hover:text-[#58a6ff] transition-colors">HTMX Docs</a>
            <a href="https://alpinejs.dev" target="_blank" class="text-xs font-medium text-[var(--color-text-muted)] hover:text-[#58a6ff] transition-colors">Alpine.js Docs</a>
            <a href="https://tailwindcss.com" target="_blank" class="text-xs font-medium text-[var(--color-text-muted)] hover:text-[#58a6ff] transition-colors">Tailwind CSS</a>
        </div>

    </main>

    <footer class="py-8 border-t border-[var(--color-border)] mt-auto">
        <div class="max-w-7xl mx-auto px-6 flex justify-between items-center">
            <!-- Left: theme toggle -->
            <button onclick="toggleTheme()" class="flex items-center gap-1.5 text-[var(--color-text-muted)] hover:text-[var(--color-text)] transition-colors w-fit">
                <span class="theme-sun text-sm">☀</span>
                <span class="theme-moon text-sm">☽</span>
            </button>
            
            <!-- Center: copyright -->
            <p class="text-[var(--color-text-muted)] text-xs">&copy; <?php echo date('Y'); ?> Monolith.</p>

            <!-- Right: empty for balance -->
            <div class="w-10"></div>
        </div>
    </footer>

<script>
function toggleTheme() {
  const isLight = document.documentElement.classList.toggle('light');
  localStorage.setItem('theme', isLight ? 'light' : 'dark');
}
</script>
</body>
</html>
