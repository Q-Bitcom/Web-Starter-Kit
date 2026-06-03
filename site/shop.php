<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

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

$currentUser = &$usersData[$userIndex];
$message = "";

// Ensure user has credits and upgrade trackers
if (!isset($currentUser['credits'])) $currentUser['credits'] = 0;
if (!isset($currentUser['upgrades'])) {
    $currentUser['upgrades'] = ['drill' => 1, 'cargo' => 1, 'fuel' => 1];
}
if (!isset($currentUser['gear'])) {
    $currentUser['gear'] = ['drill' => 1, 'maxCargo' => 15, 'maxFuel' => 100];
}

// Define the Upgrade Paths & Costs
$shopItems = [
    'drill' => [
        'name' => 'Drill Engine',
        'icon' => '⚙️',
        'desc' => 'Pierce through denser crust and mine harder ores.',
        'levels' => [
            1 => ['cost' => 0,      'value' => 1, 'text' => 'Tier 1 (Copper)'],
            2 => ['cost' => 500,    'value' => 2, 'text' => 'Tier 2 (Iron)'],
            3 => ['cost' => 2500,   'value' => 3, 'text' => 'Tier 3 (Gold)'],
            4 => ['cost' => 10000,  'value' => 4, 'text' => 'Tier 4 (Titanium)'],
            5 => ['cost' => 50000,  'value' => 5, 'text' => 'Tier 5 (Uranium)']
        ]
    ],
    'cargo' => [
        'name' => 'Cargo Hold',
        'icon' => '📦',
        'desc' => 'Expand your ship to carry more blocks per run.',
        'levels' => [
            1 => ['cost' => 0,      'value' => 15,  'text' => '15 Slots'],
            2 => ['cost' => 200,    'value' => 30,  'text' => '30 Slots'],
            3 => ['cost' => 1000,   'value' => 50,  'text' => '50 Slots'],
            4 => ['cost' => 5000,   'value' => 100, 'text' => '100 Slots'],
            5 => ['cost' => 20000,  'value' => 250, 'text' => '250 Slots']
        ]
    ],
    'fuel' => [
        'name' => 'Fuel Cell',
        'icon' => '🔋',
        'desc' => 'Increase max power to travel deeper underground.',
        'levels' => [
            1 => ['cost' => 0,      'value' => 100, 'text' => '100 Max Fuel'],
            2 => ['cost' => 300,    'value' => 150, 'text' => '150 Max Fuel'],
            3 => ['cost' => 1500,   'value' => 250, 'text' => '250 Max Fuel'],
            4 => ['cost' => 6000,   'value' => 400, 'text' => '400 Max Fuel'],
            5 => ['cost' => 25000,  'value' => 750, 'text' => '750 Max Fuel']
        ]
    ]
];

