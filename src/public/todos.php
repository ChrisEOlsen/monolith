<?php
/**
 * PAGE: todos.php (Dark Mode + Subtasks + Reliable HTMX)
 */
require_once 'db.php';
require_once 'csrf.php';

// Include Models
require_once 'classes/TodoList.php';
require_once 'classes/Todo.php';
require_once 'classes/Subtask.php';

$todoListModel = new TodoList($pdo);
$todoModel = new Todo($pdo);
$subtaskModel = new Subtask($pdo);

// Page Logic
$current_list_id = $_GET['list_id'] ?? null;
$lists = $todoListModel->getAll();

// Helper to count active tasks
function countActiveTasks($pdo, $list_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM todos WHERE list_id = :id AND is_done = 0");
    $stmt->execute(['id' => $list_id]);
    return $stmt->fetchColumn();
}

// Helper to calculate progress
function getListProgress($pdo, $list_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(is_done) as done FROM todos WHERE list_id = :id");
    $stmt->execute(['id' => $list_id]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($res['total'] == 0) return 0;
    return round(($res['done'] / $res['total']) * 100);
}

// Fetch Data Helper (Used for both Page Load and HTMX Partial)
function fetchListData($pdo, $list_id) {
    $stmt = $pdo->prepare("SELECT * FROM todos WHERE list_id = :list_id ORDER BY sort_order ASC, is_done ASC, created_at DESC");
    $stmt->execute(['list_id' => $list_id]);
    $current_todos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($current_todos as &$todo) {
        $stmt = $pdo->prepare("SELECT * FROM subtasks WHERE todo_id = :id ORDER BY created_at ASC");
        $stmt->execute(['id' => $todo['id']]);
        $all_subtasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $todo['active_subtasks'] = array_filter($all_subtasks, fn($s) => !$s['is_done']);
        $todo['completed_subtasks'] = array_filter($all_subtasks, fn($s) => $s['is_done']);
    }
    return $current_todos;
}

// --- CONTROLLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        if (isset($_SERVER['HTTP_HX_REQUEST'])) { http_response_code(403); exit; }
        die("Invalid CSRF");
    }

    $action = $_POST['action'] ?? '';
    $redirect_url = $current_list_id ? "?list_id=$current_list_id" : "?";

    try {
        if ($action === 'create_list') {
            $todoListModel->create(['title' => $_POST['title']]);
            header("Location: ?"); exit;
        }
        elseif ($action === 'delete_list') {
            $todoListModel->delete($_POST['id']);
            header("Location: ?"); exit;
        }
        // Actions that affect the List View (HTMX Target)
        elseif (in_array($action, ['create_task', 'toggle_task', 'delete_task', 'update_task', 'clear_completed', 'create_subtask', 'toggle_subtask', 'delete_subtask', 'update_subtask', 'update_order'])) {
            
            // Perform DB Update
            if ($action === 'create_task') {
                $stmt = $pdo->prepare("INSERT INTO todos (title, list_id, is_done, sort_order) VALUES (:title, :list_id, 0, 0)"); // Default 0 or max
                $stmt->execute(['title' => $_POST['title'], 'list_id' => $_POST['list_id']]);
            }
            elseif ($action === 'update_order') {
                $ids = $_POST['task_ids'] ?? [];
                if (is_array($ids)) {
                    $stmt = $pdo->prepare("UPDATE todos SET sort_order = :order WHERE id = :id");
                    foreach ($ids as $index => $id) {
                        $stmt->execute(['order' => $index, 'id' => $id]);
                    }
                }
                // No need to re-render, order is updated on client via drag. 
                // But re-rendering ensures consistency. Let's exit to avoid flicker if unwanted.
                if (isset($_SERVER['HTTP_HX_REQUEST'])) exit;
            }
            elseif ($action === 'update_task') {
                $stmt = $pdo->prepare("UPDATE todos SET title = :title WHERE id = :id");
                $stmt->execute(['title' => $_POST['title'], 'id' => $_POST['id']]);
            }
            elseif ($action === 'toggle_task') {
                $stmt = $pdo->prepare("UPDATE todos SET is_done = NOT is_done WHERE id = :id");
                $stmt->execute(['id' => $_POST['id']]);
            }
            elseif ($action === 'delete_task') {
                $todoModel->delete($_POST['id']);
            }
            elseif ($action === 'clear_completed') {
                $stmt = $pdo->prepare("DELETE FROM todos WHERE list_id = :list_id AND is_done = 1");
                $stmt->execute(['list_id' => $_POST['list_id']]);
            }
            elseif ($action === 'create_subtask') {
                $stmt = $pdo->prepare("INSERT INTO subtasks (title, todo_id, is_done) VALUES (:title, :todo_id, 0)");
                $stmt->execute(['title' => $_POST['title'], 'todo_id' => $_POST['todo_id']]);
            }
            elseif ($action === 'update_subtask') {
                $stmt = $pdo->prepare("UPDATE subtasks SET title = :title WHERE id = :id");
                $stmt->execute(['title' => $_POST['title'], 'id' => $_POST['id']]);
            }
            elseif ($action === 'toggle_subtask') {
                $stmt = $pdo->prepare("UPDATE subtasks SET is_done = NOT is_done WHERE id = :id");
                $stmt->execute(['id' => $_POST['id']]);
            }
            elseif ($action === 'delete_subtask') {
                $subtaskModel->delete($_POST['id']);
            }

            // HTMX Response: Re-render the list container
            if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                $current_list_id = $_POST['list_id'];
                $current_todos = fetchListData($pdo, $current_list_id);
                // Render just the tasks container
                renderTasksContainer($current_todos, $current_list_id);
                exit;
            }
            
            header("Location: $redirect_url"); exit;
        }

    } catch (Exception $e) { /* Log error */ }
}

