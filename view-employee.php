<?php
session_start();
include "db.php";

if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: manage-users.php");
    exit;
}

$id = intval($_GET['id']);

// Suspend / Activate
if (isset($_GET['toggle_status'])) {
    $stmt = $conn->prepare("UPDATE users SET status = IF(status='active','suspended','active') WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: view-employee.php?id=$id");
    exit;
}

// Fetch employee
$stmt = $conn->prepare("SELECT fullname, username, role, email, phone, work_area, tasks, shift, start_date, status FROM users WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) { header("Location: manage-users.php"); exit; }
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Employee | Retail System</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* ===== PRINT ONLY PROFILE ===== */
        @media print {
            body * { visibility: hidden; }
            #print-section, #print-section * { visibility: visible; }
            #print-section { position: absolute; left:0; top:0; width:100%; }
        }

        /* PROFILE CARD */
        .profile-card {
            background: #fff;
            max-width: 900px;
            margin: 20px auto 50px;
            padding: 35px;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.12);
        }

        .profile-header {
            display:flex;
            justify-content: space-between;
            align-items:center;
            margin-bottom: 25px;
        }
        .profile-header h2 { color:#ff7a18; font-size:28px; }
        .role-badge { background:#ffecd1; color:#ff7a18; padding:6px 14px; border-radius:20px; font-weight:600; font-size:14px; }
        .status-badge { padding:6px 14px; border-radius:20px; font-weight:600; font-size:14px; color:#fff; }
        .status-active { background:#28a745; }
        .status-suspended { background:#dc3545; }

        .section { margin-bottom:30px; }
        .section h3 { color:#ff7a18; margin-bottom:15px; border-bottom:2px solid #ffe0c2; padding-bottom:6px; font-size:18px; }
        .info-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:18px; }
        .info-box { background:#fff7f0; padding:16px; border-radius:12px; border:1px solid #ffd9b3; }
        .info-box span { display:block; font-size:13px; color:#555; margin-bottom:6px; }
        .info-box strong { font-size:16px; color:#333; }

        .btn-actions { display:flex; gap:15px; margin-top:30px; flex-wrap:wrap; }
        .btn { padding:10px 18px; border-radius:8px; text-decoration:none; font-weight:500; font-size:15px; display:inline-flex; align-items:center; gap:6px; cursor:pointer; transition:0.3s; }
        .btn-back { background:#ff7a18; color:#fff; }
        .btn-back:hover { background:#ff9f1c; }
        .btn-toggle { background: linear-gradient(135deg,#ff7a18,#ff9f1c); color:#fff; }
        .btn-toggle:hover { background: linear-gradient(135deg,#ff9f1c,#ff7a18); }
        .btn-print { background:#28a745; color:#fff; }
        .btn-print:hover { background:#218838; }

        /* BREADCRUMB */
        .breadcrumb { font-size:14px; margin-bottom:20px; }
        .breadcrumb a { color:#ff7a18; text-decoration:none; margin-right:5px; }
        .breadcrumb span { margin-right:5px; color:#555; }
    </style>

    <script>
        function printEmployee() { window.print(); }
    </script>
</head>
<body>

<?php include 'topbar.php'; ?>
<?php include 'sidebar.php'; ?>
<?php if(isset($_GET['success']) && $_GET['success'] == 1): ?>
    <p class="message" style="text-align:center; color:green; font-size:16px; margin-bottom:20px;">
        Employee updated successfully!
    </p>
<?php endif; ?>


<div class="main-content">

    <!-- BREADCRUMB -->
    <div class="breadcrumb">
        <a href="admin.php">Dashboard</a> <span>/</span>
        <a href="manage-users.php">Manage Users</a> <span>/</span>
        <span><?= htmlspecialchars($user['fullname']); ?></span>
    </div>

    <!-- PROFILE CARD -->
    <div class="profile-card" id="print-section">

        <div class="profile-header">
            <h2><?= htmlspecialchars($user['fullname']); ?></h2>
            <div>
                <span class="role-badge"><?= ucfirst($user['role']); ?></span>
                <span class="status-badge <?= $user['status'] === 'active' ? 'status-active' : 'status-suspended'; ?>">
                    <?= ucfirst($user['status']); ?>
                </span>
            </div>
        </div>

        <!-- PERSONAL INFO -->
        <div class="section">
            <h3><i class="fa-solid fa-user"></i> Personal Information</h3>
            <div class="info-grid">
                <div class="info-box"><span>Username</span><strong><?= htmlspecialchars($user['username']); ?></strong></div>
                <div class="info-box"><span>Email</span><strong><?= htmlspecialchars($user['email']); ?></strong></div>
                <div class="info-box"><span>Phone</span><strong><?= htmlspecialchars($user['phone']); ?></strong></div>
            </div>
        </div>

        <!-- WORK INFO -->
        <div class="section">
            <h3><i class="fa-solid fa-briefcase"></i> Work Information</h3>
            <div class="info-grid">
                <div class="info-box"><span>Work Area</span><strong><?= htmlspecialchars($user['work_area']); ?></strong></div>
                <div class="info-box"><span>Assigned Tasks</span><strong><?= htmlspecialchars($user['tasks']); ?></strong></div>
                <div class="info-box"><span>Shift</span><strong><?= htmlspecialchars($user['shift']); ?></strong></div>
                <div class="info-box"><span>Start Date</span><strong><?= htmlspecialchars($user['start_date']); ?></strong></div>
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="btn-actions">
            <a href="manage-users.php" class="btn btn-back"><i class="fa-solid fa-arrow-left"></i> Back</a>
            <a href="view-employee.php?id=<?= $id; ?>&toggle_status=1" class="btn btn-toggle">
                <i class="fa-solid fa-toggle-on"></i> <?= $user['status']=='active'?'Suspend':'Activate'; ?>
            </a>
            <button onclick="printEmployee()" class="btn btn-print"><i class="fa-solid fa-print"></i> Print</button>
        </div>

    </div>

</div>

</body>
</html>
