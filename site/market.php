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

// Ensure credits, vault, and our NEW market saturation tracking exist
if (!isset($currentUser['credits'])) $currentUser['credits'] = 0;
if (!isset($currentUser['bankedVault'])) $currentUser['bankedVault'] = ["copper" => 0, "iron" => 0, "gold" => 0, "titanium" => 0, "uranium" => 0];
if (!isset($currentUser['marketSaturation'])) $currentUser['marketSaturation'] = ["copper" => 0, "iron" => 0, "gold" => 0, "titanium" => 0, "uranium" => 0];

// The Base Market Economy (Average Price per unit)
$basePrices = [
    'copper' => 5,
    'iron' => 15,
    'gold' => 50,
    'titanium' => 150,
    'uranium' => 500
];

// Market Weights: How much 1 unit sold affects the market crash percentage
$oreWeights = [
    'copper' => 0.2,   // Need to sell 500 to crash market by 100%
    'iron' => 0.5,     // Need to sell 200 to crash market by 100%
    'gold' => 2.0,     // Need to sell 50 to crash market by 100%
    'titanium' => 5.0, // Need to sell 20 to crash market by 100%
    'uranium' => 20.0  // Need to sell just 5 to crash market by 100%
];

// Dynamic Market System: Prices shift every 5 minutes!
if (!isset($_SESSION['market_prices']) || time() > $_SESSION['market_expires']) {
    $currentPrices = [];
    foreach ($basePrices as $ore => $base) {
        // 1. Random Fluctuation between -20% and +20%
        $fluctuation = rand(-20, 20) / 100;

        // 2. Supply & Demand Penalty based on how much the player has sold
        $crashPercentage = $currentUser['marketSaturation'][$ore] * $oreWeights[$ore];
        // Cap the crash so the penalty never exceeds 90%
        $crashPercentage = min(90, $crashPercentage) / 100; 

        // Calculate final price: Base + Fluctuation - Crash Penalty
        $newPrice = ceil($base + ($base * $fluctuation) - ($base * $crashPercentage));
        $currentPrices[$ore] = max(1, $newPrice); // Price can never drop below $1

        // 3. Market Recovery: Saturation heals by 50% every cycle!
        $currentUser['marketSaturation'][$ore] = floor($currentUser['marketSaturation'][$ore] * 0.5);
    }

    // Save the newly healed saturation levels to the database
    file_put_contents('users.json', json_encode($usersData, JSON_PRETTY_PRINT));

    $_SESSION['market_prices'] = $currentPrices;
    $_SESSION['market_expires'] = time() + 300; 
}

$marketPrices = $_SESSION['market_prices'];

// Get exact seconds remaining to pass to JavaScript
$timeUntilRefresh = max(0, $_SESSION['market_expires'] - time());

