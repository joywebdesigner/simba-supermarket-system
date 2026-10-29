<?php
session_start();
include "db.php";

/* ALLOW ONLY ADMIN & MANAGER */
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','manager'])) {
    header("Location: index.php");
    exit;
}

$isManager = ($_SESSION['role'] === 'manager');

/* ✅ FETCH INVENTORY DATA */
$sql = "SELECT 
            p.id,
            p.product_name,
            p.image,
            p.category,
            p.unit,
            COALESCE(i.quantity,0) as quantity,
            COALESCE(i.min_quantity,5) as min_quantity,

            (
                SELECT COUNT(*) 
                FROM stock_requests sr
                WHERE sr.product_id = p.id
                AND sr.status IN ('pending','approved')
            ) as has_request

        FROM products p
        LEFT JOIN inventory i ON p.id = i.product_id
        ORDER BY p.product_name ASC";

$result = $conn->query($sql);

/* COUNT STATUS */
$in=0; $low=0; $out=0;
$data=[];

while($r=$result->fetch_assoc()){

    $qty = (int)$r['quantity'];
    $min = (int)$r['min_quantity'];

    if($qty <= 0){
        $out++;
        $status="OUT";
    }
    elseif($qty <= $min){
        $low++;
        $status="LOW";
    }
    else{
        $in++;
        $status="OK";
    }

    $r['status']=$status;
    $data[]=$r;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Inventory</title>

<link rel="stylesheet" href="dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.main-content{
    padding:25px;
    padding-top:100px;
    background:#f4f6f9;
    min-height:100vh;
}

.product-img{
    width:38px;
    height:38px;
    border-radius:8px;
    object-fit:cover;
    border:1px solid #ddd;
}

.product{
    display:flex;
    align-items:center;
    gap:10px;
    font-weight:600;
}

