DA<?php
session_start();
include "db.php";

/* ONLY ADMIN */
if(!isset($_SESSION['role']) || $_SESSION['role']!='admin'){
    header("Location:index.php");
    exit;
}

$admin_id = $_SESSION['user_id'];

/* ================= APPROVE ================= */
if(isset($_POST['approve'])){

    $product_id = intval($_POST['product_id']);
    $approved_total = intval($_POST['approved_qty']);

    if($approved_total <= 0){
        header("Location:approve-requests.php");
        exit;
    }

    $rows = mysqli_query($conn,"
        SELECT id, requested_qty
        FROM stock_requests
        WHERE product_id=$product_id
        AND status='pending'
        ORDER BY id ASC
    ");

    $remaining = $approved_total;

    while($r = mysqli_fetch_assoc($rows)){

        $approve_this = 0;

        if($remaining <= 0){
            $approve_this = 0;
        }
        elseif($remaining >= $r['requested_qty']){
            $approve_this = $r['requested_qty'];
        }
        else{
            $approve_this = $remaining;
        }

        mysqli_query($conn,"
            UPDATE stock_requests
            SET status='approved',
                approved_qty=$approve_this,
                approved_by=$admin_id
            WHERE id=".$r['id']."
        ");

        $remaining -= $approve_this;
    }

    header("Location:approve-requests.php");
    exit;
}

/* ================= REJECT ================= */
if(isset($_POST['reject'])){

    $product_id = intval($_POST['product_id']);

    mysqli_query($conn,"
        UPDATE stock_requests
        SET status='rejected',
            approved_by=$admin_id
        WHERE product_id=$product_id
        AND status='pending'
    ");

    header("Location:approve-requests.php");
    exit;
}

/* ================= PENDING ================= */
$pending=mysqli_query($conn,"
SELECT 
    sr.product_id,
    p.product_name,
    p.image,
    p.unit,
    SUM(sr.requested_qty) as total_requested,
    MIN(sr.request_date) as first_date,
    GROUP_CONCAT(DISTINCT u.username SEPARATOR ', ') as username
FROM stock_requests sr
JOIN products p ON p.id=sr.product_id
JOIN users u ON u.id=sr.requested_by
WHERE sr.status='pending'
GROUP BY sr.product_id
ORDER BY first_date DESC
");

/* ================= HISTORY ================= */
$history=mysqli_query($conn,"
SELECT 
    sr.product_id,
    p.product_name,
    p.image,
    p.unit,
    DATE(sr.request_date) as req_date,

    SUM(sr.requested_qty) as total_requested,
    SUM(COALESCE(sr.approved_qty,0)) as total_approved,

    COALESCE((
        SELECT SUM(gri.quantity_received)
        FROM goods_received_items gri
        JOIN goods_received gr ON gr.id=gri.grn_id
        WHERE gri.product_id = sr.product_id
        AND DATE(gr.received_date)=DATE(sr.request_date)
    ),0) as total_received,

    GROUP_CONCAT(DISTINCT u.username SEPARATOR ', ') as username,

    CASE
        WHEN SUM(CASE WHEN sr.status='received' THEN 1 ELSE 0 END) > 0 THEN 'received'
        WHEN SUM(CASE WHEN sr.status='approved' THEN 1 ELSE 0 END) > 0 THEN 'approved'
        ELSE 'rejected'
    END as status

FROM stock_requests sr
JOIN products p ON p.id=sr.product_id
JOIN users u ON u.id=sr.requested_by
WHERE sr.status!='pending'
GROUP BY sr.product_id, DATE(sr.request_date)
ORDER BY req_date DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Approve Stock Requests</title>

<link rel="stylesheet" href="dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.main-content{padding:30px;padding-top:100px;background:#f4f6f9;}
.page-card{background:white;border-radius:16px;box-shadow:0 10px 25px rgba(0,0,0,0.08);padding:25px;margin-bottom:25px;}
h2{color:#ff7a18;margin-bottom:15px;}

table{width:100%;border-collapse:collapse;margin-top:10px;}
th{background:#ff7a18;color:white;padding:12px;text-align:left;}
td{padding:12px;border-bottom:1px solid #eee;}
tr:hover{background:#fff4ea;}

.badge{padding:6px 12px;border-radius:12px;color:white;font-size:12px;font-weight:600;}
.approved{background:#2e7d32;}
.rejected{background:#c62828;}
.received{background:#1565c0;}

.qty-input{width:90px;padding:6px;border-radius:6px;border:1px solid #ccc;}
.btn{border:none;padding:7px 12px;border-radius:6px;cursor:pointer;color:white;font-weight:600;}
.approve{background:#2e7d32;}
.reject{background:#d32f2f;}

.product-img{
    width:35px;
    height:35px;
    border-radius:6px;
    object-fit:cover;
    border:1px solid #ddd;
    margin-right:8px;
}
.product-cell{
    display:flex;
    align-items:center;
}
</style>
</head>

<body>

<?php include 'topbar.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main-content">

<!-- ================= PENDING ================= -->
<div class="page-card">
<h2><i class="fa-solid fa-clipboard-check"></i> Pending Approval</h2>

<table>
<tr>
<th>Product</th>
<th>Total Requested</th>
<th>Requested By</th>
<th>Date</th>
<th>Action</th>
</tr>

<tbody>

<?php if(mysqli_num_rows($pending) == 0): ?>

<tr>
<td colspan="5" style="text-align:center; padding:20px; color:#777; font-weight:600;">
<i class="fa-solid fa-circle-check" style="color:#2e7d32;"></i>
No pending approvals waiting
</td>
</tr>

<?php else: ?>

<?php while($r=mysqli_fetch_assoc($pending)): ?>
<tr>

<td class="product-cell">
<img src="images/products/<?= $r['image'] ?: 'no-image.png' ?>" class="product-img">
<?= htmlspecialchars($r['product_name']) ?>
</td>

<td><b><?= $r['total_requested'] ?> <?= $r['unit'] ?? 'pcs' ?></b></td>
<td><?= htmlspecialchars($r['username']) ?></td>
<td><?= date("d M Y H:i",strtotime($r['first_date'])) ?></td>

<td>
<form method="POST" style="display:inline-flex;gap:6px;">
<input type="hidden" name="product_id" value="<?= $r['product_id'] ?>">
<input type="number" name="approved_qty"
min="1"
max="<?= $r['total_requested'] ?>"
value="<?= $r['total_requested'] ?>"
class="qty-input"
required>
<button class="btn approve" name="approve">
<i class="fa-solid fa-check"></i>
</button>
</form>

<form method="POST" style="display:inline;">
<input type="hidden" name="product_id" value="<?= $r['product_id'] ?>">
<button class="btn reject" name="reject">
<i class="fa-solid fa-xmark"></i>
</button>
</form>
</td>

</tr>
<?php endwhile; ?>

<?php endif; ?>

</tbody>
</table>
</div>

<!-- ================= HISTORY ================= -->
<div class="page-card">
<h2>Request History</h2>

<table>
<tr>
<th>Product</th>
<th>Requested</th>
<th>Approved</th>
<th>Received</th>
<th>Status</th>
<th>Requested By</th>
<th>Date</th>
</tr>

<?php while($r=mysqli_fetch_assoc($history)): ?>
<tr>

<td class="product-cell">
<img src="images/products/<?= $r['image'] ?: 'no-image.png' ?>" class="product-img">
<?= htmlspecialchars($r['product_name']) ?>
</td>

<td><?= $r['total_requested'] ?> <?= $r['unit'] ?? 'pcs' ?></td>
<td><?= $r['total_approved'] ?> <?= $r['unit'] ?? 'pcs' ?></td>
<td><?= $r['total_received'] ?> <?= $r['unit'] ?? 'pcs' ?></td>

<td><span class="badge <?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
<td><?= htmlspecialchars($r['username']) ?></td>
<td><?= date("d M Y",strtotime($r['req_date'])) ?></td>

</tr>
<?php endwhile; ?>

</table>
</div>

</div>
</body>
</html>