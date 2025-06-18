<?php
// Start the session
session_start();

// Check if user is logged in and is an admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Include database configuration and header
include_once("../includes/db_config.php");
include_once("../includes/header.php");
?>

<div class="container mt-5">
    <div class="jumbotron">
        <h1 class="display-4">Welcome to Admin Dashboard</h1>
    </div>
</div>

<?php
// Include footer
include_once("../includes/footer.php");
?>