// Handle Selling Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'sell_all') {
        $totalEarned = 0;
        foreach ($marketPrices as $ore => $price) {
            $amount = $currentUser['bankedVault'][$ore];
            if ($amount > 0) {
                $earnings = $amount * $price;
                $totalEarned += $earnings;

                // ADD TO SATURATION: Flooding the market!
                $currentUser['marketSaturation'][$ore] += $amount;
                $currentUser['bankedVault'][$ore] = 0; 
            }
        }

        if ($totalEarned > 0) {
            $currentUser['credits'] += $totalEarned;
            $message = "SUCCESS: Liquidated entire vault for $$totalEarned credits!";
        } else {
            $message = "NOTICE: Your vault is completely empty.";
        }
    } else {
        $oreToSell = str_replace('sell_', '', $action);

        if (array_key_exists($oreToSell, $marketPrices)) {
            $amount = $currentUser['bankedVault'][$oreToSell];

            if ($amount > 0) {
                $earnings = $amount * $marketPrices[$oreToSell];
                $currentUser['credits'] += $earnings;

                // ADD TO SATURATION: Flooding the market!
                $currentUser['marketSaturation'][$oreToSell] += $amount;
                $currentUser['bankedVault'][$oreToSell] = 0; 

                $message = "SUCCESS: Sold $amount " . ucfirst($oreToSell) . " for $$earnings credits!";
            } else {
                $message = "NOTICE: You don't have any " . ucfirst($oreToSell) . " to sell.";
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
    <title>The Mines - Surface Market</title>
    <link rel="stylesheet" href="style.css">
    <style>
        html, body {
            background-color: #0a0a0a !important; 
            color: #fff;
            font-family: Arial, sans-serif;
            background-image: none !important;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            background: #111;
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #444;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #ffd700;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .credits-display {
            font-size: 2rem;
            color: #00fa9a;
            text-align: center;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .timer-display {
            text-align: center; 
            color: #ffaa00; 
            margin-bottom: 20px; 
            font-weight: bold;
        }
        .market-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .market-table th, .market-table td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #333;
        }
        .market-table th { background: #222; color: #aaa; }
        .btn-sell {
            background-color: #444; color: #fff; border: 1px solid #666;
            padding: 8px 15px; border-radius: 4px; cursor: pointer;
            font-weight: bold; transition: 0.2s;
        }
        .btn-sell:hover { background-color: #00fa9a; color: #000; }
        .btn-sell:disabled {
            background-color: #222; color: #555; border: 1px solid #333; cursor: not-allowed;
        }
        .btn-sell-all {
            display: block; width: 100%; padding: 15px; background-color: #ffd700;
            color: #000; font-size: 1.2rem; font-weight: bold; border: none;
            border-radius: 5px; cursor: pointer; margin-top: 20px;
        }
        .btn-sell-all:hover { background-color: #ffaa00; }
        .log-box {
            text-align: center; color: #1e90ff; font-weight: bold;
            margin-bottom: 20px; padding: 10px; background: #1a1a2e; border-radius: 4px;
        }
        .warning-text { color: #ff4d4d; font-size: 0.85rem; display: block; margin-top: 5px; }

        .color-copper { color: #b87333; font-weight: bold; }
        .color-iron { color: #d3d3d3; font-weight: bold; }
        .color-gold { color: #ffd700; font-weight: bold; }
        .color-titanium { color: #a5c8d0; font-weight: bold; }
        .color-uranium { color: #39ff14; font-weight: bold; }
    </style>
</head>
<body>

    <?php include_once 'nav.php'; ?>

    <div class="container">
        <div class="header">
            <h1>SURFACE COMMODITIES MARKET</h1>
            <p>Exchange raw materials for standard galactic credits.</p>
        </div>

        <div class="credits-display">
            Balance: $<?php echo number_format($currentUser['credits']); ?>
        </div>

        <div class="timer-display" id="market-timer">
            Market Prices Update In: --:--
        </div>

        <?php if (!empty($message)): ?>
            <div class="log-box"><?php echo $message; ?></div>
        <?php endif; ?>

        <table class="market-table">
            <thead>
                <tr>
                    <th>Resource</th>
                    <th>Banked Amount</th>
                    <th>Market Value (Per Unit)</th>
                    <th>Total Value</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($marketPrices as $ore => $price): ?>
                    <?php 
                        $amount = $currentUser['bankedVault'][$ore]; 
                        $totalValue = $amount * $price;
                        $isCrashed = ($price < $basePrices[$ore] * 0.5); // Highlight if price is severely crashed
                    ?>
                    <tr>
                        <td>
                            <span class="color-<?php echo $ore; ?>"><?php echo ucfirst($ore); ?></span>
                            <?php if ($isCrashed): ?>
                                <span class="warning-text">MARKET CRASHED</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $amount; ?></td>
                        <td style="<?php echo $isCrashed ? 'color: #ff4d4d;' : ''; ?>">$<?php echo $price; ?></td>
                        <td style="color: #00fa9a;">$<?php echo number_format($totalValue); ?></td>
                        <td>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="sell_<?php echo $ore; ?>">
                                <button type="submit" class="btn-sell" <?php echo ($amount <= 0) ? 'disabled' : ''; ?>>
                                    Sell All <?php echo ucfirst($ore); ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <form method="POST">
            <input type="hidden" name="action" value="sell_all">
            <button type="submit" class="btn-sell-all">LIQUIDATE ENTIRE VAULT</button>
        </form>
    </div>

    <script>
        let timeLeft = <?php echo $timeUntilRefresh; ?>;
        const timerDisplay = document.getElementById('market-timer');

        const countdown = setInterval(function() {
            if (timeLeft <= 0) {
                clearInterval(countdown);
                timerDisplay.innerHTML = "Market Prices Update In: <span style='color: #00fa9a;'>REFRESHING...</span>";
                location.reload(); 
            } else {
                let m = Math.floor(timeLeft / 60);
                let s = timeLeft % 60;

                if (m < 10) m = "0" + m;
                if (s < 10) s = "0" + s;

                timerDisplay.innerHTML = "Market Prices Update In: " + m + ":" + s;
                timeLeft--;
            }
        }, 1000); 
    </script>

</body>
</html>
</html>