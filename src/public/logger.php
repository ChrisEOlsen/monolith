<?php
/**
 * PAGE: logger.php
 */
require_once 'db.php';
require_once 'csrf.php';

// Include Models
require_once 'classes/LogCategory.php';
require_once 'classes/LogEntry.php';

$catModel = new LogCategory($pdo);
$entryModel = new LogEntry($pdo);

$current_cat_id = $_GET['cat_id'] ?? null;
$categories = $catModel->getAll();

// Default to first category
if (!$current_cat_id && count($categories) > 0) {
    $current_cat_id = $categories[0]['id'];
}

// Helper: Decode JSON safely
function safe_json_decode($str) {
    $data = json_decode($str, true);
    return is_array($data) ? $data : [];
}

// Helper: Format Date from UTC to Local
function format_date($date_str) {
    $date = new DateTime($date_str, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone(getenv('APP_TIMEZONE') ?: 'America/New_York'));
    return $date->format('M j, Y H:i');
}

// --- CONTROLLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die("Invalid CSRF");
    }

    $action = $_POST['action'] ?? '';
    $redirect = $current_cat_id ? "?cat_id=$current_cat_id" : "?";

    try {
        // Create Category (Log Type)
        if ($action === 'create_category') {
            $title = $_POST['title'];
            // Build schema from dynamic inputs
            $names = $_POST['field_names'] ?? [];
            $types = $_POST['field_types'] ?? [];
            $schema = [];
            
            for ($i = 0; $i < count($names); $i++) {
                if (!empty($names[$i])) {
                    $schema[] = ['name' => $names[$i], 'type' => $types[$i]];
                }
            }
            
            $stmt = $pdo->prepare("INSERT INTO log_categories (title, schema_def) VALUES (:title, :schema)");
            $stmt->execute(['title' => $title, 'schema' => json_encode($schema)]);
            header("Location: ?cat_id=" . $pdo->lastInsertId()); exit;
        }
        
        // Delete Category
        elseif ($action === 'delete_category') {
            $catModel->delete($_POST['id']);
            header("Location: ?"); exit;
        }

        // Update Category Schema
        elseif ($action === 'update_category_schema') {
            $names = $_POST['field_names'] ?? [];
            $types = $_POST['field_types'] ?? [];
            $schema = [];
            
            for ($i = 0; $i < count($names); $i++) {
                if (!empty($names[$i])) {
                    $schema[] = ['name' => $names[$i], 'type' => $types[$i]];
                }
            }
            
            $stmt = $pdo->prepare("UPDATE log_categories SET schema_def = :schema WHERE id = :id");
            $stmt->execute(['schema' => json_encode($schema), 'id' => $_POST['id']]);
            header("Location: $redirect"); exit;
        }

        // Create Entry
        elseif ($action === 'create_entry') {
            $data = $_POST['data'] ?? [];
            $stmt = $pdo->prepare("INSERT INTO log_entries (category_id, entry_data) VALUES (:cat_id, :data)");
            $stmt->execute(['cat_id' => $_POST['cat_id'], 'data' => json_encode($data)]);
            header("Location: $redirect"); exit;
        }

        // Delete Entry
        elseif ($action === 'delete_entry') {
            $entryModel->delete($_POST['id']);
            header("Location: $redirect"); exit;
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch Current Data
$current_cat = null;
$current_entries = [];
$current_schema = [];

if ($current_cat_id) {
    foreach ($categories as $c) {
        if ($c['id'] == $current_cat_id) { 
             // Found it
        }
    }
    // Re-fetch strictly to be safe/easy
    $stmt = $pdo->prepare("SELECT * FROM log_categories WHERE id = :id");
    $stmt->execute(['id' => $current_cat_id]);
    $current_cat = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($current_cat) {
        $current_schema = safe_json_decode($current_cat['schema_def']);
        
        $stmt = $pdo->prepare("SELECT * FROM log_entries WHERE category_id = :id ORDER BY created_at DESC");
        $stmt->execute(['id' => $current_cat_id]);
        $current_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<?php ob_start(); ?>

<div class="min-h-screen bg-zinc-950 text-zinc-100 font-sans selection:bg-indigo-500/30">
    
    <?php include 'navbar.php'; ?>

    <div class="flex h-[calc(100vh-64px)] pt-16" x-data="{ sidebarOpen: false }">
        
        <!-- Sidebar (Desktop) -->
        <aside class="hidden md:flex w-64 bg-zinc-900 border-r border-zinc-800 flex-col">
            <div class="p-6">
                <h2 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-4">Logs</h2>
                <nav class="space-y-1">
                    <?php foreach ($categories as $cat): ?>
                    <a href="?cat_id=<?php echo $cat['id']; ?>" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $cat['id'] == $current_cat_id ? 'bg-indigo-500/10 text-indigo-400' : 'text-zinc-400 hover:bg-zinc-800 hover:text-zinc-200'; ?>">
                        <span class="text-lg leading-none opacity-50">#</span>
                        <?php echo htmlspecialchars($cat['title']); ?>
                    </a>
                    <?php endforeach; ?>
                </nav>
            </div>

            <div class="mt-auto p-4 border-t border-zinc-800">
                <button onclick="document.getElementById('new-log-modal').classList.remove('hidden')" class="flex items-center gap-2 text-sm text-zinc-500 hover:text-indigo-400 transition-colors w-full font-medium p-2 rounded hover:bg-zinc-800/50">
                    <span class="text-lg leading-none">+</span> Create New Log
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
                        <h2 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-4">Logs</h2>
                        <nav class="space-y-1 mb-8">
                            <?php foreach ($categories as $cat): ?>
                            <a href="?cat_id=<?php echo $cat['id']; ?>" class="flex items-center gap-3 px-3 py-3 rounded-lg text-base font-medium transition-colors <?php echo $cat['id'] == $current_cat_id ? 'bg-indigo-500/10 text-indigo-400' : 'text-zinc-400 hover:bg-zinc-800 hover:text-zinc-200'; ?>">
                                <span class="text-lg leading-none opacity-50">#</span>
                                <?php echo htmlspecialchars($cat['title']); ?>
                            </a>
                            <?php endforeach; ?>
                        </nav>

                        <?php if ($current_cat): ?>
                        <h2 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-4">Manage Log</h2>
                        <button @click="sidebarOpen = false; document.getElementById('edit-schema-modal').classList.remove('hidden')" class="flex items-center gap-3 w-full px-3 py-3 rounded-lg text-base font-medium text-zinc-400 hover:bg-zinc-800 hover:text-zinc-200 transition-colors text-left">
                            <span class="text-lg leading-none opacity-50">✎</span> Edit Schema
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="p-4 border-t border-zinc-800">
                    <button @click="sidebarOpen = false; document.getElementById('new-log-modal').classList.remove('hidden')" class="flex items-center gap-2 text-sm text-zinc-500 hover:text-indigo-400 transition-colors w-full font-medium p-2 rounded hover:bg-zinc-800/50">
                        <span class="text-lg leading-none">+</span> Create New Log
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col relative bg-zinc-950 overflow-hidden w-full">
            <?php if ($current_cat): ?>
            
            <!-- Header -->
            <header class="h-16 md:h-20 border-b border-zinc-800 flex items-center justify-between px-4 md:px-8 bg-zinc-950/50 backdrop-blur z-10 shrink-0">
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <button @click="sidebarOpen = true" class="md:hidden text-zinc-400 hover:text-white -ml-2 p-2 shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    <div class="min-w-0">
                        <h1 class="text-xl md:text-2xl font-bold text-white truncate"><?php echo htmlspecialchars($current_cat['title']); ?></h1>
                        <p class="hidden md:block text-zinc-500 text-sm mt-1"><?php echo count($current_entries); ?> entries</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-2 md:gap-3">
                    <form method="POST" onsubmit="return confirm('Delete this entire log?');" class="hidden md:block">
                        <input type="hidden" name="action" value="delete_category">
                        <input type="hidden" name="id" value="<?php echo $current_cat['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <button class="text-zinc-600 hover:text-red-500 p-2 transition-colors" title="Delete Log">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </form>
                    
                    <button onclick="document.getElementById('edit-schema-modal').classList.remove('hidden')" class="hidden md:block text-sm font-medium text-zinc-400 hover:text-white px-3 py-2 transition-colors">
                        Edit Schema
                    </button>

                    <button onclick="document.getElementById('new-entry-modal').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-500 text-white px-3 py-1.5 md:px-4 md:py-2 rounded-lg font-medium transition-all shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/40 flex items-center gap-2 text-sm">
                        <span>+</span> Add <span class="hidden sm:inline">Entry</span>
                    </button>
                </div>
            </header>

            <!-- Table / Cards -->
            <div class="flex-1 overflow-auto p-4 md:p-8">
                <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden shadow-sm">
                    
                    <!-- Desktop Table -->
                    <table class="hidden md:table w-full text-left text-sm text-zinc-400">
                        <thead class="bg-zinc-900/50 border-b border-zinc-800 text-xs uppercase font-medium text-zinc-500">
                            <tr>
                                <th class="px-6 py-4">Date</th>
                                <?php foreach ($current_schema as $field): ?>
                                <th class="px-6 py-4"><?php echo htmlspecialchars($field['name']); ?></th>
                                <?php endforeach; ?>
                                <th class="px-6 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800/50">
                            <?php foreach ($current_entries as $entry): 
                                $data = safe_json_decode($entry['entry_data']);
                            ?>
                            <tr class="hover:bg-zinc-800/30 transition-colors group">
                                <td class="px-6 py-4 text-zinc-500 whitespace-nowrap">
                                    <?php echo format_date($entry['created_at']); ?>
                                </td>
                                
                                <?php foreach ($current_schema as $field): ?>
                                <td class="px-6 py-4 text-zinc-300 font-medium">
                                    <?php echo htmlspecialchars($data[$field['name']] ?? '-'); ?>
                                </td>
                                <?php endforeach; ?>
                                
                                <td class="px-6 py-4 text-right">
                                    <form method="POST" onsubmit="return confirm('Delete?');" class="inline-block opacity-0 group-hover:opacity-100 transition-opacity">
                                        <input type="hidden" name="action" value="delete_entry">
                                        <input type="hidden" name="id" value="<?php echo $entry['id']; ?>">
                                        <input type="hidden" name="cat_id" value="<?php echo $current_cat_id; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <button class="text-zinc-600 hover:text-red-500"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Mobile Cards -->
                    <div class="md:hidden divide-y divide-zinc-800">
                        <?php foreach ($current_entries as $entry): 
                            $data = safe_json_decode($entry['entry_data']);
                        ?>
                        <div class="p-4 space-y-3">
                            <div class="flex justify-between items-start">
                                <span class="text-xs text-zinc-500 font-mono"><?php echo format_date($entry['created_at']); ?></span>
                                <form method="POST" onsubmit="return confirm('Delete?');">
                                    <input type="hidden" name="action" value="delete_entry">
                                    <input type="hidden" name="id" value="<?php echo $entry['id']; ?>">
                                    <input type="hidden" name="cat_id" value="<?php echo $current_cat_id; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <button class="text-zinc-600 hover:text-red-500"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                                </form>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-2">
                                <?php foreach ($current_schema as $field): ?>
                                <div>
                                    <dt class="text-[10px] uppercase text-zinc-600 font-bold mb-0.5"><?php echo htmlspecialchars($field['name']); ?></dt>
                                    <dd class="text-sm text-zinc-200"><?php echo htmlspecialchars($data[$field['name']] ?? '-'); ?></dd>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (empty($current_entries)): ?>
                    <div class="px-6 py-12 text-center text-zinc-600 border-t border-zinc-800 md:border-t-0">
                        No entries yet. Start logging!
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: ?>
            <div class="flex-1 flex flex-col items-center justify-center text-center p-8">
                <div class="w-16 h-16 bg-zinc-900 rounded-2xl flex items-center justify-center mb-6 border border-zinc-800">
                    <svg class="w-8 h-8 text-zinc-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                </div>
                <h2 class="text-xl font-bold text-white mb-2">Create a Log</h2>
                <p class="text-zinc-500 max-w-sm">Define a schema (e.g. Weight, Reps) and start tracking your data over time.</p>
                <button onclick="document.getElementById('new-log-modal').classList.remove('hidden')" class="mt-6 bg-zinc-800 hover:bg-zinc-700 text-white px-6 py-2 rounded-lg font-medium transition-colors border border-zinc-700">
                    Get Started
                </button>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- MODAL: New Log -->
    <div id="new-log-modal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 w-full max-w-md shadow-2xl relative" x-data="{ fields: [{id: 1}] }">
            <h3 class="text-xl font-bold text-white mb-4">Create New Log</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create_category">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="mb-4">
                    <label class="block text-xs font-medium text-zinc-500 uppercase mb-1">Log Name</label>
                    <input type="text" name="title" placeholder="e.g. Workout, Coffee" class="w-full bg-zinc-950 border border-zinc-800 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none" required>
                </div>

                <div class="mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-xs font-medium text-zinc-500 uppercase">Fields</label>
                        <button type="button" @click="fields.push({id: Date.now()})" class="text-xs text-indigo-400 hover:text-indigo-300">+ Add Field</button>
                    </div>
                    
                    <div class="space-y-2 max-h-48 overflow-y-auto pr-1 custom-scrollbar">
                        <template x-for="(field, index) in fields" :key="field.id">
                            <div class="flex gap-2 items-center">
                                <input type="text" name="field_names[]" placeholder="Field Name" class="flex-1 bg-zinc-950 border border-zinc-800 text-white text-sm rounded px-3 py-2 focus:border-indigo-500 outline-none" required>
                                <select name="field_types[]" class="bg-zinc-950 border border-zinc-800 text-zinc-400 text-sm rounded px-2 py-2 outline-none">
                                    <option value="text">Text</option>
                                    <option value="date">Date</option>
                                    <option value="time">Time</option>
                                </select>
                                <button type="button" @click="fields.splice(index, 1)" class="text-zinc-600 hover:text-red-500 px-1" title="Remove Field">✕</button>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex gap-3 pt-2 border-t border-zinc-800">
                    <button type="button" onclick="document.getElementById('new-log-modal').classList.add('hidden')" class="flex-1 px-4 py-2 rounded-lg text-zinc-400 hover:bg-zinc-800 transition-colors">Cancel</button>
                    <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg font-medium transition-colors">Create Log</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Edit Schema -->
    <?php if ($current_cat): ?>
    <div id="edit-schema-modal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 w-full max-w-md shadow-2xl relative" 
             x-data="{ fields: <?php echo htmlspecialchars(json_encode(array_map(fn($f) => ['id' => uniqid(), 'name' => $f['name'], 'type' => $f['type']], $current_schema))); ?> }">
            <h3 class="text-xl font-bold text-white mb-4">Edit Schema: <?php echo htmlspecialchars($current_cat['title']); ?></h3>
            <p class="text-xs text-zinc-500 mb-4">Removing fields hides past data but does not delete it.</p>
            
            <form method="POST">
                <input type="hidden" name="action" value="update_category_schema">
                <input type="hidden" name="id" value="<?php echo $current_cat_id; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-xs font-medium text-zinc-500 uppercase">Fields</label>
                        <button type="button" @click="fields.push({id: Date.now(), name: '', type: 'text'})" class="text-xs text-indigo-400 hover:text-indigo-300">+ Add Field</button>
                    </div>
                    
                    <div class="space-y-2 max-h-64 overflow-y-auto pr-1 custom-scrollbar">
                        <template x-for="(field, index) in fields" :key="field.id">
                            <div class="flex gap-2 items-center">
                                <input type="text" name="field_names[]" x-model="field.name" placeholder="Field Name" class="flex-1 bg-zinc-950 border border-zinc-800 text-white text-sm rounded px-3 py-2 focus:border-indigo-500 outline-none" required>
                                <select name="field_types[]" x-model="field.type" class="bg-zinc-950 border border-zinc-800 text-zinc-400 text-sm rounded px-2 py-2 outline-none">
                                    <option value="text">Text</option>
                                    <option value="date">Date</option>
                                    <option value="time">Time</option>
                                </select>
                                <button type="button" @click="fields.splice(index, 1)" class="text-zinc-600 hover:text-red-500 px-1" title="Remove Field">✕</button>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex gap-3 pt-2 border-t border-zinc-800">
                    <button type="button" onclick="document.getElementById('edit-schema-modal').classList.add('hidden')" class="flex-1 px-4 py-2 rounded-lg text-zinc-400 hover:bg-zinc-800 transition-colors">Cancel</button>
                    <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg font-medium transition-colors">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- MODAL: New Entry -->
    <?php if ($current_cat): ?>
    <div id="new-entry-modal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 w-full max-w-md shadow-2xl">
            <h3 class="text-xl font-bold text-white mb-4">New Entry: <?php echo htmlspecialchars($current_cat['title']); ?></h3>
            <form method="POST">
                <input type="hidden" name="action" value="create_entry">
                <input type="hidden" name="cat_id" value="<?php echo $current_cat_id; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="space-y-4 mb-6">
                    <?php foreach ($current_schema as $field): ?>
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 uppercase mb-1"><?php echo htmlspecialchars($field['name']); ?></label>
                        <input type="<?php echo htmlspecialchars($field['type']); ?>" name="data[<?php echo htmlspecialchars($field['name']); ?>]" 
                               class="w-full bg-zinc-950 border border-zinc-800 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none" required>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="flex gap-3 pt-2 border-t border-zinc-800">
                    <button type="button" onclick="document.getElementById('new-entry-modal').classList.add('hidden')" class="flex-1 px-4 py-2 rounded-lg text-zinc-400 hover:bg-zinc-800 transition-colors">Cancel</button>
                    <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg font-medium transition-colors">Add Entry</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

</div>
<?php $content = ob_get_clean(); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logger | Monolith</title>
    <!-- Alpine.js -->
    <script src="//unpkg.com/alpinejs" defer></script>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 4px; }
    </style>
</head>
<body class="bg-zinc-950 text-zinc-100">
    <?php echo $content; ?>
</body>
</html>
