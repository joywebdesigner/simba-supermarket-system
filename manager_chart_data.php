<?php
session_start();
include "db.php";
header('Content-Type: application/json');

$type = $_GET['type'] ?? 'weekly';
$data=[];

/* WEEKLY */
if($type=='weekly'){

$days=['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];

for($i=0;$i<7;$i++){

$date=date('Y-m-d',strtotime("monday this week +$i day"));

$q=mysqli_query($conn,"
SELECT COALESCE(SUM(total_amount),0) total
FROM sales
WHERE status='completed'
AND DATE(sale_date)='$date'
");

$row=mysqli_fetch_assoc($q);

$data[]=["label"=>$days[$i],"total"=>$row['total']];
}
}

/* MONTHLY */
else if($type=='monthly'){

$year=date('Y');
$month=date('m');
$days=date('t');

for($d=1;$d<=$days;$d++){

$date="$year-$month-".str_pad($d,2,'0',STR_PAD_LEFT);

$q=mysqli_query($conn,"
SELECT COALESCE(SUM(total_amount),0) total
FROM sales
WHERE status='completed'
AND DATE(sale_date)='$date'
");

$row=mysqli_fetch_assoc($q);

$data[]=["label"=>$d,"total"=>$row['total']];
}
}

/* YEARLY */
else{

$months=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

for($m=1;$m<=12;$m++){

$q=mysqli_query($conn,"
SELECT COALESCE(SUM(total_amount),0) total
FROM sales
WHERE status='completed'
AND MONTH(sale_date)=$m
AND YEAR(sale_date)=YEAR(CURDATE())
");

$row=mysqli_fetch_assoc($q);

$data[]=["label"=>$months[$m-1],"total"=>$row['total']];
}
}

echo json_encode($data);
