<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/classes/User.php';

$error = null;
$ip_address = $_SERVER['REMOTE_ADDR'];
$max_attempts = 5;
$lockout_time = 15; // minutes

// 1. Check Rate Limit
$stmt = $pdo->prepare("SELECT * FROM login_attempts WHERE ip_address = :ip");
$stmt->execute(['ip' => $ip_address]);
$attempt = $stmt->fetch(PDO::FETCH_ASSOC);

if ($attempt && $attempt['locked_until']) {
    if (strtotime($attempt['locked_until']) > time()) {
        $wait = ceil((strtotime($attempt['locked_until']) - time()) / 60);
        $error = "Too many login attempts. Please try again in $wait minutes.";
    } else {
        // Unlock
        $pdo->prepare("UPDATE login_attempts SET attempts = 0, locked_until = NULL WHERE ip_address = :ip")->execute(['ip' => $ip_address]);
        $attempt = null;
    }
}

if (!$error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF validation failed");
    }

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $userModel = new User($pdo);
    $user = $userModel->findByEmail($email);

    if ($user && password_verify($password, $user['password'])) {
        // Success: Reset attempts
        $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = :ip")->execute(['ip' => $ip_address]);

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        
        // Redirect to intended destination or home
        $redirect = $_SESSION['auth_redirect_to'] ?? '/index.php';
        if (!is_internal_url($redirect)) {
            $redirect = '/index.php';
        }
        unset($_SESSION['auth_redirect_to']);
        header("Location: " . $redirect);
        exit;
    } else {
        // Failure: Increment attempts
        if ($attempt) {
            $new_attempts = $attempt['attempts'] + 1;
            if ($new_attempts >= $max_attempts) {
                $locked_until = date('Y-m-d H:i:s', strtotime("+$lockout_time minutes"));
                $pdo->prepare("UPDATE login_attempts SET attempts = :attempts, locked_until = :locked WHERE ip_address = :ip")
                    ->execute(['attempts' => $new_attempts, 'locked' => $locked_until, 'ip' => $ip_address]);
                $error = "Too many login attempts. Please try again in $lockout_time minutes.";
            } else {
                $pdo->prepare("UPDATE login_attempts SET attempts = :attempts WHERE ip_address = :ip")
                    ->execute(['attempts' => $new_attempts, 'ip' => $ip_address]);
                 $error = "The provided credentials do not match our records.";
            }
        } else {
            $pdo->prepare("INSERT INTO login_attempts (ip_address, attempts) VALUES (:ip, 1)")
                ->execute(['ip' => $ip_address]);
             $error = "The provided credentials do not match our records.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="/style.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Login</h2>
        
        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!($attempt['locked_until'] ?? null) || strtotime($attempt['locked_until']) <= time()): ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus
                    class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                <input type="password" id="password" name="password" required
                    class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            
            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                    Sign In
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>