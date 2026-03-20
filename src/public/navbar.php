<!-- Navbar Component -->
<nav class="fixed top-0 w-full z-50 bg-[var(--color-bg)] border-b border-[var(--color-border)]">
    <div class="max-w-7xl mx-auto px-4 h-12 flex items-center justify-between">
        <a href="/" class="text-sm font-semibold text-[var(--color-text)] hover:text-[var(--color-text)] transition-colors">
            Monolith
        </a>

        <!-- Dropdown Navigation -->
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" @click.outside="open = false" class="flex items-center gap-1.5 text-xs font-medium text-[var(--color-text-muted)] hover:text-[var(--color-text)] transition-colors py-1 pl-4">
                <span>Apps</span>
                <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            </button>

            <div x-show="open" 
                 class="absolute right-0 mt-2 w-48 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-md shadow-lg overflow-hidden py-1"
                 style="display: none;">
                <a href="/" class="block px-3 py-1.5 text-xs text-[var(--color-text-muted)] hover:text-[var(--color-text)] hover:bg-[var(--color-hover)] transition-colors">Home</a>
                <div class="h-px bg-[var(--color-border)] my-1"></div>
                <a href="/todos.php" class="block px-3 py-1.5 text-xs text-[var(--color-text-muted)] hover:text-[var(--color-text)] hover:bg-[var(--color-hover)] transition-colors flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#58a6ff]"></span> TaskMaster
                </a>
                <a href="/logger.php" class="block px-3 py-1.5 text-xs text-[var(--color-text-muted)] hover:text-[var(--color-text)] hover:bg-[var(--color-hover)] transition-colors flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#3fb950]"></span> Logger
                </a>
                <a href="/planner.php" class="block px-3 py-1.5 text-xs text-[var(--color-text-muted)] hover:text-[var(--color-text)] hover:bg-[var(--color-hover)] transition-colors flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#1f6feb]"></span> Project Planner
                </a>
                <a href="/bookmarks.php" class="block px-3 py-1.5 text-xs text-[var(--color-text-muted)] hover:text-[var(--color-text)] hover:bg-[var(--color-hover)] transition-colors flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#d29922]"></span> Bookmarks
                </a>
                <a href="/dailyplanet.php" class="block px-3 py-1.5 text-xs text-[var(--color-text-muted)] hover:text-[var(--color-text)] hover:bg-[var(--color-hover)] transition-colors flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#f472b6]"></span> Daily Planet
                </a>
                <a href="/journal.php" class="block px-3 py-1.5 text-xs text-[var(--color-text-muted)] hover:text-[var(--color-text)] hover:bg-[var(--color-hover)] transition-colors flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#bc8cff]"></span> Journal
                </a>
                <a href="/codex.php" class="block px-3 py-1.5 text-xs text-[var(--color-text-muted)] hover:text-[var(--color-text)] hover:bg-[var(--color-hover)] transition-colors flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#f85149]"></span> Codex
                </a>
                <a href="/bookmap.php" class="block px-3 py-1.5 text-xs text-[var(--color-text-muted)] hover:text-[var(--color-text)] hover:bg-[var(--color-hover)] transition-colors flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#0ea5e9]"></span> BookMap
                </a>
                <a href="/reminders.php" class="block px-3 py-1.5 text-xs text-[var(--color-text-muted)] hover:text-[var(--color-text)] hover:bg-[var(--color-hover)] transition-colors flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#e8a045]"></span> Reminders
                </a>
            </div>
        </div>
    </div>
</nav>
