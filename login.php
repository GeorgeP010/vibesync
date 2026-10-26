<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: home.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired, please try again.';
    } else {
        $result = loginUser($_POST['email'] ?? '', $_POST['password'] ?? '');
        if ($result['ok']) {
            header('Location: home.php');
            exit;
        }
        $error = $result['error'] ?? 'Login failed.';
    }
}

$pageTitle  = 'Log In';
$bodyClass  = 'bg-background text-on-background min-h-screen flex flex-col relative overflow-hidden';
$hideTopBar = true;
require __DIR__ . '/includes/header.php';
?>
    <div class="absolute inset-0 z-0 pointer-events-none">
        <div class="absolute top-[-20%] left-[-10%] w-[70vw] h-[70vw] rounded-full bg-[radial-gradient(circle,rgba(183,109,255,0.2),transparent)] blur-[100px]"></div>
        <div class="absolute bottom-[-20%] right-[-10%] w-[60vw] h-[60vw] rounded-full bg-[radial-gradient(circle,rgba(5,102,217,0.2),transparent)] blur-[80px]"></div>
    </div>

    <main class="flex-grow flex flex-col items-center justify-center relative z-10 px-5 w-full max-w-md mx-auto">
        <div class="w-full glass-card rounded-2xl p-8">
            <div class="flex flex-col items-center mb-8">
                <div class="w-16 h-16 rounded-full glass-panel flex items-center justify-center neon-glow-primary mb-4">
                    <span class="material-symbols-outlined text-[32px] text-primary" style="font-variation-settings: 'FILL' 1;">graphic_eq</span>
                </div>
                <h1 class="font-display text-headline-lg text-on-surface">Welcome back</h1>
                <p class="font-body text-body-md text-on-surface-variant mt-1">Log in to your VibeSync account</p>
            </div>

            <?php if ($error): ?>
                <div class="mb-4 p-3 rounded-lg bg-error/10 border border-error/30 text-error font-body text-label-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">error</span>
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-4" autocomplete="on">
                <?= csrfField() ?>
                <label class="block">
                    <span class="font-body text-label-sm text-on-surface-variant uppercase tracking-wider">Email</span>
                    <input type="email" name="email" required autofocus
                           value="<?= e($_POST['email'] ?? '') ?>"
                           class="mt-2 w-full bg-surface-container-low border border-white/10 rounded-lg px-4 py-3 text-on-surface placeholder-on-surface-variant focus:outline-none focus:border-primary/60 transition-colors"
                           placeholder="you@example.com"/>
                </label>
                <label class="block">
                    <span class="font-body text-label-sm text-on-surface-variant uppercase tracking-wider">Password</span>
                    <input type="password" name="password" required
                           class="mt-2 w-full bg-surface-container-low border border-white/10 rounded-lg px-4 py-3 text-on-surface placeholder-on-surface-variant focus:outline-none focus:border-primary/60 transition-colors"
                           placeholder="••••••••"/>
                </label>
                <button type="submit" class="btn-primary w-full h-12 rounded-full font-display text-body-lg font-bold flex items-center justify-center gap-2 mt-6">
                    Log In
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">login</span>
                </button>
            </form>

            <div class="mt-6 text-center">
                <span class="font-body text-label-sm text-on-surface-variant">No account yet? </span>
                <a class="font-body text-label-sm text-primary hover:text-[#f0dbff] transition-colors" href="register.php">Sign up</a>
            </div>
        </div>
    </main>

<?php $hideBottomNav = true; require __DIR__ . '/includes/nav.php'; ?>
