<?php
session_start();
include "db.php";

/* ONLY ADMIN */
if(!isset($_SESSION['role']) || $_SESSION['role']!='admin'){
    header("Location:index.php");
    exit();
}

/* GET SUPPLIER */
if(!isset($_GET['id'])){
    header("Location:suppliers.php");
    exit();
}

$id = intval($_GET['id']);

$result = mysqli_query($conn,"SELECT * FROM suppliers WHERE id=$id");
$supplier = mysqli_fetch_assoc($result);

if(!$supplier){
    header("Location:suppliers.php");
    exit();
}

/* UPDATE SUPPLIER */
if(isset($_POST['update_supplier'])){

$name = mysqli_real_escape_string($conn,$_POST['supplier_name']);
$company = mysqli_real_escape_string($conn,$_POST['company_name']);
$phone = mysqli_real_escape_string($conn,$_POST['phone']);
$email = mysqli_real_escape_string($conn,$_POST['email']);

mysqli_query($conn,"
UPDATE suppliers SET
supplier_name='$name',
company_name='$company',
phone='$phone',
email='$email'
WHERE id=$id
");

header("Location:suppliers.php");
exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Supplier | Retail System</title>

<link rel="stylesheet" href="dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

.main-content{
margin-left:260px;
padding:40px;
padding-top:100px;
background:#f4f6f9;
min-height:100vh;
}

.breadcrumb{
font-size:18px;
margin-bottom:10px;
color:#6b7280;
font-weight:500;
}

.breadcrumb a{
text-decoration:none;
color:#ff7a18;
font-weight:700;
}

.breadcrumb span{
color:#374151;
font-weight:600;
}

.page-title{
margin-bottom:30px;
color:#ff7a18;
}

.form-container{
display:flex;
justify-content:center;
}

.card{
background:white;
padding:35px;
border-radius:14px;
box-shadow:0 10px 25px rgba(0,0,0,0.08);
width:100%;
max-width:600px;
}

form{
display:flex;
flex-direction:column;
gap:16px;
}

label{
font-weight:600;
font-size:14px;
color:#374151;
}

input{
padding:12px;
border-radius:8px;
border:1px solid #ddd;
font-size:14px;
}

.button-group{
display:flex;
gap:12px;
margin-top:10px;
}

.update-btn{
background:#ff7a18;
color:white;
border:none;
padding:12px 20px;
border-radius:8px;
cursor:pointer;
font-size:14px;
font-weight:600;
}

.update-btn:hover{
opacity:0.9;
}

.cancel-btn{
background:#e5e7eb;
color:#333;
text-decoration:none;
padding:12px 20px;
border-radius:8px;
font-weight:600;
font-size:14px;
}

.cancel-btn:hover{
background:#d1d5db;
}

</style>
</head>

<body>

<?php include "topbar.php"; ?>
<?php include "sidebar.php"; ?>

<div class="main-content">

<div class="breadcrumb">
<a href="suppliers.php">Suppliers</a> / <span>Edit Supplier</span>
</div>

<div class="form-container">

<div class="card">

<form method="POST">

<h2 class="page-title">
<i class="fa-solid fa-pen"></i> Edit Supplier
</h2>

<label>Supplier Name</label>
<input 
type="text" 
name="supplier_name"
value="<?= htmlspecialchars($supplier['supplier_name']); ?>"
pattern="[A-Za-z ]+"
title="Only letters allowed"
oninput="this.value = this.value.replace(/[^A-Za-z ]/g, '')"
required>

<label>Company Name</label>
<input 
type="text" 
name="company_name"
value="<?= htmlspecialchars($supplier['company_name']); ?>"
pattern="[A-Za-z0-9 ]+"
title="Only letters and numbers allowed"
oninput="this.value = this.value.replace(/[^A-Za-z0-9 ]/g, '')"
required>

<label>Phone</label>
<input 
type="tel" 
name="phone"
value="<?= htmlspecialchars($supplier['phone']); ?>"
pattern="[0-9]{10,15}"
title="Enter a valid phone number"
oninput="this.value = this.value.replace(/[^0-9]/g, '')"
required>

<label>Email</label>
<input 
type="email" 
name="email"
value="<?= htmlspecialchars($supplier['email']); ?>">

<div class="button-group">

<button type="submit" name="update_supplier" class="update-btn">
<i class="fa-solid fa-save"></i> Update Supplier
</button>

<a href="suppliers.php" class="cancel-btn">
Cancel
</a>

</div>

</form>

</div>

</div>

</div>

</body>
</html>