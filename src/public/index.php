<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Monolith</title>
    <!-- Alpine.js -->
    <script src="//unpkg.com/alpinejs" defer></script>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-zinc-950 text-zinc-100 font-sans min-h-screen flex flex-col selection:bg-indigo-500/30">

    <?php include 'navbar.php'; ?>

    <!-- Hero Section -->
    <main class="flex-1 flex flex-col items-center justify-center text-center px-6 pt-32 pb-20">
        <div class="max-w-3xl">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-zinc-900 border border-zinc-800 text-xs font-medium text-zinc-400 mb-8 animate-fade-in-up">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                </span>
                Generated in real-time
            </div>
            
            <h1 class="text-5xl md:text-7xl font-bold tracking-tight mb-6 pb-2 bg-gradient-to-b from-white via-zinc-100 to-zinc-400 bg-clip-text text-transparent">
                Code Written by<br>Intelligence.
            </h1>
            
            <p class="text-lg md:text-xl text-zinc-400 mb-12 max-w-2xl mx-auto leading-relaxed">
                Explore a collection of self-replicating applications built entirely by an AI agent living inside the container.
            </p>
        </div>

        <!-- Apps Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl w-full px-4">
            <!-- TaskMaster Card -->
            <a href="/todos.php" class="group relative bg-zinc-900 border border-zinc-800 rounded-2xl p-8 transition-all duration-300 hover:border-zinc-700 hover:-translate-y-1 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-purple-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                
                <div class="relative z-10 flex flex-col items-start text-left h-full">
                    <div class="w-12 h-12 bg-zinc-800 rounded-xl flex items-center justify-center mb-6 group-hover:bg-zinc-700 transition-colors border border-zinc-700/50">
                        <svg class="w-6 h-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    </div>
                    
                    <h2 class="text-2xl font-bold text-white mb-2 group-hover:text-indigo-300 transition-colors">TaskMaster</h2>
                    <p class="text-zinc-400 text-sm leading-relaxed mb-6">
                        A powerful Google Tasks clone featuring lists, nested subtasks, and a beautiful dark mode UI. Built with HTMX and Tailwind.
                    </p>
                    
                    <div class="mt-auto flex items-center text-sm font-medium text-zinc-500 group-hover:text-white transition-colors">
                        Launch App <span class="ml-2 transition-transform group-hover:translate-x-1">→</span>
                    </div>
                </div>
            </a>

            <!-- Logger Card -->
            <a href="/logger.php" class="group relative bg-zinc-900 border border-zinc-800 rounded-2xl p-8 transition-all duration-300 hover:border-zinc-700 hover:-translate-y-1 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-teal-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                
                <div class="relative z-10 flex flex-col items-start text-left h-full">
                    <div class="w-12 h-12 bg-zinc-800 rounded-xl flex items-center justify-center mb-6 group-hover:bg-zinc-700 transition-colors border border-zinc-700/50">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    
                    <h2 class="text-2xl font-bold text-white mb-2 group-hover:text-emerald-300 transition-colors">Logger</h2>
                    <p class="text-zinc-400 text-sm leading-relaxed mb-6">
                        A flexible data tracking tool. Define custom schemas for workouts, habits, or daily logs and visualize your history.
                    </p>
                    
                    <div class="mt-auto flex items-center text-sm font-medium text-zinc-500 group-hover:text-white transition-colors">
                        Launch App <span class="ml-2 transition-transform group-hover:translate-x-1">→</span>
                    </div>
                </div>
            </a>

            <!-- Vision Board Card -->
            <a href="/vision-board.php" class="group relative bg-zinc-900 border border-zinc-800 rounded-2xl p-8 transition-all duration-300 hover:border-zinc-700 hover:-translate-y-1 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-rose-500/5 to-pink-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                
                <div class="relative z-10 flex flex-col items-start text-left h-full">
                    <div class="w-12 h-12 bg-zinc-800 rounded-xl flex items-center justify-center mb-6 group-hover:bg-zinc-700 transition-colors border border-zinc-700/50">
                        <svg class="w-6 h-6 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    
                    <h2 class="text-2xl font-bold text-white mb-2 group-hover:text-rose-300 transition-colors">Vision Board</h2>
                    <p class="text-zinc-400 text-sm leading-relaxed mb-6">
                        Visualize your future. Set long-term goals, track milestones, and organize your life pillars in a beautiful visual dashboard.
                    </p>
                    
                    <div class="mt-auto flex items-center text-sm font-medium text-zinc-500 group-hover:text-white transition-colors">
                        Launch App <span class="ml-2 transition-transform group-hover:translate-x-1">→</span>
                    </div>
                </div>
            </a>

            <!-- Bookmarks Card -->
            <a href="/bookmarks.php" class="group relative bg-zinc-900 border border-zinc-800 rounded-2xl p-8 transition-all duration-300 hover:border-zinc-700 hover:-translate-y-1 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-cyan-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                
                <div class="relative z-10 flex flex-col items-start text-left h-full">
                    <div class="w-12 h-12 bg-zinc-800 rounded-xl flex items-center justify-center mb-6 group-hover:bg-zinc-700 transition-colors border border-zinc-700/50">
                        <svg class="w-6 h-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" /></svg>
                    </div>
                    
                    <h2 class="text-2xl font-bold text-white mb-2 group-hover:text-blue-300 transition-colors">Bookmarks</h2>
                    <p class="text-zinc-400 text-sm leading-relaxed mb-6">
                        A utility-focused link manager. Organize your reading lists and dev tools in a compact, distraction-free interface.
                    </p>
                    
                    <div class="mt-auto flex items-center text-sm font-medium text-zinc-500 group-hover:text-white transition-colors">
                        Launch App <span class="ml-2 transition-transform group-hover:translate-x-1">→</span>
                    </div>
                </div>
            </a>

            <!-- Questions Card -->
            <a href="/questions.php" class="group relative bg-zinc-900 border border-zinc-800 rounded-2xl p-8 transition-all duration-300 hover:border-zinc-700 hover:-translate-y-1 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-orange-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                
                <div class="relative z-10 flex flex-col items-start text-left h-full">
                    <div class="w-12 h-12 bg-zinc-800 rounded-xl flex items-center justify-center mb-6 group-hover:bg-zinc-700 transition-colors border border-zinc-700/50">
                        <svg class="w-6 h-6 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    
                    <h2 class="text-2xl font-bold text-white mb-2 group-hover:text-amber-300 transition-colors">Questions</h2>
                    <p class="text-zinc-400 text-sm leading-relaxed mb-6">
                        A curiosity journal. Capture fleeting questions, research them later, and build your own personal knowledge base.
                    </p>
                    
                    <div class="mt-auto flex items-center text-sm font-medium text-zinc-500 group-hover:text-white transition-colors">
                        Launch App <span class="ml-2 transition-transform group-hover:translate-x-1">→</span>
                    </div>
                </div>
            </a>

            <!-- Journal Card -->
            <a href="/journal.php" class="group relative bg-zinc-900 border border-zinc-800 rounded-2xl p-8 transition-all duration-300 hover:border-zinc-700 hover:-translate-y-1 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-violet-500/5 to-purple-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                
                <div class="relative z-10 flex flex-col items-start text-left h-full">
                    <div class="w-12 h-12 bg-zinc-800 rounded-xl flex items-center justify-center mb-6 group-hover:bg-zinc-700 transition-colors border border-zinc-700/50">
                        <svg class="w-6 h-6 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    </div>
                    
                    <h2 class="text-2xl font-bold text-white mb-2 group-hover:text-violet-300 transition-colors">Journal</h2>
                    <p class="text-zinc-400 text-sm leading-relaxed mb-6">
                        A distraction-free writing space. Capture your daily thoughts, track your mood, and build a habit of reflection.
                    </p>
                    
                    <div class="mt-auto flex items-center text-sm font-medium text-zinc-500 group-hover:text-white transition-colors">
                        Launch App <span class="ml-2 transition-transform group-hover:translate-x-1">→</span>
                    </div>
                </div>
            </a>
        </div>
    </main>

    <footer class="py-8 text-center text-zinc-600 text-xs border-t border-zinc-900">
        <p>&copy; <?php echo date('Y'); ?> Monolith. Built by AI.</p>
    </footer>

</body>
</html>