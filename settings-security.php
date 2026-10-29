<?php
session_start();
include "db.php";

if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$message = "";

function saveSetting($conn,$key,$value){
    $stmt = $conn->prepare("
        INSERT INTO system_settings (setting_key, setting_value)
        VALUES (?,?)
        ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)
    ");
    $stmt->bind_param("ss",$key,$value);
    $stmt->execute();
}

if(isset($_POST['save'])){
    saveSetting($conn,'default_role',$_POST['default_role']);
    saveSetting($conn,'auto_logout',$_POST['auto_logout']);
    saveSetting($conn,'lock_account',$_POST['lock_account']);
    $message = "Security rules updated successfully.";
}

$settings = [];
$res = $conn->query("SELECT * FROM system_settings");
while($row = $res->fetch_assoc()){
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html>
<head>
<title>User & Security Rules | Retail System</title>
<link rel="stylesheet" href="dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* MAIN CONTENT */
.main-content { padding: 30px; }

/* BREADCRUMB */
.breadcrumb { font-size: 14px; margin-bottom: 20px; }
.breadcrumb a { color: #ff7a18; text-decoration: none; margin-right:5px; }
.breadcrumb span { color: #555; margin-right:5px; }

/* CARD */
.settings-card {
    background: #fff;
    border-radius: 12px;
    padding: 30px 35px;
    max-width: 850px;
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
.field-group input,
.field-group select {
    width: 100%;
    padding: 12px 14px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 15px;
    transition: all 0.3s;
}
.field-group input:focus,
.field-group select:focus {
    border-color: #ff7a18;
    outline: none;
    box-shadow: 0 0 8px rgba(255,122,24,0.2);
}
.field-group small { display:block; font-size:13px; color:#777; margin-top:5px; }

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
        <span>User & Security Rules</span>
    </div>

    <?php if($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <form method="post" class="settings-card">
        <h2><i class="fa-solid fa-shield-halved"></i> User & Security Rules</h2>
        <p class="subtitle">Manage default roles, login security, and auto logout policies consistently with system style.</p>

        <div class="field-group">
            <label>Default Role for New Employees</label>
            <select name="default_role">
                <option value="cashier" <?= ($settings['default_role']??'cashier')=='cashier'?'selected':'' ?>>Cashier</option>
                <option value="manager" <?= ($settings['default_role']??'cashier')=='manager'?'selected':'' ?>>Manager</option>
            </select>
            <small>Automatically assigned to new employees.</small>
        </div>

        <div class="field-group">
            <label>Auto Logout (minutes)</label>
            <input type="number" name="auto_logout" value="<?= $settings['auto_logout'] ?? 30 ?>">
            <small>Logs users out after inactivity for security.</small>
        </div>

        <div class="field-group">
            <label>Lock Account After Failed Login Attempts</label>
            <select name="lock_account">
                <option value="yes" <?= ($settings['lock_account']??'yes')=='yes'?'selected':'' ?>>Yes</option>
                <option value="no" <?= ($settings['lock_account']??'yes')=='no'?'selected':'' ?>>No</option>
            </select>
            <small>Temporarily locks accounts after multiple failed logins.</small>
        </div>

        <div class="settings-actions">
            <a href="settings.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Cancel</a>
            <button name="save" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
        </div>
    </form>
</div>

</body>
</html>
