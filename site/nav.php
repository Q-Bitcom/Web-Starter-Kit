<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="game-navbar">
    <div class="nav-logo">
        <a href="index.php">The Mines</a>
    </div>
    <ul class="nav-links">
        <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="index.php">Home</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="game.php">Mining Sector</a></li>
            <li><a href="market.php">AI Market</a></li>
            <li><a href="shop.php">Workshop</a></li>
            <li><a href="checkpoints.php">Progression</a></li>
            <?php if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] === true): ?>
                <li><a href="admin.php" class="admin-link">Admin Panel</a></li>
            <?php endif; ?>
            <li><a href="logout.php" class="logout-link">Logout</a></li>
        <?php else: ?>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php">Register</a></li>
        <?php endif; ?>
    </ul>
</nav>

<style>
.game-navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #222;
    padding: 10px 20px;
    font-family: Arial, sans-serif;
}
.game-navbar a {
    color: #fff;
    text-decoration: none;
}
.nav-links {
    display: flex;
    list-style: none;
    gap: 15px;
    margin: 0;
    padding: 0;
}
.nav-links a:hover {
    color: #ffd700;
}
.admin-link {
    color: #ff4d4d !important;
}
.logout-link {
    color: #aaaaaa !important;
}
.logout-link:hover {
    color: #ff4d4d !important;
}
</style>