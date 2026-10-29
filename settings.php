<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: index.php");
    exit;
}
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Settings | Retail System</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* MAIN CONTENT */
        .main-content {
            padding: 30px;
        }

        /* BREADCRUMB */
        .breadcrumb {
            font-size: 14px; margin-bottom: 20px;
        }
        .breadcrumb a { color: #2a5298; text-decoration: none; margin-right:5px; }
        .breadcrumb span { color: #555; margin-right:5px; }

        /* PAGE HEADER */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .page-header h2 { font-size: 28px; color:#2a5298; }

        /* SETTINGS GRID */
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        /* SETTINGS CARD */
        .settings-card {
            background: #fff;
            border-radius: 14px;
            padding: 30px 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .settings-card::before {
            content: '';
            position: absolute;
            width: 120%;
            height: 120%;
            background: linear-gradient(135deg,#ff7a18,#ff9f1c);
            top: -100%; left: -100%;
            transform: rotate(45deg);
            transition: all 0.5s ease;
            z-index: 0;
        }
        .settings-card:hover::before {
            top: -10%; left: -10%;
        }

        .settings-card i {
            font-size: 40px;
            color: #ff7a18;
            margin-bottom: 15px;
            z-index:1; position:relative;
        }
        .settings-card h3 {
            font-size: 20px;
            color: #2a5298;
            margin-bottom: 8px;
            z-index:1; position:relative;
        }
        .settings-card p {
            font-size: 14px;
            color: #555;
            z-index:1; position:relative;
        }

        /* Add subtle hover scale */
        .settings-card:hover {
            transform: translateY(-8px) scale(1.02);
        }

        /* RESPONSIVE FIX */
        @media(max-width:600px){
            .settings-card { padding:25px 15px; }
        }
    </style>
</head>
<body>

<?php include 'topbar.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="<?= $role ?>.php">Dashboard</a> <span>/</span>
        <span>Settings</span>
    </div>

    <div class="page-header">
        <h2>Settings</h2>
    </div>

    <div class="settings-grid">
        <?php if($role==='admin'): ?>
        <div class="settings-card" onclick="location.href='settings-system.php'">
            <i class="fa-solid fa-store"></i>
            <h3>System Configuration</h3>
            <p>Manage store details, currency, taxes, and alerts.</p>
        </div>

        <div class="settings-card" onclick="location.href='settings-security.php'">
            <i class="fa-solid fa-shield-halved"></i>
            <h3>User & Security Rules</h3>
            <p>Control roles, passwords, and login security.</p>
        </div>
        <?php endif; ?>

        <div class="settings-card" onclick="location.href='settings-account.php'">
            <i class="fa-solid fa-user-gear"></i>
            <h3>My Account</h3>
            <p>Change your password and personal details.</p>
        </div>
    </div>
</div>

</body>
</html>
