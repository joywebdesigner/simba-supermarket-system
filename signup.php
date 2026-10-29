<?php
include "db.php";

$message = "";

if (isset($_POST['signup'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role     = $_POST['role'];

    // Hash password ONCE
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Check if username already exists
    $check = $conn->prepare("SELECT id FROM users WHERE username=?");
    $check->bind_param("s", $username);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $message = "Username already exists";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO users (username, password, role) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("sss", $username, $hashedPassword, $role);

        if ($stmt->execute()) {
            $message = "User registered successfully";
        } else {
            $message = "Error registering user";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sign Up | Retail System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <h2>Create User</h2>
        <p class="subtitle">Temporary Registration Page</p>

        <?php if ($message): ?>
            <div class="error"><?= $message ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>

            <select name="role" required style="width:100%; padding:13px; margin-bottom:15px; border-radius:8px;">
                <option value="">Select Role</option>
                <option value="admin">Admin</option>
                <option value="manager">Manager</option>
                <option value="cashier">Cashier</option>
            </select>

            <button type="submit" name="signup">Create User</button>
        </form>

        <p class="forgot">
            <span>This page will be removed later</span>
        </p>
    </div>
</div>

</body>
</html>
