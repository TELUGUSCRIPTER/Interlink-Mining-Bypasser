<?php
require_once __DIR__ . '/../lib/SessionManager.php';

// Custom Admin Session Handling
// We don't want to conflict with user session, or maybe we DO want to be "superuser"?
// Let's use a separate session key for admin auth.
SessionManager::start();

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Hardcoded Credentials — CHANGE THESE before deploying!
    // Default: admin / admin123
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['username'] = $username; // Set username for compatibility with Utils if needed
        $_SESSION['role'] = 'admin';
        
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid Admin Credentials";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Interlink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: { 900: '#0f172a', 800: '#1e293b' },
                        primary: '#f43f5e', // Rose for Admin
                    }
                }
            }
        }
    </script>
    <style>
        body { 
            background: linear-gradient(135deg, #0f172a 0%, #3f1926 100%); 
            min-height: 100vh; 
            font-family: sans-serif;
        }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="text-white font-sans antialiased flex items-center justify-center p-4">

    <div class="max-w-md w-full glass p-8 rounded-2xl shadow-2xl border-t-4 border-rose-500">
        
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white">
                Admin Panel
            </h1>
            <p class="text-slate-400 text-sm mt-2">Restricted Access</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-rose-500/10 border border-rose-500/20 text-rose-200 text-sm p-3 rounded-xl mb-4 text-center">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm text-slate-400 mb-1">Username</label>
                <input type="text" name="username" required class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-rose-500 transition-all text-white placeholder-slate-600" placeholder="Enter admin username">
            </div>
            
            <div>
                <label class="block text-sm text-slate-400 mb-1">Passcode</label>
                <input type="password" name="password" inputmode="numeric" pattern="[0-9]*" required class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-rose-500 transition-all text-white placeholder-slate-600" placeholder="Enter numeric code">
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-rose-600 to-rose-700 hover:from-rose-500 hover:to-rose-600 py-3.5 rounded-xl font-bold shadow-lg shadow-rose-900/20 transition-all mt-4">
                Verify & Login
            </button>
        </form>
    </div>

</body>
</html>
