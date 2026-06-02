<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

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

$currentUser = &$usersData[$userIndex];
$maxDepthLimit = isset($settings['maxDepth']) ? $settings['maxDepth'] : 1000; // Increased to allow deep mining
$fuelCost = isset($settings['fuelCostPerClick']) ? $settings['fuelCostPerClick'] : 1;
$maxCargo = isset($settings['maxCargo']) ? $settings['maxCargo'] : 15; 
$message = "";

// Ensure new progression structures & new ores exist safely
if (!isset($currentUser['activeRun']['cargo'])) $currentUser['activeRun']['cargo'] = [];
if (!isset($currentUser['bankedVault'])) $currentUser['bankedVault'] = [];
if (!isset($currentUser['gear']['drill'])) $currentUser['gear']['drill'] = 1; 

// Patch in all ores for older profiles to prevent crashes
$oresList = ['copper', 'iron', 'gold', 'titanium', 'uranium'];
foreach ($oresList as $ore) {
    if (!isset($currentUser['activeRun']['cargo'][$ore])) $currentUser['activeRun']['cargo'][$ore] = 0;
    if (!isset($currentUser['bankedVault'][$ore])) $currentUser['bankedVault'][$ore] = 0;
}

if (!isset($currentUser['activeRun']['x'])) $currentUser['activeRun']['x'] = 0; 
if (!isset($currentUser['activeRun']['map'])) $currentUser['activeRun']['map'] = []; 

// Block Tiers: Defines what Drill Tier is required
$blockTiers = [
    'dirt' => 1,
    'copper' => 1,
    'iron' => 2,
    'gold' => 3,
    'titanium' => 4,
    'uranium' => 5
];

// Procedural Map Engine (Expanded Depths!)
function getBlock(&$map, $x, $y, $maxDepth) {
    $key = "$x,$y";
    if ($y < 0) return 'sky';
    if ($y == 0) return 'surface';
    if ($y > $maxDepth) return 'bedrock';

    if (isset($map[$key])) return $map[$key];

    $rand = rand(1, 100);
    if ($rand <= 2) {
        $type = ($x == 0 && $y == 1) ? 'dirt' : 'bedrock'; 
    } else {
        // ZONE 1: Shallow Crust (0m - 20m)
        if ($y < 20) {
            if ($rand <= 20) $type = 'copper';
            else $type = 'dirt';
        } 
        // ZONE 2: The Iron Depths (20m - 50m)
        elseif ($y < 50) {
            if ($rand <= 5) $type = 'copper'; 
            elseif ($rand <= 20) $type = 'iron'; 
            else $type = 'dirt';
        } 
        // ZONE 3: Mid-Mantle (50m - 100m)
        elseif ($y < 100) {
            if ($rand <= 2) $type = 'copper'; 
            elseif ($rand <= 10) $type = 'iron'; 
            elseif ($rand <= 20) $type = 'gold'; 
            else $type = 'dirt';
        }
        // ZONE 4: Deep Core (100m+)
        else {
            if ($rand <= 1) $type = 'copper'; // Nearly gone
            elseif ($rand <= 3) $type = 'iron'; // Rare
            elseif ($rand <= 12) $type = 'gold'; // Common
            elseif ($rand <= 22) $type = 'titanium'; // NEW: Titanium appears
            elseif ($rand <= 25 && $y >= 150) $type = 'uranium'; // NEW: Uranium appears below 150m
            else $type = 'dirt';
        }
    }

    $map[$key] = $type;
    return $type;
}

