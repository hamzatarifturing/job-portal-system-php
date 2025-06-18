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

// Get count of all database entities
// Total users count
$users_query = "SELECT COUNT(*) as total_users FROM users";
$users_result = mysqli_query($conn, $users_query);
$total_users = mysqli_fetch_assoc($users_result)['total_users'];

// User counts by user_type (excluding admin)
$jobseeker_query = "SELECT COUNT(*) as count FROM users WHERE user_type = 'jobseeker'";
$jobseeker_result = mysqli_query($conn, $jobseeker_query);
$jobseeker_count = mysqli_fetch_assoc($jobseeker_result)['count'];

$employer_query = "SELECT COUNT(*) as count FROM users WHERE user_type = 'employer'";
$employer_result = mysqli_query($conn, $employer_query);
$employer_count = mysqli_fetch_assoc($employer_result)['count'];

// Job postings count
$job_postings_query = "SELECT COUNT(*) as total_jobs FROM job_postings";
$job_postings_result = mysqli_query($conn, $job_postings_query);
$total_job_postings = mysqli_fetch_assoc($job_postings_result)['total_jobs'];

// Job applications count
$job_applications_query = "SELECT COUNT(*) as total_applications FROM job_applications";
$job_applications_result = mysqli_query($conn, $job_applications_query);
$total_job_applications = mysqli_fetch_assoc($job_applications_result)['total_applications'];
?>

<div class="container mt-5">
    <div class="jumbotron">
        <h1 class="display-4">Welcome to Admin Dashboard</h1>
        <p class="lead">System statistics and admin controls</p>
    </div>
    
    <!-- Database Entities Counts -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-3">Database Statistics</h2>
        </div>
    </div>
    
    <div class="row mb-5">
        <!-- Total Users Card -->
        <div class="col-md-4 mb-4">
            <div class="card border-primary h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">Total Users</h5>
                </div>
                <div class="card-body text-center">
                    <p class="display-4"><?php echo $total_users; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Job Postings Card -->
        <div class="col-md-4 mb-4">
            <div class="card border-success h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="m-0">Job Postings</h5>
                </div>
                <div class="card-body text-center">
                    <p class="display-4"><?php echo $total_job_postings; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Job Applications Card -->
        <div class="col-md-4 mb-4">
            <div class="card border-info h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="m-0">Job Applications</h5>
                </div>
                <div class="card-body text-center">
                    <p class="display-4"><?php echo $total_job_applications; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- User Type Distribution -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-3">User Distribution</h2>
        </div>
    </div>
    
    <div class="row mb-5">
        <!-- Job Seekers Card -->
        <div class="col-md-6 mb-4">
            <div class="card border-warning h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="m-0">Job Seekers</h5>
                </div>
                <div class="card-body text-center">
                    <p class="display-4"><?php echo $jobseeker_count; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Employers Card -->
        <div class="col-md-6 mb-4">
            <div class="card border-danger h-100">
                <div class="card-header bg-danger text-white">
                    <h5 class="m-0">Employers</h5>
                </div>
                <div class="card-body text-center">
                    <p class="display-4"><?php echo $employer_count; ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once("../includes/footer.php");
?>