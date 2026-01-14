<?php
/**
 * PAGE: journal.php
 */
require_once 'db.php';
require_once 'csrf.php';
require_once 'classes/JournalEntry.php';

$journalModel = new JournalEntry($pdo);

// Logic
$current_entry_id = $_GET['id'] ?? null;

// --- CONTROLLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        if (isset($_SERVER['HTTP_HX_REQUEST'])) { http_response_code(403); exit; }
        die("Invalid CSRF");
    }

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create_entry') {
            // Default to today
            $date = date('Y-m-d');
            $stmt = $pdo->prepare("INSERT INTO journal_entries (entry_date, content, mood) VALUES (:date, '', 'neutral')");
            $stmt->execute(['date' => $date]);
            header("Location: ?id=" . $pdo->lastInsertId()); exit;
        }
        elseif ($action === 'update_entry') {
            $stmt = $pdo->prepare("UPDATE journal_entries SET content = :content, title = :title, mood = :mood, entry_date = :date WHERE id = :id");
            $stmt->execute([
                'content' => $_POST['content'],
                'title' => $_POST['title'],
                'mood' => $_POST['mood'],
                'date' => $_POST['entry_date'],
                'id' => $_POST['id']
            ]);
            if (isset($_SERVER['HTTP_HX_REQUEST'])) exit; // Silent save
            header("Location: ?id=" . $_POST['id']); exit;
        }
        elseif ($action === 'delete_entry') {
            $journalModel->delete($_POST['id']);
            header("Location: ?"); exit;
        }
    } catch (Exception $e) { /* Log */ }
}

// Fetch Entries for Sidebar (Group by Month)
$stmt = $pdo->query("SELECT id, title, entry_date, LEFT(content, 50) as snippet FROM journal_entries ORDER BY entry_date DESC, created_at DESC");
$all_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

$entries_by_month = [];
foreach ($all_entries as $e) {
    $month = date('F Y', strtotime($e['entry_date']));
    $entries_by_month[$month][] = $e;
}

