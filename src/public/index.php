<?php
// Simple router or landing page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Monolith</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-zinc-900 text-zinc-100 min-h-screen flex flex-col items-center justify-center font-sans">
    <div class="text-center max-w-2xl">
        <h1 class="text-6xl font-bold bg-gradient-to-r from-blue-400 to-indigo-500 bg-clip-text text-transparent mb-6">
            PHP Monolith
        </h1>
        <p class="text-xl text-zinc-400 mb-12">
            The factory is in the building.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-6 bg-zinc-800 rounded-xl border border-zinc-700">
                <h2 class="text-2xl font-semibold mb-2 text-white">Runtime</h2>
                <p class="text-zinc-400">PHP 8.2 + Apache running your application logic.</p>
            </div>
            <div class="p-6 bg-zinc-800 rounded-xl border border-zinc-700">
                <h2 class="text-2xl font-semibold mb-2 text-white">Builder</h2>
                <p class="text-zinc-400">Embedded Python MCP Server generating code on the fly.</p>
            </div>
        </div>
        
        <div class="mt-12 text-zinc-500">
            <p>Generated Features will appear here:</p>
            <div class="flex flex-wrap justify-center gap-4 mt-4">
                <!-- Links to generated pages would go here dynamically -->
                <?php
                $files = glob('*.php');
                foreach ($files as $file) {
                    if ($file !== 'index.php' && $file !== 'db.php') {
                        echo '<a href="/'.$file.'" class="text-indigo-400 hover:underline">'.ucfirst(basename($file, '.php')).'</a>';
                    }
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>