// Handle Buying Upgrades
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upgrade_id'])) {
    $upgradeId = $_POST['upgrade_id'];

    if (array_key_exists($upgradeId, $shopItems)) {
        $currentLevel = $currentUser['upgrades'][$upgradeId];
        $nextLevel = $currentLevel + 1;

        // Check if a next level actually exists
        if (isset($shopItems[$upgradeId]['levels'][$nextLevel])) {
            $cost = $shopItems[$upgradeId]['levels'][$nextLevel]['cost'];

            if ($currentUser['credits'] >= $cost) {
                // Deduct money and level up
                $currentUser['credits'] -= $cost;
                $currentUser['upgrades'][$upgradeId] = $nextLevel;

                // Apply the new stats to the user's active gear
                if ($upgradeId === 'drill') $currentUser['gear']['drill'] = $shopItems['drill']['levels'][$nextLevel]['value'];
                if ($upgradeId === 'cargo') $currentUser['gear']['maxCargo'] = $shopItems['cargo']['levels'][$nextLevel]['value'];
                if ($upgradeId === 'fuel') {
                    $currentUser['gear']['maxFuel'] = $shopItems['fuel']['levels'][$nextLevel]['value'];
                    // Automatically top off their fuel when they upgrade the tank
                    $currentUser['activeRun']['fuel'] = $currentUser['gear']['maxFuel'];
                }

                $message = "SUCCESS: Upgraded " . $shopItems[$upgradeId]['name'] . " to Level " . $nextLevel . "!";
            } else {
                $message = "ERROR: Insufficient credits. You need $" . number_format($cost) . ".";
            }
        }
    }

    file_put_contents('users.json', json_encode($usersData, JSON_PRETTY_PRINT));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>The Mines - Upgrade Shop</title>
    <link rel="stylesheet" href="style.css">
    <style>
        html, body {
            background-color: #0a0a0a !important; 
            color: #fff;
            font-family: Arial, sans-serif;
            background-image: none !important;
        }
        .container {
            max-width: 900px;
            margin: 40px auto;
            background: #111;
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #444;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #00fa9a;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .credits-display {
            font-size: 2rem;
            color: #00fa9a;
            text-align: center;
            margin-bottom: 30px;
            font-weight: bold;
        }
        .shop-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .shop-card {
            background: #222;
            border: 2px solid #333;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .shop-icon { font-size: 3rem; margin-bottom: 10px; }
        .shop-title { font-size: 1.5rem; color: #ffaa00; margin-bottom: 10px; font-weight: bold; }
        .shop-desc { font-size: 0.9rem; color: #aaa; margin-bottom: 15px; min-height: 40px; }
        .shop-stat { font-size: 1.1rem; margin-bottom: 15px; color: #fff; }

        .btn-buy {
            background-color: #00fa9a;
            color: #000;
            border: none;
            padding: 12px;
            font-size: 1.1rem;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            transition: 0.2s;
        }
        .btn-buy:hover { background-color: #00cc7a; }
        .btn-disabled {
            background-color: #444;
            color: #777;
            cursor: not-allowed;
        }
        .btn-disabled:hover { background-color: #444; }

        .log-box {
            text-align: center; color: #1e90ff; font-weight: bold;
            margin-bottom: 20px; padding: 10px; background: #1a1a2e; border-radius: 4px;
        }
    </style>
</head>
<body>

    <?php include_once 'nav.php'; ?>

    <div class="container">
        <div class="header">
            <h1>GEAR UPGRADE BAY</h1>
            <p>Spend credits to enhance your mining rig's capabilities.</p>
        </div>

        <div class="credits-display">
            Balance: $<?php echo number_format($currentUser['credits']); ?>
        </div>

        <?php if (!empty($message)): ?>
            <div class="log-box"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="shop-grid">
            <?php foreach ($shopItems as $id => $item): ?>
                <?php 
                    $currentLevel = $currentUser['upgrades'][$id];
                    $currentData = $item['levels'][$currentLevel];
                    $isMaxed = !isset($item['levels'][$currentLevel + 1]);

                    if (!$isMaxed) {
                        $nextData = $item['levels'][$currentLevel + 1];
                        $cost = $nextData['cost'];
                        $canAfford = ($currentUser['credits'] >= $cost);
                    }
                ?>
                <div class="shop-card">
                    <div>
                        <div class="shop-icon"><?php echo $item['icon']; ?></div>
                        <div class="shop-title"><?php echo $item['name']; ?></div>
                        <div class="shop-desc"><?php echo $item['desc']; ?></div>

                        <div class="shop-stat">
                            <span style="color: #aaa;">Current:</span> <?php echo $currentData['text']; ?><br>
                            <?php if (!$isMaxed): ?>
                                <span style="color: #00fa9a;">Next: <?php echo $nextData['text']; ?></span>
                            <?php else: ?>
                                <span style="color: #ff4d4d; font-weight: bold;">MAXIMUM LEVEL REACHED</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!$isMaxed): ?>
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="upgrade_id" value="<?php echo $id; ?>">
                            <button type="submit" class="btn-buy <?php echo (!$canAfford) ? 'btn-disabled' : ''; ?>" <?php echo (!$canAfford) ? 'disabled' : ''; ?>>
                                Upgrade ($<?php echo number_format($cost); ?>)
                            </button>
                        </form>
                    <?php else: ?>
                        <button class="btn-buy btn-disabled" disabled>Fully Upgraded</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</body>
</html>