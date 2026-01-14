<?php
/**
 * PAGE: questions.php
 */
require_once 'db.php';
require_once 'csrf.php';
require_once 'classes/Question.php';
require_once 'classes/QuestionCategory.php';

$qModel = new Question($pdo);
$catModel = new QuestionCategory($pdo);

// Page Logic
$current_cat_id = $_GET['cat_id'] ?? null;
$categories = $catModel->getAll();

if (!$current_cat_id && count($categories) > 0) {
    $current_cat_id = $categories[0]['id'];
}

// --- CONTROLLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        if (isset($_SERVER['HTTP_HX_REQUEST'])) { http_response_code(403); exit; }
        die("Invalid CSRF");
    }

    $action = $_POST['action'] ?? '';
    $redirect = $current_cat_id ? "?cat_id=$current_cat_id" : "?";

    try {
        // --- CATEGORIES ---
        if ($action === 'create_category') {
            $catModel->create(['title' => $_POST['title']]);
            header("Location: ?cat_id=" . $pdo->lastInsertId()); exit;
        }
        elseif ($action === 'update_category') {
            $stmt = $pdo->prepare("UPDATE question_categories SET title = :title WHERE id = :id");
            $stmt->execute(['title' => $_POST['title'], 'id' => $_POST['id']]);
            header("Location: $redirect"); exit;
        }
        elseif ($action === 'delete_category') {
            $catModel->delete($_POST['id']);
            header("Location: ?"); exit;
        }

        // --- QUESTIONS ---
        elseif ($action === 'create_question') {
            $stmt = $pdo->prepare("INSERT INTO questions (content, category_id) VALUES (:content, :cat_id)");
            $stmt->execute(['content' => $_POST['content'], 'cat_id' => $_POST['cat_id']]);
            header("Location: $redirect"); exit;
        }
        elseif ($action === 'update_status') {
            $stmt = $pdo->prepare("UPDATE questions SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $_POST['status'], 'id' => $_POST['id']]);
            if (isset($_SERVER['HTTP_HX_REQUEST'])) { renderQuestionList($pdo, $current_cat_id); exit; }
            header("Location: $redirect"); exit;
        }
        elseif ($action === 'update_notes') {
            $stmt = $pdo->prepare("UPDATE questions SET notes = :notes WHERE id = :id");
            $stmt->execute(['notes' => $_POST['notes'], 'id' => $_POST['id']]);
            if (isset($_SERVER['HTTP_HX_REQUEST'])) exit;
            header("Location: $redirect"); exit;
        }
        elseif ($action === 'delete_question') {
            $qModel->delete($_POST['id']);
            if (isset($_SERVER['HTTP_HX_REQUEST'])) { renderQuestionList($pdo, $current_cat_id); exit; }
            header("Location: $redirect"); exit;
        }
    } catch (Exception $e) { /* Log */ }
}

