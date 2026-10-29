<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* AUTH CHECK */
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: index.php?expired=1");
    exit;
}

if (!in_array($_SESSION['role'], ['admin','manager'])) {
    header("Location: index.php?denied=1");
    exit;
}

include "db.php";

/* GET PRODUCT ID */
if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit;
}

$id = intval($_GET['id']);

/* FETCH PRODUCT */
$stmt = $conn->prepare("SELECT * FROM products WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    die("Product not found");
}

/* UPDATE LOGIC */
if(isset($_POST['save'])){

    $name = $_POST['product_name'];
    $category = $_POST['category'];
    $buying = $_POST['buying_price'];
    $selling = $_POST['selling_price'];
    $min_qty = $_POST['min_quantity'];
    $status = $_POST['status'];
    $unit = $_POST['unit']; // ✅ NEW

    $imageName = $product['image'];

    if(isset($_FILES['image']) && $_FILES['image']['name'] != ""){
        $imageName = time() . "_" . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "images/products/" . $imageName);
    }

    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("
        UPDATE products 
        SET product_name=?, category=?, buying_price=?, selling_price=?, 
            min_quantity=?, status=?, image=?, unit=?, updated_by=?, updated_at=NOW()
        WHERE id=?
    ");

    $stmt->bind_param("ssddisssii", $name, $category, $buying, $selling, $min_qty, $status, $imageName, $unit, $user_id, $id);

    if($stmt->execute()){

        $action = "Updated Product";
        $details = "Product ID: $id updated by user ID: $user_id";

        $log = $conn->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?,?,?)");
        $log->bind_param("iss", $user_id, $action, $details);
        $log->execute();

        header("Location: products.php?updated=1&id=$id");
        exit;
    } else {
        $error = "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Product | Retail System</title>

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

.error {
    background: #ffebee;
    color: #c62828;
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
    <span>Edit Product</span>
</div>

<div class="form-wrapper">

<?php if(isset($error)): ?>
<div class="error"><?= $error ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="form-card">

<h2><i class="fa-solid fa-pen"></i> Edit Product</h2>

<div class="field-group">
<label>Product Name</label>
<input type="text" name="product_name" value="<?= $product['product_name'] ?>" required>
</div>

<!-- ✅ SMART CATEGORY -->
<div class="field-group">
<label>Category</label>
<input type="text" name="category" id="category" value="<?= $product['category'] ?>" list="categoryList" required>

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
</div>

<!-- ✅ UNIT -->
<div class="field-group">
<label>Unit</label>
<select name="unit" id="unit">
<option value="pcs" <?= ($product['unit']=='pcs')?'selected':'' ?>>Pieces</option>
<option value="kg" <?= ($product['unit']=='kg')?'selected':'' ?>>Kilograms (kg)</option>
<option value="L" <?= ($product['unit']=='L')?'selected':'' ?>>Liters (L)</option>
</select>
</div>

<div class="field-group">
<label>Product Image</label>
<input type="file" name="image" accept="image/*">
<br><br>
<?php if($product['image']): ?>
<img src="images/products/<?= $product['image'] ?>" width="100">
<?php endif; ?>
</div>

<div class="field-group">
<label>Buying Price</label>
<input type="number" step="0.01" min="0" name="buying_price" value="<?= $product['buying_price'] ?>" required>
</div>

<div class="field-group">
<label>Selling Price</label>
<input type="number" step="0.01" min="0" name="selling_price" value="<?= $product['selling_price'] ?>" required>
</div>

<div class="field-group">
<label>Minimum Quantity</label>
<input type="number" min="1" step="1" name="min_quantity" value="<?= $product['min_quantity'] ?>" required>
</div>

<div class="field-group">
<label>Status</label>
<select name="status">
<option value="active" <?= $product['status']=='active'?'selected':'' ?>>Active</option>
<option value="inactive" <?= $product['status']=='inactive'?'selected':'' ?>>Inactive</option>
</select>
</div>

<button type="submit" name="save" class="btn-save">
<i class="fa-solid fa-save"></i> Update Product
</button>

<a href="products.php" class="back-link">← Back to Products</a>

</form>
</div>
</div>

<!-- ✅ SMART UNIT SCRIPT -->
<script>
const categoryInput = document.getElementById("category");
const unitSelect = document.getElementById("unit");

categoryInput.addEventListener("input", () => {
    const val = categoryInput.value.toLowerCase();

    if(val.includes("drink")){
        unitSelect.value = "L";
    }
    else if(val.includes("food") || val.includes("rice") || val.includes("snack") || val.includes("fruit") || val.includes("vegetable")){
        unitSelect.value = "kg";
    }
    else {
        unitSelect.value = "pcs";
    }
});
</script>

</body>
</html>