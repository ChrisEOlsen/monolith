<?php
/**
 * PAGE: bookmarks.php
 */
require_once 'db.php';
require_once 'csrf.php';

// Include Models
require_once 'classes/BookmarkCategory.php';
require_once 'classes/Bookmark.php';

$catModel = new BookmarkCategory($pdo);
$bmModel = new Bookmark($pdo);

// Logic
$current_cat_id = $_GET['cat_id'] ?? null;
$categories = $catModel->getAll();

if (!$current_cat_id && count($categories) > 0) {
    $current_cat_id = $categories[0]['id'];
}

// --- CONTROLLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die("Invalid CSRF");
    }
    $action = $_POST['action'] ?? '';
    $redirect = $current_cat_id ? "?cat_id=$current_cat_id" : "?";

    try {
        // Categories
        if ($action === 'create_category') {
            $catModel->create(['title' => $_POST['title']]);
            header("Location: ?cat_id=" . $pdo->lastInsertId()); exit;
        }
        elseif ($action === 'delete_category') {
            $catModel->delete($_POST['id']);
            header("Location: ?"); exit;
        }
        elseif ($action === 'update_category') {
            $stmt = $pdo->prepare("UPDATE bookmark_categories SET title = :title WHERE id = :id");
            $stmt->execute(['title' => $_POST['title'], 'id' => $_POST['id']]);
            header("Location: $redirect"); exit;
        }

        // Bookmarks
        elseif ($action === 'create_bookmark') {
            $stmt = $pdo->prepare("INSERT INTO bookmarks (category_id, title, url, description) VALUES (:cat_id, :title, :url, :desc)");
            $stmt->execute([
                'cat_id' => $_POST['cat_id'],
                'title' => $_POST['title'],
                'url' => $_POST['url'],
                'desc' => $_POST['description']
            ]);
            header("Location: $redirect"); exit;
        }
        elseif ($action === 'delete_bookmark') {
            $bmModel->delete($_POST['id']);
            header("Location: $redirect"); exit;
        }
        elseif ($action === 'update_bookmark') {
            $stmt = $pdo->prepare("UPDATE bookmarks SET title = :title, url = :url, description = :desc WHERE id = :id");
            $stmt->execute([
                'title' => $_POST['title'],
                'url' => $_POST['url'],
                'desc' => $_POST['description'],
                'id' => $_POST['id']
            ]);
            header("Location: $redirect"); exit;
        }

    } catch (Exception $e) { /* Log */ }
}

