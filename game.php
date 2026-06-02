<?php
session_start();

// Security Gatekeeper: Ensure only logged-in players can access the underworld grid
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Load global configuration constants and player database
$settings = json_decode(file_get_contents('settings.json'), true);
$usersData = json_decode(file_get_contents('users.json'), true);

$userIndex = null;
foreach ($usersData as $index => $user) {
    if ($user['id'] === $_SESSION['user_id']) {
        $userIndex = $index;
        break;
    }
}

if ($userIndex === null) {
    echo "ERROR: User session authentication invalid.";
    exit;
}

// Create a reference pointer directly to the active user array data
$currentUser = &$usersData[$userIndex];
$maxDepthLimit = isset($settings['maxDepth']) ? $settings['maxDepth'] : 100;
$message = "";

// Process Coordinate Movement Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'descend') {
        if ($currentUser['activeRun']['y'] < $maxDepthLimit) {
            $currentUser['activeRun']['y'] += 1;

            // If the player goes deeper than ever before, update career record metrics
            if ($currentUser['activeRun']['y'] > $currentUser['maxDepth']) {
                $currentUser['maxDepth'] = $currentUser['activeRun']['y'];
            }
            $message = "SUCCESS: Mining rig descended further into the dark crust.";
        } else {
            $message = "WARNING: Bedrock boundary reached. Cannot descend beyond the depth threshold.";
        }
    } elseif ($action === 'ascend') {
        if ($currentUser['activeRun']['y'] > 0) {
            $currentUser['activeRun']['y'] -= 1;
            $message = "SUCCESS: Thrusters engaged. Ascending back toward surface gates.";
        } else {
            $message = "WARNING: Already positioned at surface level zero.";
        }
    }

    // Commit the coordinate changes back to the JSON database file
    file_put_contents('users.json', json_encode($usersData, JSON_PRETTY_PRINT));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>The Mines - Mining Sector</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .game-container {
            max-width: 600px;
            margin: 40px auto;
            font-family: Arial, sans-serif;
            border: 1px solid #444;
            background-color: #111;
            color: #fff;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        .status-panel {
            background-color: #222;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #ff4d4d;
        }
        .depth-display {
            font-size: 2.5rem;
            font-weight: bold;
            color: #ff4d4d;
            margin: 10px 0;
        }
        .controls-panel {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }
        .control-btn {
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: bold;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            color: #000;
        }
        .btn-ascend {
            background-color: #aaa;
        }
        .btn-descend {
            background-color: #ff4d4d;
            color: #fff;
        }
        .btn-ascend:hover { background-color: #ccc; }
        .btn-descend:hover { background-color: #ff6666; }
        .log-box {
            margin-top: 20px;
            padding: 10px;
            background: #1a1a1a;
            border: 1px solid #333;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

    <?php include_once 'nav.php'; ?>

    <div class="game-container">
        <h2>Mining Sector - Underworld Grid</h2>
        <p>Operational Status: Contract Binding Active</p>

        <div class="status-panel">
            <div>Current Depth Location</div>
            <div class="depth-display"><?php echo $currentUser['activeRun']['y']; ?> meters</div>
            <div>Max Depth Plotted: <?php echo $currentUser['maxDepth']; ?> meters</div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="log-box">
                <span style="color: #ffd700;"><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <div class="controls-panel">
            <form action="game.php" method="POST">
                <input type="hidden" name="action" value="ascend">
                <button type="submit" class="control-btn btn-ascend" <?php echo ($currentUser['activeRun']['y'] == 0) ? 'disabled' : ''; ?>>Ascend Rig</button>
            </form>

            <form action="game.php" method="POST">
                <input type="hidden" name="action" value="descend">
                <button type="submit" class="control-btn btn-descend" <?php echo ($currentUser['activeRun']['y'] >= $maxDepthLimit) ? 'disabled' : ''; ?>>Descend Rig</button>
            </form>
        </div>
    </div>

</body>
</html>