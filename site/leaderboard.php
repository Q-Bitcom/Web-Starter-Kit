<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$usersData = json_decode(file_get_contents('users.json'), true);

// Determine which leaderboard view to display (default to 'depth')
$view = isset($_GET['view']) && $_GET['view'] === 'ores' ? 'ores' : 'depth';

// Ensure every user has a stats tracking array before sorting
foreach ($usersData as $index => &$user) {
    if (!isset($user['stats'])) {
        $user['stats'] = [
            'maxDepth' => 0,
            // Seed their starting score with whatever is currently in their vault
            'totalMined' => isset($user['bankedVault']) ? array_sum($user['bankedVault']) : 0 
        ];
    }
}
unset($user); // Break reference pointer

// Sort users based on the selected view in descending order
usort($usersData, function($a, $b) use ($view) {
    if ($view === 'ores') {
        $scoreA = $a['stats']['totalMined'];
        $scoreB = $b['stats']['totalMined'];
    } else {
        $scoreA = $a['stats']['maxDepth'];
        $scoreB = $b['stats']['maxDepth'];
    }
    return $scoreB <=> $scoreA; // Sorts highest to lowest
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>The Mines - Galactic Leaderboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        html, body {
            background-color: #0a0a0a !important; 
            color: #fff;
            font-family: Arial, sans-serif;
            background-image: none !important;
        }
        .container {
            max-width: 700px;
            margin: 40px auto;
            background: #111;
            padding: 30px;
            border-radius: 8px;
            border: 2px solid #444;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #00fa9a;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .toggle-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
        }
        .toggle-btn {
            padding: 12px 24px;
            font-weight: bold;
            font-size: 1rem;
            text-decoration: none;
            border-radius: 4px;
            border: 1px solid #444;
            transition: 0.2s;
        }
        .btn-inactive {
            background: #222;
            color: #aaa;
        }
        .btn-inactive:hover {
            background: #333;
            color: #fff;
        }
        .btn-active {
            background: #00fa9a;
            color: #000;
            border-color: #00fa9a;
        }
        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
        }
        .leaderboard-table th, .leaderboard-table td {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #222;
        }
        .leaderboard-table th {
            background: #161616;
            color: #888;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        .rank-col { font-weight: bold; font-size: 1.1rem; width: 15%; }
        .username-col { text-align: left !important; width: 55%; font-weight: bold; }
        .score-col { color: #ffaa00; font-weight: bold; font-size: 1.2rem; width: 30%; }

        /* Highlight podium positions */
        .rank-1 { color: #ffd700; } /* Gold */
        .rank-2 { color: #c0c0c0; } /* Silver */
        .rank-3 { color: #cd7f32; } /* Bronze */

        .current-user-row {
            background: #1a241f !important;
            border: 1px solid #00fa9a;
        }
    </style>
</head>
<body>

    <?php include_once 'nav.php'; ?>

    <div class="container">
        <div class="header">
            <h1>🏆 GALACTIC LEADERBOARDS</h1>
            <p>See how your planetary mining operation stacks up against the competition.</p>
        </div>

        <div class="toggle-container">
            <a href="leaderboard.php?view=depth" class="toggle-btn <?php echo $view === 'depth' ? 'btn-active' : 'btn-inactive'; ?>">
                Depth Records
            </a>
            <a href="leaderboard.php?view=ores" class="toggle-btn <?php echo $view === 'ores' ? 'btn-active' : 'btn-inactive'; ?>">
                Total Ores Mined
            </a>
        </div>

        <table class="leaderboard-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th style="text-align: left;">Miner</th>
                    <th><?php echo $view === 'ores' ? 'Total Ores Mined' : 'Max Depth Reached'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usersData as $index => $user): ?>
                    <?php 
                        $rank = $index + 1;
                        $isCurrentUser = ($user['id'] === $_SESSION['user_id']);

                        // Extract correct score view metric
                        if ($view === 'ores') {
                            $score = number_format($user['stats']['totalMined']) . " units";
                        } else {
                            $score = number_format($user['stats']['maxDepth']) . " meters";
                        }

                        // Special styling handles for top 3 positions
                        $rankClass = '';
                        if ($rank === 1) $rankClass = 'rank-1';
                        if ($rank === 2) $rankClass = 'rank-2';
                        if ($rank === 3) $rankClass = 'rank-3';
                    ?>
                    <tr class="<?php echo $isCurrentUser ? 'current-user-row' : ''; ?>">
                        <td class="rank-col <?php echo $rankClass; ?>">
                            <?php 
                                if ($rank === 1) echo "🥇";
                                elseif ($rank === 2) echo "🥈";
                                elseif ($rank === 3) echo "🥉";
                                else echo $rank;
                            ?>
                        </td>
                        <td class="username-col">
                            <?php echo htmlspecialchars($user['username']); ?>
                            <?php if ($isCurrentUser): ?>
                                <span style="color: #00fa9a; font-size: 0.8rem; margin-left: 5px;">(You)</span>
                            <?php endif; ?>
                        </td>
                        <td class="score-col"><?php echo $score; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</body>
</html>