<?php
session_start();
include "db.php";

if(!isset($_SESSION['role']) || $_SESSION['role']!='manager'){
    header("Location:index.php");
    exit;
}

$manager_id = $_SESSION['user_id'];

/* ================= HANDLE REQUEST ================= */
if(isset($_POST['request_stock'])){

    $product_id = intval($_POST['product_id']);
    $qty = intval($_POST['qty']);
    $force = isset($_POST['force_order']) ? 1 : 0;

    if($qty <= 0){
        header("Location:stock-requests.php?error=qty");
        exit;
    }

    $existing = mysqli_fetch_assoc(mysqli_query($conn,"
        SELECT COUNT(*) as total
        FROM stock_requests
        WHERE product_id=$product_id
        AND status IN ('pending','approved')
    "));

    if($existing['total'] > 0 && !$force){
        header("Location:stock-requests.php?confirm=".$product_id."&qty=".$qty);
        exit;
    }

    mysqli_query($conn,"
        INSERT INTO stock_requests(product_id,requested_qty,requested_by,status,request_date)
        VALUES($product_id,$qty,$manager_id,'pending',NOW())
    ");

    header("Location:stock-requests.php?created=1");
    exit;
}

/* ================= PRODUCTS ================= */
$products=mysqli_query($conn,"
SELECT 
p.id,
p.product_name,
p.image,
p.unit, /* ✅ ADDED */
COALESCE(i.quantity,0) as quantity,
COALESCE(i.min_quantity,5) as min_quantity
FROM products p
LEFT JOIN inventory i ON p.id=i.product_id
WHERE COALESCE(i.quantity,0) <= COALESCE(i.min_quantity,5)
ORDER BY p.product_name ASC
");

/* ================= HISTORY ================= */
$history=mysqli_query($conn,"
SELECT 
sr.product_id,
p.product_name,
p.image,
p.unit, /* ✅ ADDED */
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

CASE
    WHEN SUM(CASE WHEN sr.status='pending' THEN 1 ELSE 0 END) > 0 THEN 'pending'
    WHEN SUM(CASE WHEN sr.status='approved' THEN 1 ELSE 0 END) > 0 THEN 'approved'
    ELSE 'received'
END as status

FROM stock_requests sr
JOIN products p ON p.id=sr.product_id
GROUP BY sr.product_id, DATE(sr.request_date)
ORDER BY req_date DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Stock Requests</title>

<link rel="stylesheet" href="dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.main-content{padding:25px;padding-top:95px;background:#f4f6f9;}

.card{
background:white;
padding:22px;
border-radius:14px;
box-shadow:0 10px 25px rgba(0,0,0,0.08);
margin-bottom:25px;
}

h2{color:#ff7a18;margin-bottom:15px;}

table{width:100%;border-collapse:collapse;}
th{background:#ff7a18;color:white;padding:12px;text-align:left;}
td{padding:12px;border-bottom:1px solid #eee;}

.btn{
background:#ff7a18;
color:white;
border:none;
padding:7px 14px;
border-radius:6px;
cursor:pointer;
}

.badge{
padding:5px 10px;
border-radius:12px;
color:white;
font-size:12px;
}
.pending{background:#f57c00;}
.approved{background:#2e7d32;}
.received{background:#1565c0;}

.product-cell{
display:flex;
align-items:center;
gap:10px;
font-weight:600;
}

.product-img{
width:35px;
height:35px;
border-radius:6px;
object-fit:cover;
border:1px solid #ddd;
}
</style>
</head>

<body>

<?php include "topbar.php"; ?>
<?php include "sidebar.php"; ?>

<div class="main-content">

<h2><i class="fa-solid fa-clipboard-list"></i> Request Stock</h2>

<?php if(isset($_GET['created'])): ?>
<p style="color:green;font-weight:bold;">Request sent successfully</p>
<?php endif; ?>

<?php if(isset($_GET['error']) && $_GET['error']=='qty'): ?>
<p style="color:red;font-weight:bold;">Invalid quantity.</p>
<?php endif; ?>

<div class="card">
<h3>Products Needing Restock</h3>

<table>
<tr>
<th>Product</th>
<th>Available</th>
<th>Minimum</th>
<th>Request Qty</th>
<th>Action</th>
</tr>

<?php while($p=mysqli_fetch_assoc($products)): ?>
<tr>

<td>
<div class="product-cell">
<img src="images/products/<?= $p['image'] ?: 'no-image.png' ?>" class="product-img">
<?= htmlspecialchars($p['product_name']) ?>
</div>
</td>

<!-- ✅ WITH UNIT -->
<td style="color:red;font-weight:bold">
<?= $p['quantity'] ?> <?= $p['unit'] ?? 'pcs' ?>
</td>

<td>
<?= $p['min_quantity'] ?> <?= $p['unit'] ?? 'pcs' ?>
</td>

<td>
<form method="POST" style="display:flex;gap:8px;align-items:center;">
<input type="number" name="qty" min="1" required style="width:80px">
<input type="hidden" name="product_id" value="<?= $p['id'] ?>">
</td>

<td>
<button class="btn" name="request_stock">
<i class="fa-solid fa-paper-plane"></i> Send
</button>
</form>
</td>

</tr>
<?php endwhile; ?>

</table>
</div>

<div class="card">
<h3>Request History</h3>

<table>
<tr>
<th>Product</th>
<th>Requested</th>
<th>Approved</th>
<th>Received</th>
<th>Status</th>
<th>Date</th>
</tr>

<?php while($h=mysqli_fetch_assoc($history)): ?>
<tr>

<td>
<div class="product-cell">
<img src="images/products/<?= $h['image'] ?: 'no-image.png' ?>" class="product-img">
<?= htmlspecialchars($h['product_name']) ?>
</div>
</td>

<td><?= $h['total_requested'] ?> <?= $h['unit'] ?? 'pcs' ?></td>
<td><?= $h['total_approved'] ?> <?= $h['unit'] ?? 'pcs' ?></td>
<td><?= $h['total_received'] ?> <?= $h['unit'] ?? 'pcs' ?></td>

<td><span class="badge <?= $h['status'] ?>"><?= ucfirst($h['status']) ?></span></td>
<td><?= date("d M Y",strtotime($h['req_date'])) ?></td>

</tr>
<?php endwhile; ?>

</table>
</div>

</div>

<script>
<?php if(isset($_GET['confirm'])): ?>
let confirmOrder = confirm("⚠ This product already has a pending/approved request.\n\nDo you still want to place a new order?");

if(confirmOrder){
    let form = document.createElement("form");
    form.method = "POST";
    form.action = "stock-requests.php";

    form.innerHTML = `
        <input type="hidden" name="product_id" value="<?= $_GET['confirm'] ?>">
        <input type="hidden" name="qty" value="<?= $_GET['qty'] ?>">
        <input type="hidden" name="force_order" value="1">
        <input type="hidden" name="request_stock" value="1">
    `;

    document.body.appendChild(form);
    form.submit();
}
<?php endif; ?>
</script>

</body>
</html>