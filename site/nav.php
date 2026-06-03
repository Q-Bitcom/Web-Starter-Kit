<?php
// Ensure session is active to read the logged in user profile securely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav style="background: #111; padding: 15px; border-bottom: 1px solid #333; text-align: center;">
    <a href="index.php" style="color: #fff; margin: 0 15px; text-decoration: none; font-weight: bold;">🏠 Home</a>
    <a href="game.php" style="color: #00fa9a; margin: 0 15px; text-decoration: none; font-weight: bold;">🚀 Play The Mines</a>
    <a href="market.php" style="color: #fff; margin: 0 15px; text-decoration: none; font-weight: bold;">Market</a>
    <a href="shop.php" style="color: #fff; margin: 0 15px; text-decoration: none; font-weight: bold;">Upgrade Shop</a>
    <a href="leaderboard.php" style="color: #fff; margin: 0 15px; text-decoration: none; font-weight: bold;">🏆 Leaderboards</a>
    <a href="checkpoints.php" style="color: #ffaa00; margin: 0 15px; text-decoration: none; font-weight: bold;">📁 Project Checkpoints</a>

    <a href="admin.php" style="color: #ff4d4d; margin: 0 15px; text-decoration: none; font-weight: bold; border-left: 1px solid #444; padding-left: 15px;">
        ⚙️ Admin
    </a>

    <a href="logout.php" style="color: #ff4d4d; margin: 0 15px; text-decoration: none; font-weight: bold; border-left: 1px solid #444; padding-left: 15px;">
        🚪 Log Out
    </a>
</nav>