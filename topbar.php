<div class="topbar">

    <div class="brand">
        <div class="logo-badge">
            <i class="fa-solid fa-store"></i>
        </div>
        <span>SIMBA'S RETAIL & INVENTORY MANAGEMENT SYSTEM</span>
    </div>

    <div class="topbar-center">
       
    </div>

    <div class="topbar-right">

        <div class="user-info">
            <div class="user-avatar">
                <i class="fa-solid fa-user"></i>
            </div>

            <div class="user-text">
                <span class="name"><?php echo $_SESSION['fullname']; ?></span>
                <span class="role"><?php echo ucfirst($_SESSION['role']); ?></span>
            </div>
        </div>

        <a href="logout.php" class="logout-btn">
            Logout
        </a>

    </div>

</div>