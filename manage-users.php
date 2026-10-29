<?php
session_start();
include "db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

/* DELETE USER */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // Optional safety: prevent deleting yourself
    if ($id != $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    header("Location: manage-users.php?deleted=1");
    exit;
}

/* FETCH USERS */
$result = $conn->query("SELECT id, username, role, fullname, email, phone FROM users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Users | Retail System</title>

    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        th.actions,
td.actions {
    width: 240px;
    white-space: nowrap;
}

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        th, td {
            padding: 14px 18px;
        }

        th {
            background: linear-gradient(90deg,#ff7a18,#ff9f1c);
            color: #fff;
            font-weight: 600;
        }

        tr:hover {
            background: #fff2e6;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
            color: #fff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .edit-btn { background: #5bc0de; }
        .delete-btn { background: #d9534f; }
        .view-btn { background: #28a745; }

        .edit-btn:hover { background: #31b0d5; }
        .delete-btn:hover { background: #c9302c; }
        .view-btn:hover { background: #218838; }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .add-btn {
            background: linear-gradient(135deg,#ff7a18,#ff9f1c);
            padding: 10px 18px;
            border-radius: 6px;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .add-btn:hover {
            background: linear-gradient(135deg,#ff9f1c,#ff7a18);
        }
    </style>
</head>
<body>

<?php include 'topbar.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main-content">

    <div class="page-header">
        <h2><i class="fa-solid fa-users"></i> Manage Users</h2>
        <a href="add-employee.php" class="add-btn">
            <i class="fa-solid fa-user-plus"></i> Add Employee
        </a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Full Name</th>
                <th>Username</th>
                <th>Role</th>
                <th>Email</th>
                <th>Phone</th>
                <th class="actions">Actions</th>

            </tr>
        </thead>
        <tbody>
        <?php while($user = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($user['fullname']); ?></td>
                <td><?= htmlspecialchars($user['username']); ?></td>
                <td><?= ucfirst($user['role']); ?></td>
                <td><?= htmlspecialchars($user['email']); ?></td>
                <td><?= htmlspecialchars($user['phone']); ?></td>
                <td class="actions">
    <div style="display:flex; gap:6px; flex-wrap:nowrap;">
        <a href="view-employee.php?id=<?= $user['id']; ?>" class="action-btn view-btn">
            <i class="fa-solid fa-eye"></i>
        </a>

        <a href="edit-user.php?id=<?= $user['id']; ?>" class="action-btn edit-btn">
            <i class="fa-solid fa-pen"></i>
        </a>

        <a href="#" class="action-btn delete-btn"
           onclick="confirmDelete(<?= $user['id']; ?>)">
            <i class="fa-solid fa-trash"></i>
        </a>
    </div>
</td>

            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- DELETE CONFIRMATION -->
<script>
function confirmDelete(userId) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This user will be permanently deleted!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d9534f',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete user'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'manage-users.php?delete=' + userId;
        }
    });
}
</script>

<!-- SUCCESS MESSAGE -->
<?php if (isset($_GET['deleted'])): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'User Deleted',
    text: 'The user has been removed successfully.',
    confirmButtonColor: '#ff7a18'
});
</script>
<?php endif; ?>

</body>
</html>
