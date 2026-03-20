<?php
require_once 'db.php';
require_once 'csrf.php';
require_once 'classes/Shortcut.php';
require_once 'classes/Reminder.php';

$shortcutModel = new Shortcut($pdo);
$reminderModel = new Reminder($pdo);
$upcoming_reminders = $reminderModel->getUpcoming(5);

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die("Invalid CSRF");
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create_shortcut') {
        $url = $_POST['url'];
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "https://" . $url;
        }
        $shortcutModel->create([
            'title' => $_POST['title'],
            'url' => $url
        ]);
        header("Location: index.php");
        exit;
    }
    elseif ($action === 'delete_shortcut') {
        $shortcutModel->delete($_POST['id']);
        header("Location: index.php");
        exit;
    }
}

$shortcuts = $shortcutModel->getAll();

// App Configuration
$apps = [
    [
        'title' => 'TaskMaster',
        'url' => '/todos.php',
        'color' => 'indigo',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />'
    ],
    [
        'title' => 'Logger',
        'url' => '/logger.php',
        'color' => 'emerald',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />'
    ],
    [
        'title' => 'Project Planner',
        'url' => '/planner.php',
        'color' => 'cyan',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />'
    ],
    [
        'title' => 'Bookmarks',
        'url' => '/bookmarks.php',
        'color' => 'amber',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />'
    ],
    [
        'title' => 'Daily Planet',
        'url' => '/dailyplanet.php',
        'color' => 'rose',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />'
    ],
    [
        'title' => 'Journal',
        'url' => '/journal.php',
        'color' => 'violet',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />'
    ],
    [
        'title' => 'Codex',
        'url' => '/codex.php',
        'color' => 'orange',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />'
    ],
    [
        'title' => 'BookMap',
        'url' => '/bookmap.php',
        'color' => 'sky',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />'
    ],
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Lab</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <script src="//unpkg.com/alpinejs" defer></script>
    <?php include 'theme.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-[var(--color-bg)] text-[var(--color-text)] font-sans min-h-screen flex flex-col selection:bg-[#58a6ff]/30">

    <?php include 'navbar.php'; ?>

    <main class="flex-1 max-w-4xl mx-auto w-full px-4 py-8 space-y-8">
        
        <!-- Header -->
        <div class="text-center pt-12">
            <!-- <h1 class="text-xl font-bold text-[var(--color-text)] mb-1">Home Lab</h1> -->
        </div>

        <!-- Apps Grid (Minimalist) -->
        <section>
            <h2 class="text-xs font-semibold text-[var(--color-text-muted)] mb-3 pl-1">Applications</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                <?php 
                $app_colors = [
                    'indigo' => '#58a6ff',
                    'emerald' => '#3fb950',
                    'cyan' => '#1f6feb',
                    'amber' => '#d29922',
                    'violet' => '#bc8cff',
                    'orange' => '#f85149',
                    'sky' => '#0ea5e9',
                    'rose' => '#f472b6',
                    'slate' => '#94a3b8'
                ];
                foreach ($apps as $app): 
                    $hex = $app_colors[$app['color']] ?? '#58a6ff';
                ?>
                <a href="<?php echo $app['url']; ?>" class="group flex items-center gap-2.5 bg-[var(--color-surface)] border border-[var(--color-border)] hover:border-[var(--color-border)] rounded-md p-2.5 transition-colors">
                    <div style="color: <?php echo $hex; ?>;" class="p-1 bg-[var(--color-surface-2)] rounded transition-transform shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <?php echo $app['icon']; ?>
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-[var(--color-text-muted)] group-hover:text-[var(--color-text)] transition-colors truncate"><?php echo $app['title']; ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Reminders Widget -->
        <section>
            <div class="flex items-center justify-between mb-3 pl-1">
                <h2 class="text-xs font-semibold text-[var(--color-text-muted)]">Upcoming Reminders</h2>
                <a href="/reminders.php" class="text-[10px] font-bold text-[#e8a045] hover:text-[#f0b060] transition-colors">View all →</a>
            </div>
            <ul class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-md divide-y divide-[var(--color-border)]">
                <?php if (empty($upcoming_reminders)): ?>
                    <li class="px-3 py-3 text-xs text-[var(--color-text-muted)]">No upcoming reminders.</li>
                <?php else: ?>
                    <?php foreach ($upcoming_reminders as $r): ?>
                    <li class="px-3 py-2.5 flex items-center justify-between">
                        <span class="text-sm text-[var(--color-text)] truncate mr-4"><?php echo htmlspecialchars($r['title']); ?></span>
                        <span class="text-xs text-[var(--color-text-muted)] shrink-0"><?php echo date('D M j, H:i', strtotime($r['remind_at'])); ?></span>
                    </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </section>

        <!-- Shortcuts Component -->
        <section x-data="{ showForm: false }">
            <div class="flex items-center justify-between mb-3 pl-1">
                <h2 class="text-xs font-semibold text-[var(--color-text-muted)]">Quick Links</h2>
                <button @click="showForm = !showForm" class="text-[10px] font-bold text-[#58a6ff] hover:text-[#79c0ff] transition-colors flex items-center gap-1">
                    <span x-text="showForm ? 'Cancel' : '+ Add Shortcut'"></span>
                </button>
            </div>

            <!-- Add Form -->
            <div x-show="showForm" x-collapse x-cloak class="mb-6 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-md p-3">
                <form method="POST" class="flex flex-col md:flex-row gap-2">
                    <input type="hidden" name="action" value="create_shortcut">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <input type="text" name="title" placeholder="Title" required
                           class="flex-1 bg-[var(--color-bg)] border border-[var(--color-border)] rounded-md px-3 py-1.5 text-xs text-[var(--color-text)] focus:border-[#58a6ff] focus:ring-1 focus:ring-[#58a6ff] outline-none transition-all">

                    <input type="text" name="url" placeholder="URL" required
                           class="flex-[2] bg-[var(--color-bg)] border border-[var(--color-border)] rounded-md px-3 py-1.5 text-xs text-[var(--color-text)] focus:border-[#58a6ff] focus:ring-1 focus:ring-[#58a6ff] outline-none transition-all">
                    
                    <button type="submit" class="bg-[#238636] hover:bg-[#2ea043] text-white px-4 py-1.5 rounded-md text-[10px] font-bold transition-colors">
                        Add Link
                    </button>
                </form>
            </div>

            <!-- Shortcuts Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2">
                <?php foreach ($shortcuts as $shortcut): 
                    $faviconUrl = "https://www.google.com/s2/favicons?domain=" . urlencode($shortcut['url']) . "&sz=32";
                ?>
                <div class="group relative bg-[var(--color-surface)]/50 hover:bg-[var(--color-surface)] border border-[var(--color-border)] rounded-md p-2 flex items-center gap-2 transition-colors">
                    
                    <!-- Delete Button (Hover) -->
                    <form method="POST" class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity" onsubmit="return confirm('Remove shortcut?');">
                        <input type="hidden" name="action" value="delete_shortcut">
                        <input type="hidden" name="id" value="<?php echo $shortcut['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <button type="submit" class="text-[var(--color-text-muted)] hover:text-[#f85149] p-0.5 rounded hover:bg-[var(--color-hover)]">
                            <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </form>

                    <a href="<?php echo htmlspecialchars($shortcut['url']); ?>" target="_blank" class="flex items-center gap-2 w-full pr-4">
                        <img src="<?php echo $faviconUrl; ?>" alt="" class="w-4 h-4 rounded-sm opacity-60 group-hover:opacity-100 transition-opacity shrink-0">
                        <span class="text-xs font-medium text-[var(--color-text-muted)] group-hover:text-[var(--color-text)] truncate"><?php echo htmlspecialchars($shortcut['title']); ?></span>
                    </a>
                </div>
                <?php endforeach; ?>

                <?php if (empty($shortcuts)): ?>
                    <div class="col-span-full py-6 text-center border border-dashed border-[var(--color-border)] rounded-md text-[var(--color-text-muted)] text-[10px] font-bold">
                        No shortcuts pinned
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </main>

    <footer class="py-8 border-t border-[var(--color-border)] mt-auto">
        <div class="max-w-7xl mx-auto px-6 grid grid-cols-3 items-center">
            <!-- Left: theme toggle -->
            <button onclick="toggleTheme()" class="flex items-center gap-1.5 text-[var(--color-text-muted)] hover:text-[var(--color-text)] transition-colors w-fit">
                <span class="theme-sun text-sm">☀</span>
                <span class="theme-moon text-sm">☽</span>
            </button>
            <!-- Center: copyright -->
            <p class="text-[var(--color-text-muted)] text-xs text-center">&copy; <?php echo date('Y'); ?> Monolith.</p>
            <!-- Right: automator link -->
            <div class="flex justify-end">
                <a href="/automator.php" class="text-[var(--color-text-muted)] hover:text-[var(--color-text)] transition-colors flex items-center gap-1.5 group">
                    <span class="w-1.5 h-1.5 rounded-full bg-[var(--color-border)] group-hover:bg-[var(--color-text-muted)] transition-colors"></span>
                    <span class="text-[10px] font-bold uppercase tracking-widest">Automator</span>
                </a>
            </div>
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
