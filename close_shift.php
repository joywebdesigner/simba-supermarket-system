<?php
session_start();
include "db.php";

date_default_timezone_set('Africa/Kigali');

if(!isset($_SESSION['role']) || $_SESSION['role']!='cashier'){
    header("Location:index.php");
    exit;
}

$cashier = $_SESSION['user_id'];
$today = date('Y-m-d');

/* GET EXPECTED CASH (ONLY CASH SALES) */
$expectedQuery = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT SUM(si.quantity*si.price) total
FROM sale_items si
JOIN sales s ON s.id=si.sale_id
WHERE s.cashier_id=$cashier
AND s.payment_method='Cash'
AND s.status='completed'
AND DATE(s.sale_date)='$today'
"));

$expectedCash = $expectedQuery['total'] ?? 0;

/* WHEN FORM SUBMITTED */
if(isset($_POST['counted_cash'])){

    $counted = floatval($_POST['counted_cash']);
    $difference = $counted - $expectedCash;

    if($difference == 0){
        $status = "Balanced";
    } elseif($difference < 0){
        $status = "Shortage";
    } else {
        $status = "Overage";
    }

    mysqli_query($conn,"
    INSERT INTO cash_reconciliation 
    (cashier_id, closing_date, expected_cash, counted_cash, difference, status)
    VALUES ($cashier,'$today',$expectedCash,$counted,$difference,'$status')
    ");

    header("Location: reconciliation_result.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Close Shift</title>

<style>
body{
    background:#f4f6f9;
    font-family:Segoe UI;
}

.box{
    width:420px;
    margin:80px auto;
    background:white;
    padding:30px;
    border-radius:12px;
    box-shadow:0 10px 25px rgba(0,0,0,0.1);
}

h2{text-align:center;margin-bottom:20px;}

.expected{
    background:#fff3e0;
    padding:15px;
    border-radius:8px;
    text-align:center;
    font-size:18px;
    font-weight:bold;
    color:#e65100;
}

input{
    width:100%;
    padding:12px;
    margin-top:15px;
    border-radius:6px;
    border:1px solid #ccc;
}

button{
    width:100%;
    margin-top:20px;
    padding:12px;
    border:none;
    background:#ff7a18;
    color:white;
    font-weight:bold;
    border-radius:6px;
    cursor:pointer;
}
</style>
</head>

<body>

<div class="box">

<h2>Cash Drawer Reconciliation</h2>

<div class="expected">
Expected Cash in Drawer:<br>
RWF <?php echo number_format($expectedCash); ?>
</div>

<form method="POST">

<label style="margin-top:15px;display:block;">
Enter Counted Cash Amount (RWF):
</label>

<input type="number" name="counted_cash" required min="0" step="0.01">

<button type="submit">Confirm Closing</button>

</form>

</div>

</body>
</html>