function renderQuestionList($pdo, $cat_id) {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE category_id = :cat_id ORDER BY FIELD(status, 'Unanswered', 'Researching', 'Answered'), created_at DESC");
    $stmt->execute(['cat_id' => $cat_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div id="questions-list" class="space-y-4 pb-20">
        <?php if (empty($questions)): ?>
            <div class="text-center py-20">
                <div class="text-6xl mb-4 opacity-50">🤔</div>
                <p class="text-zinc-500 font-medium">No questions in this category.</p>
            </div>
        <?php else: ?>
            <?php foreach ($questions as $q): ?>
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 transition-all hover:border-amber-500/30 group" x-data="{ expanded: false }">
                <div class="flex items-start gap-4">
                    <!-- Status Icon -->
                    <div class="shrink-0 mt-1">
                        <form hx-post="" hx-target="#questions-list" hx-swap="outerHTML">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id" value="<?php echo $q['id']; ?>">
                            <input type="hidden" name="cat_id" value="<?php echo $cat_id; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            
                            <?php if ($q['status'] === 'Answered'): ?>
                                <input type="hidden" name="status" value="Unanswered">
                                <button class="w-6 h-6 rounded-full bg-amber-500 flex items-center justify-center text-zinc-900 hover:bg-amber-400 transition-colors" title="Mark Unanswered">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                </button>
                            <?php else: ?>
                                <input type="hidden" name="status" value="Answered">
                                <button class="w-6 h-6 rounded-full border-2 border-zinc-600 hover:border-amber-500 transition-colors" title="Mark Answered"></button>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-start gap-4 cursor-pointer" @click="expanded = !expanded">
                            <h3 class="text-lg font-medium text-zinc-200 <?php echo $q['status'] === 'Answered' ? 'line-through opacity-50' : ''; ?>">
                                <?php echo htmlspecialchars($q['content']); ?>
                            </h3>
                            <button class="text-zinc-600 hover:text-amber-500 transition-colors shrink-0">
                                <svg class="w-5 h-5 transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </button>
                        </div>

                        <!-- Notes -->
                        <div x-show="expanded" style="display: none;" class="mt-4 pt-4 border-t border-zinc-800">
                            <form hx-post="" hx-trigger="change, blur from:textarea" hx-swap="none">
                                <input type="hidden" name="action" value="update_notes">
                                <input type="hidden" name="id" value="<?php echo $q['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <label class="block text-xs font-bold text-zinc-600 uppercase mb-2">Notes & Research</label>
                                <textarea name="notes" placeholder="Paste links or write your findings..." 
                                          class="w-full bg-zinc-950/50 border border-zinc-800 rounded-lg p-3 text-sm text-zinc-300 focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/50 outline-none min-h-[100px] resize-y"><?php echo htmlspecialchars($q['notes'] ?? ''); ?></textarea>
                            </form>
                            
                            <div class="mt-4 flex justify-between items-center">
                                <span class="text-xs text-zinc-600 font-mono"><?php echo date('M j, Y', strtotime($q['created_at'])); ?></span>
                                <form method="POST" hx-post="" hx-target="#questions-list" hx-swap="outerHTML" onsubmit="return confirm('Delete question?');">
                                    <input type="hidden" name="action" value="delete_question">
                                    <input type="hidden" name="id" value="<?php echo $q['id']; ?>">
                                    <input type="hidden" name="cat_id" value="<?php echo $cat_id; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <button class="text-xs text-red-500/50 hover:text-red-500 font-medium transition-colors">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
}
?>
<?php ob_start(); ?>

<div class="min-h-screen bg-zinc-950 text-zinc-100 font-sans selection:bg-amber-500/30" x-data="{ sidebarOpen: false }">
    
    <?php include 'navbar.php'; ?>

    <div class="flex h-[calc(100vh-64px)] pt-16">
        
        <!-- Sidebar (Desktop) -->
        <aside class="hidden md:flex w-64 bg-zinc-900 border-r border-zinc-800 flex-col">
            <div class="p-6">
                <h2 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-4">Topics</h2>
                <nav class="space-y-1">
                    <?php foreach ($categories as $cat): ?>
                    <div class="group flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $cat['id'] == $current_cat_id ? 'bg-zinc-800 text-white' : 'text-zinc-400 hover:bg-zinc-800/50 hover:text-zinc-200'; ?>">
                        <a href="?cat_id=<?php echo $cat['id']; ?>" class="flex-1 flex items-center gap-2">
                            <span class="opacity-50">#</span> <?php echo htmlspecialchars($cat['title']); ?>
                        </a>
                        <button @click="$dispatch('edit-category', {id: '<?php echo $cat['id']; ?>', title: '<?php echo addslashes(htmlspecialchars($cat['title'])); ?>'})" 
                                class="opacity-0 group-hover:opacity-100 text-zinc-500 hover:text-white p-1">
                            ✎
                        </button>
                    </div>
                    <?php endforeach; ?>
                </nav>
            </div>
            <div class="mt-auto p-4 border-t border-zinc-800">
                <button onclick="document.getElementById('new-cat-modal').classList.remove('hidden')" class="w-full py-2 text-xs font-bold text-zinc-500 hover:text-white border border-zinc-800 hover:border-zinc-600 rounded transition-colors uppercase tracking-wider">
                    + New Topic
                </button>
            </div>
        </aside>

        <!-- Sidebar (Mobile Drawer) -->
        <div x-show="sidebarOpen" class="fixed inset-0 z-[60] flex md:hidden" role="dialog" aria-modal="true" style="display: none;">
            <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black/80" @click="sidebarOpen = false"></div>

            <div x-show="sidebarOpen" x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="relative flex-1 flex flex-col max-w-xs w-full bg-zinc-900 border-r border-zinc-800">
                <div class="absolute top-0 right-0 -mr-12 pt-2">
                    <button type="button" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white" @click="sidebarOpen = false">
                        <span class="sr-only">Close sidebar</span>
                        <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 h-0 pt-5 pb-4 overflow-y-auto">
                    <div class="px-6">
                        <h2 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-4">Topics</h2>
                        <nav class="space-y-1">
                            <?php foreach ($categories as $cat): ?>
                            <div class="flex items-center justify-between px-3 py-3 rounded-lg text-base font-medium transition-colors <?php echo $cat['id'] == $current_cat_id ? 'bg-zinc-800 text-white' : 'text-zinc-400 hover:bg-zinc-800/50 hover:text-zinc-200'; ?>">
                                <a href="?cat_id=<?php echo $cat['id']; ?>" class="flex-1 flex items-center gap-3">
                                    <span class="opacity-50">#</span> <?php echo htmlspecialchars($cat['title']); ?>
                                </a>
                                <button @click="$dispatch('edit-category', {id: '<?php echo $cat['id']; ?>', title: '<?php echo addslashes(htmlspecialchars($cat['title'])); ?>'}); sidebarOpen = false;" 
                                        class="text-zinc-500 hover:text-white p-2">
                                    ✎
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </nav>
                    </div>
                </div>
                <div class="p-4 border-t border-zinc-800">
                    <button onclick="document.getElementById('new-cat-modal').classList.remove('hidden'); this.closest('[x-data]').__x.$data.sidebarOpen = false;" class="w-full py-3 text-sm font-bold text-zinc-500 hover:text-white border border-zinc-800 hover:border-zinc-600 rounded transition-colors uppercase tracking-wider">
                        + New Topic
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col relative bg-zinc-950 w-full overflow-hidden">
            
            <!-- Mobile Header -->
            <header class="h-16 border-b border-zinc-800 flex items-center justify-between px-4 bg-zinc-900/50 backdrop-blur z-10 shrink-0 md:hidden">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = true" class="text-zinc-400 hover:text-white -ml-2 p-2">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    <h1 class="font-bold text-white truncate text-lg">
                        <?php 
                            foreach($categories as $c) if($c['id'] == $current_cat_id) echo htmlspecialchars($c['title']); 
                        ?>
                    </h1>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto px-4 md:px-8 py-8 max-w-4xl mx-auto w-full">
                
                <!-- Desktop Header -->
                <div class="text-center mb-10 hidden md:block">
                    <h1 class="text-4xl font-bold text-white mb-2">
                        <?php foreach($categories as $c) if($c['id'] == $current_cat_id) echo htmlspecialchars($c['title']); ?>
                    </h1>
                    <p class="text-zinc-400">Capture your curiosity. Answer it later.</p>
                </div>

                <!-- Input Bar -->
                <?php if ($current_cat_id): ?>
                <div class="mb-12 relative group z-10">
                    <div class="absolute -inset-1 bg-gradient-to-r from-amber-500 to-orange-600 rounded-2xl blur opacity-20 group-hover:opacity-40 transition duration-500"></div>
                    <form method="POST" class="relative bg-zinc-900 rounded-xl shadow-2xl flex items-center p-2">
                        <input type="hidden" name="action" value="create_question">
                        <input type="hidden" name="cat_id" value="<?php echo $current_cat_id; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <span class="pl-4 text-2xl">🤔</span>
                        <input type="text" name="content" placeholder="What are you wondering?" required autocomplete="off"
                               class="w-full bg-transparent border-none text-lg text-white placeholder-zinc-500 focus:ring-0 px-4 py-3">
                        <button type="submit" class="bg-zinc-800 hover:bg-zinc-700 text-white px-4 py-2 rounded-lg font-bold transition-colors">
                            Ask
                        </button>
                    </form>
                </div>

                <!-- List -->
                <?php renderQuestionList($pdo, $current_cat_id); ?>
                <?php else: ?>
                    <div class="text-center py-20">
                        <p class="text-zinc-500">Create a topic to start asking questions.</p>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <!-- Modals -->
    
    <!-- New Category -->
    <div id="new-cat-modal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-6 w-full max-w-sm shadow-xl">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4">New Topic</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create_category">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="text" name="title" placeholder="Name" class="w-full bg-zinc-950 border border-zinc-800 text-white rounded px-3 py-2 mb-4 text-sm focus:border-amber-500 outline-none" required autofocus>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('new-cat-modal').classList.add('hidden')" class="px-3 py-1.5 text-zinc-400 hover:text-white text-sm">Cancel</button>
                    <button type="submit" class="bg-white text-zinc-900 px-3 py-1.5 rounded text-sm font-bold hover:bg-zinc-200">Create</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Category -->
    <div id="edit-cat-modal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-data="{ editCat: {id:'', title:''} }" @edit-category.window="editCat = $event.detail; document.getElementById('edit-cat-modal').classList.remove('hidden')">
        <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-6 w-full max-w-sm shadow-xl">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4">Edit Topic</h3>
            <form method="POST">
                <input type="hidden" name="id" x-model="editCat.id">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="text" name="title" x-model="editCat.title" class="w-full bg-zinc-950 border border-zinc-800 text-white rounded px-3 py-2 mb-4 text-sm focus:border-amber-500 outline-none" required>
                <div class="flex justify-between">
                    <button type="submit" name="action" value="delete_category" class="text-red-500 hover:text-red-400 text-sm" onclick="return confirm('Delete topic?')">Delete</button>
                    <div class="flex gap-2">
                        <button type="button" onclick="document.getElementById('edit-cat-modal').classList.add('hidden')" class="px-3 py-1.5 text-zinc-400 hover:text-white text-sm">Cancel</button>
                        <button type="submit" name="action" value="update_category" class="bg-white text-zinc-900 px-3 py-1.5 rounded text-sm font-bold hover:bg-zinc-200">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>
<?php $content = ob_get_clean(); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questions | Monolith</title>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script>
</head>
<body class="bg-zinc-950 text-zinc-100">
    <?php echo $content; ?>
</body>
</html>