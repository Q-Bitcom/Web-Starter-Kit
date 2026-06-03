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

$currentUser = $usersData[$userIndex];

// --- GITHUB LIVE SYNC CONFIGURATION ---
$repoUser = "Q-Bitcom";
$repoName = "Web-Starter-Kit";
$apiUrl = "https://api.github.com/repos/$repoUser/$repoName/commits";

// GitHub API requires a User-Agent header to allow requests
$options = [
    'http' => [
        'method' => "GET",
        'header' => "User-Agent: TheMinesGameEngine/1.0\r\n"
    ]
];
$context = stream_context_create($options);

// Fetch data while suppressing standard PHP warning messages via @
$response = @file_get_contents($apiUrl, false, $context);
$commits = json_decode($response, true);

// Verify if data was returned successfully
$apiSuccess = is_array($commits) && !isset($commits['message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>The Mines - Project Checkpoints</title>
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
            padding: 30px;
            border-radius: 8px;
            border: 2px solid #444;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #ffaa00;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .links-container {
            text-align: center;
            margin-bottom: 40px;
        }
        .hub-btn {
            display: inline-block;
            min-width: 250px;
            text-align: center;
            padding: 15px 30px;
            font-weight: bold;
            font-size: 1.1rem;
            text-decoration: none;
            border-radius: 5px;
            transition: 0.2s;
            background-color: #24292e;
            color: #fff;
            border: 1px solid #444;
        }
        .hub-btn:hover { 
            background-color: #333; 
            border-color: #666;
        }

        /* Timeline Styling */
        .timeline {
            position: relative;
            border-left: 3px solid #ffaa00;
            padding-left: 20px;
            margin-left: 10px;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 35px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -27px;
            top: 5px;
            background: #0a0a0a;
            border: 3px solid #ffaa00;
            border-radius: 50%;
            width: 12px;
            height: 12px;
        }
        .timeline-title {
            font-size: 1.2rem;
            color: #00fa9a;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .timeline-date {
            font-size: 0.85rem;
            color: #888;
            margin-bottom: 8px;
        }
        .timeline-desc {
            color: #ccc;
            font-size: 0.95rem;
            line-height: 1.4;
            background: #161616;
            padding: 10px;
            border-radius: 4px;
            border-left: 2px solid #555;
        }
        .error-box {
            background: #2b1111;
            border: 1px solid #ff4d4d;
            padding: 20px;
            border-radius: 5px;
            color: #ffcccc;
            line-height: 1.5;
        }
    </style>
</head>
<body>

    <?php include_once 'nav.php'; ?>

    <div class="container">
        <div class="header">
            <h1>🚀 AUTOMATED DEVELOPMENT HUB</h1>
            <p>Direct tracking synced in real-time with live repository revisions.</p>
        </div>

        <div class="links-container">
            <a href="https://github.com/Q-Bitcom/Web-Starter-Kit" target="_blank" class="hub-btn">
                🐙 Open GitHub Repository
            </a>
        </div>

        <h2>Project Milestones (<?php echo $apiSuccess ? count($commits) : '0'; ?> Total)</h2>
        <hr style="border: 1px solid #333; margin-bottom: 20px;">

        <div class="timeline">
            <?php if ($apiSuccess): ?>
                <?php 
                $totalCommits = count($commits);
                foreach ($commits as $index => $c): 
                    $rawMessage = $c['commit']['message'];

                    // Separate the first line of the commit message from any extended descriptions
                    $messageLines = explode("\n", $rawMessage);
                    $title = htmlspecialchars($messageLines[0]);

                    $dateStr = $c['commit']['author']['date'];
                    $date = new DateTime($dateStr);
                    $formattedDate = $date->format('M d, Y @ h:i A');

                    $shortSha = substr($c['sha'], 0, 7);
                    $commitUrl = $c['html_url'];

                    // Counts downward so oldest commit is Checkpoint 1
                    $checkpointNum = $totalCommits - $index; 
                ?>
                    <div class="timeline-item">
                        <div class="timeline-title">Checkpoint <?php echo $checkpointNum; ?>: <?php echo $title; ?></div>
                        <div class="timeline-date">
                            💾 <?php echo $formattedDate; ?> | 
                            ID: <a href="<?php echo $commitUrl; ?>" target="_blank" style="color: #ffaa00; text-decoration: underline;">#<?php echo $shortSha; ?></a>
                        </div>

                        <?php if (count($messageLines) > 1): ?>
                            <?php 
                                $details = trim(implode("\n", array_slice($messageLines, 1)));
                                if (!empty($details)): 
                            ?>
                                <div class="timeline-desc">
                                    <?php echo nl2br(htmlspecialchars($details)); ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="error-box">
                    <strong>⚠️ Unable to stream automated checkpoints.</strong><br>
                    This occurs if your GitHub repository <strong>Web-Starter-Kit</strong> is currently set to <em>Private</em>, or if the public API connection request timed out.<br><br>
                    <span style="font-size: 0.9rem; color: #aaa;">To fix this: Ensure the repository visibility is changed to <strong>Public</strong> in your GitHub settings layout.</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>