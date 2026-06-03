<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] === true;
?>
<nav style="background: #ffffff; padding: 15px; border-bottom: 2px solid #000000; text-align: center;">

    <?php if ($isLoggedIn): ?>
        <a href="index.php" style="color: #000000; margin: 0 15px; text-decoration: none; font-weight: bold;">🏠 Dashboard</a>
        <a href="game.php" style="color: #00b36b; margin: 0 15px; text-decoration: none; font-weight: bold;">🚀 Play The Mines</a>
        <a href="market.php" style="color: #000000; margin: 0 15px; text-decoration: none; font-weight: bold;">Market</a>
        <a href="shop.php" style="color: #000000; margin: 0 15px; text-decoration: none; font-weight: bold;">Upgrade Shop</a>
        <a href="leaderboard.php" style="color: #000000; margin: 0 15px; text-decoration: none; font-weight: bold;">🏆 Leaderboards</a>
        <a href="checkpoints.php" style="color: #ffaa00; margin: 0 15px; text-decoration: none; font-weight: bold;">📁 Project Checkpoints</a>

        <?php if ($isAdmin): ?>
            <a href="admin.php" style="color: #ff4d4d; margin: 0 15px; text-decoration: none; font-weight: bold; border-left: 2px solid #000000; padding-left: 15px;">⚙️ Admin</a>
        <?php endif; ?>

        <a href="logout.php" style="color: #ff4d4d; margin: 0 15px; text-decoration: none; font-weight: bold; border-left: 2px solid #000000; padding-left: 15px;">🚪 Log Out</a>
    <?php else: ?>
        <a href="#" onclick="if(typeof toggleView === 'function'){ toggleView('login'); } return false;" style="color: #b38600; margin: 0 15px; text-decoration: none; font-weight: bold; background: #fffbe6; padding: 5px 10px; border: 1px solid #b38600; border-radius: 4px;">🔑 Sign In</a>
        <a href="#" onclick="if(typeof toggleView === 'function'){ toggleView('register'); } return false;" style="color: #000000; margin: 0 15px; text-decoration: none; font-weight: bold; padding: 5px 10px; border: 1px solid #000000; border-radius: 4px;">📝 Sign Up</a>
    <?php endif; ?>
</nav>