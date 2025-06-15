<?php
// Database configuration file
require_once 'db_config.php';

// Set page title if not already set
if(!isset($pageTitle)) {
    $pageTitle = 'Job Portal';
}
// Check if includePath is defined (for subdirectory pages)
if (!isset($includePath)) {
    $includePath = '';  // Default to current directory
}

// Adjust asset paths based on include path
$cssPath = $includePath . "assets/css/style.css";
$jsPath = $includePath . "assets/js/main.js";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Bootstrap CSS (from CDN for simplicity) -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $cssPath; ?>">
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container">
                <a class="navbar-brand" href="index.php">Job Portal</a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="jobs.php">Browse Jobs</a>
                        </li>
                        <?php if(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'jobseeker'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="jobseeker/dashboard.php">My Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="logout.php">Logout</a>
                            </li>
                        <?php elseif(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'employer'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="employer/dashboard.php">My Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="logout.php">Logout</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="login.php">Login</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="register.php">Register</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <main class="py-4">