$bookmarks = [];
if ($current_cat_id) {
    $stmt = $pdo->prepare("SELECT * FROM bookmarks WHERE category_id = :id ORDER BY created_at DESC");
    $stmt->execute(['id' => $current_cat_id]);
    $bookmarks = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Helper: Get Favicon
function get_favicon($url) {
    $domain = parse_url($url, PHP_URL_HOST);
    return "https://www.google.com/s2/favicons?domain=$domain&sz=32";
}
?>
<?php ob_start(); ?>

<div class="min-h-screen bg-zinc-950 text-zinc-100 font-sans selection:bg-indigo-500/30" x-data="{ sidebarOpen: false }">
    
    <?php include 'navbar.php'; ?>

    <div class="flex h-[calc(100vh-64px)] pt-16">
        
        <!-- Sidebar (Desktop) -->
        <aside class="hidden md:flex w-64 bg-zinc-900 border-r border-zinc-800 flex-col">
            <div class="p-4">
                <h2 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Folders</h2>
                <nav class="space-y-0.5">
                    <?php foreach ($categories as $cat): ?>
                    <div class="group flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium transition-colors <?php echo $cat['id'] == $current_cat_id ? 'bg-zinc-800 text-white' : 'text-zinc-400 hover:bg-zinc-800/50 hover:text-zinc-200'; ?>">
                        <a href="?cat_id=<?php echo $cat['id']; ?>" class="flex-1 flex items-center gap-2">
                            <span class="opacity-50">📂</span> <?php echo htmlspecialchars($cat['title']); ?>
                        </a>
                        <!-- Edit Trigger -->
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
                    + New Folder
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
                        <h2 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-4">Folders</h2>
                        <nav class="space-y-1">
                            <?php foreach ($categories as $cat): ?>
                            <div class="flex items-center justify-between px-3 py-3 rounded-lg text-base font-medium transition-colors <?php echo $cat['id'] == $current_cat_id ? 'bg-zinc-800 text-white' : 'text-zinc-400 hover:bg-zinc-800/50 hover:text-zinc-200'; ?>">
                                <a href="?cat_id=<?php echo $cat['id']; ?>" class="flex-1 flex items-center gap-3">
                                    <span class="text-xl opacity-50">📂</span> <?php echo htmlspecialchars($cat['title']); ?>
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
                        + New Folder
                    </button>
                </div>
            </div>
        </div>

        <!-- Main -->
        <main class="flex-1 flex flex-col relative bg-zinc-950 w-full" x-data="{ editBm: {} }">
            <?php if ($current_cat_id): ?>
            
            <header class="h-16 md:h-14 border-b border-zinc-800 flex items-center justify-between px-4 md:px-6 bg-zinc-900/50 backdrop-blur z-10 shrink-0">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = true" class="md:hidden text-zinc-400 hover:text-white -ml-2 p-2">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    <h1 class="font-bold text-white truncate text-lg md:text-base">
                        <?php 
                            foreach($categories as $c) if($c['id'] == $current_cat_id) echo htmlspecialchars($c['title']); 
                        ?>
                    </h1>
                </div>
                <button onclick="document.getElementById('new-bm-modal').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-500 text-white px-3 py-1.5 rounded text-sm font-medium transition-colors shadow-lg shadow-indigo-500/20">
                    Add Link
                </button>
            </header>

            <div class="flex-1 overflow-y-auto p-0">
                <table class="w-full text-left text-sm text-zinc-400">
                    <thead class="bg-zinc-900 border-b border-zinc-800 text-xs uppercase font-medium text-zinc-500 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 w-12"></th>
                            <th class="px-6 py-3">Title</th>
                            <th class="px-6 py-3 hidden md:table-cell">URL</th>
                            <th class="px-6 py-3 w-24 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800">
                        <?php foreach ($bookmarks as $bm): ?>
                        <tr class="group hover:bg-zinc-900/50 transition-colors">
                            <td class="px-6 py-3">
                                <img src="<?php echo get_favicon($bm['url']); ?>" class="w-4 h-4 rounded-sm opacity-75">
                            </td>
                            <td class="px-6 py-3">
                                <a href="<?php echo htmlspecialchars($bm['url']); ?>" target="_blank" class="text-zinc-200 hover:text-indigo-400 font-medium block truncate max-w-xs">
                                    <?php echo htmlspecialchars($bm['title']); ?>
                                </a>
                                <?php if($bm['description']): ?>
                                    <div class="text-xs text-zinc-500 truncate max-w-xs mt-0.5"><?php echo htmlspecialchars($bm['description']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-3 hidden md:table-cell text-xs text-zinc-600 truncate max-w-xs font-mono">
                                <?php echo htmlspecialchars($bm['url']); ?>
                            </td>
                            <td class="px-6 py-3 text-right opacity-0 group-hover:opacity-100 transition-opacity">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="$dispatch('edit-bookmark', {
                                        id: '<?php echo $bm['id']; ?>', 
                                        title: '<?php echo addslashes(htmlspecialchars($bm['title'])); ?>',
                                        url: '<?php echo addslashes(htmlspecialchars($bm['url'])); ?>',
                                        description: '<?php echo addslashes(htmlspecialchars($bm['description'])); ?>'
                                    })" class="text-zinc-500 hover:text-white p-1">✎</button>
                                    
                                    <form method="POST" onsubmit="return confirm('Delete?');">
                                        <input type="hidden" name="action" value="delete_bookmark">
                                        <input type="hidden" name="id" value="<?php echo $bm['id']; ?>">
                                        <input type="hidden" name="cat_id" value="<?php echo $current_cat_id; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <button class="text-zinc-500 hover:text-red-500 p-1">✕</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($bookmarks)): ?>
                        <tr><td colspan="4" class="px-6 py-12 text-center text-zinc-600 text-xs uppercase tracking-widest">No links yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php endif; ?>
        </main>
    </div>

    <!-- Modals -->
    
    <!-- New Category -->
    <div id="new-cat-modal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-6 w-full max-w-sm shadow-xl">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4">New Folder</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create_category">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="text" name="title" placeholder="Name" class="w-full bg-zinc-950 border border-zinc-800 text-white rounded px-3 py-2 mb-4 text-sm focus:border-indigo-500 outline-none" required autofocus>
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
            <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4">Edit Folder</h3>
            <form method="POST">
                <input type="hidden" name="id" x-model="editCat.id">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="text" name="title" x-model="editCat.title" class="w-full bg-zinc-950 border border-zinc-800 text-white rounded px-3 py-2 mb-4 text-sm focus:border-indigo-500 outline-none" required>
                <div class="flex justify-between">
                    <button type="submit" name="action" value="delete_category" class="text-red-500 hover:text-red-400 text-sm" onclick="return confirm('Delete folder?')">Delete</button>
                    <div class="flex gap-2">
                        <button type="button" onclick="document.getElementById('edit-cat-modal').classList.add('hidden')" class="px-3 py-1.5 text-zinc-400 hover:text-white text-sm">Cancel</button>
                        <button type="submit" name="action" value="update_category" class="bg-white text-zinc-900 px-3 py-1.5 rounded text-sm font-bold hover:bg-zinc-200">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- New Bookmark -->
    <div id="new-bm-modal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-6 w-full max-w-md shadow-xl">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4">New Link</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create_bookmark">
                <input type="hidden" name="cat_id" value="<?php echo $current_cat_id; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="space-y-3 mb-4">
                    <input type="text" name="title" placeholder="Title" class="w-full bg-zinc-950 border border-zinc-800 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 outline-none" required>
                    <input type="url" name="url" placeholder="URL" class="w-full bg-zinc-950 border border-zinc-800 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 outline-none" required>
                    <textarea name="description" placeholder="Description (Optional)" class="w-full bg-zinc-950 border border-zinc-800 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 outline-none h-20"></textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('new-bm-modal').classList.add('hidden')" class="px-3 py-1.5 text-zinc-400 hover:text-white text-sm">Cancel</button>
                    <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded text-sm font-bold hover:bg-indigo-500">Add Link</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Bookmark -->
    <div id="edit-bm-modal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-data="{ bm: {id:'', title:'', url:'', description:''} }" @edit-bookmark.window="bm = $event.detail; document.getElementById('edit-bm-modal').classList.remove('hidden')">
        <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-6 w-full max-w-md shadow-xl">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4">Edit Link</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_bookmark">
                <input type="hidden" name="id" x-model="bm.id">
                <input type="hidden" name="cat_id" value="<?php echo $current_cat_id; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="space-y-3 mb-4">
                    <input type="text" name="title" x-model="bm.title" class="w-full bg-zinc-950 border border-zinc-800 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 outline-none" required>
                    <input type="url" name="url" x-model="bm.url" class="w-full bg-zinc-950 border border-zinc-800 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 outline-none" required>
                    <textarea name="description" x-model="bm.description" class="w-full bg-zinc-950 border border-zinc-800 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 outline-none h-20"></textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('edit-bm-modal').classList.add('hidden')" class="px-3 py-1.5 text-zinc-400 hover:text-white text-sm">Cancel</button>
                    <button type="submit" class="bg-white text-zinc-900 px-3 py-1.5 rounded text-sm font-bold hover:bg-zinc-200">Save</button>
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
    <title>Bookmarks | Monolith</title>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script>
</head>
<body class="bg-zinc-950 text-zinc-100">
    <?php echo $content; ?>
</body>
</html>
