<?php
/**
 * Splash / landing page. If the visitor is already logged in, jump to home.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: home.php');
    exit;
}

$pageTitle  = 'Festival Assistant';
$bodyClass  = 'bg-background text-on-background min-h-screen flex flex-col relative overflow-hidden';
$hideTopBar = true;
require __DIR__ . '/includes/header.php';
?>
    <!-- Background Gradients -->
    <div class="absolute inset-0 z-0 pointer-events-none">
        <div class="absolute top-[-20%] left-[-10%] w-[70vw] h-[70vw] rounded-full bg-[radial-gradient(circle,rgba(183,109,255,0.2),transparent)] blur-[100px]"></div>
        <div class="absolute bottom-[-20%] right-[-10%] w-[60vw] h-[60vw] rounded-full bg-[radial-gradient(circle,rgba(5,102,217,0.2),transparent)] blur-[80px]"></div>
        <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
    </div>

    <main class="flex-grow flex flex-col items-center justify-center relative z-10 px-5 w-full max-w-md mx-auto">
        <div class="flex flex-col items-center mb-16">
            <div class="relative mb-10 group">
                <div class="absolute inset-0 bg-primary/40 rounded-full blur-[30px] transition-transform duration-1000 group-hover:scale-110"></div>
                <div class="w-28 h-28 rounded-full glass-panel flex items-center justify-center relative z-10 neon-glow-primary">
                    <span class="material-symbols-outlined text-[56px] text-primary" style="font-variation-settings: 'FILL' 1;">graphic_eq</span>
                </div>
            </div>
            <h1 class="font-display text-display-xl text-transparent bg-clip-text bg-gradient-to-r from-primary via-tertiary to-secondary text-center tracking-tight">VibeSync</h1>
            <p class="font-body text-body-lg text-on-surface-variant mt-3 text-center max-w-[280px]">Sync your frequency. Find your crowd.</p>
        </div>

        <div class="w-full mt-10">
            <a href="register.php" class="btn-primary w-full h-14 rounded-full font-display text-headline-md font-bold flex items-center justify-center gap-2 no-underline">
                Get Started
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">arrow_forward</span>
            </a>
            <div class="mt-5 text-center">
                <span class="font-body text-label-sm text-on-surface-variant">Already have an account? </span>
                <a class="font-body text-label-sm text-primary hover:text-[#f0dbff] transition-colors" href="login.php">Log In</a>
            </div>
        </div>
    </main>

<?php $hideBottomNav = true; require __DIR__ . '/includes/nav.php'; ?>
