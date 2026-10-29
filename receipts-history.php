<?php
session_start();
include "db.php";

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'cashier'){
    header("Location: index.php");
    exit();
}

$cashier_id = $_SESSION['user_id'];

$condition = "sales.cashier_id = $cashier_id";

/* filters */
if(isset($_GET['receipt_no']) && $_GET['receipt_no'] != ""){
    $receipt_no = intval($_GET['receipt_no']);
    $condition .= " AND sales.id = $receipt_no";
}

if(isset($_GET['date']) && $_GET['date'] != ""){
    $date = $_GET['date'];
    $condition .= " AND DATE(sales.sale_date) = '$date'";
}

/* sales */
$sales = mysqli_query($conn,
"SELECT sales.id, sales.sale_date, sales.total_amount, sales.payment_method,
        sales.status
 FROM sales
 WHERE $condition
 ORDER BY sales.id DESC");

/* total */
$totalQuery = mysqli_query($conn,
"SELECT SUM(total_amount) as total 
 FROM sales 
 WHERE status='completed' AND cashier_id = $cashier_id");

$totalRevenue = mysqli_fetch_assoc($totalQuery)['total'] ?? 0;
?>

<!DOCTYPE html>
<html>
<head>
<title>Receipts</title>

<link rel="stylesheet" href="dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

.history-card{
    background:white;
    padding:25px;
    border-radius:12px;
    box-shadow:0 10px 25px rgba(0,0,0,0.08);
}

.history-table{
    width:100%;
    border-collapse:collapse;
}

.history-table th, .history-table td{
    padding:12px;
    border-bottom:1px solid #eee;
}

.history-table thead{
    background:#ff7a18;
    color:white;
}

.product-row{
    display:flex;
    align-items:center;
    gap:10px;
    margin-bottom:6px;
}

.product-img{
    width:35px;
    height:35px;
    border-radius:6px;
    object-fit:cover;
    border:1px solid #ddd;
}

.product-info{
    font-size:13px;
}

.product-name{
    font-weight:600;
}

.product-meta{
    color:#777;
    font-size:12px;
}

.unit{
    font-size:11px;
    color:#555;
    margin-left:3px;
}

.badge{
    padding:4px 10px;
    border-radius:6px;
    font-size:12px;
    font-weight:bold;
}
.completed{background:#d4edda;color:#155724;}
.voided{background:#f8d7da;color:#721c24;}

.view{background:#007bff;color:white;padding:6px 10px;border-radius:6px;}
.void{background:#dc3545;color:white;padding:6px 10px;border-radius:6px;}

.revenue-box{
    background:#fff3e0;
    padding:12px;
    border-radius:8px;
    margin-bottom:10px;
    font-weight:bold;
}

/* FILTER */
.filter-bar{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-bottom:15px;
}

.filter-bar input{
    padding:8px;
    border-radius:6px;
    border:1px solid #ccc;
}

.btn{
    padding:8px 14px;
    border:none;
    border-radius:6px;
    color:white;
    cursor:pointer;
}

.search-btn{background:#ff7a18;}
.reset-btn{background:#777;}
.pdf-btn{background:#2e7d32;}

/* PRINT HEADER */
.print-header{
    display:none;
    text-align:center;
    margin-bottom:15px;
}

.print-header img{
    width:80px;
}

.print-header h1{
    margin:5px 0;
}

.print-header p{
    font-size:13px;
}

/* PRINT STYLE */
@media print {

.sidebar, .topbar, .filter-bar, .btn {
    display:none !important;
}

.print-header{
    display:block !important;
}

body{
    background:white;
    font-size:12px;
}

.history-card{
    box-shadow:none;
}

.history-table th{
    background:#ddd !important;
    color:black !important;
}

.history-table td, .history-table th{
    border:1px solid #999;
}

h2{
    display:none;
}

}

</style>
</head>

<body>

<?php include "topbar.php"; ?>
<?php include "sidebar.php"; ?>

<div class="main-content">

<!-- 🔥 PRINT HEADER -->
<div class="print-header">
<img src="images/simba logo2.png">
<h1>SIMBA SUPERMARKET</h1>
<p>Receipt Report</p>
<p><?= date("d M Y H:i") ?></p>
</div>

<h2><i class="fa-solid fa-receipt"></i> My Receipts</h2>

<div class="revenue-box">
Total Revenue: RWF <?= number_format($totalRevenue) ?>
</div>

<!-- FILTER + PDF -->
<div class="filter-bar">

<form method="GET" style="display:flex; gap:10px; flex-wrap:wrap;">

<input type="number" name="receipt_no" placeholder="Receipt No"
value="<?= $_GET['receipt_no'] ?? '' ?>">

<input type="date" name="date"
value="<?= $_GET['date'] ?? '' ?>">

<button class="btn search-btn">
<i class="fa-solid fa-magnifying-glass"></i> Search
</button>

<button type="button" class="btn reset-btn" onclick="window.location='receipt-history.php'">
Reset
</button>

</form>

<button onclick="window.print()" class="btn pdf-btn">
<i class="fa-solid fa-file-pdf"></i> Export PDF
</button>

</div>

<div class="history-card">

<table class="history-table">

<thead>
<tr>
<th>Receipt</th>
<th>Date</th>
<th>Products</th>
<th>Payment</th>
<th>Total</th>
<th>Status</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php while($row=mysqli_fetch_assoc($sales)){ ?>

<?php
$items = mysqli_query($conn,"
SELECT p.product_name, p.image, p.unit, si.quantity, si.price,
(si.quantity * si.price) as subtotal
FROM sale_items si
JOIN products p ON p.id = si.product_id
WHERE si.sale_id = ".$row['id']);
?>

<tr>

<td>#<?= $row['id'] ?></td>

<td><?= date("d M Y H:i",strtotime($row['sale_date'])) ?></td>

<td>

<?php while($item=mysqli_fetch_assoc($items)){ ?>

<div class="product-row">

<img src="images/products/<?= $item['image'] ?: 'no-image.png' ?>" class="product-img">

<div class="product-info">
<div class="product-name"><?= $item['product_name'] ?></div>

<div class="product-meta">
<?= $item['quantity'] ?>
<span class="unit"><?= $item['unit'] ?? 'pcs' ?></span>
× <?= number_format($item['price']) ?> = 
<b><?= number_format($item['subtotal']) ?> RWF</b>
</div>

</div>

</div>

<?php } ?>

</td>

<td><?= $row['payment_method'] ?></td>

<td><b><?= number_format($row['total_amount']) ?> RWF</b></td>

<td>
<?php if($row['status']=="completed"){ ?>
<span class="badge completed">Completed</span>
<?php } else { ?>
<span class="badge voided">Voided</span>
<?php } ?>
</td>

<td>
<a href="receipt.php?sale_id=<?= $row['id'] ?>" class="view">
<i class="fa-solid fa-eye"></i>
</a>

<?php if($row['status']=="completed"){ ?>
<a href="void_sale.php?sale_id=<?= $row['id'] ?>" class="void">
<i class="fa-solid fa-ban"></i>
</a>
<?php } ?>
</td>

</tr>

<?php } ?>

</tbody>
</table>

</div>
</div>

</body>
</html>