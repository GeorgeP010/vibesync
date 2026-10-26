<?php
/**
 * Bottom navigation. Closes <body>/<html>.
 * Set $hideBottomNav = true; before including to skip nav (e.g. splash/login pages).
 */

declare(strict_types=1);

$hideBottomNav = $hideBottomNav ?? false;
?>
<?php if (!$hideBottomNav): ?>
<nav class="bottom-nav">
    <div class="nav-items">
        <a href="home.php" class="nav-item" data-page="home">
            <span class="material-symbols-outlined">home</span>
            <span>Home</span>
        </a>
        <a href="map.php" class="nav-item" data-page="map">
            <span class="material-symbols-outlined">map</span>
            <span>Map</span>
        </a>
        <a href="schedule.php" class="nav-item" data-page="schedule">
            <span class="material-symbols-outlined">event_note</span>
            <span>Schedule</span>
        </a>
        <a href="artists.php" class="nav-item" data-page="artists">
            <span class="material-symbols-outlined">explore</span>
            <span>Artists</span>
        </a>
        <a href="queues.php" class="nav-item" data-page="queues">
            <span class="material-symbols-outlined">hourglass_empty</span>
            <span>Queues</span>
        </a>
    </div>
</nav>
<?php endif; ?>

<script src="js/app.js"></script>
</body>
</html>
