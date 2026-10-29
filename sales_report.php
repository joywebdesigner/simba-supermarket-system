<?php
session_start();
include "db.php";

/* ADMIN + MANAGER ONLY */
if(!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','manager'])){
    header("Location:index.php");
    exit;
}

/* DATE FILTER */
$from = $_GET['from'] ?? '';
$to   = $_GET['to'] ?? '';

$dateFilter = "";

if($from && $to){
    $dateFilter = " AND DATE(sale_date) BETWEEN '$from' AND '$to' ";
}

/* ===== SUMMARY ===== */

$totalSales = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COALESCE(SUM(total_amount),0) total
FROM sales
WHERE status='completed'
$dateFilter
"))['total'];

$totalReceipts = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) cnt
FROM sales
WHERE status='completed'
$dateFilter
"))['cnt'];

$cashSales = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COALESCE(SUM(total_amount),0) total
FROM sales
WHERE status='completed'
AND payment_method='Cash'
$dateFilter
"))['total'];

$momSales = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COALESCE(SUM(total_amount),0) total
FROM sales
WHERE status='completed'
AND payment_method='Mobile Money'
$dateFilter
"))['total'];

/* ===== DETAILED REPORT ===== */

$report = mysqli_query($conn,"
SELECT 
    s.id as receipt,
    s.sale_date,
    s.payment_method,
    p.product_name,
    p.image,
    p.unit,
    si.quantity,
    si.price,
    (si.quantity * si.price) as subtotal
FROM sale_items si
JOIN sales s ON s.id = si.sale_id
JOIN products p ON p.id = si.product_id
WHERE s.status='completed'
$dateFilter
ORDER BY s.sale_date DESC, s.id DESC
");
?>
<!DOCTYPE html>
<html>
<head>
<title>Sales Report</title>

<link rel="stylesheet" href="dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.main-content{padding:25px;padding-top:100px;background:#f4f6f9;min-height:100vh;}

.product-cell{display:flex;align-items:center;gap:8px;}
.product-img{width:35px;height:35px;border-radius:6px;object-fit:cover;border:1px solid #ddd;}

.cards{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
gap:18px;
margin-bottom:25px;
}

.card{
background:white;
padding:20px;
border-radius:14px;
box-shadow:0 10px 25px rgba(0,0,0,0.08);
}

.card h3{margin:0;font-size:22px;color:#ff7a18;}
.card span{color:#555;font-size:14px;}

.filter{
background:white;
padding:15px;
border-radius:12px;
box-shadow:0 8px 20px rgba(0,0,0,0.08);
margin-bottom:20px;
display:flex;
gap:10px;
align-items:center;
flex-wrap:wrap;
}

.filter input,.filter button{
padding:8px 12px;
border-radius:8px;
border:1px solid #ccc;
}

.filter button{
background:#ff7a18;
color:white;
border:none;
cursor:pointer;
}

.reset-btn{
background:#777;
color:white;
padding:8px 14px;
border-radius:8px;
text-decoration:none;
}

.print-btn{
background:#2e7d32;
color:white;
padding:8px 14px;
border-radius:8px;
border:none;
cursor:pointer;
}

.table-card{
background:white;
border-radius:14px;
box-shadow:0 10px 25px rgba(0,0,0,0.08);
overflow:hidden;
}

table{width:100%;border-collapse:collapse;}
th{background:#ff7a18;color:white;padding:12px;text-align:left;}
td{padding:12px;border-bottom:1px solid #eee;vertical-align:middle;}
tr:hover{background:#fff4ea;}

td{white-space:nowrap;}

.print-footer{display:none;margin-top:70px;}
.sign-row{display:flex;justify-content:space-between;margin-top:60px;}
.sign-box{width:40%;text-align:center;}
.sign-line{border-top:2px solid #000;margin-bottom:8px;height:40px;}

@media print{
.sidebar,.topbar,.filter{display:none !important;}
.main-content{margin:0;padding:0;width:100%;}
body{background:white;margin:0;padding:0;}
button,a{display:none !important;}
th{background:#ddd;color:black;}
}
</style>
</head>

<body>

<?php include "topbar.php"; ?>
<?php include "sidebar.php"; ?>

<div class="main-content">

<h2><i class="fa-solid fa-chart-line"></i> Sales Report</h2>

<form class="filter" method="GET">
<label>From:</label>
<input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
<label>To:</label>
<input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
<button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
<a href="sales_report.php" class="reset-btn">Reset</a>
<button type="button" class="print-btn" onclick="window.print()">Print</button>
</form>

<div class="cards">
<div class="card"><h3><?= number_format($totalSales) ?> RWF</h3><span>Total Sales</span></div>
<div class="card"><h3><?= $totalReceipts ?></h3><span>Transactions</span></div>
<div class="card"><h3><?= number_format($cashSales) ?> RWF</h3><span>Cash</span></div>
<div class="card"><h3><?= number_format($momSales) ?> RWF</h3><span>Mobile Money</span></div>
</div>

<div class="table-card">
<table>

<tr>
<th>Receipt</th>
<th>Date</th>
<th>Product</th>
<th style="text-align:center;">Qty</th>
<th style="text-align:right;">Unit Price</th>
<th style="text-align:right;">Subtotal</th>
<th>Payment</th>
</tr>

<?php
$rows = [];
$grandTotal = 0;

while($row = mysqli_fetch_assoc($report)){
    $rows[$row['receipt']][] = $row;
}

foreach($rows as $receiptId => $items){

    $rowspan = count($items);
    $first = true;
    $receiptTotal = 0;

    foreach($items as $item){

        $receiptTotal += $item['subtotal'];
        $grandTotal += $item['subtotal'];

        echo "<tr>";

        if($first){
            echo "<td rowspan='$rowspan'><b>#$receiptId</b></td>
                  <td rowspan='$rowspan'>".date("d M Y H:i",strtotime($item['sale_date']))."</td>";
        }

        echo "<td>
        <div class='product-cell'>
        <img src='images/products/".($item['image'] ?: 'no-image.png')."' class='product-img'>
        ".htmlspecialchars($item['product_name'])."
        </div>
        </td>";

        echo "<td style='text-align:center;font-weight:600;'>"
            .$item['quantity']." 
            <span style='color:#777;font-size:12px;'>".$item['unit']."</span>
        </td>";

        echo "<td style='text-align:right;font-weight:600;'>"
        .number_format($item['price'])." RWF</td>";

        echo "<td style='text-align:right;font-weight:600;'>"
        .number_format($item['subtotal'])." RWF</td>";

        if($first){
            echo "<td rowspan='$rowspan'>{$item['payment_method']}</td>";
        }

        echo "</tr>";

        $first = false;
    }

    echo "<tr style='background:#fff3e6;font-weight:bold;'>
            <td colspan='5' style='text-align:right;color:#ff7a18;'>Receipt Total</td>
            <td style='text-align:right;color:#ff7a18;'>"
            .number_format($receiptTotal)." RWF
            </td>
            <td></td>
          </tr>";
}
?>

<tr style="background:#ff7a18;color:white;font-weight:bold;">
<td colspan="5" style="text-align:right;">TOTAL SALES</td>
<td style="text-align:right;"><?= number_format($grandTotal) ?> RWF</td>
<td></td>
</tr>

</table>
</div>

</div>
</body>
</html>