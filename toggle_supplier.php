<?php
session_start();
include "db.php";

/* ONLY ADMIN */
if(!isset($_SESSION['role']) || $_SESSION['role']!='admin'){
    header("Location:index.php");
    exit;
}

if(!isset($_GET['id'])){
    header("Location:suppliers.php");
    exit;
}

$id = intval($_GET['id']);

/* GET CURRENT STATUS */
$get = mysqli_fetch_assoc(mysqli_query($conn,"SELECT status FROM suppliers WHERE id=$id"));

if(!$get){
    header("Location:suppliers.php");
    exit;
}

/* SWITCH STATUS */
$newStatus = ($get['status']=='active') ? 'inactive' : 'active';

mysqli_query($conn,"UPDATE suppliers SET status='$newStatus' WHERE id=$id");

header("Location:suppliers.php");
exit;
?>