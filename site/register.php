<?php
session_start();

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $usersData = json_decode(file_get_contents('users.json'), true);
        if (!is_array($usersData)) { $usersData = []; }

        
        $userExists = false;
        foreach ($usersData as $user) {
            if (strtolower($user['username']) === strtolower($username)) {
                $userExists = true;
                break;
            }
        }

        if ($userExists) {
            $message = "ERROR: Callsign already taken by another pilot.";
        } else {
            $newUser = [
                "id" => uniqid(),
                "username" => $username,
                "password" => password_hash($password, PASSWORD_DEFAULT),
                "isAdmin" => false,
                "bankedBalance" => 0,
                "maxDepth" => 0,
                "lifetimeOres" => 0,
                "activeRun" => [
                    "y" => 0,
                    "fuel" => 100,
                    "cargo" => ["copper" => 0, "iron" => 0]
                ],
                "bankedVault" => ["copper" => 0, "iron" => 0],
                "equippedDrill" => "Basic Drill"
            ];

            $usersData[] = $newUser;
            file_put_contents('users.json', json_encode($usersData, JSON_PRETTY_PRINT));
            
            $message = "SUCCESS: Registration complete. You can now log in.";
        }
    } else {
        $message = "WARNING: Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>The Mines - Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php include_once 'nav.php'; ?>

    <main style="padding: 20px; max-width: 400px; margin: 40px auto; font-family: sans-serif; border: 1px solid #ccc; border-radius: 8px;">
        <h2>Pilot Registration</h2>
        <p>Register your credentials to claim an operational mining rig.</p>
        
        <?php if (!empty($message)): ?>
            <p style="font-weight: bold; color: #ffd700; background: #333; padding: 10px; border-radius: 4px;"><?php echo $message; ?></p>
        <?php endif; ?>

        <form action="register.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
            <label>
                <strong>Pilot Callsign:</strong><br>
                <input type="text" name="username" required style="width: 100%; padding: 8px; margin-top: 5px;">
            </label>
            <label>
                <strong>Security Passkey:</strong><br>
                <input type="password" name="password" required style="width: 100%; padding: 8px; margin-top: 5px;">
            </label>
            <button type="submit" style="padding: 10px; background-color: #ffd700; border: none; font-weight: bold; cursor: pointer;">Initialize Account</button>
        </form>
    </main>

</body>
</html>