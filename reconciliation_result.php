<?php
session_start();
include "db.php";

$cashier = $_SESSION['user_id'];

$result = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT * FROM cash_reconciliation
WHERE cashier_id=$cashier
ORDER BY id DESC
LIMIT 1
"));
?>

<!DOCTYPE html>
<html>
<head>
<title>Reconciliation Result</title>

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
    text-align:center;
}

.balanced{color:green;}
.shortage{color:red;}
.overage{color:orange;}

button{
    margin-top:20px;
    padding:10px 20px;
    border:none;
    background:#ff7a18;
    color:white;
    border-radius:6px;
    cursor:pointer;
}
</style>
</head>

<body>

<div class="box">

<h2>Closing Result</h2>

<p>Expected Cash: <b>RWF <?php echo number_format($result['expected_cash']); ?></b></p>
<p>Counted Cash: <b>RWF <?php echo number_format($result['counted_cash']); ?></b></p>
<p>Difference: <b>RWF <?php echo number_format($result['difference']); ?></b></p>

<h3 class="<?php echo strtolower($result['status']); ?>">
<?php echo $result['status']; ?>
</h3>

<button onclick="window.location.href='cashier.php'">Back to Dashboard</button>

</div>

</body>
</html>