<?php
session_start();
include "db.php";

/* ONLY ADMIN + MANAGER */
if(!isset($_SESSION['role']) || ($_SESSION['role']!='admin' && $_SESSION['role']!='manager')){
    header("Location:index.php");
    exit();
}

$isAdmin = ($_SESSION['role']=='admin');

$suppliers = mysqli_query($conn,"SELECT * FROM suppliers ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
<title>Suppliers | Retail System</title>

<link rel="stylesheet" href="dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

.main-content{
padding:25px;
padding-top:100px;
background:#f4f6f9;
min-height:100vh;
}

.card{
background:white;
padding:25px;
border-radius:14px;
box-shadow:0 10px 25px rgba(0,0,0,0.08);
}

.add-btn{
background:#ff7a18;
color:white;
padding:10px 18px;
border-radius:8px;
text-decoration:none;
display:inline-block;
margin-bottom:15px;
}

.add-btn:hover{opacity:0.9;}

table{
width:100%;
border-collapse:collapse;
}

th{
background:#ff7a18;
color:white;
padding:14px;
text-align:left;
}

td{
padding:12px;
border-bottom:1px solid #eee;
}

.badge{
padding:5px 10px;
border-radius:6px;
font-size:12px;
font-weight:bold;
}

.active{background:#d4edda;color:#155724;}
.inactive{background:#f8d7da;color:#721c24;}

.action-btn{
padding:6px 12px;
border-radius:6px;
color:white;
text-decoration:none;
font-size:13px;
display:inline-flex;
align-items:center;
gap:5px;
margin-right:6px;
}

.toggle{background:#2e7d32;}

.note{
background:#fff3e0;
padding:12px;
border-radius:8px;
margin-bottom:15px;
color:#e65100;
font-size:14px;
}
.edit{
background:blue;
margin-right:6px;
}

</style>
</head>

<body>

<?php include "topbar.php"; ?>
<?php include "sidebar.php"; ?>

<div class="main-content">

<h2><i class="fa-solid fa-truck-field"></i> Suppliers</h2>

<?php if(!$isAdmin): ?>
<div class="note">
<i class="fa-solid fa-circle-info"></i>
You have view-only access. Only admin can manage suppliers.
</div>
<?php endif; ?>

<?php if($isAdmin): ?>
<a class="add-btn" href="add-supplier.php">
<i class="fa-solid fa-plus"></i> Add Supplier
</a>
<?php endif; ?>

<div class="card">

<table>
<thead>
<tr>
<th>ID</th>
<th>Name</th>
<th>Company</th>
<th>Phone</th>
<th>Email</th>
<th>Status</th>
<?php if($isAdmin): ?>
<th>Action</th>
<?php endif; ?>
</tr>
</thead>

<tbody>

<?php while($row=mysqli_fetch_assoc($suppliers)){ ?>
<tr>

<td><?= $row['id']; ?></td>
<td><?= htmlspecialchars($row['supplier_name']); ?></td>
<td><?= htmlspecialchars($row['company_name']); ?></td>
<td><?= htmlspecialchars($row['phone']); ?></td>
<td><?= htmlspecialchars($row['email']); ?></td>

<td>
<?php if($row['status']=='active'){ ?>
<span class="badge active">Active</span>
<?php } else { ?>
<span class="badge inactive">Inactive</span>
<?php } ?>
</td>

<?php if($isAdmin): ?>
<td>

<a class="action-btn edit"
href="edit-supplier.php?id=<?= $row['id'] ?>">
<i class="fa-solid fa-pen"></i> Edit
</a>

<a class="action-btn toggle"
href="toggle_supplier.php?id=<?= $row['id'] ?>">
<i class="fa-solid fa-power-off"></i>
<?= ($row['status']=='active') ? 'Deactivate' : 'Activate'; ?>
</a>

</td>
<?php endif; ?>

</tr>
<?php } ?>

</tbody>
</table>

</div>
</div>
</body>
</html>