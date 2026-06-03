<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// THE PASSWORD DEFINITION
$adminPassword = 'Q-Bitcom';

// Handle password submission
if (isset($_POST['admin_password_input'])) {
    if ($_POST['admin_password_input'] === $adminPassword) {
        $_SESSION['admin_authenticated'] = true;
        // Refresh page to clear POST data
        header("Location: admin.php");
        exit;
    } else {
        $error = "❌ Invalid terminal password sequence. Access revoked.";
    }
}

// IF NOT AUTHENTICATED, SHOW THE PASSWORD INPUT SCREEN
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true): 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>The Mines - Terminal Lockout</title>
    <link rel="stylesheet" href="style.css">
    <style>
        html, body { background-color: #0a0a0a !important; color: #fff; font-family: Arial, sans-serif; }
        .lock-container { max-width: 400px; margin: 100px auto; background: #111; padding: 30px; border-radius: 8px; border: 2px solid #ffaa00; text-align: center; }
        .lock-container h2 { color: #ffaa00; margin-top: 0; font-size: 1.4rem; }
        .password-field { width: 90%; padding: 12px; margin: 15px 0; background: #222; border: 1px solid #555; color: #fff; text-align: center; font-size: 1.1rem; border-radius: 4px; }
        .submit-btn { background: #ffaa00; color: #000; border: none; padding: 10px 25px; font-weight: bold; cursor: pointer; border-radius: 4px; font-size: 1rem; }
        .submit-btn:hover { background: #fff; }
        .error-msg { color: #ff4d4d; font-weight: bold; margin-bottom: 15px; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="lock-container">
        <h2>🔒 AUTHENTICATION REQUIRED</h2>
        <p style="color: #888; font-size: 0.9rem;">Enter the administrative key to clear security protocols.</p>

        <?php if (isset($error)): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="admin.php">
            <input type="password" name="admin_password_input" class="password-field" placeholder="Enter Password" autocomplete="off" autofocus required>
            <br>
            <button type="submit" class="submit-btn">Decrypt Terminal</button>
        </form>
        <p style="margin-top: 20px;"><a href="game.php" style="color: #666; text-decoration: none; font-size: 0.85rem;">← Abort and Return to Game</a></p>
    </div>
</body>
</html>
<?php
exit; // Stop processing the rest of the page metrics until they pass the check above
endif;

// --- SECURE DATA AGGREGATION ENGINE (RUNS ONLY AFTER PASSWORD IS CORRECT) ---
$usersData = json_decode(file_get_contents('users.json'), true);

$totalPlayers = count($usersData);
$globalOresMined = 0;
$globalBankedCredits = 0;

$globalOresBreakdown = [
    'copper' => 0,
    'iron' => 0,
    'gold' => 0,
    'titanium' => 0,
    'uranium' => 0
];

foreach ($usersData as $user) {
    if (isset($user['stats']['totalMined'])) {
        $globalOresMined += $user['stats']['totalMined'];
    } elseif (isset($user['lifetimeOres'])) {
        $globalOresMined += $user['lifetimeOres'];
    }

    if (isset($user['credits'])) {
        $globalBankedCredits += $user['credits'];
    } elseif (isset($user['balance'])) {
        $globalBankedCredits += $user['balance'];
    }

    if (isset($user['bankedVault'])) {
        foreach ($globalOresBreakdown as $ore => $amount) {
            if (isset($user['bankedVault'][$ore])) {
                $globalOresBreakdown[$ore] += $user['bankedVault'][$ore];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>The Mines - Central Command Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        html, body { background-color: #0a0a0a !important; color: #fff; font-family: Arial, sans-serif; background-image: none !important; }
        .container { max-width: 900px; margin: 40px auto; background: #111; padding: 30px; border-radius: 8px; border: 2px solid #555; }
        .header { text-align: center; border-bottom: 2px solid #00fa9a; padding-bottom: 15px; margin-bottom: 30px; }
        .grid-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 35px; }
        .stat-card { background: #161616; padding: 20px; border-radius: 6px; border-left: 4px solid #00fa9a; text-align: center; }
        .stat-card h3 { margin: 0 0 10px 0; color: #888; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; }
        .stat-card p { margin: 0; font-size: 1.8rem; font-weight: bold; color: #fff; }
        .breakdown-box { background: #161616; padding: 25px; border-radius: 6px; border: 1px solid #333; }
        .breakdown-box h2 { margin-top: 0; font-size: 1.3rem; color: #ffaa00; }
        .ore-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #222; font-size: 1.1rem; }
        .ore-row:last-child { border-bottom: none; }
        .ore-name { font-weight: bold; }
        .ore-count { color: #00fa9a; font-weight: bold; }
    </style>
</head>
<body>

    <?php include_once 'nav.php'; ?>

    <div class="container">
        <div class="header">
            <h1>⚙️ CENTRAL COMMAND TERMINAL</h1>
            <p>Global server metrics and macro-economic data surveillance monitors.</p>
        </div>

        <div class="grid-stats">
            <div class="stat-card">
                <h3>Total Registered Pilots</h3>
                <p>👤 <?php echo number_format($totalPlayers); ?></p>
            </div>
            <div class="stat-card" style="border-left-color: #ffaa00;">
                <h3>Global Extraction Volume</h3>
                <p>⛏️ <?php echo number_format($globalOresMined); ?></p>
            </div>
            <div class="stat-card" style="border-left-color: #ffd700;">
                <h3>Total System Economy Wealth</h3>
                <p>🪙 <?php echo number_format($globalBankedCredits); ?></p>
            </div>
        </div>

        <div class="breakdown-box">
            <h2>📦 Total Banked Reserves (All Players Combined)</h2>
            <p style="color: #666; font-size: 0.9rem; margin-bottom: 20px;">Quantities currently secured safely within player home vaults.</p>

            <div class="ore-row">
                <span class="ore-name" style="color: #b87333;">🟧 Copper Reserves</span>
                <span class="ore-count"><?php echo number_format($globalOresBreakdown['copper']); ?> units</span>
            </div>
            <div class="ore-row">
                <span class="ore-name" style="color: #d3d3d3;">⬜ Iron Reserves</span>
                <span class="ore-count"><?php echo number_format($globalOresBreakdown['iron']); ?> units</span>
            </div>
            <div class="ore-row">
                <span class="ore-name" style="color: #ffaa00;">🟨 Gold Reserves</span>
                <span class="ore-count"><?php echo number_format($globalOresBreakdown['gold']); ?> units</span>
            </div>
            <div class="ore-row">
                <span class="ore-name" style="color: #a5c8d0;">💠 Titanium Reserves</span>
                <span class="ore-count"><?php echo number_format($globalOresBreakdown['titanium']); ?> units</span>
            </div>
            <div class="ore-row">
                <span class="ore-name" style="color: #39ff14;">🟩 Uranium Reserves</span>
                <span class="ore-count"><?php echo number_format($globalOresBreakdown['uranium']); ?> units</span>
            </div>
        </div>
    </div>

</body>
</html>