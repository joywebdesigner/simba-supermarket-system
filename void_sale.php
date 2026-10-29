<?php
session_start();
include "db.php";

if(!isset($_SESSION['role']) || $_SESSION['role']!='cashier'){
    header("Location:index.php");
    exit;
}

$sale_id = intval($_GET['sale_id']);

/* Check if already voided */
$check=mysqli_query($conn,"SELECT status FROM sales WHERE id=$sale_id");
$s=mysqli_fetch_assoc($check);

if($s['status']=='voided'){
    header("Location: receipts-history.php");
    exit;
}

/* STEP 1 — restore stock */
$items=mysqli_query($conn,"
SELECT product_id, quantity
FROM sale_items
WHERE sale_id=$sale_id");

while($item=mysqli_fetch_assoc($items)){
    $pid=$item['product_id'];
    $qty=$item['quantity'];

    mysqli_query($conn,"
UPDATE inventory
SET quantity = quantity + $qty,
    last_updated = NOW()
WHERE product_id=$pid");
}

/* STEP 2 — VERY IMPORTANT
   reverse financial transaction */
mysqli_query($conn,"
UPDATE sale_items
SET quantity = 0, price = 0
WHERE sale_id=$sale_id");

/* STEP 3 — mark receipt voided */
mysqli_query($conn,"
UPDATE sales
SET status='voided',
total_amount=0,
amount_paid=0
WHERE id=$sale_id");

header("Location: receipts-history.php?void=success");
exit;
?>
