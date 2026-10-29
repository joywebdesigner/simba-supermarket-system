<?php
session_start();
include "db.php";
date_default_timezone_set('Africa/Kigali');

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'cashier'){
    header("Location: index.php");
    exit();
}

$cashier_id = $_SESSION['user_id'];

$date = $_GET['date'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

//  PAYMENT SUMMARY
$cash = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT SUM(total_amount) as total 
FROM sales 
WHERE DATE(sale_date)='$date'
AND payment_method='Cash'
AND status IN ('completed','closed')
AND cashier_id='$cashier_id'
"))['total'] ?? 0;

$momo = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT SUM(total_amount) as total 
FROM sales 
WHERE DATE(sale_date)='$date'
AND payment_method='Mobile Money'
AND status IN ('completed','closed')
AND cashier_id='$cashier_id'
"))['total'] ?? 0;

$totalSales = $cash + $momo;

$totalTransactions = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as total 
FROM sales 
WHERE DATE(sale_date)='$date'
AND status IN ('completed','closed')
AND cashier_id='$cashier_id'
"))['total'] ?? 0;

$totalItems = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT SUM(quantity) as total 
FROM sale_items 
JOIN sales ON sale_items.sale_id = sales.id
WHERE DATE(sales.sale_date)='$date'
AND sales.status IN ('completed','closed')
AND sales.cashier_id='$cashier_id'
"))['total'] ?? 0;

// SALES QUERY
$condition = "
DATE(sale_date)='$date'
AND cashier_id='$cashier_id'
";

if($search != ""){
    $condition .= " AND id = ".intval($search);
}

$sales = mysqli_query($conn,"
SELECT * FROM sales 
WHERE $condition
ORDER BY sale_date DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Daily Report</title>

<link rel="stylesheet" href="dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

/* FIX HEADER VISIBILITY */
.main-content{
    margin-left:260px;
    padding:85px 40px 40px; /* 🔥 FIXED */
}

/* HEADER */
.page-title{
    display:flex;
    align-items:center;
    gap:10px;
    font-size:24px;
    font-weight:600;
    margin-bottom:20px;
}

/* CARDS */
.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
    gap:15px;
    margin-bottom:20px;
}

.card{
    background:white;
    padding:20px;
    border-radius:14px;
    box-shadow:0 10px 25px rgba(0,0,0,0.08);
    display:flex;
    align-items:center;
    gap:15px;
}

/* ICON BOX */
.icon-box{
    width:45px;
    height:45px;
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
    font-size:18px;
}

/* DIFFERENT COLORS */
.bg1{background:#ff7a18;}
.bg2{background:#3b82f6;}
.bg3{background:#22c55e;}
.bg4{background:#10b981;}
.bg5{background:#8b5cf6;}

/* FILTER */
.filter-bar{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-bottom:15px;
}

.filter-bar input{
    padding:8px;
    border-radius:8px;
    border:1px solid #ccc;
}

.btn{
    padding:8px 14px;
    border:none;
    border-radius:8px;
    color:white;
    cursor:pointer;
}

.search-btn{background:#ff7a18;}
.reset-btn{background:#777;}
.print-btn{background:#2e7d32;}

/* TABLE */
.table-container{
    background:white;
    border-radius:14px;
    overflow:hidden;
    box-shadow:0 10px 25px rgba(0,0,0,0.08);
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#ff7a18;
    color:white;
    padding:12px;
    text-align:left;
}

td{
    padding:12px;
    border-bottom:1px solid #eee;
}

tr:hover{
    background:#fafafa;
}

/* PRODUCTS */
.product{
    font-size:13px;
    margin-bottom:4px;
}
.page-title{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
    flex-wrap:wrap;
}

.title-left{
    display:flex;
    align-items:center;
    gap:10px;
    font-size:24px;
    font-weight:600;
    color:#ff7a18; /* 🔥 ORANGE */
}

.title-left i{
    color:#ff7a18;
}

.title-date{
    background:#fff3e6;
    color:#ff7a18;
    padding:6px 12px;
    border-radius:8px;
    font-size:24px;
    font-weight:500;
}

/* STATUS */
.badge{
    padding:5px 10px;
    border-radius:6px;
    font-size:12px;
    font-weight:bold;
}

.completed{background:#d4edda;color:#155724;}
.voided{background:#f8d7da;color:#721c24;}

@page{
    size:A4;
    margin:10mm;
}

@media print {

    html, body{
        width:100%;
        margin:0;
        padding:0;
        background:white;
    }

    /* hide sidebar/topbar only */
    .sidebar, .topbar, .filter-bar, .btn{
        display:none !important;
    }

    /*  FIX HALF PAGE ISSUE */
    .main-content{
        margin:0 !important;
        padding:20px !important;
        width:100% !important;
    }

    /* KEEP YOUR DESIGN */
    table{
        width:100% !important;
    }

    /* KEEP COLORS */
    *{
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

}@page{
    size:A4;
    margin:10mm;
}

@media print {

    html, body{
        width:100%;
        margin:0;
        padding:0;
        background:white;
    }

    /* hide sidebar/topbar only */
    .sidebar, .topbar, .filter-bar, .btn{
        display:none !important;
    }

    /* 🔥 FIX HALF PAGE ISSUE */
    .main-content{
        margin:0 !important;
        padding:20px !important;
        width:100% !important;
    }

    /* KEEP YOUR DESIGN */
    table{
        width:100% !important;
    }

    /* KEEP COLORS */
    *{
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

}@page{
    size:A4;
    margin:10mm;
}

@media print {

    html, body{
        width:100%;
        margin:0;
        padding:0;
        background:white;
    }

    /* hide sidebar/topbar only */
    .sidebar, .topbar, .filter-bar, .btn{
        display:none !important;
    }

    /* 🔥 FIX HALF PAGE ISSUE */
    .main-content{
        margin:0 !important;
        padding:20px !important;
        width:100% !important;
    }

    /* KEEP YOUR DESIGN */
    table{
        width:100% !important;
    }

    /* KEEP COLORS */
    *{
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

}@page{
    size:A4;
    margin:10mm;
}
/* hidden on normal screen */
.print-header{
    display:none;
}

/* ONLY show when printing */
@media print {

    .print-header{
        display:block !important;
        text-align:center;
        margin-bottom:15px;
    }

    .print-header img{
        width:70px;
        margin-bottom:5px;
    }


    /* hide sidebar/topbar only */
    .sidebar, .topbar, .filter-bar, .btn{
        display:none !important;
    }

    /* 🔥 FIX HALF PAGE ISSUE */
    .main-content{
        margin:0 !important;
        padding:20px !important;
        width:100% !important;
    }

    /* KEEP YOUR DESIGN */
    table{
        width:100% !important;
    }

    /* KEEP COLORS */
    *{
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }


}

</style>
</head>

<body>

<?php include "topbar.php"; ?>
<?php include "sidebar.php"; ?>

<div class="main-content">
    <div class="print-header">
    <img src="images/simba logo2.png" alt="logo">

    <h3>My Daily Report</h3>

    <p>
        Cashier: <?= $_SESSION['fullname'] ?? '' ?> 
        (<?= $_SESSION['username'] ?? '' ?>)
    </p>

    <p>As at <?= date('d M Y') ?></p>
</div>

<!-- HEADER -->
<div class="page-title">
    <div class="title-left">
        <i class="fa-solid fa-chart-line"></i>
        <b><span>Daily Report</span></b><br><br><br><br>
    </div>

    <b><div class="title-date">
        As at <?= date('d M Y') ?>
    </div></b>
</div>

<!-- FILTER -->
<div class="filter-bar">
<form method="GET">
<input type="number" name="search" placeholder="Sale ID"
value="<?= $_GET['search'] ?? '' ?>">



<button class="btn search-btn">Search</button>
<button type="button" class="btn reset-btn" onclick="window.location='daily_report.php'">Reset</button>
</form>

<button onclick="window.print()" class="btn print-btn">Print</button>
</div>

<!-- SUMMARY -->
<div class="cards">

<div class="card">
<div class="icon-box bg1"><i class="fa-solid fa-money-bill"></i></div>
<div>Total Sales<br><b>RWF <?= number_format($totalSales) ?></b></div>
</div>

<div class="card">
<div class="icon-box bg2"><i class="fa-solid fa-receipt"></i></div>
<div>Transactions<br><b><?= $totalTransactions ?></b></div>
</div>

<div class="card">
<div class="icon-box bg3"><i class="fa-solid fa-box"></i></div>
<div>Items Sold<br><b><?= number_format($totalItems) ?></b></div>
</div>

<div class="card">
<div class="icon-box bg4"><i class="fa-solid fa-coins"></i></div>
<div>Cash<br><b>RWF <?= number_format($cash) ?></b></div>
</div>

<div class="card">
<div class="icon-box bg5"><i class="fa-solid fa-mobile"></i></div>
<div>MoMo<br><b>RWF <?= number_format($momo) ?></b></div>
</div>

</div>

<!-- TABLE -->
<div class="table-container">

<table>

<tr>
<th>ID</th>
<th>Time</th>
<th>Products</th>
<th>Total</th>
<th>Payment</th>
<th>Status</th>
</tr>

<?php while($row = mysqli_fetch_assoc($sales)) { ?>

<?php
$items = mysqli_query($conn,"
SELECT p.product_name, si.quantity, si.price
FROM sale_items si
JOIN products p ON p.id = si.product_id
WHERE si.sale_id = ".$row['id']);
?>

<tr>
<td>#<?= $row['id'] ?></td>
<td><?= date('H:i:s', strtotime($row['sale_date'])) ?></td>

<td>
<?php while($item=mysqli_fetch_assoc($items)){ ?>
<div class="product">
<?= $item['product_name'] ?> 
(<?= $item['quantity'] ?> × <?= number_format($item['price']) ?>)
</div>
<?php } ?>
</td>

<td><b><?= number_format($row['total_amount']) ?> RWF</b></td>
<td><?= $row['payment_method'] ?></td>

<td>
<?php if($row['status']=="completed"){ ?>
<span class="badge completed">Completed</span>
<?php } else { ?>
<span class="badge voided">Voided</span>
<?php } ?>
</td>

</tr>

<?php } ?>

</table>

</div>

</div>

</body>
</html>