<?php
session_start();
include "db.php";

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'cashier'){
    header("Location: index.php");
    exit();
}

/* No product selected */
if(!isset($_POST['product_id'])){
    echo "<script>
    alert('Please select at least one product.');
    window.location.href='sales.php';
    </script>";
    exit();
}

$cashier_id = $_SESSION['user_id'];
$payment_method = $_POST['payment_method'];
$amount_paid = $_POST['amount_paid'];

$total_amount = 0;
$items = [];

/* FIRST: calculate total and verify stock */
foreach($_POST['product_id'] as $pid){

    $qty = $_POST['qty_'.$pid];
    if($qty <= 0) continue;

    $product = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT products.product_name, products.selling_price, inventory.quantity
         FROM products
         JOIN inventory ON products.id = inventory.product_id
         WHERE products.id = $pid"));

    /* product missing */
    if(!$product){
        echo "<script>
        alert('Product not found.');
        window.location.href='sales.php';
        </script>";
        exit();
    }

    $price = $product['selling_price'];
    $stock = $product['quantity'];

    /* OUT OF STOCK (CRITICAL FIX) */
    if($qty > $stock){
        echo "<script>
        alert('Not enough stock for: ".$product['product_name']."\\nAvailable quantity: ".$stock."');
        window.location.href='sales.php';
        </script>";
        exit();
    }

    $subtotal = $price * $qty;
    $total_amount += $subtotal;

    // store item temporarily
    $items[] = [
        'pid' => $pid,
        'qty' => $qty,
        'price' => $price
    ];
}

/* IMPORTANT: verify payment BEFORE creating sale */
if($amount_paid < $total_amount){
    echo "<script>
    alert('Amount paid is less than the total bill. Sale cancelled.');
    window.location.href='sales.php';
    </script>";
    exit();
}

/* NOW create the sale */
mysqli_query($conn, 
    "INSERT INTO sales (cashier_id,total_amount,payment_method,amount_paid) 
     VALUES ($cashier_id,$total_amount,'$payment_method',$amount_paid)");

$sale_id = mysqli_insert_id($conn);

/* save items and reduce stock */
foreach($items as $item){

    $pid = $item['pid'];
    $qty = $item['qty'];
    $price = $item['price'];

    mysqli_query($conn,
        "INSERT INTO sale_items (sale_id, product_id, quantity, price)
         VALUES ($sale_id,$pid,$qty,$price)");

    mysqli_query($conn,
    "UPDATE inventory
     SET quantity = quantity - $qty,
         last_updated = NOW()
     WHERE product_id = $pid");
}

/* go to receipt */
header("Location: receipt.php?sale_id=".$sale_id);
exit();
?>