// Fetch Current Entry
$current_entry = null;
if ($current_entry_id) {
    $stmt = $pdo->prepare("SELECT * FROM journal_entries WHERE id = :id");
    $stmt->execute(['id' => $current_entry_id]);
    $current_entry = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif (!empty($all_entries)) {
    // Default to most recent if none selected? No, show empty state or welcome.
    // Let's show most recent for convenience.
    // $current_entry_id = $all_entries[0]['id'];
    // $current_entry = ... 
    // Actually, keep it empty state to encourage "New Entry".
}
?>
<?php ob_start(); ?>

<div class="min-h-screen bg-zinc-950 text-zinc-100 font-sans selection:bg-violet-500/30" x-data="{ sidebarOpen: false }">
    
    <?php include 'navbar.php'; ?>

    <div class="flex h-[calc(100vh-64px)] pt-16">
        
        <!-- Sidebar -->
        <aside class="hidden md:flex w-72 bg-zinc-900 border-r border-zinc-800 flex-col">
            <div class="p-4">
                <form method="POST">
                    <input type="hidden" name="action" value="create_entry">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <button class="w-full bg-violet-600 hover:bg-violet-500 text-white px-4 py-3 rounded-xl font-bold transition-all shadow-lg shadow-violet-500/20 flex items-center justify-center gap-2">
                        <span class="text-xl">+</span> New Entry
                    </button>
                </form>
            </div>

            <div class="flex-1 overflow-y-auto px-2 custom-scrollbar">
                <?php foreach ($entries_by_month as $month => $entries): ?>
                <div class="mb-6">
                    <h3 class="px-4 text-xs font-bold text-zinc-500 uppercase tracking-widest mb-2 sticky top-0 bg-zinc-900 py-2"><?php echo $month; ?></h3>
                    <div class="space-y-1">
                        <?php foreach ($entries as $e): ?>
                        <a href="?id=<?php echo $e['id']; ?>" class="block px-4 py-3 rounded-lg transition-colors group <?php echo $e['id'] == $current_entry_id ? 'bg-zinc-800' : 'hover:bg-zinc-800/50'; ?>">
                            <div class="flex justify-between items-baseline mb-1">
                                <span class="font-bold text-sm <?php echo $e['id'] == $current_entry_id ? 'text-white' : 'text-zinc-300'; ?>">
                                    <?php echo date('D, jS', strtotime($e['entry_date'])); ?>
                                </span>
                                <?php if ($e['title']): ?>
                                <span class="text-xs text-zinc-500 truncate ml-2 max-w-[80px]"><?php echo htmlspecialchars($e['title']); ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-zinc-500 line-clamp-2 leading-relaxed">
                                <?php echo htmlspecialchars(strip_tags($e['snippet'] ?: 'No content...')); ?>
                            </p>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </aside>

        <!-- Mobile Sidebar Drawer -->
        <div x-show="sidebarOpen" class="fixed inset-0 z-[60] flex md:hidden" role="dialog" aria-modal="true" style="display: none;">
            <div class="fixed inset-0 bg-black/80" @click="sidebarOpen = false"></div>
            <div class="relative flex-1 flex flex-col max-w-xs w-full bg-zinc-900 border-r border-zinc-800">
                <div class="p-4 border-b border-zinc-800 flex justify-between items-center">
                    <h2 class="text-lg font-bold">Journal</h2>
                    <button @click="sidebarOpen = false" class="text-zinc-400">✕</button>
                </div>
                <div class="p-4">
                    <form method="POST">
                        <input type="hidden" name="action" value="create_entry">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <button class="w-full bg-violet-600 hover:bg-violet-500 text-white px-4 py-3 rounded-xl font-bold">New Entry</button>
                    </form>
                </div>
                <!-- Mobile List (Simplified loop) -->
                <div class="flex-1 overflow-y-auto px-2">
                    <?php foreach ($entries_by_month as $month => $entries): ?>
                        <h3 class="px-4 text-xs font-bold text-zinc-500 uppercase mt-4 mb-2"><?php echo $month; ?></h3>
                        <?php foreach ($entries as $e): ?>
                        <a href="?id=<?php echo $e['id']; ?>" class="block px-4 py-3 rounded-lg bg-zinc-800/30 mb-1 border border-zinc-800">
                            <div class="font-bold text-sm text-zinc-200"><?php echo date('D, jS', strtotime($e['entry_date'])); ?></div>
                        </a>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Editor -->
        <main class="flex-1 flex flex-col relative bg-zinc-950 w-full overflow-hidden">
            <?php if ($current_entry): ?>
            
            <!-- Hidden Delete Form -->
            <form id="delete-form" method="POST" onsubmit="return confirm('Delete this entry?');" class="hidden">
                <input type="hidden" name="action" value="delete_entry">
                <input type="hidden" name="id" value="<?php echo $current_entry['id']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            </form>

            <form id="entry-form" class="flex flex-col h-full" 
                  hx-post="" hx-trigger="change delay:500ms, keyup delay:1000ms from:textarea, keyup delay:1000ms from:input" hx-swap="none">
                
                <input type="hidden" name="action" value="update_entry">
                <input type="hidden" name="id" value="<?php echo $current_entry['id']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <!-- Editor Header -->
                <header class="h-16 border-b border-zinc-800 flex items-center justify-between px-4 md:px-8 bg-zinc-950 z-10 shrink-0">
                    <div class="flex items-center gap-4">
                        <button type="button" @click="sidebarOpen = true" class="md:hidden text-zinc-400 -ml-2 p-2">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                        </button>
                        
                        <input type="date" name="entry_date" value="<?php echo $current_entry['entry_date']; ?>" 
                               class="bg-transparent border-none text-zinc-400 text-sm focus:ring-0 p-0 font-mono">
                    </div>

                    <div class="flex items-center gap-4">
                        <!-- Mood Selector -->
                        <select name="mood" class="bg-zinc-900 border-none text-xl rounded-lg focus:ring-0 cursor-pointer hover:bg-zinc-800 transition-colors py-1 px-2">
                            <option value="neutral" <?php echo $current_entry['mood'] == 'neutral' ? 'selected' : ''; ?>>😐</option>
                            <option value="happy" <?php echo $current_entry['mood'] == 'happy' ? 'selected' : ''; ?>>🙂</option>
                            <option value="great" <?php echo $current_entry['mood'] == 'great' ? 'selected' : ''; ?>>🤩</option>
                            <option value="sad" <?php echo $current_entry['mood'] == 'sad' ? 'selected' : ''; ?>>😔</option>
                            <option value="angry" <?php echo $current_entry['mood'] == 'angry' ? 'selected' : ''; ?>>😡</option>
                            <option value="tired" <?php echo $current_entry['mood'] == 'tired' ? 'selected' : ''; ?>>😴</option>
                        </select>
                    </div>
                    
                    <!-- Delete Button -->
                    <button type="submit" form="delete-form" class="text-zinc-600 hover:text-red-500 transition-colors p-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                </header>
                
                <!-- Editor Canvas -->
                <div class="flex-1 overflow-y-auto px-4 md:px-8 py-8 max-w-3xl mx-auto w-full">
                    <input type="text" name="title" placeholder="Title (Optional)" value="<?php echo htmlspecialchars($current_entry['title'] ?? ''); ?>" 
                           class="w-full bg-transparent border-none text-3xl font-bold text-white placeholder-zinc-700 focus:ring-0 p-0 mb-6">
                    
                    <textarea name="content" placeholder="Start writing..." 
                              class="w-full h-[calc(100vh-250px)] bg-transparent border-none text-lg text-zinc-300 placeholder-zinc-700 focus:ring-0 p-0 resize-none leading-relaxed font-serif outline-none"><?php echo htmlspecialchars($current_entry['content']); ?></textarea>
                </div>
                
                <!-- Status Indicator -->
                <div class="fixed bottom-4 right-4 text-xs text-zinc-600 font-mono" id="save-status">
                    Auto-saved
                </div>
                <script>
                    document.body.addEventListener('htmx:beforeRequest', function() { document.getElementById('save-status').innerText = 'Saving...'; });
                    document.body.addEventListener('htmx:afterRequest', function() { document.getElementById('save-status').innerText = 'Saved'; setTimeout(() => document.getElementById('save-status').innerText = '', 2000); });
                </script>

            </form>

            <?php else: ?>
            <div class="flex-1 flex flex-col items-center justify-center text-center p-8">
                <div class="w-20 h-20 bg-zinc-900 rounded-full flex items-center justify-center mb-6 border border-zinc-800">
                    <span class="text-4xl">✍️</span>
                </div>
                <h2 class="text-2xl font-bold text-white mb-2">Daily Journal</h2>
                <p class="text-zinc-500 max-w-sm mb-8">Clear your mind. Capture your days.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="create_entry">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <button class="bg-violet-600 hover:bg-violet-500 text-white px-8 py-3 rounded-full font-bold transition-all shadow-lg shadow-violet-500/20">
                        Start Writing
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </main>
    </div>

</div>
<?php $content = ob_get_clean(); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal | Monolith</title>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #27272a; border-radius: 4px; }
    </style>
</head>
<body class="bg-zinc-950 text-zinc-100">
    <?php echo $content; ?>
</body>
</html>
