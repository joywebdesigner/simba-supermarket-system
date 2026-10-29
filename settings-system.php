<?php
session_start();
include "db.php";

if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$message = "";

function saveSetting($conn,$k,$v){
    $stmt = $conn->prepare("
        INSERT INTO system_settings (setting_key, setting_value)
        VALUES (?,?)
        ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)
    ");
    $stmt->bind_param("ss",$k,$v);
    $stmt->execute();
}

if(isset($_POST['save'])){
    saveSetting($conn,'store_name',$_POST['store_name']);
    saveSetting($conn,'currency',$_POST['currency']);
    saveSetting($conn,'low_stock',$_POST['low_stock']);
    saveSetting($conn,'sales_tax',$_POST['sales_tax']);
    $message = "System configuration updated successfully.";
}

$settings = [];
$res = $conn->query("SELECT * FROM system_settings");
while($r = $res->fetch_assoc()){
    $settings[$r['setting_key']] = $r['setting_value'];
}
?>
<!DOCTYPE html>
<html>
<head>
<title>System Configuration | Retail System</title>
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
        <span>System Configuration</span>
    </div>

    <?php if($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <form method="post" class="settings-card">
        <h2><i class="fa-solid fa-store"></i> System Configuration</h2>
        <p class="subtitle">Set store name, currency, low stock alert, and sales tax. Fully consistent with system style.</p>

        <div class="field-group">
            <label>Store / Branch Name</label>
            <input name="store_name" value="<?= $settings['store_name'] ?? '' ?>" required>
            <small>Appears on invoices, reports, dashboards.</small>
        </div>

        <div class="field-group">
            <label>Currency</label>
            <input name="currency" value="<?= $settings['currency'] ?? 'RWF' ?>">
            <small>Used for all product prices (e.g., RWF, USD).</small>
        </div>

        <div class="field-group">
            <label>Low Stock Alert Quantity</label>
            <input type="number" name="low_stock" value="<?= $settings['low_stock'] ?? 10 ?>">
            <small>Warns you when product stock is low.</small>
        </div>

        <div class="field-group">
            <label>Sales Tax (%)</label>
            <input type="number" step="0.01" name="sales_tax" value="<?= $settings['sales_tax'] ?? 0 ?>">
            <small>Applied automatically to sales totals.</small>
        </div>

        <div class="settings-actions">
            <a href="settings.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Cancel</a>
            <button name="save" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
        </div>
    </form>
</div>

</body>
</html>
