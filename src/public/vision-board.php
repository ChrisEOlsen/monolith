<?php
/**
 * PAGE: vision-board.php
 */
require_once 'db.php';
require_once 'csrf.php';

// Include Models
require_once 'classes/VisionCategory.php';
require_once 'classes/VisionGoal.php';
require_once 'classes/VisionMilestone.php';

$catModel = new VisionCategory($pdo);
$goalModel = new VisionGoal($pdo);
$milestoneModel = new VisionMilestone($pdo);

// Page Logic
$current_cat_id = $_GET['cat_id'] ?? null;
$categories = $catModel->getAll();

if (!$current_cat_id && count($categories) > 0) {
    $current_cat_id = $categories[0]['id'];
}

function getGoalProgress($pdo, $goal_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(is_done) as done FROM vision_milestones WHERE goal_id = :id");
    $stmt->execute(['id' => $goal_id]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($res['total'] == 0) return 0;
    return round(($res['done'] / $res['total']) * 100);
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
        if ($action === 'create_category') {
            $catModel->create(['title' => $_POST['title']]);
            header("Location: ?cat_id=" . $pdo->lastInsertId()); exit;
        }
        elseif ($action === 'update_category') {
            $stmt = $pdo->prepare("UPDATE vision_categories SET title = :title WHERE id = :id");
            $stmt->execute(['title' => $_POST['title'], 'id' => $_POST['id']]);
            header("Location: $redirect"); exit;
        }
        elseif ($action === 'delete_category') {
            $catModel->delete($_POST['id']);
            header("Location: ?"); exit;
        }
        elseif ($action === 'create_goal') {
            $stmt = $pdo->prepare("INSERT INTO vision_goals (category_id, title, target_year) VALUES (:cat_id, :title, :year)");
            $stmt->execute([
                'cat_id' => $_POST['cat_id'],
                'title' => $_POST['title'],
                'year' => $_POST['target_year']
            ]);
            header("Location: $redirect"); exit;
        }
        elseif ($action === 'update_goal') {
            $stmt = $pdo->prepare("UPDATE vision_goals SET title = :title, target_year = :year WHERE id = :id");
            $stmt->execute([
                'title' => $_POST['title'],
                'year' => $_POST['target_year'],
                'id' => $_POST['id']
            ]);
            header("Location: $redirect"); exit;
        }
        elseif ($action === 'delete_goal') {
            $goalModel->delete($_POST['id']);
            header("Location: $redirect"); exit;
        }
        elseif ($action === 'create_milestone') {
            $stmt = $pdo->prepare("INSERT INTO vision_milestones (goal_id, title, is_done) VALUES (:goal_id, :title, 0)");
            $stmt->execute(['goal_id' => $_POST['goal_id'], 'title' => $_POST['title']]);
        }
        elseif ($action === 'toggle_milestone') {
            $stmt = $pdo->prepare("UPDATE vision_milestones SET is_done = NOT is_done WHERE id = :id");
            $stmt->execute(['id' => $_POST['id']]);
        }
        elseif ($action === 'delete_milestone') {
            $milestoneModel->delete($_POST['id']);
        }

        // HTMX Response
        if (isset($_SERVER['HTTP_HX_REQUEST'])) {
            $goal_id = $_POST['goal_id'] ?? null;
            if ($goal_id) {
                $stmt = $pdo->prepare("SELECT * FROM vision_goals WHERE id = :id");
                $stmt->execute(['id' => $goal_id]);
                $goal = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Fetch fresh milestones
                $stmt = $pdo->prepare("SELECT * FROM vision_milestones WHERE goal_id = :id ORDER BY created_at ASC, id ASC");
                $stmt->execute(['id' => $goal_id]);
                $all_milestones = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $goal['active_milestones'] = array_filter($all_milestones, fn($m) => !$m['is_done']);
                $goal['completed_milestones'] = array_filter($all_milestones, fn($m) => $m['is_done']);
                $goal['progress'] = getGoalProgress($pdo, $goal_id);
                
                renderGoalCard($goal, $current_cat_id, $pdo);
                exit;
            }
        }

    } catch (Exception $e) { /* Log */ }
}

$goals = [];
if ($current_cat_id) {
    $stmt = $pdo->prepare("SELECT * FROM vision_goals WHERE category_id = :id ORDER BY target_year ASC, created_at DESC");
    $stmt->execute(['id' => $current_cat_id]);
    $goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($goals as &$goal) {
        $stmt = $pdo->prepare("SELECT * FROM vision_milestones WHERE goal_id = :id ORDER BY is_done ASC, created_at ASC, id ASC");
        $stmt->execute(['id' => $goal['id']]);
        $all_milestones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $goal['active_milestones'] = array_filter($all_milestones, fn($m) => !$m['is_done']);
        $goal['completed_milestones'] = array_filter($all_milestones, fn($m) => $m['is_done']);
        $goal['progress'] = getGoalProgress($pdo, $goal['id']);
    }
    unset($goal);
}

