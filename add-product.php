<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* VERY IMPORTANT — prevent empty session redirect */
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: index.php?expired=1");
    exit;
}

/* Allow admin and manager */
if (!in_array($_SESSION['role'], ['admin','manager'])) {
    header("Location: index.php?denied=1");
    exit;
}

include "db.php";

$message = "";

// Handle form submission
if(isset($_POST['save'])){

    $name = $_POST['product_name'];
    $category = $_POST['category'];
    $buying = $_POST['buying_price'];
    $selling = $_POST['selling_price'];
    $min_qty = $_POST['min_quantity'];
    $status = $_POST['status'];
    $unit = $_POST['unit']; // ✅ added only

    // 🔥 HANDLE IMAGE UPLOAD
    $imageName = "";

    if(isset($_FILES['image']) && $_FILES['image']['name'] != ""){
        $imageName = time() . "_" . $_FILES['image']['name'];
        $target = "images/products/" . $imageName;

        move_uploaded_file($_FILES['image']['tmp_name'], $target);
    }

    $stmt = $conn->prepare("
        INSERT INTO products 
        (product_name, category, buying_price, selling_price, min_quantity, status, image, unit, created_at, updated_at) 
        VALUES (?,?,?,?,?,?,?, ?,NOW(),NOW())
    ");

    $stmt->bind_param("ssddisss", $name, $category, $buying, $selling, $min_qty, $status, $imageName, $unit);

    if($stmt->execute()){
        $product_id = $stmt->insert_id;

        $stmt2 = $conn->prepare("
            INSERT INTO inventory (product_id, quantity, min_quantity, last_updated) 
            VALUES (?,?,?,NOW())
        ");
        $qty = 0;
        $stmt2->bind_param("iii", $product_id, $qty, $min_qty);
        $stmt2->execute();

        $message = "Product added successfully! Initial stock is 0. You can add stock later.";
    } else {
        $message = "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Product | Retail System</title>

<link rel="stylesheet" href="dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.form-wrapper { max-width: 700px; margin: 30px auto; }
.form-card {
    background: #fff;
    padding: 35px;
    border-radius: 14px;
    box-shadow: 0 12px 25px rgba(0,0,0,0.1);
}
.form-card h2 { color: #ff7a18; margin-bottom: 20px; display:flex; align-items:center; gap:10px; }
.field-group { margin-bottom: 20px; }
.field-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #333; }
.field-group input, .field-group select { width: 100%; padding: 12px; border-radius: 6px; border: 1px solid #ccc; font-size: 15px; }

.btn-save {
    background: #ff7a18;
    color: #fff;
    padding: 12px 28px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    margin-top: 15px;
}
.btn-save:hover { background: #e06500; }

.message {
    background: #fff3e0;
    color: #e65100;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 15px;
}

.back-link {
    display: inline-block;
    margin-top: 15px;
    color: #ff7a18;
    text-decoration: none;
    font-weight: 600;
}

.breadcrumb { font-size: 14px; margin-bottom: 20px; }
.breadcrumb a { color: #ff7a18; text-decoration: none; margin-right: 5px; }
.breadcrumb span { color: #555; margin-right: 5px; }
</style>
</head>

<body>

<?php include 'topbar.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main-content">

<div class="breadcrumb">
    <a href="admin.php">Dashboard</a> &gt;
    <a href="products.php">Products</a> &gt;
    <span>Add Product</span>
</div>

<div class="form-wrapper">

<?php if($message): ?>
<div class="message"><?= $message ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="form-card">

<h2><i class="fa-solid fa-box"></i> Add Product</h2>

<div class="field-group">
<label>Product Name</label>
<input 
type="text" 
name="product_name"
pattern="[A-Za-z0-9 ]+"
title="Only letters and numbers allowed"
oninput="this.value = this.value.replace(/[^A-Za-z0-9 ]/g, '')"
required>
</div>

<div class="field-group">
<label>Category</label>

<input 
type="text" 
name="category"
id="category"
list="categoryList"
pattern="[A-Za-z ]+"
title="Only letters allowed"
oninput="this.value = this.value.replace(/[^A-Za-z ]/g, '')"
required>

<datalist id="categoryList">
<option value="Drinks">
<option value="Food">
<option value="Snacks">
<option value="Electronics">
<option value="Toiletries">
<option value="Fruits">
<option value="Vegetables">
<option value="Bakery">
<option value="Dairy">
</datalist>

<div class="hint">💡 Start typing to see suggestions</div>
</div>

<!-- ✅ ONLY NEW FIELD -->
<div class="field-group">
<label>Unit</label>
<select name="unit" id="unit">
<option value="pcs">Pieces</option>
<option value="kg">Kilograms (kg)</option>
<option value="L">Liters (L)</option>
</select>
</div>

<div class="field-group">
<label>Product Image</label>
<input type="file" name="image" accept="image/*">
</div>

<div class="field-group">
<label>Buying Price</label>
<input type="number" step="0.01" min="0" name="buying_price" required>
</div>

<div class="field-group">
<label>Selling Price</label>
<input type="number" step="0.01" min="0" name="selling_price" required>
</div>

<div class="field-group">
<label>Minimum Quantity</label>
<input type="number" min="1" step="1" name="min_quantity" required>
</div>

<div class="field-group">
<label>Status</label>
<select name="status">
<option value="active">Active</option>
<option value="inactive">Inactive</option>
</select>
</div>

<button type="submit" name="save" class="btn-save">
<i class="fa-solid fa-plus"></i> Add Product
</button>

<a href="products.php" class="back-link">← Back to Products</a>

</form>
</div>
</div>

<!--  CLEAN SMART LOGIC ONLY -->
<script>
const categoryInput = document.getElementById("category");
const unitSelect = document.getElementById("unit");

categoryInput.addEventListener("input", () => {
    const val = categoryInput.value.toLowerCase().trim();

    if(val.includes("drink")){
        unitSelect.value = "L";
    }
    else if(
        val.includes("food") ||
        val.includes("rice") ||
        val.includes("flour") ||
        val.includes("sugar") ||
        val.includes("snack") ||
        val.includes("fruit") ||
        val.includes("vegetable")
    ){
        unitSelect.value = "kg";
    }
    else {
        unitSelect.value = "pcs";
    }
});
</script>

</body>
</html>