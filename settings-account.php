<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php"); 
    exit;
}

$message="";

if(isset($_POST['change'])){
    $stmt=$conn->prepare("SELECT password FROM users WHERE id=?");
    $stmt->bind_param("i",$_SESSION['user_id']);
    $stmt->execute();
    $p=$stmt->get_result()->fetch_assoc();

    if(password_verify($_POST['current'],$p['password'])){
        if($_POST['new']===$_POST['confirm']){
            $h=password_hash($_POST['new'],PASSWORD_DEFAULT);
            $u=$conn->prepare("UPDATE users SET password=? WHERE id=?");
            $u->bind_param("si",$h,$_SESSION['user_id']);
            $u->execute();
            $message="Password updated successfully.";
        } else {
            $message="New passwords do not match!";
        }
    } else {
        $message="Current password is incorrect!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>My Account | Retail System</title>
<link rel="stylesheet" href="dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.main-content { padding: 30px; }

/* CARD */
.settings-card {
    background: #fff;
    border-radius: 12px;
    padding: 30px 35px;
    max-width: 600px;
    margin: auto;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    transition: transform 0.3s;
}
.settings-card:hover { transform: translateY(-3px); }

.settings-card h2 {
    color: #ff7a18;
    font-size: 26px;
    margin-bottom: 8px;
}
.settings-card p.subtitle {
    color: #555;
    margin-bottom: 25px;
    font-size: 15px;
}

/* FORM FIELDS */
.field-group { margin-bottom: 20px; }
.field-group label {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 6px;
}
.field-group input {
    width: 100%;
    padding: 12px 14px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 15px;
    transition: all 0.3s;
}
.field-group input:focus {
    border-color: #ff7a18;
    outline: none;
    box-shadow: 0 0 8px rgba(255,122,24,0.2);
}

/* BUTTONS */
.settings-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 30px;
}
.btn-save {
    background: linear-gradient(135deg,#ff7a18,#ff9f1c);
    color: #fff;
    padding: 12px 28px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}
.btn-save:hover {
    background: linear-gradient(135deg,#ff9f1c,#ff7a18);
    transform: translateY(-2px);
}
.back-link {
    text-decoration: none;
    color: #ff7a18;
    font-weight: 600;
}

/* MESSAGE */
.message {
    background: #eaf7f0;
    color: #28a745;
    padding: 14px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
    font-weight: 600;
}
</style>
</head>
<body>

<?php include 'topbar.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main-content">

    <div class="breadcrumb">
        <a href="settings.php">Settings</a> <span>/</span>
        <span>My Account</span>
    </div>

    <?php if($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <form method="post" class="settings-card">
        <h2><i class="fa-solid fa-user-gear"></i> My Account</h2>
        <p class="subtitle">Update your password and keep your account secure.</p>

        <div class="field-group">
            <label>Current Password</label>
            <input type="password" name="current" placeholder="Enter your current password" required>
        </div>

        <div class="field-group">
            <label>New Password</label>
            <input type="password" name="new" placeholder="Enter your new password" required>
        </div>

        <div class="field-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm" placeholder="Confirm your new password" required>
        </div>

        <div class="settings-actions">
            <a href="settings.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Cancel</a>
            <button type="submit" name="change" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Update Password</button>
        </div>
    </form>
</div>

</body>
</html>
