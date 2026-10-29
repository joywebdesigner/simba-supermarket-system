<?php 
session_start();
include "db.php";

/* ONLY MANAGER */
if(!isset($_SESSION['role']) || $_SESSION['role']!='manager'){
    header("Location:index.php");
    exit;
}

/* APPROVED PRODUCTS */
$requests=mysqli_query($conn,"
SELECT 
    sr.product_id,
    p.product_name,
    p.image,
    p.unit, /* ✅ ADDED */
    SUM(sr.approved_qty) AS approved_total
FROM stock_requests sr
JOIN products p ON p.id=sr.product_id
WHERE sr.status='approved'
GROUP BY sr.product_id
HAVING approved_total > 0
ORDER BY p.product_name ASC
");

/* SUPPLIERS */
$suppliers=mysqli_query($conn,"
SELECT * FROM suppliers
WHERE status='active'
ORDER BY company_name ASC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Receive Stock</title>

<link rel="stylesheet" href="dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.main-content{
padding:25px;
padding-top:100px;
background:#f4f6f9;
min-height:100vh;
}

.page-card{
background:white;
border-radius:16px;
box-shadow:0 10px 25px rgba(0,0,0,0.08);
padding:25px;
}

h2{
color:#ff7a18;
margin-bottom:15px;
}

table{
width:100%;
border-collapse:collapse;
}

th{
background:#ff7a18;
color:white;
padding:14px;
text-align:left;
}

td{
padding:12px;
border-bottom:1px solid #eee;
}

tr:hover{
background:#fff4ea;
}

/* PRODUCT */
.product-cell{
display:flex;
align-items:center;
gap:10px;
font-weight:600;
}

.product-img{
width:40px;
height:40px;
border-radius:8px;
object-fit:cover;
border:1px solid #ddd;
}

/* BADGE */
.qty-badge{
background:#e8f5e9;
color:#2e7d32;
padding:6px 10px;
border-radius:8px;
font-weight:bold;
}

/* SEARCHABLE DROPDOWN */
.search-select{
position:relative;
width:100%;
}

.search-input{
width:100%;
padding:8px;
border-radius:6px;
border:1px solid #ccc;
margin-bottom:5px;
}

.select-box{
width:100%;
padding:8px;
border-radius:6px;
border:1px solid #ccc;
}

/* BUTTON */
.btn{
background:#2e7d32;
color:white;
border:none;
padding:7px 14px;
border-radius:7px;
cursor:pointer;
font-weight:bold;
}

.btn:hover{
background:#1b5e20;
}

.alert{
padding:14px 18px;
border-radius:10px;
margin-bottom:18px;
font-weight:600;
display:flex;
align-items:center;
gap:10px;
}

.success{
background:#e8f5e9;
color:#2e7d32;
border-left:5px solid #2e7d32;
}

.empty{
background:#fff3e0;
color:#e65100;
border-left:5px solid #e65100;
}
</style>
</head>

<body>

<?php include "topbar.php"; ?>
<?php include "sidebar.php"; ?>

<div class="main-content">

<h2><i class="fa-solid fa-truck-ramp-box"></i> Receive Deliveries</h2>

<?php if(isset($_GET['success'])): ?>
<div class="alert success">
<i class="fa-solid fa-circle-check"></i>
Stock successfully received and inventory updated.
</div>
<?php endif; ?>

<div class="page-card">

<table>

<thead>
<tr>
<th>Product</th>
<th>Approved Quantity</th>
<th>Select Supplier</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php 
$hasRows=false;
while($r=mysqli_fetch_assoc($requests)): 
$hasRows=true;
?>

<tr>

<form method="POST" action="save_grn.php">

<td>
<div class="product-cell">

<?php if(!empty($r['image'])){ ?>
<img src="images/products/<?= $r['image'] ?>" class="product-img">
<?php } else { ?>
<img src="images/no-image.png" class="product-img">
<?php } ?>

<?= htmlspecialchars($r['product_name']) ?>

</div>

<input type="hidden" name="product_id" value="<?= $r['product_id'] ?>">
</td>

<!-- ✅ SMART UNIT DISPLAY -->
<td>
<span class="qty-badge">
<?= $r['approved_total'] ?> <?= $r['unit'] ?? 'pcs' ?>
</span>
</td>

<td>

<div class="search-select">

<input type="text" class="search-input" placeholder="Search supplier..." onkeyup="filterSuppliers(this)">

<select name="supplier_id" class="select-box" required>
<option value="">Choose Supplier</option>

<?php
mysqli_data_seek($suppliers,0);
while($s=mysqli_fetch_assoc($suppliers)){ ?>
<option value="<?= $s['id'] ?>">
<?= htmlspecialchars($s['supplier_name']) ?> (<?= htmlspecialchars($s['company_name']) ?>)
</option>
<?php } ?>

</select>

</div>

</td>

<td>
<button class="btn">
<i class="fa-solid fa-check"></i> Receive
</button>
</td>

</form>

</tr>

<?php endwhile; ?>

<?php if(!$hasRows): ?>
<tr>
<td colspan="4">
<div class="alert empty">
<i class="fa-solid fa-circle-info"></i>
There are no approved deliveries waiting to be received.
</div>
</td>
</tr>
<?php endif; ?>

</tbody>

</table>

</div>
</div>

<script>
function filterSuppliers(input){
let filter = input.value.toLowerCase();
let select = input.nextElementSibling;
let options = select.options;

for(let i=0;i<options.length;i++){
let txt = options[i].text.toLowerCase();
options[i].style.display = txt.includes(filter) ? "" : "none";
}
}
</script>

</body>
</html>