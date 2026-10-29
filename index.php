<?php 
session_start();
include "db.php";

$error = "";

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username=? AND status='active'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['fullname'];

            if ($user['role'] === 'admin') header("Location: admin.php");
            elseif ($user['role'] === 'manager') header("Location: manager.php");
            else header("Location: cashier.php");
            exit;
        } else {
            $error = "Incorrect password";
        }
    } else {
        $error = "User not found or inactive";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Login | Simba Supermarket System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', sans-serif;
}

body {
    height: 100vh;
    background: linear-gradient(135deg, #ff7a18, #ffb347);
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
    position: relative;
}

/* 🔥 SOFT GLOW BACKGROUND */
body::before, body::after {
    content: "";
    position: absolute;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(255,255,255,0.2), transparent);
    filter: blur(90px);
    animation: floatGlow 10s infinite alternate ease-in-out;
}

body::before {
    top: -100px;
    left: -100px;
}

body::after {
    bottom: -100px;
    right: -100px;
    animation-delay: 3s;
}

@keyframes floatGlow {
    from { transform: translate(0,0); }
    to { transform: translate(80px, 60px); }
}

/* MAIN CARD */
.container {
    width: 950px;
    height: 540px;
    display: flex;
    border-radius: 25px;
    overflow: hidden;
    background: rgba(255,255,255,0.96);
    backdrop-filter: blur(12px);
    box-shadow: 
        0 30px 80px rgba(0,0,0,0.25),
        inset 0 1px 0 rgba(255,255,255,0.6);
    animation: fadeIn 0.8s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(25px); }
    to { opacity: 1; transform: translateY(0); }
}

/* LEFT SIDE */
.left {
    flex: 1;
    background: linear-gradient(135deg, #ff7a18, #ff9500);
    color: white;
    padding: 60px 50px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    clip-path: ellipse(85% 100% at 0% 50%);
    position: relative;
}

/* ✨ subtle shine */
.left::after {
    content: "";
    position: absolute;
    width: 200%;
    height: 100%;
    top: 0;
    left: -100%;
    background: linear-gradient(
        120deg,
        transparent,
        rgba(255,255,255,0.2),
        transparent
    );
    animation: shine 6s infinite;
}

@keyframes shine {
    to { left: 100%; }
}

.left h1 {
    font-size: 42px;
    margin-bottom: 10px;
    letter-spacing: 1px;
    text-shadow: 0 4px 10px rgba(0,0,0,0.2);
}

.left p {
    opacity: 0.9;
}

.left i {
    opacity: 0.85;
}

/* RIGHT SIDE */
.right {
    flex: 1;
    padding: 50px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

/* LOGO */
.logo-right img {
    width: 115px;
    filter: drop-shadow(0 8px 18px rgba(0,0,0,0.25));
    transition: 0.3s;
}

.logo-right img:hover {
    transform: scale(1.05);
}

/* TITLE */
.right h2 {
    font-size: 30px;
    text-align: center;
    background: linear-gradient(to right, #ff7a18, #ff9500);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.right p {
    color: #777;
    margin-bottom: 20px;
    text-align: center;
}

/* ERROR */
.error {
    background: #fff3f2;
    color: #d9534f;
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* INPUTS */
.input-group {
    position: relative;
    margin-bottom: 15px;
}

.input-group input {
    width: 100%;
    padding: 14px 45px;
    border-radius: 12px;
    border: none;
    background: #f4f4f4;
    transition: all 0.3s ease;
}

.input-group input:hover {
    background: #eeeeee;
}

.input-group input:focus {
    background: #fff;
    box-shadow: 
        0 0 0 2px rgba(255,122,24,0.3),
        0 10px 25px rgba(0,0,0,0.1);
}

.input-group i {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    transition: 0.3s;
}

.input-group input:focus ~ i {
    color: #ff7a18;
}

/* BUTTON */
button {
    width: 100%;
    padding: 14px;
    background: linear-gradient(to right, #ff7a18, #ffb347);
    border: none;
    border-radius: 12px;
    color: white;
    font-size: 16px;
    cursor: pointer;
    margin-top: 10px;
    transition: all 0.3s ease;
}

button:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 12px 30px rgba(0,0,0,0.25);
}

/* FOOTER */
.forgot {
    margin-top: 15px;
    font-size: 13px;
    text-align: center;
}

/* RESPONSIVE */
@media(max-width: 768px){
    .container {
        flex-direction: column;
        height: auto;
    }

    .left {
        clip-path: none;
        text-align: center;
    }
}
</style>
</head>

<body>

<div class="container">

<!-- LEFT -->
<div class="left">
    <h1>WELCOME BACK</h1>
    <p>RETAIL & INVENTORY MANAGEMENT SYSTEM</p><br>
    <i>Manage your supermarket operations efficiently<br>
     and securely</i>
</div>

<!-- RIGHT -->
<div class="right">

    <div class="logo-right">
        <img src="images/simba logo2.png" alt="Simba">
    </div>

    <h2>SIMBA SUPERMARKET</h2>
    <br>
    <p>Enter your username and password</p>

    <?php if ($error): ?>
        <div class="error">
            <i class="fa fa-exclamation-triangle"></i>
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="input-group">
            <i class="fa fa-user icon-left"></i>
            <input type="text" name="username" placeholder="Username" required autofocus>
        </div>

        <div class="input-group">
            <i class="fa fa-lock icon-left"></i>
            <input type="password" name="password" id="password" placeholder="Password" required>
            <i class="fa fa-eye toggle" id="togglePassword"></i>
        </div>

        <button type="submit" name="login">Login</button>

    </form>

    <p class="forgot">Forgot password? Contact system administrator</p>

</div>

</div>

<script>
const toggle = document.getElementById("togglePassword");
const password = document.getElementById("password");

toggle.addEventListener("click", () => {
    password.type = password.type === "password" ? "text" : "password";
    toggle.classList.toggle("fa-eye-slash");
});
</script>

</body>
</html>