function renderGoalCard($goal, $current_cat_id, $pdo) {
    $goalData = htmlspecialchars(json_encode([
        'id' => $goal['id'],
        'title' => $goal['title'],
        'target_year' => $goal['target_year']
    ]), ENT_QUOTES, 'UTF-8');
    ?>
    <div id="goal-<?php echo $goal['id']; ?>" class="group relative bg-zinc-900 border border-zinc-800 rounded-3xl p-6 flex flex-col transition-all hover:border-zinc-700 hover:shadow-2xl" x-data="{ expanded: false }">
        
        <!-- Header -->
        <div class="flex justify-between items-start mb-4">
            <div class="flex-1 min-w-0">
                <?php if ($goal['target_year']): ?>
                    <span class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest mb-1 block">
                        Target <?php echo htmlspecialchars($goal['target_year']); ?>
                    </span>
                <?php endif; ?>
                <h3 class="text-xl font-bold text-white tracking-tight truncate">
                    <?php echo htmlspecialchars($goal['title']); ?>
                </h3>
            </div>

            <!-- Actions -->
            <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                <button @click.stop="$dispatch('edit-goal', <?php echo $goalData; ?>)" class="text-zinc-500 hover:text-white p-1.5 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                </button>
                <form method="POST" onsubmit="return confirm('Delete goal?');">
                    <input type="hidden" name="action" value="delete_goal">
                    <input type="hidden" name="id" value="<?php echo $goal['id']; ?>">
                    <input type="hidden" name="cat_id" value="<?php echo $current_cat_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <button class="text-zinc-500 hover:text-red-500 p-1.5 transition-colors"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                </form>
            </div>
        </div>

        <!-- Progress -->
        <div class="mb-6">
            <div class="flex justify-between text-[10px] uppercase font-bold text-zinc-600 mb-1.5 tracking-wider">
                <span>Milestones</span>
                <span><?php echo $goal['progress']; ?>%</span>
            </div>
            <div class="w-full bg-zinc-800 h-1 rounded-full overflow-hidden">
                <div class="bg-indigo-500 h-full rounded-full transition-all duration-1000" style="width: <?php echo $goal['progress']; ?>%"></div>
            </div>
                    </div>
        
                    <!-- Milestones List -->
                    <div class="space-y-2 flex-1 overflow-y-auto max-h-64 custom-scrollbar pr-1">
                        <?php foreach ($goal['active_milestones'] as $ms): ?>
                        <div id="milestone-<?php echo $ms['id']; ?>" class="flex items-start gap-3 group/ms">
        
                <form hx-post="" hx-trigger="change" hx-target="#goal-<?php echo $goal['id']; ?>" hx-swap="outerHTML">
                    <input type="hidden" name="action" value="toggle_milestone">
                    <input type="hidden" name="id" value="<?php echo $ms['id']; ?>">
                    <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                    <input type="hidden" name="cat_id" value="<?php echo $current_cat_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <label class="cursor-pointer mt-0.5 flex items-center justify-center w-4 h-4 rounded border border-zinc-700 hover:border-indigo-500 transition-colors">
                        <input type="checkbox" class="hidden" name="status" onchange="htmx.trigger(this.form, 'submit')">
                        <svg class="w-2.5 h-2.5 text-white opacity-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    </label>
                </form>
                <span class="text-sm flex-1 text-zinc-400 group-hover/ms:text-zinc-200 transition-colors line-clamp-2"><?php echo htmlspecialchars($ms['title']); ?></span>
                <form method="POST" hx-post="" hx-target="#goal-<?php echo $goal['id']; ?>" hx-swap="outerHTML" class="opacity-0 group-hover/ms:opacity-100 transition-opacity">
                    <input type="hidden" name="action" value="delete_milestone">
                    <input type="hidden" name="id" value="<?php echo $ms['id']; ?>">
                    <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                    <input type="hidden" name="cat_id" value="<?php echo $current_cat_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <button class="text-zinc-700 hover:text-red-500"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                </form>
            </div>
            <?php endforeach; ?>

            <!-- Completed Milestones -->
            <?php if (!empty($goal['completed_milestones'])): ?>
            <div x-data="{ open: false }" class="pt-2 border-t border-zinc-800/50">
                <button @click="open = !open" class="flex items-center gap-2 text-[10px] font-bold text-zinc-600 hover:text-zinc-400 transition-colors uppercase tracking-widest w-full">
                    <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    <span>Done (<?php echo count($goal['completed_milestones']); ?>)</span>
                </button>
                <div x-show="open" style="display: none;" class="mt-2 space-y-1 opacity-40">
                    <?php foreach ($goal['completed_milestones'] as $ms): ?>
                    <div class="flex items-start gap-3">
                        <form hx-post="" hx-trigger="change" hx-target="#goal-<?php echo $goal['id']; ?>" hx-swap="outerHTML">
                            <input type="hidden" name="action" value="toggle_milestone">
                            <input type="hidden" name="id" value="<?php echo $ms['id']; ?>">
                            <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                            <input type="hidden" name="cat_id" value="<?php echo $current_cat_id; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <label class="cursor-pointer mt-0.5 flex items-center justify-center w-4 h-4 rounded border border-zinc-700 bg-indigo-600 border-indigo-600">
                                <input type="checkbox" class="hidden" name="status" onchange="htmx.trigger(this.form, 'submit')" checked>
                                <svg class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            </label>
                        </form>
                        <span class="text-xs text-zinc-500 line-through"><?php echo htmlspecialchars($ms['title']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Add Milestone -->
        <form method="POST" hx-post="" hx-target="#goal-<?php echo $goal['id']; ?>" hx-swap="outerHTML" class="mt-4 pt-4 border-t border-zinc-800/50">
            <input type="hidden" name="action" value="create_milestone">
            <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
            <input type="hidden" name="cat_id" value="<?php echo $current_cat_id; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="text" name="title" placeholder="+ Add step" class="bg-transparent border-none text-xs text-indigo-400 placeholder-zinc-700 focus:ring-0 w-full p-0 font-medium" required autocomplete="off">
        </form>
    </div>
    <?php
}
ob_start(); ?>

<div class="min-h-screen bg-zinc-950 text-zinc-100 font-sans selection:bg-indigo-500/30" 
     x-data="{ 
        editGoal: {id: '', title: '', target_year: ''},
        editCat: {id: '', title: ''}
     }"
     @edit-goal.window="editGoal = $event.detail; document.getElementById('edit-goal-modal').classList.remove('hidden')"
     @edit-category.window="editCat = $event.detail; document.getElementById('edit-cat-modal').classList.remove('hidden')"
