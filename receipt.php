<?php
session_start();
include "db.php";

if(!isset($_GET['sale_id'])){
    die("No receipt found.");
}

$sale_id=intval($_GET['sale_id']);

$sale=mysqli_fetch_assoc(mysqli_query($conn,
"SELECT sales.*, users.fullname
 FROM sales
 JOIN users ON sales.cashier_id=users.id
 WHERE sales.id=$sale_id"));

if(!$sale){ die("Invalid sale."); }

$items=mysqli_query($conn,
"SELECT products.product_name, products.image, products.unit,
        sale_items.quantity, sale_items.price
 FROM sale_items
 JOIN products ON sale_items.product_id=products.id
 WHERE sale_items.sale_id=$sale_id");
?>
<!DOCTYPE html>
<html>
<head>
<title>Receipt #<?php echo $sale['id']; ?></title>

<style>
body{
    background:#ddd;
    font-family:"Courier New",monospace;
}

.receipt{
    width:300px;
    margin:20px auto;
    background:white;
    padding:15px;
    box-shadow:0 0 10px rgba(0,0,0,0.25);
}

.center{text-align:center;}

.logo{
    width:110px;
    margin-bottom:5px;
}

.store-name{
    font-size:18px;
    font-weight:bold;
}

.small{font-size:12px;}

.line{
    border-top:1px dashed #000;
    margin:10px 0;
}

table{
    width:100%;
    border-collapse:collapse;
    font-size:13px;
}

td{padding:3px 0;}

.right{text-align:right;}

.grand{
    font-weight:bold;
    font-size:16px;
}

/* PRODUCT */
.product-row{
    display:flex;
    align-items:center;
    gap:8px;
    margin-bottom:3px;
}

.product-img{
    width:30px;
    height:30px;
    object-fit:cover;
    border-radius:5px;
    border:1px solid #ccc;
}

.unit{
    font-size:11px;
    color:#555;
    margin-left:3px;
}

.footer{
    text-align:center;
    margin-top:10px;
    font-size:12px;
}

.actions{
    text-align:center;
    margin-top:15px;
}

.btn{
    padding:8px 15px;
    border:none;
    background:black;
    color:white;
    cursor:pointer;
    margin:5px;
}

.cancel{background:#555;}
.void{background:#c62828;}

@media print{
    body{background:white;}
    .actions{display:none;}
    .receipt{box-shadow:none;margin:0;width:280px;}
}
</style>
</head>

<body>

<div class="receipt">

<div class="center">
<img src="images/simba logo1.jpg" class="logo">
<div class="store-name">SIMBA SUPERMARKET</div>
<div class="small">Kigali - Rwanda</div>
<div class="small">Tel: 0789 107 678</div>
</div>

<div class="line"></div>

<div class="small">
Receipt #: <?php echo $sale['id']; ?><br>
Date: <?php echo date("d/m/Y H:i",strtotime($sale['sale_date'])); ?><br>
Cashier: <?php echo $sale['fullname']; ?><br>
Status:
<?php if($sale['status']=="completed"){ ?>
<span style="color:green;font-weight:bold;">COMPLETED</span>
<?php } else { ?>
<span style="color:red;font-weight:bold;">VOIDED</span>
<?php } ?>
</div>

<div class="line"></div>

<table>
<?php
$grand=0;
while($row=mysqli_fetch_assoc($items)){
$subtotal=$row['price']*$row['quantity'];
$grand+=$subtotal;
?>

<tr>
<td colspan="2">

<div class="product-row">

<img src="images/products/<?php echo $row['image'] ?: 'no-image.png'; ?>" class="product-img">

<div>
<?php echo $row['product_name']; ?>
</div>

</div>

</td>
</tr>

<tr>
<td>
<?php echo $row['quantity']; ?>
<span class="unit"><?php echo $row['unit'] ?? 'pcs'; ?></span>
× <?php echo number_format($row['price']); ?>
</td>

<td class="right"><?php echo number_format($subtotal); ?></td>
</tr>

<?php } ?>
</table>

<div class="line"></div>

<?php $change=$sale['amount_paid']-$grand; ?>

<table>
<tr>
<td>Total:</td>
<td class="right"><b>RWF <?php echo number_format($grand); ?></b></td>
</tr>

<tr>
<td>Paid:</td>
<td class="right">RWF <?php echo number_format($sale['amount_paid']); ?></td>
</tr>

<tr class="grand">
<td>Change:</td>
<td class="right">RWF <?php echo number_format($change); ?></td>
</tr>
</table>

<div class="line"></div>

<div class="footer">
Thank you for shopping with us!<br>
Goods once sold are not returnable.
</div>

<div class="actions">

<button class="btn" onclick="window.print()">Print</button>

<button class="btn" onclick="window.location.href='sales.php'">New Sale</button>

<button class="btn cancel" onclick="window.location.href='receipts-history.php'">Cancel</button>

<?php if($sale['status']=="completed"){ ?>
<button class="btn void"
onclick="if(confirm('Void this receipt? Stock will be restored!'))
window.location.href='void_sale.php?sale_id=<?php echo $sale_id; ?>'">
Void Receipt
</button>
<?php } ?>

</div>

</div>
</body>
</html>