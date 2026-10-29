<?php
session_start();
include "db.php";

if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$message = "";
if(isset($_POST['submit'])) {

    $username = $_POST['username'];

    // 🔐 BACKEND DUPLICATE CHECK
    $check = $conn->prepare("SELECT id FROM users WHERE username=?");
    $check->bind_param("s", $username);
    $check->execute();
    $res = $check->get_result();

    if($res->num_rows > 0){
        $message = "Username already exists!";
    } else {

        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $fullname = $_POST['fullname'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $work_area = $_POST['work_area'];
        $tasks = $_POST['tasks'];
        $shift = $_POST['shift'];
        $start_date = $_POST['start_date'];

        $stmt = $conn->prepare("
            INSERT INTO users 
            (username, password, role, fullname, email, phone, work_area, tasks, shift, start_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "ssssssssss",
            $username, $password, $role, $fullname, $email, $phone,
            $work_area, $tasks, $shift, $start_date
        );

        if($stmt->execute()) {
            header("Location: manage-users.php");
            exit;
        } else {
            $message = "Error: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Add Employee | Retail System</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="dashboard.css">

<style>
.main-content { margin-left:240px; padding:100px 30px 30px; min-height:100vh; }

.form-card {
    background:#fff; padding:40px 30px; max-width:700px; margin:0 auto;
    border-radius:16px; box-shadow:0 15px 35px rgba(0,0,0,0.12);
}
.form-card h2 { text-align:center; margin-bottom:30px; color:#ff7a18; }

.input-group { position: relative; margin-bottom:15px; }
.input-group i {
    position: absolute; left:12px; top:50%; transform:translateY(-50%);
    color:#aaa;
}
.input-group input, .input-group select, .input-group textarea {
    width:100%; padding:14px 16px 14px 38px;
    border-radius:8px; border:1px solid #ccc;
}

.btn-group { display:flex; justify-content:space-between; margin-top:20px; }

.btn-submit {
    background:#ff7a18; color:#fff; border:none;
    padding:12px 25px; border-radius:8px;
}
.btn-cancel{
    background:#fff;
    color:#ff7a18;
    padding:12px 25px;
    border-radius:8px;
    border:2px solid #ff7a18;
    font-weight:600;
    text-decoration:none;
    display:flex;
    align-items:center;
    gap:6px;
    transition:0.3s;
}

.btn-cancel:hover{
    background:#ff7a18;
    color:#fff;
    transform:translateY(-2px);
    box-shadow:0 6px 15px rgba(255,122,24,0.3);
}

.message { text-align:center; margin-bottom:15px; color:red; }
</style>
</head>

<body>

<?php include 'topbar.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main-content">
<div class="form-card">

<h2>Add New Employee</h2>

<?php if($message): ?>
<p class="message"><?= $message ?></p>
<?php endif; ?>

<form method="post">

<!-- FULL NAME -->
<div class="input-group"><i class="fa-solid fa-user"></i>
<input type="text" id="fullname" name="fullname"
placeholder="e.g. Joyeuse utamuriza"
pattern="[A-Za-z ]+"
title="Only letters allowed"
oninput="this.value=this.value.replace(/[^A-Za-z ]/g,''); generateUsername();"
required>
</div>

<!-- USERNAME -->
<div class="input-group"><i class="fa-solid fa-user"></i>
<input type="text" id="username" name="username"
placeholder="auto (e.g. Joyeuse.utamuriza)"
oninput="checkUsername()"
required>
</div>

<div id="usernameMsg" style="font-size:13px;"></div>

<!-- PASSWORD -->
<div class="input-group"><i class="fa-solid fa-lock"></i>
<input type="password" name="password" placeholder="e.g. Pass@123" required>
</div>

<!-- EMAIL -->
<div class="input-group"><i class="fa-solid fa-envelope"></i>
<input type="email" name="email" placeholder="e.g. joyeuse@gmail.com">
</div>

<!-- PHONE -->
<div class="input-group"><i class="fa-solid fa-phone"></i>
<input type="tel" name="phone" placeholder="e.g. 0789......"
oninput="this.value=this.value.replace(/[^0-9]/g,'')">
</div>

<!-- ROLE -->
<div class="input-group"><i class="fa-solid fa-briefcase"></i>
<select name="role" required>
<option value="">Select Role</option>
<option value="manager">Manager</option>
<option value="cashier">Cashier</option>
</select>
</div>

<!-- WORK AREA -->
<div class="input-group"><i class="fa-solid fa-location-dot"></i>
<input type="text" name="work_area" placeholder=" Work-Area : Warehouse / Shop Floor">
</div>

<!-- TASKS -->
<div class="input-group"><i class="fa-solid fa-tasks"></i>
<textarea name="tasks" placeholder=" Assigned-Tasks : Manage stock, handle sales"></textarea>
</div>

<!-- SHIFT -->
<div class="input-group"><i class="fa-solid fa-clock"></i>
<input type="text" name="shift" placeholder="e.g. Morning / Night">
</div>

<!-- DATE -->
<div class="input-group"><i class="fa-solid fa-calendar"></i>
<input type="date" name="start_date">
</div>

<div class="btn-group">
<button type="submit" name="submit" class="btn-submit">Add Employee</button>
<a href="manage-users.php" class="btn-cancel">Cancel</a>
</div>

</form>
</div>
</div>

<script>

// AUTO USERNAME
function generateUsername(){
let fullname=document.getElementById("fullname").value.toLowerCase().trim();
if(!fullname) return;

let parts=fullname.split(" ");
let username = parts.length>1 ? parts[0]+"."+parts[parts.length-1] : parts[0];

document.getElementById("username").value=username;
checkUsername();
}

// CHECK USERNAME
function checkUsername(){
let username=document.getElementById("username").value;
let msg=document.getElementById("usernameMsg");

if(username.length<3){ msg.innerHTML=""; return; }

fetch("check-username.php?username="+username)
.then(res=>res.text())
.then(data=>{
if(data==="exists"){
msg.innerHTML="Username already taken ❌";
msg.style.color="red";
}else{
msg.innerHTML="Username available ✅";
msg.style.color="green";
}
});
}

</script>

</body>
</html>