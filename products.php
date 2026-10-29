<?php
session_start();
include "db.php";

if (!isset($_SESSION['role'])) {
    header("Location: index.php");
    exit;
}

$role = $_SESSION['role'];

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$query = "SELECT 
            p.*,
            i.quantity AS available_qty,
            u.fullname AS updated_by_name,
            u.role AS updated_by_role
          FROM products p
          LEFT JOIN inventory i ON p.id = i.product_id
          LEFT JOIN users u ON p.updated_by = u.id
          WHERE 1";

$params = [];

if ($search) {
    $query .= " AND product_name LIKE ?";
    $params[] = "%$search%";
}

if ($category) {
    $query .= " AND category=?";
    $params[] = $category;
}

$stmt = $conn->prepare($query);
if ($params) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$categories_res = $conn->query("SELECT DISTINCT category FROM products ORDER BY category ASC");
$categories = [];
while ($row = $categories_res->fetch_assoc()) {
    $categories[] = $row['category'];
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Products | Retail System</title>

<link rel="stylesheet" href="dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* KEEP YOUR STYLE EXACTLY */
.main-content { padding: 30px; }
h2 { color:#2a5298; margin-bottom: 15px; }

.breadcrumb { font-size:14px; margin-bottom:20px; }
.breadcrumb a { color:#2a5298; text-decoration:none; margin-right:5px; }
.breadcrumb span { color:#555; margin-right:5px; }

.table-controls { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:15px; align-items:center; }
.table-controls input, .table-controls select { padding:10px; border-radius:6px; border:1px solid #ccc; font-size:14px; }

.table-controls button { background:#ff7a18; color:#fff; border:none; padding:10px 18px; border-radius:6px; cursor:pointer; display:flex; align-items:center; gap:5px;}
.table-controls button:hover { background:#ff9f1c; }

.btn-add { background:#ff7a18; color:#fff; padding:10px 18px; border-radius:6px; text-decoration:none; font-weight:600; display:flex; align-items:center; gap:5px; }
.btn-add:hover { background:#ff9f1c; }

table { width:100%; border-collapse:collapse; }
th, td { padding:12px 15px; text-align:left; }
th { background:#ff7a18; color:#fff; text-transform:uppercase; }

tr:nth-child(even) { background:#f9f9f9; }
tr:hover { background:#ffe6d6; }

.highlight {
    background: #fff3cd !important;
    animation: glow 1s ease-in-out;
}
@keyframes glow {
    from { background: #ffe082; }
    to { background: #fff3cd; }
}

.product-img{
width:45px;
height:45px;
object-fit:cover;
border-radius:8px;
border:1px solid #ddd;
}

.status { padding:5px 10px; border-radius:12px; font-weight:600; color:#fff; font-size:12px; }
.active { background:#2e7d32; }
.low-stock { background:#d32f2f; }

.action-btn{
width:35px;height:35px;border-radius:50%;
display:inline-flex;justify-content:center;align-items:center;
color:#fff;font-size:16px;margin-right:5px;
}
.action-edit{background:#2a5298;}
.action-delete{background:#d32f2f;}

.role-tag{
font-size:12px;
color:#ff7a18;
font-weight:600;
}
</style>
</head>

<body>

<?php include 'topbar.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main-content">

<div class="breadcrumb">
<a href="<?= $role ?>.php">Dashboard</a> /
<span>Products</span>
</div>

<h2><i class="fa-solid fa-box"></i> Products</h2>

<div class="table-controls">
<form method="get" style="display:flex; gap:10px;">
<input type="text" name="search" placeholder="Search product..." value="<?= htmlspecialchars($search) ?>">

<select name="category">
<option value="">All Categories</option>
<?php foreach($categories as $c): ?>
<option value="<?= htmlspecialchars($c) ?>" <?= $category==$c?'selected':'' ?>>
<?= htmlspecialchars($c) ?>
</option>
<?php endforeach; ?>
</select>

<button type="submit">
<i class="fa-solid fa-magnifying-glass"></i> Filter
</button>
</form>

<?php if($role === 'admin' || $role === 'manager'): ?>
<a href="add-product.php" class="btn-add">
<i class="fa-solid fa-plus"></i> Add Product
</a>
<?php endif; ?>
</div>

<table>
<thead>
<tr>
<th>ID</th>
<th>Image</th>
<th>Name</th>
<th>Category</th>
<th>Buying Price</th>
<th>Selling Price</th>

<?php if($role === 'cashier'): ?>
<th>Available</th>
<?php else: ?>
<th>Min Quantity</th>
<?php endif; ?>

<th>Status</th>

<?php if($role === 'admin' || $role === 'manager'): ?>
<th>Last Updated By</th>
<th>Actions</th>
<?php endif; ?>
</tr>
</thead>

<tbody>
<?php while($row = $result->fetch_assoc()): ?>
<tr class="<?= (isset($_GET['id']) && $_GET['id']==$row['id']) ? 'highlight' : '' ?>">

<td><?= $row['id'] ?></td>

<td>
<?php if(!empty($row['image'])): ?>
<img src="images/products/<?= $row['image'] ?>" class="product-img">
<?php else: ?>
<img src="images/no-image.png" class="product-img">
<?php endif; ?>
</td>

<td><?= htmlspecialchars($row['product_name']) ?></td>
<td><?= htmlspecialchars($row['category']) ?></td>

<td><?= number_format($row['buying_price'],2) ?></td>
<td><?= number_format($row['selling_price'],2) ?></td>

<td>
<?php if($role === 'cashier'): ?>

<?php if(($row['available_qty'] ?? 0) <= 0): ?>
<span class="status low-stock">Out of Stock</span>
<?php else: ?>
<b>
<?= ($row['available_qty'] ?? 0) ?>
<small style="color:#777;">
<?= $row['unit'] ?? 'pcs' ?>
</small>
</b>
<?php endif; ?>

<?php else: ?>

<?= $row['min_quantity'] ?>
<small style="color:#777;">
<?= $row['unit'] ?? 'pcs' ?>
</small>

<?php endif; ?>
</td>

<td>
<span class="status <?= $row['status']=='active'?'active':'low-stock' ?>">
<?= ucfirst($row['status']) ?>
</span>
</td>

<?php if($role === 'admin' || $role === 'manager'): ?>
<td>
<?php if(!empty($row['updated_by_name'])): ?>
<?= htmlspecialchars($row['updated_by_name']) ?><br>
<span class="role-tag">(<?= ucfirst($row['updated_by_role']) ?>)</span>
<?php else: ?>
—
<?php endif; ?>
</td>

<td>
<a href="edit-product.php?id=<?= $row['id'] ?>" class="action-btn action-edit">
<i class="fa-solid fa-pen"></i>
</a>

<?php if($role === 'admin'): ?>
<a href="#" onclick="confirmDelete(event, <?= $row['id'] ?>)" class="action-btn action-delete">
<i class="fa-solid fa-trash"></i>
</a>
<?php endif; ?>
</td>
<?php endif; ?>

</tr>
<?php endwhile; ?>
</tbody>
</table>

</div>

<script>
<?php if(isset($_GET['updated'])): ?>
Swal.fire({
    icon: 'success',
    title: 'Updated!',
    text: 'Product updated successfully.',
    timer: 2000,
    showConfirmButton: false
});
<?php endif; ?>

function confirmDelete(event,id){
event.preventDefault();

Swal.fire({
title:'Are you sure?',
text:"This action cannot be undone!",
icon:'warning',
showCancelButton:true,
confirmButtonColor:'#d32f2f',
cancelButtonColor:'#777',
confirmButtonText:'Yes, delete it'
}).then((result)=>{
if(result.isConfirmed){
window.location.href="delete-product.php?id="+id+"&deleted=1";
}
});
}
</script>

</body>
</html>