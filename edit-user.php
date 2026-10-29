<?php
session_start();
include "db.php";

if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: manage-users.php");
    exit;
}

$id = intval($_GET['id']);
$message = "";

/* FETCH USER */
$stmt = $conn->prepare("SELECT fullname, username, role, email, phone, work_area, tasks, shift, start_date, status FROM users WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage-users.php");
    exit;
}

$user = $result->fetch_assoc();

/* UPDATE */
if (isset($_POST['update'])) {

    $username = $_POST['username'];

    // 🔐 DUPLICATE CHECK (exclude current user)
    $check = $conn->prepare("SELECT id FROM users WHERE username=? AND id!=?");
    $check->bind_param("si", $username, $id);
    $check->execute();
    $res = $check->get_result();

    if($res->num_rows > 0){
        $message = "Username already exists!";
    } else {

        $fullname = $_POST['fullname'];
        $email    = $_POST['email'];
        $phone    = $_POST['phone'];
        $role     = $_POST['role'];
        $work_area= $_POST['work_area'];
        $tasks    = $_POST['tasks'];
        $shift    = $_POST['shift'];
        $start_date = $_POST['start_date'];
        $status   = $_POST['status'];

        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET fullname=?, username=?, password=?, email=?, phone=?, role=?, work_area=?, tasks=?, shift=?, start_date=?, status=? WHERE id=?");
            $stmt->bind_param("sssssssssssi", $fullname, $username, $password, $email, $phone, $role, $work_area, $tasks, $shift, $start_date, $status, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET fullname=?, username=?, email=?, phone=?, role=?, work_area=?, tasks=?, shift=?, start_date=?, status=? WHERE id=?");
            $stmt->bind_param("ssssssssssi", $fullname, $username, $email, $phone, $role, $work_area, $tasks, $shift, $start_date, $status, $id);
        }

        if ($stmt->execute()) {
            $message = "success";
        } else {
            $message = "Error updating employee: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit Employee | Retail System</title>

<link rel="stylesheet" href="dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.form-card { background:#fff; padding:40px 30px; max-width:700px; margin:30px auto; border-radius:16px; box-shadow:0 15px 35px rgba(0,0,0,0.12); }
.form-card h2 { text-align:center; margin-bottom:30px; color:#ff7a18; }

.form-card input, .form-card select, .form-card textarea {
width:100%; padding:14px 16px; margin:12px 0;
border-radius:8px; border:1px solid #ccc;
}

.btn-group { display:flex; justify-content:space-between; margin-top:20px; gap:10px; }

.btn-submit {
background:linear-gradient(135deg,#ff7a18,#ff9f1c);
color:#fff; padding:14px 25px;
border:none; border-radius:8px; cursor:pointer;
}

.btn-cancel{
background:#fff;
color:#ff7a18;
padding:14px 25px;
border-radius:8px;
border:2px solid #ff7a18;
text-decoration:none;
display:flex;
align-items:center;
gap:6px;
}

.btn-cancel:hover{
background:#ff7a18;
color:#fff;
}

.breadcrumb { font-size:14px; margin-bottom:20px; }
.breadcrumb a { color:#ff7a18; text-decoration:none; }
</style>
</head>

<body>

<?php include 'topbar.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main-content">

<div class="breadcrumb">
<a href="admin.php">Dashboard</a> /
<a href="manage-users.php">Manage Users</a> /
<span>Edit Employee</span>
</div>

<div class="form-card">
<h2>Edit Employee</h2>

<?php if($message && $message!="success"): ?>
<p style="color:red;"><?= $message ?></p>
<?php endif; ?>

<form method="post" id="updateForm">

<!-- FULL NAME -->
<input type="text" id="fullname" name="fullname"
value="<?= htmlspecialchars($user['fullname']); ?>"
placeholder="e.g. John Doe"
pattern="[A-Za-z ]+"
oninput="this.value=this.value.replace(/[^A-Za-z ]/g,''); generateUsername();"
required>

<!-- USERNAME -->
<input type="text" id="username" name="username"
value="<?= htmlspecialchars($user['username']); ?>"
placeholder="e.g. john.doe"
oninput="checkUsername()"
required>

<div id="usernameMsg" style="font-size:13px;"></div>

<input type="password" name="password" placeholder="Leave blank to keep password">

<input type="email" name="email"
value="<?= htmlspecialchars($user['email']); ?>"
placeholder="e.g. john@gmail.com">

<input type="tel" name="phone"
value="<?= htmlspecialchars($user['phone']); ?>"
placeholder="e.g. 0789123456"
oninput="this.value=this.value.replace(/[^0-9]/g,'')">

<select name="role">
<option value="manager" <?= $user['role']=='manager'?'selected':''; ?>>Manager</option>
<option value="cashier" <?= $user['role']=='cashier'?'selected':''; ?>>Cashier</option>
<option value="admin" <?= $user['role']=='admin'?'selected':''; ?>>Admin</option>
</select>

<input type="text" name="work_area"
value="<?= htmlspecialchars($user['work_area']); ?>"
placeholder="e.g. Warehouse">

<textarea name="tasks" placeholder="e.g. Manage stock"><?= htmlspecialchars($user['tasks']); ?></textarea>

<input type="text" name="shift"
value="<?= htmlspecialchars($user['shift']); ?>"
placeholder="e.g. Morning Shift">

<input type="date" name="start_date"
value="<?= htmlspecialchars($user['start_date']); ?>">

<select name="status">
<option value="active" <?= $user['status']=='active'?'selected':''; ?>>Active</option>
<option value="suspended" <?= $user['status']=='suspended'?'selected':''; ?>>Suspended</option>
</select>

<div class="btn-group">
<button type="button" class="btn-submit" onclick="confirmUpdate()">Update Employee</button>

<a href="view-employee.php?id=<?= $id; ?>" class="btn-cancel">
<i class="fa-solid fa-arrow-left"></i> Cancel
</a>
</div>

</form>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

// CONFIRM UPDATE
function confirmUpdate(){
Swal.fire({
title:'Update employee?',
icon:'warning',
showCancelButton:true,
confirmButtonColor:'#ff7a18'
}).then((result)=>{
if(result.isConfirmed){
let form=document.getElementById("updateForm");
let input=document.createElement("input");
input.type="hidden";
input.name="update";
input.value="1";
form.appendChild(input);
form.submit();
}
});
}

// SUCCESS ALERT
<?php if($message === "success"): ?>
Swal.fire({
title:'Updated!',
icon:'success'
}).then(()=>{
window.location.href="view-employee.php?id=<?= $id ?>";
});
<?php endif; ?>

</script>

</body>
</html>