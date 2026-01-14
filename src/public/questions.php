<?php
/**
 * PAGE: questions.php
 */
require_once 'db.php';
require_once 'csrf.php';
require_once 'classes/Question.php';

$qModel = new Question($pdo);

// --- CONTROLLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        if (isset($_SERVER['HTTP_HX_REQUEST'])) { http_response_code(403); exit; }
        die("Invalid CSRF");
    }

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create_question') {
            $stmt = $pdo->prepare("INSERT INTO questions (content) VALUES (:content)");
            $stmt->execute(['content' => $_POST['content']]);
            header("Location: ?"); exit;
        }
        elseif ($action === 'update_status') {
            $stmt = $pdo->prepare("UPDATE questions SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $_POST['status'], 'id' => $_POST['id']]);
            if (isset($_SERVER['HTTP_HX_REQUEST'])) { renderQuestionList($pdo); exit; }
            header("Location: ?"); exit;
        }
        elseif ($action === 'update_notes') {
            $stmt = $pdo->prepare("UPDATE questions SET notes = :notes WHERE id = :id");
            $stmt->execute(['notes' => $_POST['notes'], 'id' => $_POST['id']]);
            if (isset($_SERVER['HTTP_HX_REQUEST'])) exit; // Silent save
            header("Location: ?"); exit;
        }
        elseif ($action === 'delete_question') {
            $qModel->delete($_POST['id']);
            if (isset($_SERVER['HTTP_HX_REQUEST'])) { renderQuestionList($pdo); exit; }
            header("Location: ?"); exit;
        }
    } catch (Exception $e) { /* Log */ }
}

function renderQuestionList($pdo) {
    // Fetch all questions ordered by status (Unanswered first) then date
    $stmt = $pdo->query("SELECT * FROM questions ORDER BY FIELD(status, 'Unanswered', 'Researching', 'Answered'), created_at DESC");
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div id="questions-list" class="space-y-4">
        <?php if (empty($questions)): ?>
            <div class="text-center py-20">
                <div class="text-6xl mb-4 opacity-50">🤔</div>
                <p class="text-zinc-500 font-medium">No questions yet. Stay curious!</p>
            </div>
        <?php else: ?>
            <?php foreach ($questions as $q): ?>
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 transition-all hover:border-amber-500/30 group" x-data="{ expanded: false }">
                <div class="flex items-start gap-4">
                    <!-- Status Icon/Toggle -->
                    <div class="shrink-0 mt-1">
                        <form hx-post="" hx-target="#questions-list" hx-swap="outerHTML">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id" value="<?php echo $q['id']; ?>">
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

                        <!-- Expanded Section (Notes) -->
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

<div class="min-h-screen bg-zinc-950 text-zinc-100 font-sans selection:bg-amber-500/30">
    
    <?php include 'navbar.php'; ?>

    <div class="pt-24 pb-8 px-6 max-w-3xl mx-auto">
        
        <!-- Header -->
        <div class="text-center mb-10">
            <h1 class="text-4xl font-bold text-white mb-2">Questions</h1>
            <p class="text-zinc-400">Capture your curiosity. Answer it later.</p>
        </div>

        <!-- Input Bar -->
        <div class="mb-12 relative group z-10">
            <div class="absolute -inset-1 bg-gradient-to-r from-amber-500 to-orange-600 rounded-2xl blur opacity-20 group-hover:opacity-40 transition duration-500"></div>
            <form method="POST" class="relative bg-zinc-900 rounded-xl shadow-2xl flex items-center p-2">
                <input type="hidden" name="action" value="create_question">
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
        <?php renderQuestionList($pdo); ?>

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