>
    
    <?php include 'navbar.php'; ?>

    <div class="pt-24 pb-8 px-6 max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
            <div>
                <h1 class="text-4xl font-bold text-white tracking-tight mb-2">Vision Board</h1>
                <p class="text-zinc-500 font-medium">Define your future pillars.</p>
            </div>
            <button onclick="document.getElementById('new-goal-modal').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-2.5 rounded-xl font-bold transition-all shadow-lg shadow-indigo-500/20 flex items-center gap-2">
                <span>+</span> New Goal
            </button>
        </div>

        <div class="flex items-center gap-2 overflow-x-auto pt-2 pb-4 no-scrollbar border-b border-zinc-900 mb-8">
            <?php foreach ($categories as $cat): ?>
            <div class="relative group/cat shrink-0">
                <a href="?cat_id=<?php echo $cat['id']; ?>" 
                   class="px-5 py-2 rounded-xl text-sm font-bold transition-all block <?php echo $cat['id'] == $current_cat_id ? 'bg-zinc-800 text-indigo-400 border border-zinc-700 pr-10' : 'text-zinc-500 hover:text-zinc-300 hover:bg-zinc-900/50'; ?>">
                    <?php echo htmlspecialchars($cat['title']); ?>
                </a>
                <?php if ($cat['id'] == $current_cat_id): 
                    $catData = htmlspecialchars(json_encode(['id' => $cat['id'], 'title' => $cat['title']]), ENT_QUOTES, 'UTF-8');
                ?>
                <button @click="$dispatch('edit-category', <?php echo $catData; ?>)" 
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-600 hover:text-white p-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                </button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <button onclick="document.getElementById('new-cat-modal').classList.remove('hidden')" class="ml-2 w-10 h-10 rounded-xl border border-zinc-800 flex items-center justify-center text-zinc-600 hover:text-white hover:border-zinc-600 transition-colors shrink-0 font-bold">
                +
            </button>
        </div>

        <main class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php if (empty($goals)): ?>
                <div class="col-span-full py-32 text-center">
                    <p class="text-zinc-600 font-bold uppercase tracking-widest text-xs">Waiting for your vision...</p>
                </div>
            <?php else: ?>
                <?php foreach ($goals as $goal): ?>
                    <?php renderGoalCard($goal, $current_cat_id, $pdo); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modals -->
    <div id="new-cat-modal" class="hidden fixed inset-0 bg-black/90 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-8 w-full max-w-sm shadow-2xl">
            <h3 class="text-2xl font-bold text-white mb-6">New Pillar</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create_category">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="text" name="title" placeholder="e.g. Finance, Legacy" class="w-full bg-zinc-950 border border-zinc-800 text-white rounded-xl px-4 py-4 mb-6 focus:ring-2 focus:ring-indigo-500 outline-none font-medium" required autofocus>
                <div class="flex gap-3">
                    <button type="button" onclick="document.getElementById('new-cat-modal').classList.add('hidden')" class="flex-1 px-4 py-3 rounded-xl text-zinc-500 font-bold hover:bg-zinc-800 transition-colors">Cancel</button>
                    <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-3 rounded-xl font-bold transition-colors shadow-lg shadow-indigo-500/20">Create</button>
                </div>
            </form>
        </div>
    </div>

    <div id="edit-cat-modal" class="hidden fixed inset-0 bg-black/90 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-8 w-full max-w-sm shadow-2xl">
            <h3 class="text-2xl font-bold text-white mb-6">Edit Pillar</h3>
            <form method="POST">
                <input type="hidden" name="id" x-model="editCat.id">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="text" name="title" x-model="editCat.title" class="w-full bg-zinc-950 border border-zinc-800 text-white rounded-xl px-4 py-4 mb-6 focus:ring-2 focus:ring-indigo-500 outline-none font-medium" required>
                <div class="flex flex-col gap-3">
                    <button type="submit" name="action" value="update_category" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white py-4 rounded-xl font-bold transition-colors shadow-lg shadow-indigo-500/20">Save Changes</button>
                    <div class="flex gap-3">
                        <button type="button" onclick="document.getElementById('edit-cat-modal').classList.add('hidden')" class="flex-1 py-3 text-zinc-500 font-bold hover:bg-zinc-800 rounded-xl transition-colors">Cancel</button>
                        <button type="submit" name="action" value="delete_category" class="flex-1 py-3 text-red-500 font-bold hover:bg-red-500/10 rounded-xl transition-colors" onclick="return confirm('Delete pillar?')">Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="new-goal-modal" class="hidden fixed inset-0 bg-black/90 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-8 w-full max-w-md shadow-2xl">
            <h3 class="text-2xl font-bold text-white mb-6">New Goal</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create_goal">
                <input type="hidden" name="cat_id" value="<?php echo $current_cat_id; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="space-y-4 mb-8">
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-500 uppercase tracking-widest mb-2">Goal Title</label>
                        <input type="text" name="title" placeholder="What is your ambition?" class="w-full bg-zinc-950 border border-zinc-800 text-white rounded-xl px-4 py-4 focus:ring-2 focus:ring-indigo-500 outline-none font-medium" required>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-500 uppercase tracking-widest mb-2">Target Year</label>
                        <input type="number" name="target_year" value="<?php echo date('Y'); ?>" class="w-full bg-zinc-950 border border-zinc-800 text-white rounded-xl px-4 py-4 focus:ring-2 focus:ring-indigo-500 outline-none font-medium">
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="document.getElementById('new-goal-modal').classList.add('hidden')" class="flex-1 py-4 text-zinc-500 font-bold hover:bg-zinc-800 rounded-xl transition-colors">Cancel</button>
                    <button type="submit" class="flex-1 bg-white text-zinc-950 py-4 rounded-xl font-bold hover:bg-zinc-200 transition-colors shadow-lg shadow-white/5">Set Goal</button>
                </div>
            </form>
        </div>
    </div>

    <div id="edit-goal-modal" class="hidden fixed inset-0 bg-black/90 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-8 w-full max-w-md shadow-2xl">
            <h3 class="text-2xl font-bold text-white mb-6">Edit Goal</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_goal">
                <input type="hidden" name="id" x-model="editGoal.id">
                <input type="hidden" name="cat_id" value="<?php echo $current_cat_id; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="space-y-4 mb-8">
                    <input type="text" name="title" x-model="editGoal.title" class="w-full bg-zinc-950 border border-zinc-800 text-white rounded-xl px-4 py-4 focus:ring-2 focus:ring-indigo-500 outline-none font-medium" required>
                    <input type="number" name="target_year" x-model="editGoal.target_year" class="w-full bg-zinc-950 border border-zinc-800 text-white rounded-xl px-4 py-4 focus:ring-2 focus:ring-indigo-500 outline-none font-medium">
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="document.getElementById('edit-goal-modal').classList.add('hidden')" class="flex-1 py-4 text-zinc-500 font-bold hover:bg-zinc-800 rounded-xl transition-colors">Cancel</button>
                    <button type="submit" class="flex-1 bg-white text-zinc-950 py-4 rounded-xl font-bold hover:bg-zinc-200 transition-colors shadow-lg shadow-white/5">Save Changes</button>
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
    <title>Vision Board | Monolith</title>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script>
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #27272a; border-radius: 10px; }
    </style>
</head>
<body class="bg-zinc-950 text-zinc-100">
    <?php echo $content; ?>
</body>
</html>
