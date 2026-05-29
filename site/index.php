<?php
session_start();

// Gatekeeper check: Kick out unauthenticated visitors
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>The Mines - Home</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php include_once 'nav.php'; ?>

    <main style="padding: 20px; max-width: 800px; margin: 0 auto; font-family: sans-serif;">
        <h2>Welcome to The Mines, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
        <p><em>Status: Operational</em></p>
        <hr>
        <h3>The Lore</h3>
        <blockquote style="background: #f9f9f9; border-left: 5px solid #ccc; padding: 10px; color: #333;">
            "You were a regular person in the working class till one day you went into debt and had to sell your soul to the devil. Now you are a miner in the mines of the underworld forever."
        </blockquote>
    </main>

</body>
</html>