$currentCargo = array_sum($currentUser['activeRun']['cargo']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'bank') {
        if ($currentUser['activeRun']['y'] == 0) {
            foreach ($oresList as $ore) {
                $currentUser['bankedVault'][$ore] += $currentUser['activeRun']['cargo'][$ore];
                $currentUser['activeRun']['cargo'][$ore] = 0;
            }
            $currentUser['activeRun']['fuel'] = 100; 
            $currentUser['activeRun']['map'] = [];
            $currentUser['activeRun']['x'] = 0; 
            $currentCargo = 0;

            $message = "SYSTEM: Cargo secured. Grid reset for a new expedition.";
        }
    } 
    elseif ($action === 'recall') {
        $taxLog = [];
        foreach ($oresList as $ore) {
            $tax = ceil($currentUser['activeRun']['cargo'][$ore] / 2);
            $currentUser['activeRun']['cargo'][$ore] -= $tax;
            if ($tax > 0) $taxLog[] = "$tax " . ucfirst($ore);
        }

        $currentUser['activeRun']['y'] = 0;
        $currentUser['activeRun']['x'] = 0;
        $currentUser['activeRun']['fuel'] = 100;
        $currentCargo = array_sum($currentUser['activeRun']['cargo']);

        $taxString = empty($taxLog) ? "nothing" : implode(", ", $taxLog);
        $message = "DEVIL'S PACT: You were teleported to the surface. Taxed: $taxString.";
    } 
    else {
        $targetX = $currentUser['activeRun']['x'];
        $targetY = $currentUser['activeRun']['y'];

        if ($action === 'up') $targetY -= 1;
        if ($action === 'down') $targetY += 1;
        if ($action === 'left') $targetX -= 1;
        if ($action === 'right') $targetX += 1;

        if ($currentUser['activeRun']['fuel'] < $fuelCost) {
            $message = "ALERT: Fuel exhausted. Core thrusters offline.";
        } else {
            $targetBlock = getBlock($currentUser['activeRun']['map'], $targetX, $targetY, $maxDepthLimit);
            $reqDrillTier = isset($blockTiers[$targetBlock]) ? $blockTiers[$targetBlock] : 1;

            if ($targetBlock === 'bedrock') {
                $message = "WARNING: Indestructible bedrock blocking the path!";
            } elseif ($targetBlock === 'sky') {
                $message = "WARNING: Cannot ascend above the landing gates.";
            } elseif ($currentUser['gear']['drill'] < $reqDrillTier) {
                $message = "ALERT: Your Tier {$currentUser['gear']['drill']} drill cannot penetrate $targetBlock (Requires Tier $reqDrillTier)!";
            } elseif (in_array($targetBlock, $oresList) && $currentCargo >= $maxCargo) {
                $message = "ALERT: Cargo hold is full ($maxCargo/$maxCargo)! Return to the surface to bank.";
            } else {
                $currentUser['activeRun']['fuel'] -= $fuelCost;
                $currentUser['activeRun']['x'] = $targetX;
                $currentUser['activeRun']['y'] = $targetY;

                if ($targetY > $currentUser['maxDepth']) $currentUser['maxDepth'] = $targetY;

                if (in_array($targetBlock, array_merge(['dirt'], $oresList))) {
                    $currentUser['activeRun']['map']["$targetX,$targetY"] = 'empty';

                    if (in_array($targetBlock, $oresList)) {
                        $currentUser['activeRun']['cargo'][$targetBlock] += 1;
                        $currentUser['lifetimeOres'] += 1;
                        $currentCargo += 1;
                        $message = "MINED: Extracted 1 " . ucfirst($targetBlock) . "!";
                    } else {
                        $message = "Drilling through the crust...";
                    }
                } else {
                    $message = "Navigating through empty shaft.";
                }
            }
        }
    }
}

$camX = $currentUser['activeRun']['x'];
$camY = $currentUser['activeRun']['y'];

for ($r = $camY - 3; $r <= $camY + 3; $r++) {
    for ($c = $camX - 3; $c <= $camX + 3; $c++) {
        getBlock($currentUser['activeRun']['map'], $c, $r, $maxDepthLimit);
    }
}

