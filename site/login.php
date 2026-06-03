<?php
// Force environment errors to surface if anything goes wrong
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Redirect to interface engine if session validation token is already active
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : 'login';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if ($username !== '' && $password !== '') {
        $usersData = json_decode(file_get_contents('users.json'), true);
        if (!is_array($usersData)) { $usersData = []; }

        if ($action === 'register') {
            // Check if callsign is already taken
            $exists = false;
            foreach ($usersData as $user) {
                if (isset($user['username']) && strtolower($user['username']) === strtolower($username)) {
                    $exists = true;
                    break;
                }
            }

            if ($exists) {
                $message = "ERROR: Callsign already registered to an active pilot.";
            } else {
                // Register a fresh default player schema matching checkpoint 12 expectations
                $newUser = [
                    'id' => uniqid('u_', true),
                    'username' => $username,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'isAdmin' => false,
                    'bankedBalance' => 0,
                    'maxDepth' => 0,
                    'lifetimeOres' => 0,
                    'activeRun' => [
                        'y' => 0, 'fuel' => 100, 'x' => 0,
                        'cargo' => ['copper' => 0, 'iron' => 0, 'gold' => 0, 'titanium' => 0, 'uranium' => 0],
                        'map' => (object)[]
                    ],
                    'bankedVault' => ['copper' => 0, 'iron' => 0, 'gold' => 0, 'titanium' => 0, 'uranium' => 0],
                    'equippedDrill' => "Basic Drill",
                    'gear' => ['drill' => 1, 'maxFuel' => 100, 'maxCargo' => 15],
                    'credits' => 0,
                    'marketSaturation' => ['copper' => 0, 'iron' => 0, 'gold' => 0, 'titanium' => 0, 'uranium' => 0],
                    'upgrades' => ['drill' => 1, 'cargo' => 1, 'fuel' => 1],
                    'stats' => ['maxDepth' => 0, 'totalMined' => 0]
                ];

                $usersData[] = $newUser;
                file_put_contents('users.json', json_encode($usersData, JSON_PRETTY_PRINT));
                $message = "SUCCESS: Clearance granted! You can now log in.";
            }
        } else {
            // Process Login Action
            $authenticated = false;
            foreach ($usersData as $user) {
                if (isset($user['username']) && strtolower($user['username']) === strtolower($username)) {
                    if (password_verify($password, $user['password'])) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['isAdmin'] = isset($user['isAdmin']) ? $user['isAdmin'] : false;

                        $authenticated = true;
                        break;
                    }
                }
            }

            if ($authenticated) {
                header("Location: index.php");
                exit;
            } else {
                $message = "ERROR: Invalid callsign credentials or passkey mismatch.";
            }
        }
    } else {
        $message = "WARNING: Please input complete system parameters.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>The Mines - Terminal Gateway</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="background-color: #ffffff; color: #000000; font-family: Arial, sans-serif; margin: 0; padding: 0;">

    <?php include_once 'nav.php'; ?>

    <main style="padding: 20px; max-width: 400px; margin: 40px auto; font-family: sans-serif; border: 1px solid #000000; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">

        <?php if (!empty($message)): ?>
            <p style="font-weight: bold; color: #ff4d4d; background: #fee; padding: 10px; border: 1px solid #ff4d4d; border-radius: 4px; text-align: center; margin-bottom: 20px;">
                <?php echo $message; ?>
            </p>
        <?php endif; ?>

        <div id="login-panel">
            <h2 style="color: #000000; margin-top: 0;">Pilot Sign-In</h2>
            <p style="color: #444444; font-size: 0.9rem; margin-bottom: 20px;">Establish a secure operational link to your profile.</p>

            <form action="login.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                <input type="hidden" name="action" value="login">
                <label>
                    <strong style="color: #000000;">Pilot Callsign:</strong><br>
                    <input type="text" name="username" required style="width: 100%; padding: 8px; margin-top: 5px; color: #000000; background: #ffffff; border: 1px solid #000000; box-sizing: border-box; border-radius: 4px;">
                </label>
                <label>
                    <strong style="color: #000000;">Security Passkey:</strong><br>
                    <input type="password" name="password" required style="width: 100%; padding: 8px; margin-top: 5px; color: #000000; background: #ffffff; border: 1px solid #000000; box-sizing: border-box; border-radius: 4px;">
                </label>
                <button type="submit" style="padding: 10px; background-color: #ffd700; border: 1px solid #000000; font-weight: bold; cursor: pointer; color: #000000; font-size: 1rem; margin-top: 5px; border-radius: 4px;">Establish Connection</button>
            </form>
        </div>

        <div id="register-panel" style="display: none;">
            <h2 style="color: #000000; margin-top: 0;">Pilot Sign-Up</h2>
            <p style="color: #444444; font-size: 0.9rem; margin-bottom: 20px;">Provision fresh grid indices for standard clearance.</p>

            <form action="login.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                <input type="hidden" name="action" value="register">
                <label>
                    <strong style="color: #000000;">Desired Callsign:</strong><br>
                    <input type="text" name="username" required style="width: 100%; padding: 8px; margin-top: 5px; color: #000000; background: #ffffff; border: 1px solid #000000; box-sizing: border-box; border-radius: 4px;">
                </label>
                <label>
                    <strong style="color: #000000;">Account Passkey:</strong><br>
                    <input type="password" name="password" required style="width: 100%; padding: 8px; margin-top: 5px; color: #000000; background: #ffffff; border: 1px solid #000000; box-sizing: border-box; border-radius: 4px;">
                </label>
                <button type="submit" style="padding: 10px; background-color: #ffd700; border: 1px solid #000000; font-weight: bold; cursor: pointer; color: #000000; font-size: 1rem; margin-top: 5px; border-radius: 4px;">Create Profile</button>
            </form>
        </div>

    </main>

    <script>
        // Global view toggling accessible by the nav bar elements
        function toggleView(view) {
            const loginBox = document.getElementById('login-panel');
            const registerBox = document.getElementById('register-panel');

            if (view === 'register') {
                loginBox.style.display = 'none';
                registerBox.style.display = 'block';
            } else {
                loginBox.style.display = 'block';
                registerBox.style.display = 'none';
            }
        }
    </script>

</body>
</html>