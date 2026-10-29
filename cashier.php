<?php
session_start();
include "db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cashier') {
    header("Location: index.php");
    exit;
}

$cashier = $_SESSION['user_id'];
$cashierName = $_SESSION['fullname'] ?? 'Cashier';

/* TODAY SALES */
$todayQuery = mysqli_query($conn,"
SELECT SUM(si.quantity*si.price) total, COUNT(DISTINCT s.id) receipts
FROM sale_items si
JOIN sales s ON s.id=si.sale_id
WHERE s.cashier_id=$cashier
AND s.status='completed'
AND DATE(s.sale_date)=CURDATE()");
$todayData = mysqli_fetch_assoc($todayQuery);
$todaySales = $todayData['total'] ?? 0;
$totalReceipts = $todayData['receipts'] ?? 0;

/* ITEMS SOLD TODAY */
$itemQuery = mysqli_query($conn,"
SELECT SUM(si.quantity) total
FROM sale_items si
JOIN sales s ON s.id=si.sale_id
WHERE s.cashier_id=$cashier
AND s.status='completed'
AND DATE(s.sale_date)=CURDATE()");
$itemsSold = mysqli_fetch_assoc($itemQuery)['total'] ?? 0;

/* AVERAGE SALE */
$avgSale = ($totalReceipts>0)? $todaySales/$totalReceipts : 0;

/* TOTAL SALES EVER */
$totalSalesQuery = mysqli_query($conn,"
SELECT SUM(si.quantity*si.price) total
FROM sale_items si
JOIN sales s ON s.id=si.sale_id
WHERE s.cashier_id=$cashier
AND s.status='completed'");
$totalSalesEver = mysqli_fetch_assoc($totalSalesQuery)['total'] ?? 0;

/* TOP PRODUCTS TODAY */
$pieProducts=mysqli_query($conn,"
SELECT p.product_name,SUM(si.quantity) qty
FROM sale_items si
JOIN sales s ON s.id=si.sale_id
JOIN products p ON p.id=si.product_id
WHERE s.cashier_id=$cashier
AND s.status='completed'
AND DATE(s.sale_date)=CURDATE()
GROUP BY p.product_name
ORDER BY qty DESC
LIMIT 5");

$labels=[];
$data=[];
while($p=mysqli_fetch_assoc($pieProducts)){
    $labels[]=$p['product_name'];
    $data[]=$p['qty'];
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Cashier Dashboard</title>

<link rel="stylesheet" href="dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body{background:#f4f6f9;font-family:Segoe UI;}

.cards{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(230px,1fr));
gap:18px;
}

.card{
background:white;
padding:20px;
border-radius:14px;
box-shadow:0 6px 18px rgba(0,0,0,0.08);
display:flex;
gap:15px;
align-items:center;
}
.card p{font-size:22px;font-weight:bold;margin:0;}

.chart-box{
background:white;
padding:30px;
margin-top:25px;
border-radius:18px;
box-shadow:0 12px 30px rgba(0,0,0,0.10);
}

.chart-wrapper{
max-width:520px;
height:350px;
margin:0 auto;
}

.chartBtn{
background:#ff7a18;
color:white;
border:none;
padding:6px 12px;
margin-left:5px;
border-radius:6px;
cursor:pointer;
}
</style>

</head>

<body>

<?php include 'topbar.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main-content">

<h2>Welcome, <?php echo htmlspecialchars($cashierName); ?> 👋</h2>
<p style="color:#666;margin-bottom:25px;">
Here is your sales performance today.
</p>

<div class="cards">

<div class="card">
<i class="fa-solid fa-cash-register fa-2x" style="color:#ff7a18;"></i>
<div>
<h3>My Sales Today</h3>
<p>RWF <?php echo number_format($todaySales); ?></p>
</div>
</div>

<div class="card">
<i class="fa-solid fa-receipt fa-2x" style="color:#ff7a18;"></i>
<div>
<h3>Receipts Today</h3>
<p><?php echo $totalReceipts; ?></p>
</div>
</div>

<div class="card">
<i class="fa-solid fa-box fa-2x" style="color:#ff7a18;"></i>
<div>
<h3>Items Sold</h3>
<p><?php echo $itemsSold ?: 0; ?></p>
</div>
</div>

<div class="card">
<i class="fa-solid fa-chart-line fa-2x" style="color:#ff7a18;"></i>
<div>
<h3>Average Sale</h3>
<p>RWF <?php echo number_format($avgSale); ?></p>
</div>
</div>

<div class="card">
<i class="fa-solid fa-sack-dollar fa-2x" style="color:#ff7a18;"></i>
<div>
<h3>Total Sales So Far</h3>
<p>RWF <?php echo number_format($totalSalesEver); ?></p>
</div>
</div>

</div>

<!-- SALES CHART -->

<div class="chart-box">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
<h3 id="chartTitle">Sales Analytics</h3>
<div>
<button class="chartBtn" onclick="loadChart('weekly')">Weekly</button>
<button class="chartBtn" onclick="loadChart('monthly')">Monthly</button>
<button class="chartBtn" onclick="loadChart('yearly')">Yearly</button>
</div>
</div>
<canvas id="salesChart" height="120"></canvas>
</div>

<!-- PIE CHART -->

<div class="chart-box">
<h3>Top Selling Products Today</h3>
<div class="chart-wrapper">
<canvas id="productChart"></canvas>
</div>
</div>

</div>

<script>

let chart;

function money(v){
return "RWF " + Number(v).toLocaleString();
}

function getMonthName(){
const date = new Date();
return date.toLocaleString('default', { month: 'long', year:'numeric' });
}

function getYear(){
return new Date().getFullYear();
}

async function loadChart(type='weekly'){

const title = document.getElementById("chartTitle");

if(type==='weekly'){
title.innerText = "This Week Sales";
}
else if(type==='monthly'){
title.innerText = getMonthName() + " Sales";
}
else{
title.innerText = getYear() + " Sales";
}

const res = await fetch("cashier_chart_data.php?type="+type);
const result = await res.json();

const labels = result.map(r=>r.label);
const values = result.map(r=>parseFloat(r.total));

if(chart) chart.destroy();

chart = new Chart(document.getElementById('salesChart'),{
type:'line',
data:{
labels:labels,
datasets:[{
label:"Revenue",
data:values,
borderColor:'#ff7a18',
backgroundColor:'rgba(255,122,24,0.15)',
fill:true,
tension:0.45,
pointRadius:4
}]
},
options:{
responsive:true,
plugins:{
legend:{display:false},
tooltip:{callbacks:{label:(ctx)=>money(ctx.raw)}}
},
scales:{
y:{ticks:{callback:(v)=>money(v)}}
}
}
});

}

loadChart('weekly');

</script>

<script>

let productLabels = <?php echo json_encode($labels); ?>;
let productData   = <?php echo json_encode($data); ?>;

if(!productLabels || productLabels.length === 0){

productLabels = ["No Sales Today"];
productData = [1];

new Chart(document.getElementById('productChart'),{
type:'doughnut',
data:{
labels:productLabels,
datasets:[{
data:productData,
backgroundColor:['#e0e0e0']
}]
},
options:{
plugins:{
legend:{display:false},
tooltip:{enabled:false},
title:{
display:true,
text:"No completed sales today"
}
}
}
});

}else{

new Chart(document.getElementById('productChart'),{
type:'doughnut',
data:{
labels:productLabels,
datasets:[{
data:productData,
backgroundColor:[
'#ff7a18',
'#ff9a4d',
'#ffc107',
'#28a745',
'#17a2b8'
],
borderWidth:3,
borderColor:'#ffffff',
hoverOffset:20
}]
},
options:{
responsive:true,
maintainAspectRatio:false,
cutout:'65%',
plugins:{
legend:{position:'bottom'},
tooltip:{
callbacks:{
label:(ctx)=> ctx.label + " : " + ctx.raw + " items"
}
}
}
}
});

}

</script>

</body>
</html>