<?php
session_start();
include "db.php";

if(!isset($_SESSION['role']) || $_SESSION['role']!='manager'){
    header("Location:index.php");
    exit;
}

if(!isset($_POST['product_id'], $_POST['supplier_id'])){
    header("Location:receive-stock.php");
    exit;
}

$product_id  = intval($_POST['product_id']);
$supplier_id = intval($_POST['supplier_id']);
$user        = $_SESSION['user_id'];

/* ================= START TRANSACTION FIRST ================= */
mysqli_begin_transaction($conn);

try{

    /* LOCK APPROVED REQUESTS (REAL LOCK NOW) */
    $requests = mysqli_query($conn,"
    SELECT id, approved_qty
    FROM stock_requests
    WHERE product_id = $product_id
    AND status = 'approved'
    FOR UPDATE
    ");

    /* NOTHING TO RECEIVE -> STOP */
    if(mysqli_num_rows($requests) == 0){
        mysqli_rollback($conn);
        header("Location:receive-stock.php?error=already_received");
        exit;
    }

    $total_qty   = 0;
    $request_ids = [];

    while($r = mysqli_fetch_assoc($requests)){
        $qty = (int)$r['approved_qty'];

        if($qty > 0){
            $total_qty += $qty;
            $request_ids[] = $r['id'];
        }
    }

    if($total_qty <= 0){
        mysqli_rollback($conn);
        header("Location:receive-stock.php?error=already_received");
        exit;
    }

    /* 1. CREATE GRN */
    mysqli_query($conn,"
    INSERT INTO goods_received(supplier_id, received_by, received_date)
    VALUES($supplier_id, $user, NOW())
    ");

    $grn_id = mysqli_insert_id($conn);

    /* 2. INSERT GRN ITEM */
    mysqli_query($conn,"
    INSERT INTO goods_received_items(grn_id, product_id, quantity_received)
    VALUES($grn_id, $product_id, $total_qty)
    ");

    /* 3. ENSURE INVENTORY EXISTS */
    $checkInv = mysqli_query($conn,"
        SELECT product_id FROM inventory WHERE product_id = $product_id
    ");

    if(mysqli_num_rows($checkInv) == 0){
        mysqli_query($conn,"
        INSERT INTO inventory(product_id, quantity, min_quantity)
        VALUES($product_id, 0, 5)
        ");
    }

    /* 4. UPDATE INVENTORY */
    mysqli_query($conn,"
    UPDATE inventory
    SET quantity = quantity + $total_qty,
        last_updated = NOW()
    WHERE product_id = $product_id
    ");

    /* 5. MARK REQUESTS RECEIVED */
    $ids = implode(',', $request_ids);

    mysqli_query($conn,"
    UPDATE stock_requests
    SET status = 'received',
        received_date = NOW()
    WHERE id IN ($ids)
    AND status='approved'
    ");

    /* COMMIT */
    mysqli_commit($conn);

}catch(Exception $e){

    mysqli_rollback($conn);
    header("Location:receive_stock.php?error=system");
    exit;
}

header("Location:receive_stock.php?success=1");
exit;
?>