// Fetch Tasks if in a list
$current_todos = [];
$current_list_title = "";
if ($current_list_id) {
    foreach ($lists as $l) { if ($l['id'] == $current_list_id) { $current_list_title = $l['title']; break; } }
    $current_todos = fetchListData($pdo, $current_list_id);
}

// --- VIEW HELPER FUNCTION ---
function renderTasksContainer($current_todos, $current_list_id) {
    $active_tasks = array_filter($current_todos, fn($t) => !$t['is_done']);
    $completed_tasks = array_filter($current_todos, fn($t) => $t['is_done']);
    ?>
    <div id="tasks-container" class="flex-1 overflow-y-auto px-2 md:px-8 py-6 space-y-4 pb-24 md:pb-6">
        
        <!-- Active Tasks -->
        <div class="space-y-3 sortable-list">
            <?php foreach ($active_tasks as $todo): ?>
                <?php renderTaskItem($todo, $current_list_id); ?>
            <?php endforeach; ?>
        </div>

        <!-- Sort Order Form (Hidden) -->
        <form id="sort-form" hx-post="" hx-trigger="submit" hx-swap="none">
            <input type="hidden" name="action" value="update_order">
            <input type="hidden" name="list_id" value="<?php echo $current_list_id; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <!-- task_ids[] will be appended here by JS -->
        </form>

        <?php if (empty($active_tasks) && empty($completed_tasks)): ?>
            <div class="text-center py-20">
                <div class="text-zinc-700 text-6xl mb-4">📝</div>
                <p class="text-zinc-500">List is empty.</p>
            </div>
        <?php endif; ?>

        <!-- Completed Tasks Dropdown -->
        <?php if (!empty($completed_tasks)): ?>
        <div x-data="{ open: false }" class="mt-8 pt-4 border-t border-zinc-100/10">
            <div class="flex items-center justify-between mb-4">
                <button @click="open = !open" class="flex items-center gap-2 text-sm font-medium text-zinc-500 hover:text-zinc-300 transition-colors">
                    <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    <span>Completed (<?php echo count($completed_tasks); ?>)</span>
                </button>

                <form method="POST" hx-post="" hx-target="#tasks-container" hx-swap="outerHTML" onsubmit="return confirm('Clear all completed tasks?');">
                    <input type="hidden" name="action" value="clear_completed">
                    <input type="hidden" name="list_id" value="<?php echo $current_list_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <button type="submit" class="text-xs font-medium text-zinc-600 hover:text-red-500 transition-colors px-2 py-1 rounded hover:bg-zinc-800">Clear All</button>
                </form>
            </div>
            
            <div x-show="open" x-collapse class="space-y-3 opacity-60" style="display: none;">
                <?php foreach ($completed_tasks as $todo): ?>
                    <?php renderTaskItem($todo, $current_list_id); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

function renderTaskItem($todo, $current_list_id) {
    ?>
    <div id="todo-<?php echo $todo['id']; ?>" data-id="<?php echo $todo['id']; ?>" class="bg-zinc-900 border border-zinc-800 rounded-xl p-3 md:p-4 transition-all hover:border-zinc-700 group flex gap-4">
        <!-- Sort Order Input (Deprecated, handled by JS now) -->
        
        <!-- Drag Handle -->
        <div class="drag-handle cursor-move text-zinc-600 hover:text-zinc-400 pt-1 hidden group-hover:block">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" /></svg>
        </div>

        <div class="flex items-start gap-4 flex-1">
            <!-- Main Checkbox -->
            <form hx-post="" hx-target="#tasks-container" hx-swap="outerHTML">
                <input type="hidden" name="action" value="toggle_task">
                <input type="hidden" name="id" value="<?php echo $todo['id']; ?>">
                <input type="hidden" name="list_id" value="<?php echo $current_list_id; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <label class="cursor-pointer relative flex items-center justify-center w-6 h-6 rounded-full border-2 border-zinc-600 hover:border-indigo-500 transition-colors <?php echo $todo['is_done'] ? 'bg-indigo-600 border-indigo-600' : ''; ?>">
                    <input type="checkbox" class="hidden" onchange="htmx.trigger(this.form, 'submit')" <?php echo $todo['is_done'] ? 'checked' : ''; ?>>
                    <svg class="w-3.5 h-3.5 text-white <?php echo $todo['is_done'] ? 'opacity-100' : 'opacity-0'; ?> transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                </label>
            </form>

            <div class="flex-1 min-w-0">
                <div class="flex justify-between items-start">
                    <form class="flex-1 mr-2" hx-post="" hx-target="#tasks-container" hx-trigger="change, submit" hx-swap="outerHTML">
                        <input type="hidden" name="action" value="update_task">
                        <input type="hidden" name="id" value="<?php echo $todo['id']; ?>">
                        <input type="hidden" name="list_id" value="<?php echo $current_list_id; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="text" name="title" value="<?php echo htmlspecialchars($todo['title']); ?>" 
                               class="w-full bg-transparent border-0 p-0 text-lg font-medium <?php echo $todo['is_done'] ? 'text-zinc-500 line-through' : 'text-zinc-200'; ?> focus:ring-0 focus:border-b focus:border-indigo-500 transition-colors placeholder-zinc-600"
                               autocomplete="off">
                    </form>
                    
                    <form method="POST" hx-post="" hx-target="#tasks-container" hx-swap="outerHTML">
                        <input type="hidden" name="action" value="delete_task">
                        <input type="hidden" name="id" value="<?php echo $todo['id']; ?>">
                        <input type="hidden" name="list_id" value="<?php echo $current_list_id; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <button class="text-zinc-600 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                    </form>
                </div>

                <!-- Subtasks -->
                <div class="mt-3 pl-0 space-y-2">
                    <?php 
                    // Render Active Subtasks
                    foreach ($todo['active_subtasks'] as $sub) { renderSubtaskItem($sub, $current_list_id); }
                    
                    // Render Completed Subtasks (Inline or hidden?)
                    // Requirement: "clicking on a subtask... put it into the 'completed' dropdown."
                    // If this dropdown refers to the MAIN 'Completed' list, that's for main tasks.
                    // For subtasks, usually they just get crossed out or stay in place. 
                    // Let's keep them in place but crossed out for now, OR move them to a mini-completed list inside the card.
                    // Let's render completed subtasks at the bottom of the card, slightly faded.
                    if (!empty($todo['completed_subtasks'])) {
                        echo '<div class="pt-2 border-t border-zinc-800 mt-2">';
                        foreach ($todo['completed_subtasks'] as $sub) { renderSubtaskItem($sub, $current_list_id); }
                        echo '</div>';
                    }
                    ?>

                    <!-- Add Subtask Input -->
                    <form method="POST" hx-post="" hx-target="#tasks-container" hx-swap="outerHTML" class="mt-2 flex items-center gap-2 pl-2">
                        <input type="hidden" name="action" value="create_subtask">
                        <input type="hidden" name="todo_id" value="<?php echo $todo['id']; ?>">
                        <input type="hidden" name="list_id" value="<?php echo $current_list_id; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="w-4 h-0.5 bg-zinc-700"></div>
                        <input type="text" name="title" placeholder="Add subtask..." required
                               class="bg-transparent border-none text-xs text-zinc-300 placeholder-zinc-600 focus:ring-0 w-full p-0">
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function renderSubtaskItem($sub, $current_list_id) {
    ?>
    <div id="subtask-<?php echo $sub['id']; ?>" class="flex items-center gap-3 pl-2 py-1 group">
        <form hx-post="" hx-target="#tasks-container" hx-swap="outerHTML">
            <input type="hidden" name="action" value="toggle_subtask">
            <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
            <input type="hidden" name="list_id" value="<?php echo $current_list_id; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <label class="cursor-pointer w-4 h-4 rounded border border-zinc-600 hover:border-indigo-400 flex items-center justify-center <?php echo $sub['is_done'] ? 'bg-indigo-600 border-indigo-600' : ''; ?>">
                <input type="checkbox" class="hidden" onchange="htmx.trigger(this.form, 'submit')" <?php echo $sub['is_done'] ? 'checked' : ''; ?>>
                <svg class="w-2.5 h-2.5 text-white <?php echo $sub['is_done'] ? 'opacity-100' : 'opacity-0'; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
            </label>
        </form>
        
        <form class="flex-1" hx-post="" hx-target="#tasks-container" hx-trigger="change, submit" hx-swap="outerHTML">
            <input type="hidden" name="action" value="update_subtask">
            <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
            <input type="hidden" name="list_id" value="<?php echo $current_list_id; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="text" name="title" value="<?php echo htmlspecialchars($sub['title']); ?>" 
                   class="w-full bg-transparent border-0 p-0 text-sm <?php echo $sub['is_done'] ? 'text-zinc-600 line-through' : 'text-zinc-400'; ?> focus:ring-0 focus:border-b focus:border-indigo-500 transition-colors placeholder-zinc-700"
                   autocomplete="off">
        </form>
        
        <form method="POST" hx-post="" hx-target="#tasks-container" hx-swap="outerHTML" class="opacity-0 group-hover:opacity-100 transition-opacity">
            <input type="hidden" name="action" value="delete_subtask">
            <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
            <input type="hidden" name="list_id" value="<?php echo $current_list_id; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <button class="text-zinc-700 hover:text-red-500"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
        </form>
    </div>
    <?php
}
?>
<?php ob_start(); ?>

<div class="min-h-screen bg-zinc-950 text-zinc-100 font-sans selection:bg-indigo-500/30">
    
    <?php include 'navbar.php'; ?>

    <main class="max-w-7xl mx-auto px-6 py-8 pt-24">
        
        <?php if (!$current_list_id): ?>
        <!-- === DASHBOARD VIEW === -->
        <div class="mb-8 flex items-end justify-between">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2">My Lists</h1>
                <p class="text-zinc-400">Overview of all your projects.</p>
            </div>
            <button onclick="document.getElementById('new-list-modal').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg font-medium transition-all shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/40 flex items-center gap-2">
                <span>+</span> New List
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($lists as $list): 
                $active = countActiveTasks($pdo, $list['id']);
                $progress = getListProgress($pdo, $list['id']);
            ?>
            <a href="?list_id=<?php echo $list['id']; ?>" class="group bg-zinc-900 border border-zinc-800 hover:border-indigo-500/50 rounded-2xl p-6 transition-all hover:shadow-2xl hover:shadow-indigo-500/10 hover:-translate-y-1 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-24 bg-indigo-500/5 blur-3xl rounded-full -mr-12 -mt-12 group-hover:bg-indigo-500/10 transition-colors"></div>
                
                <div class="relative z-10">
                    <div class="flex justify-between items-start mb-4">
                        <div class="opacity-0 group-hover:opacity-100 transition-opacity ml-auto">
                            <form method="POST" onsubmit="return confirm('Delete list?');">
                                <input type="hidden" name="action" value="delete_list">
                                <input type="hidden" name="id" value="<?php echo $list['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <button class="text-zinc-500 hover:text-red-500 p-1"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                            </form>
                        </div>
                    </div>
                    
                    <h2 class="text-xl font-bold text-zinc-100 mb-1"><?php echo htmlspecialchars($list['title']); ?></h2>
                    <p class="text-zinc-400 text-sm mb-6"><?php echo $active; ?> active tasks</p>
                    
                    <div class="w-full bg-zinc-800 h-1.5 rounded-full overflow-hidden">
                        <div class="bg-indigo-500 h-full rounded-full transition-all duration-500" style="width: <?php echo $progress; ?>%"></div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        
        <!-- === LIST DETAIL VIEW === -->
        <div class="max-w-3xl mx-auto">
            <header class="mb-8">
                <h1 class="text-4xl font-bold text-white mb-2"><?php echo htmlspecialchars($current_list_title); ?></h1>
                <div class="flex items-center gap-4 text-sm text-zinc-400">
                    <span><?php echo date('l, F j'); ?></span>
                    <span class="w-1 h-1 bg-zinc-700 rounded-full"></span>
                    <span><?php echo countActiveTasks($pdo, $current_list_id); ?> tasks remaining</span>
                </div>
            </header>

            <!-- Add Task -->
            <div class="mb-8">
                <form method="POST" class="relative group" hx-post="" hx-target="#tasks-container" hx-swap="outerHTML" hx-on::after-request="this.reset()">
                    <input type="hidden" name="action" value="create_task">
                    <input type="hidden" name="list_id" value="<?php echo $current_list_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                        <span class="text-zinc-500 text-xl">+</span>
                    </div>
                    <input type="text" name="title" placeholder="Add a new task..." required autocomplete="off"
                           class="w-full bg-zinc-900 border border-zinc-800 text-zinc-100 pl-10 pr-4 py-4 rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 outline-none transition-all placeholder-zinc-600">
                </form>
            </div>

            <!-- Tasks Container (HTMX Target) -->
            <?php renderTasksContainer($current_todos, $current_list_id); ?>
            
        </div>
        <?php endif; ?>

    </main>

    <!-- New List Modal -->
    <div id="new-list-modal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-zinc-900 border border-zinc-700 rounded-2xl p-6 w-full max-w-sm shadow-2xl">
            <h3 class="text-xl font-bold text-white mb-4">Create New List</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create_list">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="text" name="title" placeholder="List Name" class="w-full bg-zinc-950 border border-zinc-800 text-white rounded-lg px-4 py-3 mb-4 focus:ring-2 focus:ring-indigo-500 outline-none" required autofocus>
                <div class="flex gap-3">
                    <button type="button" onclick="document.getElementById('new-list-modal').classList.add('hidden')" class="flex-1 px-4 py-2 rounded-lg text-zinc-400 hover:bg-zinc-800 transition-colors">Cancel</button>
                    <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg font-medium transition-colors">Create</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Alpine.js for interactions -->
    <script src="//unpkg.com/alpinejs" defer></script>

</div>
<?php $content = ob_get_clean(); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>TaskMaster</title>
        <script src="https://unpkg.com/htmx.org@1.9.10"></script>
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
    </head>
    <body class="bg-zinc-950 text-zinc-100">
        <?php echo $content; ?>
        
            <script>
                htmx.onLoad(function(content) {
                    var sortables = content.querySelectorAll(".sortable-list");
                    for (var i = 0; i < sortables.length; i++) {
                        var sortable = sortables[i];
                        new Sortable(sortable, {
                            handle: '.drag-handle',
                            animation: 150,
                            ghostClass: 'bg-zinc-800',
                            onEnd: function (evt) {
                                var itemEl = evt.item;
                                var container = itemEl.parentElement;
                                var sortForm = document.getElementById('sort-form');
                                
                                // Clear previous IDs
                                var existingInputs = sortForm.querySelectorAll('input[name="task_ids[]"]');
                                existingInputs.forEach(input => input.remove());
        
                                // Collect new order
                                var items = container.querySelectorAll('[data-id]');
                                items.forEach(function(item) {
                                    var input = document.createElement('input');
                                    input.type = 'hidden';
                                    input.name = 'task_ids[]';
                                    input.value = item.getAttribute('data-id');
                                    sortForm.appendChild(input);
                                });
        
                                // Trigger HTMX
                                htmx.trigger(sortForm, 'submit');
                            }
                        });
                    }
                });
            </script>
        </body>
        </html>
        