<?php
session_start();
include "db.php";

if(!isset($_SESSION['role']) || $_SESSION['role']!='manager'){
    header("Location:index.php");
    exit;
}

$manager_id = $_SESSION['user_id'];

/* MANAGER NAME */
$qUser = mysqli_query($conn,"SELECT fullname FROM users WHERE id=$manager_id");
$user = mysqli_fetch_assoc($qUser);
$managerName = $user['fullname'];

/* TODAY SALES */
$qToday = mysqli_query($conn,"
SELECT COALESCE(SUM(total_amount),0) total_sales,
COUNT(*) receipts
FROM sales
WHERE status='completed'
AND DATE(sale_date)=CURDATE()
");
$today = mysqli_fetch_assoc($qToday);
$todaySales = $today['total_sales'];
$receipts = $today['receipts'];

/* ITEMS SOLD TODAY */
$qItems = mysqli_query($conn,"
SELECT COALESCE(SUM(si.quantity),0) qty
FROM sale_items si
JOIN sales s ON s.id=si.sale_id
WHERE s.status='completed'
AND DATE(s.sale_date)=CURDATE()
");
$itemData = mysqli_fetch_assoc($qItems);
$itemsSold = $itemData['qty'];

/* INVENTORY STATUS */
$outStock = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) cnt FROM inventory WHERE quantity=0"))['cnt'];
$lowStock = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) cnt FROM inventory WHERE quantity>0 AND quantity<=min_quantity"))['cnt'];
$goodStock = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) cnt FROM inventory WHERE quantity>min_quantity"))['cnt'];
?>

<!DOCTYPE html>

<html>
<head>
<title>Manager Dashboard</title>

<link rel="stylesheet" href="dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

.cards{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(230px,1fr));
gap:18px;
margin-bottom:25px;
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

.card p{
font-size:22px;
font-weight:bold;
margin:0;
}

.chart-box{
background:white;
padding:25px;
border-radius:14px;
box-shadow:0 10px 25px rgba(0,0,0,0.08);
margin-top:25px;
}

.chartBtn{
background:#ff7a18;
color:white;
border:none;
padding:7px 14px;
margin-left:6px;
border-radius:7px;
cursor:pointer;
font-weight:600;
}

.chartBtn:hover{
background:#ff9a4d;
}

</style>

</head>

<body>

<?php include 'topbar.php'; ?>

<?php include 'sidebar.php'; ?>

<div class="main-content">

<h2>Welcome, <?php echo htmlspecialchars($managerName); ?> 👋</h2>

<p>Store performance overview</p>
<br>
<div class="cards">

<div class="card">
<i class="fa-solid fa-sack-dollar fa-2x" style="color:#ff7a18;"></i>
<div>
<h3>Today Sales</h3>
<p>RWF <?php echo number_format($todaySales); ?></p>
</div>
</div>

<div class="card">
<i class="fa-solid fa-receipt fa-2x" style="color:#ff7a18;"></i>
<div>
<h3>Transactions</h3>
<p><?php echo $receipts; ?></p>
</div>
</div>

<div class="card">
<i class="fa-solid fa-box fa-2x" style="color:#ff7a18;"></i>
<div>
<h3>Items Sold</h3>
<p><?php echo $itemsSold; ?></p>
</div>
</div>

<div class="card">
<i class="fa-solid fa-triangle-exclamation fa-2x" style="color:#e53935;"></i>
<div>
<h3>Low Stock</h3>
<p><?php echo $lowStock; ?></p>
</div>
</div>

<div class="card">
<i class="fa-solid fa-ban fa-2x" style="color:#b71c1c;"></i>
<div>
<h3>Out Of Stock</h3>
<p><?php echo $outStock; ?></p>
</div>
</div>

</div>

<div class="chart-box">

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
<h3 id="chartTitle">This Week Sales</h3>
<div>
<button class="chartBtn" onclick="loadChart('weekly')">Weekly</button>
<button class="chartBtn" onclick="loadChart('monthly')">Monthly</button>
<button class="chartBtn" onclick="loadChart('yearly')">Yearly</button>
</div>
</div>

<canvas id="salesChart" height="120"></canvas>

</div>

</div>
<script>

let chart;

function money(v){
    return "RWF " + Number(v).toLocaleString();
}

function monthName(){
    const d = new Date();
    return d.toLocaleString('default',{month:'long'});
}

function yearNow(){
    return new Date().getFullYear();
}

async function loadChart(type='weekly'){

    /* ===== CHANGE TITLE ===== */
    const title = document.getElementById("chartTitle");

    if(type === 'weekly'){
        title.innerText = "This Week Sales";
    }
    else if(type === 'monthly'){
        title.innerText = monthName() + " Sales";
    }
    else{
        title.innerText = yearNow() + " Sales";
    }

    /* ===== GET DATA ===== */
    const res = await fetch("manager_chart_data.php?type="+type);
    const result = await res.json();

    const labels = result.map(r => r.label);
    const values = result.map(r => parseFloat(r.total));

    if(chart) chart.destroy();

    chart = new Chart(document.getElementById('salesChart'),{
        type:'bar',
        data:{
            labels:labels,
            datasets:[{
                data:values,
                backgroundColor:'#1fb5ad',
                borderRadius:6,
                barThickness:26
            }]
        },
        options:{
            responsive:true,
            plugins:{
                legend:{display:false},
                tooltip:{
                    callbacks:{
                        label:(ctx)=>money(ctx.raw)
                    }
                }
            },
            scales:{
                x:{
                    grid:{display:false}
                },
                y:{
                    beginAtZero:true,
                    grid:{color:'#e3e3e3'},
                    ticks:{
                        callback:(v)=>money(v)
                    }
                }
            }
        }
    });

}

/* LOAD FIRST TIME */
loadChart('weekly');

</script>


</body>
</html>
