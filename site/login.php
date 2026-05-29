<?php
session_start();

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $usersData = json_decode(file_get_contents('users.json'), true);
        if (!is_array($usersData)) { $usersData = []; }

        $authenticated = false;
        foreach ($usersData as $user) {
            if (strtolower($user['username']) === strtolower($username) && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['isAdmin'] = $user['isAdmin'];
                
                $authenticated = true;
                break;
            }
        }

        if ($authenticated) {
            header("Location: index.php");
            exit;
        } else {
            $message = "ERROR: Invalid callsign or passkey mismatch.";
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
    <title>The Mines - Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php include_once 'nav.php'; ?>

    <main style="padding: 20px; max-width: 400px; margin: 40px auto; font-family: sans-serif; border: 1px solid #ccc; border-radius: 8px;">
        <h2>Pilot Sign-In</h2>
        <p>Establish a secure link to your active profile.</p>

        <?php if (!empty($message)): ?>
            <p style="font-weight: bold; color: #ff4d4d; background: #333; padding: 10px; border-radius: 4px;"><?php echo $message; ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
            <label>
                <strong>Pilot Callsign:</strong><br>
                <input type="text" name="username" required style="width: 100%; padding: 8px; margin-top: 5px;">
            </label>
            <label>
                <strong>Security Passkey:</strong><br>
                <input type="password" name="password" required style="width: 100%; padding: 8px; margin-top: 5px;">
            </label>
            <button type="submit" style="padding: 10px; background-color: #ffd700; border: none; font-weight: bold; cursor: pointer;">Establish Connection</button>
        </form>
    </main>

</body>
</html>