/* YOUR ORIGINAL CSS UNTOUCHED */
.inventory-header{
background:linear-gradient(45deg,#ff7a18,#ff9a4d);
color:white;
padding:25px;
border-radius:16px;
box-shadow:0 10px 25px rgba(0,0,0,0.12);
margin-bottom:20px;
}
.inventory-header h2{margin:0;font-size:22px;font-weight:600;}
.inventory-header p{margin-top:8px;opacity:0.9;}
.summary{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
gap:15px;
margin-bottom:20px;
}
.sum-card{
background:white;
padding:18px;
border-radius:14px;
box-shadow:0 8px 20px rgba(0,0,0,0.08);
display:flex;
align-items:center;
gap:15px;
}
.sum-card i{
font-size:28px;
padding:14px;
border-radius:12px;
color:white;
}
.green{background:#2e7d32;}
.orange{background:#ff9800;}
.red{background:#c62828;}
.search-box{
background:white;
padding:14px;
border-radius:12px;
box-shadow:0 6px 18px rgba(0,0,0,0.08);
margin-bottom:15px;
display:flex;
align-items:center;
gap:10px;
}
.search-box input{border:none;outline:none;flex:1;font-size:15px;}
.table-card{
background:white;
border-radius:16px;
box-shadow:0 10px 25px rgba(0,0,0,0.08);
overflow:hidden;
}
.inventory-table{width:100%;border-collapse:collapse;}
.inventory-table thead{background:#fff3e0;}
.inventory-table th{
padding:16px;
text-align:left;
font-weight:600;
color:#e65100;
}
.inventory-table td{padding:15px;border-bottom:1px solid #eee;}
.inventory-table tr:hover{background:#fffaf4;}
.badge{
padding:6px 12px;
border-radius:20px;
font-size:12px;
font-weight:bold;
color:white;
}
.ok{background:#2e7d32;}
.low{background:#ff9800;}
.out{background:#c62828;}
.req-btn{
padding:7px 12px;
border-radius:8px;
color:white;
text-decoration:none;
font-size:13px;
font-weight:600;
display:inline-block;
}
.warn{background:#ff9800;}
.urgent{background:#d32f2f;}
.req-btn:hover{opacity:0.85;}
</style>
</head>

<body>

<?php include "topbar.php"; ?>
<?php include "sidebar.php"; ?>

<div class="main-content">

<div class="inventory-header">
<h2><i class="fa-solid fa-warehouse"></i> Inventory Overview</h2>
<p>Monitor stock levels and detect shortages instantly</p>
</div>

<div class="summary">
<div class="sum-card">
<i class="fa-solid fa-box green"></i>
<div><h3><?= $in ?></h3><span>In Stock Products</span></div>
</div>

<div class="sum-card">
<i class="fa-solid fa-triangle-exclamation orange"></i>
<div><h3><?= $low ?></h3><span>Low Stock</span></div>
</div>

<div class="sum-card">
<i class="fa-solid fa-ban red"></i>
<div><h3><?= $out ?></h3><span>Out of Stock</span></div>
</div>
</div>

<div class="search-box">
<i class="fa fa-search"></i>
<input type="text" id="search" placeholder="Search product name...">
</div>

<div class="table-card">

<table class="inventory-table" id="inventoryTable">

<thead>
<tr>
<th>Product</th>
<th>Category</th>
<th>Available</th>
<th>Minimum</th>
<th>Status</th>
<?php if($isManager): ?>
<th>Action</th>
<?php endif; ?>
</tr>
</thead>

<tbody>

<?php foreach($data as $row): ?>
<tr>

<td class="product">
<img src="images/products/<?= $row['image'] ?: 'no-image.png' ?>" class="product-img">
<?= htmlspecialchars($row['product_name']) ?>
</td>

<td><?= ucfirst($row['category']) ?></td>

<!-- ✅ SMART AVAILABLE -->
<td>
<?php 
$qty = $row['quantity'];
$unit = $row['unit'] ?? 'pcs';
$min = $row['min_quantity'];
?>

<?php if($qty <= 0): ?>
<span class="badge out">❌ Out (0 <?= $unit ?>)</span>

<?php elseif($qty <= $min): ?>
<span class="badge low">⚠️ <?= $qty . " " . $unit ?> left</span>

<?php else: ?>
<b><?= $qty ?> <small style="color:#777;"><?= $unit ?></small></b>
<?php endif; ?>
</td>

<!-- ✅ MIN WITH UNIT -->
<td>
<?= $row['min_quantity'] ?>
<small style="color:#777;"><?= $unit ?></small>
</td>

<td>
<?php if($row['status']=="OK"): ?>
<span class="badge ok">In Stock</span>
<?php elseif($row['status']=="LOW"): ?>
<span class="badge low">Low Stock</span>
<?php else: ?>
<span class="badge out">Out of Stock</span>
<?php endif; ?>
</td>

<?php if($isManager): ?>
<td>

<?php if($row['has_request']>0): ?>
<span style="color:#999;font-weight:600;">
<i class="fa-solid fa-clock"></i> Request Pending
</span>

<?php elseif($row['status']=="LOW"): ?>
<a href="stock-requests.php?product_id=<?= $row['id'] ?>" class="req-btn warn">
<i class="fa-solid fa-cart-plus"></i> Request
</a>

<?php elseif($row['status']=="OUT"): ?>
<a href="stock-requests.php?product_id=<?= $row['id'] ?>" class="req-btn urgent">
<i class="fa-solid fa-triangle-exclamation"></i> Urgent
</a>

<?php else: ?>
—
<?php endif; ?>

</td>
<?php endif; ?>

</tr>
<?php endforeach; ?>

</tbody>
</table>

</div>
</div>

<script>
document.getElementById("search").addEventListener("keyup",function(){
let value=this.value.toLowerCase();
document.querySelectorAll("#inventoryTable tbody tr").forEach(row=>{
row.style.display=row.innerText.toLowerCase().includes(value)?"":"none";
});
});
</script>

</body>
</html>