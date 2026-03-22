<?php
require_once __DIR__ . '/lib/SessionManager.php';

// Visitor Counter Logic
$visitorFile = __DIR__ . '/data/visitors.txt';
$visits = 0;

// Ensure data directory exists
if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0777, true);
}

if (!file_exists($visitorFile)) {
    file_put_contents($visitorFile, '0');
}

// Increment count
$visits = (int)file_get_contents($visitorFile);
$visits++;
file_put_contents($visitorFile, $visits);

// Check if already logged in (will auto-login if Remember Me is valid)
$check = SessionManager::checkSession();
if ($check === true) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $code = $_POST['code'] ?? '';
    $remember = isset($_POST['remember']);

    // Logic: Security Code must be username + "1"
    if ($username && $code === $username . "1") {
        // Secure Login
        SessionManager::login([
            'email' => $username,
            'username' => $username,
            'role' => 'admin'
        ]);
        
        if ($remember) {
            SessionManager::rememberMe($username);
        }
        
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid Username or Security Code";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Interlink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: { 900: '#0f172a', 800: '#1e293b' },
                        primary: '#6366f1',
                    }
                }
            }
        }
    </script>
    <style>
        body { 
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); 
            min-height: 100vh; 
            font-family: sans-serif;
        }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .visitor-counter {
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.3);
            text-shadow: 0 0 10px rgba(99, 102, 241, 0.5);
        }
    </style>
</head>
<body class="text-white font-sans antialiased flex items-center justify-center p-4 overflow-hidden">

    <!-- Page Loader -->
    <div id="pageLoader" class="fixed inset-0 z-[100] bg-[#0f172a] flex flex-col items-center justify-center transition-opacity duration-500">
        <div class="relative mb-4">
            <div class="w-16 h-16 border-4 border-indigo-500/30 border-t-indigo-500 rounded-full animate-spin"></div>
            <div class="absolute inset-0 flex items-center justify-center font-bold text-xs text-indigo-500">INT</div>
        </div>
        <p class="text-slate-400 text-sm font-medium animate-pulse">Loading...</p>
    </div>

    <script>
        window.addEventListener('load', () => {
             const loader = document.getElementById('pageLoader');
             setTimeout(() => {
                 loader.classList.add('opacity-0');
                 setTimeout(() => {
                     loader.remove();
                     document.body.classList.remove('overflow-hidden');
                 }, 500);
             }, 300);
        });
    </script>

    <div class="max-w-md w-full glass p-8 rounded-2xl shadow-2xl relative">
        
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-primary to-purple-500">
                InterlinkPanel <span class="text-white text-lg font-normal">By <a href="https://t.me/teluguscripter" target="_blank" class="hover:underline hover:text-indigo-400 transition-colors">teluguscripter</a></span>
            </h1>
            <p class="text-slate-400 text-sm mt-2">Sign in to manage your miners</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-rose-500/10 border border-rose-500/20 text-rose-200 text-sm p-3 rounded-xl mb-4 text-center">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm text-slate-400 mb-1">Username</label>
                <input type="text" name="username" required class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all text-white placeholder-slate-600" placeholder="Enter username" oninput="this.value = this.value.replace(/\s/g, '')">
            </div>
            
            <div>
                <label class="block text-sm text-slate-400 mb-1">Security Code</label>
                <input type="password" name="code" required class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all text-white placeholder-slate-600" placeholder="Enter code">
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="remember" id="remember" class="w-4 h-4 rounded border-slate-700 bg-slate-900 text-primary focus:ring-primary">
                <label for="remember" class="text-sm text-slate-400 select-none cursor-pointer">Keep me logged in for 30 days</label>
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-primary to-indigo-600 hover:from-primary/90 hover:to-indigo-600/90 py-3.5 rounded-xl font-bold shadow-lg shadow-primary/25 transition-all mt-4">
                Login
            </button>
        </form>
        
        <div class="text-center mt-6 text-xs text-slate-500">
            <p>Secure System • v2.0 (Advanced)</p>
        </div>

        <div class="mt-8 flex justify-center">
            <div class="visitor-counter px-4 py-2 rounded-full flex items-center gap-2">
                <span class="text-indigo-400 text-xs uppercase tracking-wider font-bold">Total Visitors</span>
                <span class="text-white font-mono font-bold text-lg"><?= number_format($visits) ?></span>
            </div>
        </div>

    </div>

</body>
</html>
