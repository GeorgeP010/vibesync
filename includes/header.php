<?php
/**
 * Shared <head> and top app bar.
 *
 * Pages set these variables before including this file:
 *   $pageTitle       string  e.g. "Home"
 *   $bodyClass       string  override body classes (optional)
 *   $topBarLeft      string  HTML for the left action (optional, defaults to profile link)
 *   $topBarRight     string  HTML for the right action (optional, defaults to notifications)
 *   $topBarTitle     string  visible title in the bar (defaults to "VibeSync")
 *   $hideTopBar      bool    skip top bar entirely (used by index/login/register)
 *   $extraHeadHtml   string  extra <head> content
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$pageTitle    = $pageTitle    ?? 'VibeSync';
$bodyClass    = $bodyClass    ?? 'bg-background text-on-background bg-radial-default min-h-screen font-body text-body-md overflow-x-hidden';
$topBarTitle  = $topBarTitle  ?? 'VibeSync';
$hideTopBar   = $hideTopBar   ?? false;
$extraHeadHtml = $extraHeadHtml ?? '';
?><!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"/>
    <title>VibeSync - <?= e($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=Inter:wght@400;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="css/style.css" rel="stylesheet"/>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: { extend: {
                colors: { "primary": "#ddb7ff", "primary-container": "#b76dff", "tertiary": "#ffb0cd", "tertiary-container": "#f751a1", "secondary": "#adc6ff", "secondary-container": "#0566d9", "surface": "#131315", "surface-container": "#201f21", "surface-container-high": "#2a2a2c", "surface-container-highest": "#353437", "surface-container-low": "#1c1b1d", "surface-container-lowest": "#0e0e10", "on-surface": "#e5e1e4", "on-surface-variant": "#cfc2d6", "outline": "#988d9f", "outline-variant": "#4d4354", "on-primary": "#490080", "on-primary-container": "#400071", "background": "#131315", "on-background": "#e5e1e4", "error": "#ffb4ab", "secondary-fixed-dim": "#adc6ff" },
                fontFamily: { "display": ["Sora"], "body": ["Inter"] },
                fontSize: { "display-xl": ["48px", { lineHeight: "56px", letterSpacing: "-0.02em", fontWeight: "800" }], "headline-lg": ["32px", { lineHeight: "40px", letterSpacing: "-0.01em", fontWeight: "700" }], "headline-md": ["24px", { lineHeight: "32px", fontWeight: "600" }], "body-lg": ["18px", { lineHeight: "28px", fontWeight: "400" }], "body-md": ["16px", { lineHeight: "24px", fontWeight: "400" }], "label-sm": ["12px", { lineHeight: "16px", letterSpacing: "0.05em", fontWeight: "600" }] }
            }}
        }
    </script>
    <?= $extraHeadHtml ?>
</head>
<body class="<?= e($bodyClass) ?>" data-logged-in="<?= isLoggedIn() ? '1' : '0' ?>">
<?php if (!$hideTopBar):
    $defaultLeft = '<a href="profile.php" class="text-primary hover:bg-white/5 transition-colors p-2 rounded-full">'
                 . '<span class="material-symbols-outlined">account_circle</span></a>';
    $defaultRight = '<button class="hover:bg-white/5 transition-colors p-2 rounded-full" data-action="open-notifications" data-message="No new notifications">'
                  . '<span class="material-symbols-outlined text-primary">notifications</span></button>';
    $left  = $topBarLeft  ?? $defaultLeft;
    $right = $topBarRight ?? $defaultRight;
?>
<header class="top-bar">
    <?= $left ?>
    <h1 class="top-bar-title"><?= e($topBarTitle) ?></h1>
    <?= $right ?>
</header>
<?php endif; ?>
