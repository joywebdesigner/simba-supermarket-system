<?php
session_start();
include "db.php";

if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$adminName = $_SESSION['fullname'] ?? 'Admin';

/* ===============================
   KPI DATA
=================================*/

// Total Users
$totalUsers = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total FROM users"
))['total'];

// Total Products
$totalProducts = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total FROM products"
))['total'];

// Revenue This Month
$revenueMonth = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT IFNULL(SUM(total_amount),0) as total
    FROM sales
    WHERE YEAR(sale_date)=YEAR(CURDATE())
    AND MONTH(sale_date)=MONTH(CURDATE())
    AND status='completed'
"))['total'];

// Pending Requests
$pendingRequests = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT COUNT(*) as total
    FROM stock_requests
    WHERE status='pending'
"))['total'];


/* ===============================
   REQUESTS AWAITING APPROVAL
=================================*/
$pendingList = mysqli_query($conn,"
    SELECT p.product_name,
           SUM(sr.requested_qty) as total_requested,
           DATE(sr.request_date) as request_date
    FROM stock_requests sr
    JOIN products p ON sr.product_id = p.id
    WHERE sr.status='pending'
    GROUP BY sr.product_id, DATE(sr.request_date)
");


/* ===============================
   MONTHLY REVENUE TREND
=================================*/
$months = [];
$monthlyRevenue = [];

for ($m=1; $m<=12; $m++) {
    $months[] = date("M", mktime(0,0,0,$m,1));
    $result = mysqli_fetch_assoc(mysqli_query($conn,"
        SELECT IFNULL(SUM(total_amount),0) as total
        FROM sales
        WHERE YEAR(sale_date)=YEAR(CURDATE())
        AND MONTH(sale_date)=$m
        AND status='completed'
    "));
    $monthlyRevenue[] = $result['total'];
}


/* ===============================
   SALES BY CASHIER (CURRENT MONTH)
=================================*/
$cashierQuery = mysqli_query($conn,"
    SELECT u.fullname,
           IFNULL(SUM(s.total_amount),0) as total_sales
    FROM users u
    LEFT JOIN sales s 
        ON u.id = s.cashier_id
        AND YEAR(s.sale_date)=YEAR(CURDATE())
        AND MONTH(s.sale_date)=MONTH(CURDATE())
        AND s.status='completed'
    WHERE u.role='cashier'
    GROUP BY u.id
");

$cashierNames = [];
$cashierTotals = [];

while($row=mysqli_fetch_assoc($cashierQuery)){
    $cashierNames[] = $row['fullname'];
    $cashierTotals[] = $row['total_sales'];
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard | Retail System</title>
<link rel="stylesheet" href="dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<?php include "topbar.php"; ?>
<?php include "sidebar.php"; ?>

<div class="main-content">

<h2> <?php echo htmlspecialchars($adminName); ?> 👋</h2>
<p style="color:#666; margin-bottom:25px;">
Here is an overview of your retail system today.
</p>

<div class="cards">

<div class="card">
<div class="card-icon orange">
<i class="fa-solid fa-users"></i>
</div>
<div>
<h3>Total Users</h3>
<p><?= $totalUsers ?></p>
</div>
</div>

<div class="card">
<div class="card-icon orange">
<i class="fa-solid fa-box"></i>
</div>
<div>
<h3>Total Products</h3>
<p><?= $totalProducts ?></p>
</div>
</div>

<div class="card">
<div class="card-icon orange">
<i class="fa-solid fa-coins"></i>
</div>
<div>
<h3>Revenue This Month</h3>
<p>RWF <?= number_format($revenueMonth,2) ?></p>
</div>
</div>

<div class="card">
<div class="card-icon orange">
<i class="fa-solid fa-clock"></i>
</div>
<div>
<h3>Pending Requests</h3>
<p><?= $pendingRequests ?></p>

<a href="approve-requests.php"
style="display:inline-block;margin-top:8px;
background:#ff8c00;color:#fff;
padding:6px 12px;border-radius:6px;
font-size:12px;text-decoration:none;">
Go to Approvals
</a>

</div>
</div>

</div>


<div style="margin-top:40px; background:#fff; padding:20px; border-radius:10px;">
<h3>Requests Awaiting Approval</h3>

<table style="width:100%; border-collapse:collapse;">
<tr style="background:#f4f6f9;">
<th style="padding:10px; text-align:left;">Product</th>
<th style="padding:10px; text-align:left;">Requested</th>
<th style="padding:10px; text-align:left;">Date</th>
</tr>

<?php while($row=mysqli_fetch_assoc($pendingList)){ ?>

<tr style="border-bottom:1px solid #eee;">
<td style="padding:10px;"><?= $row['product_name'] ?></td>
<td style="padding:10px;"><?= $row['total_requested'] ?></td>
<td style="padding:10px;"><?= $row['request_date'] ?></td>
</tr>

<?php } ?>

</table>

</div>


<div style="display:flex; gap:30px; margin-top:40px;">

<div style="flex:1; background:#fff; padding:20px; border-radius:10px;">
<h3>Monthly Revenue Trend</h3>
<canvas id="monthlyChart"></canvas>
</div>

<div style="flex:1; background:#fff; padding:20px; border-radius:10px;">
<h3>Sales by Cashier (Current Month)</h3>
<canvas id="cashierChart"></canvas>
</div>

</div>

</div>


<script>

new Chart(document.getElementById('monthlyChart'), {
type:'line',
data:{
labels: <?= json_encode($months) ?>,
datasets:[{
label:'Revenue (RWF)',
data: <?= json_encode($monthlyRevenue) ?>,
borderWidth:3,
tension:0.4,
fill:false
}]
}
});

new Chart(document.getElementById('cashierChart'), {
type:'bar',
data:{
labels: <?= json_encode($cashierNames) ?>,
datasets:[{
label:'Sales (RWF)',
data: <?= json_encode($cashierTotals) ?>,
backgroundColor:'#ff8c00',
borderRadius:8,
barThickness:40
}]
},
options:{
responsive:true,
plugins:{
legend:{display:false},
tooltip:{
callbacks:{
label:function(context){
return "RWF " + context.raw.toLocaleString();
}
}
}
},
scales:{
y:{
beginAtZero:true,
ticks:{
callback:function(value){
return value.toLocaleString();
}
}
}
}
}
});

</script>

</body>
</html>