file_put_contents('users.json', json_encode($usersData, JSON_PRETTY_PRINT));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>The Mines - Underworld Grid</title>
    <link rel="stylesheet" href="style.css">
    <style>
        html, body {
            background-image: none !important; 
            background-color: #0a0a0a !important; 
            color: #fff;
            font-family: Arial, sans-serif;
        }
        .hud {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
            background: #222;
            padding: 10px;
            max-width: 800px;
            margin: 20px auto;
            border-radius: 5px;
            border-bottom: 3px solid #ff4d4d;
        }
        .hud div { font-size: 1.1rem; font-weight: bold; }
        .hud-cargo-full { color: #ff4d4d !important; animation: blink 1s infinite; }
        @keyframes blink { 50% { opacity: 0.5; } }

        .viewport {
            display: grid;
            grid-template-columns: repeat(7, 45px);
            grid-template-rows: repeat(7, 45px);
            gap: 2px;
            background-color: #000;
            width: max-content;
            margin: 20px auto;
            border: 4px solid #444;
            border-radius: 8px;
            padding: 5px;
        }

        .block {
            width: 45px; height: 45px;
            border-radius: 3px;
            display: flex; justify-content: center; align-items: center;
            font-size: 24px;
        }
        .sky { background-color: #1a1a2e; }
        .surface { background-color: #333; border-top: 3px solid #ffd700; }
        .dirt { background-color: #3e2723; }
        .empty { background-color: #111; }

        /* Ore Colors */
        .copper { background-color: #b87333; }
        .iron { background-color: #d3d3d3; }
        .gold { background-color: #ffd700; }
        .titanium { background-color: #a5c8d0; }
        .uranium { background-color: #39ff14; box-shadow: inset 0 0 10px #000; }

        .bedrock { background-color: #1a1a1a; border: 1px solid #000; }
        .player-rig { background-color: #ff4d4d; border-radius: 50%; width: 35px; height: 35px; box-shadow: 0 0 10px #ff4d4d;}

        .d-pad {
            display: grid;
            grid-template-columns: 65px 65px 65px;
            grid-template-rows: 65px 65px;
            gap: 5px;
            justify-content: center;
            margin-top: 15px;
        }
        .pad-up { grid-column: 2; grid-row: 1; }
        .pad-left { grid-column: 1; grid-row: 2; }
        .pad-down { grid-column: 2; grid-row: 2; }
        .pad-right { grid-column: 3; grid-row: 2; }

        .btn-move {
            width: 100%; height: 100%;
            background-color: #444; color: white;
            border: none; border-radius: 8px;
            font-size: 1.5rem; cursor: pointer;
            font-weight: bold;
        }
        .btn-move:hover { background-color: #666; }
        .btn-move:disabled { background-color: #222; color: #555; cursor: not-allowed; }

        .btn-bank, .btn-recall {
            display: block; margin: 20px auto;
            padding: 15px; width: 300px;
            font-weight: bold; border: none; cursor: pointer; border-radius: 5px;
        }
        .btn-bank { background-color: #ffd700; color: #000; }
        .btn-recall { background-color: #8b0000; color: #fff; border: 2px solid #ff4d4d; }

        .log-box { text-align: center; color: #ffd700; font-weight: bold; margin-top: 10px; }
    </style>
</head>
<body>

    <?php include_once 'nav.php'; ?>

    <div class="hud">
        <div style="color: #ff4d4d;">Depth: <?php echo $camY; ?>m</div>
        <div style="color: #1e90ff;">Fuel: <?php echo $currentUser['activeRun']['fuel']; ?></div>

        <div class="<?php echo ($currentCargo >= $maxCargo) ? 'hud-cargo-full' : ''; ?>" style="color: #ffd700;">
            Cargo: <?php echo $currentCargo; ?>/<?php echo $maxCargo; ?>
        </div>

        <div style="color: #b87333;">Copper: <?php echo $currentUser['activeRun']['cargo']['copper']; ?></div>
        <div style="color: #d3d3d3;">Iron: <?php echo $currentUser['activeRun']['cargo']['iron']; ?></div>
        <div style="color: #ffaa00;">Gold: <?php echo $currentUser['activeRun']['cargo']['gold']; ?></div>
        <div style="color: #a5c8d0;">Titanium: <?php echo $currentUser['activeRun']['cargo']['titanium']; ?></div>
        <div style="color: #39ff14;">Uranium: <?php echo $currentUser['activeRun']['cargo']['uranium']; ?></div>

        <div style="width: 100%; text-align: center; color: #00fa9a; border-top: 1px solid #444; padding-top: 5px;">
            Drill Tier: <?php echo $currentUser['gear']['drill']; ?>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="log-box"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="viewport">
        <?php 
        for ($r = $camY - 3; $r <= $camY + 3; $r++) {
            for ($c = $camX - 3; $c <= $camX + 3; $c++) {
                if ($r == $camY && $c == $camX) {
                    echo "<div class='block empty'><div class='player-rig'></div></div>";
                } else {
                    $type = getBlock($currentUser['activeRun']['map'], $c, $r, $maxDepthLimit);
                    $icon = '';
                    if ($type === 'copper') $icon = '🟧';
                    if ($type === 'iron') $icon = '⬜';
                    if ($type === 'gold') $icon = '🟨';
                    if ($type === 'titanium') $icon = '💠';
                    if ($type === 'uranium') $icon = '🟩';
                    if ($type === 'bedrock') $icon = '⬛';

                    echo "<div class='block $type'>$icon</div>";
                }
            }
        }
        ?>
    </div>

    <div class="d-pad">
        <div class="pad-up">
            <form action="game.php" method="POST">
                <input type="hidden" name="action" value="up">
                <button class="btn-move" <?php echo ($currentUser['activeRun']['fuel'] <= 0) ? 'disabled' : ''; ?>>▲</button>
            </form>
        </div>
        <div class="pad-left">
            <form action="game.php" method="POST">
                <input type="hidden" name="action" value="left">
                <button class="btn-move" <?php echo ($currentUser['activeRun']['fuel'] <= 0) ? 'disabled' : ''; ?>>◀</button>
            </form>
        </div>
        <div class="pad-down">
            <form action="game.php" method="POST">
                <input type="hidden" name="action" value="down">
                <button class="btn-move" <?php echo ($currentUser['activeRun']['fuel'] <= 0) ? 'disabled' : ''; ?>>▼</button>
            </form>
        </div>
        <div class="pad-right">
            <form action="game.php" method="POST">
                <input type="hidden" name="action" value="right">
                <button class="btn-move" <?php echo ($currentUser['activeRun']['fuel'] <= 0) ? 'disabled' : ''; ?>>▶</button>
            </form>
        </div>
    </div>

    <?php if ($camY == 0): ?>
        <form action="game.php" method="POST">
            <input type="hidden" name="action" value="bank">
            <button type="submit" class="btn-bank">BANK CARGO & RESET GRID</button>
        </form>
    <?php endif; ?>

    <?php if ($currentUser['activeRun']['fuel'] <= 0): ?>
        <form action="game.php" method="POST">
            <input type="hidden" name="action" value="recall">
            <button type="submit" class="btn-recall">INVOKE PACT: EMERGENCY RECALL (50% TAX)</button>
        </form>
    <?php endif; ?>

</body>
</html>