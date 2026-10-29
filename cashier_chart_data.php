<?php
session_start();
include "db.php";

$cashier = $_SESSION['user_id'];
$type = $_GET['type'] ?? 'weekly';

$data=[];

/* ================= WEEKLY (MONDAY → SUNDAY) ================= */
if($type=='weekly'){

// find monday of current week
$monday = date('Y-m-d', strtotime('monday this week'));
$sunday = date('Y-m-d', strtotime('sunday this week'));

for($i=0;$i<7;$i++){

    $current = date('Y-m-d', strtotime("$monday +$i day"));

    $query=mysqli_query($conn,"
    SELECT SUM(si.quantity*si.price) total
    FROM sale_items si
    JOIN sales s ON s.id=si.sale_id
    WHERE s.cashier_id=$cashier
    AND s.status='completed'
    AND DATE(s.sale_date)='$current'");

    $row=mysqli_fetch_assoc($query);

    $data[]=[
        'label'=>date('D',strtotime($current)),
        'total'=>(float)($row['total']??0)
    ];
}

}


/* ================= MONTHLY (CURRENT MONTH DAYS) ================= */
elseif($type=='monthly'){

$year = date('Y');
$month = date('m');
$daysInMonth = date('t'); // total days in this month

for($i=1;$i<=$daysInMonth;$i++){

    $day = sprintf("%02d",$i);
    $date = "$year-$month-$day";

    $query=mysqli_query($conn,"
    SELECT SUM(si.quantity*si.price) total
    FROM sale_items si
    JOIN sales s ON s.id=si.sale_id
    WHERE s.cashier_id=$cashier
    AND s.status='completed'
    AND DATE(s.sale_date)='$date'");

    $row=mysqli_fetch_assoc($query);

    $data[]=[
        'label'=>$i,
        'total'=>(float)($row['total']??0)
    ];
}

}


/* ================= YEARLY (JAN → DEC) ================= */
else{

$year=date('Y');

for($i=1;$i<=12;$i++){

    $month = sprintf("%02d",$i);

    $query=mysqli_query($conn,"
    SELECT SUM(si.quantity*si.price) total
    FROM sale_items si
    JOIN sales s ON s.id=si.sale_id
    WHERE s.cashier_id=$cashier
    AND s.status='completed'
    AND YEAR(s.sale_date)=$year
    AND MONTH(s.sale_date)=$i");

    $row=mysqli_fetch_assoc($query);

    $data[]=[
        'label'=>date('M',mktime(0,0,0,$i,1)),
        'total'=>(float)($row['total']??0)
    ];
}

}

echo json_encode($data);
?>