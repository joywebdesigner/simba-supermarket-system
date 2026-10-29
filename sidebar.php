<div class="sidebar">

    <!-- LOGO -->
    <div class="sidebar-logo">
        <img src="images/simba logo2.png" alt="Simba Supermarket">
        <h3>SIMBA SUPERMARKET</h3>
    </div>


<?php if ($_SESSION['role'] === 'admin'): ?>

<a href="admin.php" class="<?= basename($_SERVER['PHP_SELF'])=='admin.php'?'active':'' ?>">
<i class="fa-solid fa-chart-line"></i> Dashboard
</a>

<a href="products.php" class="<?= in_array(basename($_SERVER['PHP_SELF']), ['products.php','add-product.php','edit-product.php'])?'active':'' ?>">
<i class="fa-solid fa-box"></i> Manage Products
</a>

<a href="approve-requests.php" class="<?= basename($_SERVER['PHP_SELF'])=='approve-requests.php'?'active':'' ?>">
<i class="fa-solid fa-check-circle"></i> Approve Requests
</a>

<a href="inventory.php" class="<?= basename($_SERVER['PHP_SELF'])=='inventory.php'?'active':'' ?>">
<i class="fa-solid fa-boxes-stacked"></i> Inventory
</a>


<a href="suppliers.php" class="<?= in_array(basename($_SERVER['PHP_SELF']), ['suppliers.php','edit-supplier.php','add-supplier.php'])?'active':'' ?>"><i class="fa-solid fa-truck-field"></i>Suppliers </a>

<a href="sales_report.php" class="<?= basename($_SERVER['PHP_SELF'])=='sales_report.php'?'active':'' ?>">
<i class="fa-solid fa-file-lines"></i> Reports
</a>

<a href="manage-users.php" class="<?= in_array(basename($_SERVER['PHP_SELF']), ['manage-users.php','add-employee.php','edit-user.php','view-employee.php'])?'active':'' ?>">
<i class="fa-solid fa-users-gear"></i> Manage Users
</a>


<?php elseif ($_SESSION['role'] === 'manager'): ?>

<a href="manager.php" class="<?= basename($_SERVER['PHP_SELF'])=='manager.php'?'active':'' ?>">
<i class="fa-solid fa-chart-line"></i> Dashboard
</a>

<a href="inventory.php" class="<?= basename($_SERVER['PHP_SELF'])=='inventory.php'?'active':'' ?>">
<i class="fa-solid fa-boxes-stacked"></i> Manage Inventory
</a>

<a href="stock-requests.php" class="<?= basename($_SERVER['PHP_SELF'])=='stock-requests.php'?'active':'' ?>">
<i class="fa-solid fa-clipboard-list"></i> Stock Requests
</a>

<a href="receive_stock.php" class="<?= basename($_SERVER['PHP_SELF'])=='receive_stock.php'?'active':'' ?>">
<i class="fa-solid fa-warehouse"></i> Receive Stock
</a>

<a href="suppliers.php" class="<?= basename($_SERVER['PHP_SELF'])=='suppliers.php'?'active':'' ?>">
<i class="fa-solid fa-truck"></i> Suppliers
</a>

<a href="products.php" class="<?= basename($_SERVER['PHP_SELF'])=='products.php'?'active':'' ?>">
<i class="fa-solid fa-box"></i> Available Products
</a>

<a href="sales_report.php" class="<?= basename($_SERVER['PHP_SELF'])=='sales_report.php'?'active':'' ?>">
<i class="fa-solid fa-file-lines"></i> Sales Reports
</a>


<?php elseif ($_SESSION['role'] === 'cashier'): ?>

<a href="cashier.php" class="<?= basename($_SERVER['PHP_SELF'])=='cashier.php'?'active':'' ?>">
<i class="fa-solid fa-chart-line"></i> Dashboard
</a>

<a href="products.php" class="<?= basename($_SERVER['PHP_SELF'])=='products.php'?'active':'' ?>">
<i class="fa-solid fa-box"></i> View Products
</a>

<a href="sales.php" class="<?= basename($_SERVER['PHP_SELF'])=='sales.php'?'active':'' ?>">
<i class="fa-solid fa-cash-register"></i> Record Sale
</a>

<a href="receipts-history.php" class="<?= basename($_SERVER['PHP_SELF'])=='receipts-history.php'?'active':'' ?>">
<i class="fa-solid fa-receipt"></i> Receipts
</a>

<a href="daily_report.php" class="<?= basename($_SERVER['PHP_SELF'])=='daily_report.php'?'active':'' ?>">
<i class="fa-solid fa-file-invoice-dollar"></i> Daily Report
</a>

<?php endif; ?>


<!-- SHARED -->
<a href="settings.php" class="<?= basename($_SERVER['PHP_SELF'])=='settings.php'?'active':'' ?>">
<i class="fa-solid fa-gear"></i> Settings
</a>

</div>