<?php
session_start();

// Security Gatekeeper: Ensure only logged-in pilots can see this
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Load the database
$usersData = json_decode(file_get_contents('users.json'), true);
$currentUser = null;

// Search for the active player's record
foreach ($usersData as $user) {
    if ($user['id'] === $_SESSION['user_id']) {
        $currentUser = $user;
        break;
    }
}

// Fallback if data is missing
if (!$currentUser) {
    echo "ERROR: Profile synchronization failed. Please log in again.";
    exit;
}

// Calculate Prospector Operational Rank based on Banked Balance
$balance = $currentUser['bankedBalance'];
if ($balance >= 5000) {
    $rank = "Deep-Crust Tycoon";
} elseif ($balance >= 1000) {
    $rank = "Elite Prospector";
} else {
    $rank = "Surface Scrapper";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>The Mines - Profile Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-container {
            max-width: 600px;
            margin: 40px auto;
            font-family: Arial, sans-serif;
            border: 1px solid #444;
            background-color: #1a1a1a;
            color: #fff;
            border-radius: 8px;
            padding: 20px;
        }
        .profile-header {
            border-bottom: 2px solid #ffd700;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .metric-card {
            background-color: #2a2a2a;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #ffd700;
        }
        .metric-title {
            font-size: 0.85rem;
            color: #aaa;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .metric-value {
            font-size: 1.4rem;
            font-weight: bold;
        }
        .rank-highlight {
            color: #ffd700;
        }
    </style>
</head>
<body>

    <?php include_once 'nav.php'; ?>

    <div class="profile-container">
        <div class="profile-header">
            <h2>Pilot Console</h2>
            <p>System Status: Secure Link Established</p>
        </div>

        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-title">Pilot Callsign</div>
                <div class="metric-value"><?php echo htmlspecialchars($currentUser['username']); ?></div>
            </div>

            <div class="metric-card">
                <div class="metric-title">Banked Balance</div>
                <div class="metric-value"><?php echo $currentUser['bankedBalance']; ?> credits</div>
            </div>

            <div class="metric-card">
                <div class="metric-title">Equipped Tool Asset</div>
                <div class="metric-value"><?php echo htmlspecialchars($currentUser['equippedDrill']); ?></div>
            </div>

            <div class="metric-card">
                <div class="metric-title">Current Fuel Allocation</div>
                <div class="metric-value"><?php echo $currentUser['activeRun']['fuel']; ?> units</div>
            </div>

            <div class="metric-card">
                <div class="metric-title">Maximum Depth Plotted</div>
                <div class="metric-value"><?php echo $currentUser['maxDepth']; ?> meters</div>
            </div>

            <div class="metric-card">
                <div class="metric-title">Lifetime Ores Excavated</div>
                <div class="metric-value"><?php echo $currentUser['lifetimeOres']; ?> units</div>
            </div>

            <div class="metric-card">
                <div class="metric-title">Banked Copper Reserve</div>
                <div class="metric-value"><?php echo $currentUser['bankedVault']['copper']; ?> blocks</div>
            </div>

            <div class="metric-card">
                <div class="metric-title">Banked Iron Reserve</div>
                <div class="metric-value"><?php echo $currentUser['bankedVault']['iron']; ?> blocks</div>
            </div>
        </div>

        <div class="metric-card" style="margin-top: 15px; border-left-color: #ff4d4d;">
            <div class="metric-title">Prospector Operational Rank</div>
            <div class="metric-value rank-highlight"><?php echo $rank; ?></div>
        </div>
    </div>

</body>
</html>