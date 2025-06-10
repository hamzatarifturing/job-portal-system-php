<?php
// Start the session
session_start();

// Check if user is logged in and is a jobseeker
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'jobseeker') {
    // Redirect to login page with error message
    header("Location: ../login.php?message=" . urlencode("Please login as a job seeker to access the dashboard."));
    exit();
}

// Set page title
$pageTitle = "Dashboard - Job Seeker";

// Calculate relative path for includes since we're in a subdirectory
$includePath = "../includes/";

// Include header (with adjusted path)
include_once($includePath . "header.php");

// Get user data from session
$userFirstName = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : '';
$userLastName = isset($_SESSION['last_name']) ? $_SESSION['last_name'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
?>

<div class="container mt-4">
    <!-- Success message display -->
    <?php 
    if(isset($_GET['message'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($_GET['message']);
        echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>';
        echo '</div>';
    }
    ?>

    <!-- Welcome section -->
    <div class="jumbotron">
        <h1 class="display-4">Welcome, <?php echo htmlspecialchars($userFirstName . ' ' . $userLastName); ?>!</h1>
        <p class="lead">This is your job seeker dashboard where you can manage your job applications and profile.</p>
        <hr class="my-4">
        <p>Start searching for jobs that match your skills and experience, or update your profile to attract better job offers.</p>
        <a class="btn btn-primary btn-lg" href="../jobs.php" role="button">Search Jobs</a>
        <a class="btn btn-outline-secondary btn-lg" href="profile.php" role="button">Update Profile</a>
    </div>
    
    <!-- Dashboard stats -->
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h5 class="card-title">Job Applications</h5>
                    <p class="card-text display-4">0</p>
                    <a href="applications.php" class="btn btn-outline-primary">View Applications</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h5 class="card-title">Saved Jobs</h5>
                    <p class="card-text display-4">0</p>
                    <a href="saved_jobs.php" class="btn btn-outline-primary">View Saved Jobs</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h5 class="card-title">Profile Views</h5>
                    <p class="card-text display-4">0</p>
                    <a href="profile.php" class="btn btn-outline-primary">View Profile</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent job listings -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Recent Job Listings</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <p>No recent job listings found that match your profile. Please update your skills and preferences to see more relevant jobs.</p>
                <a href="profile.php" class="btn btn-sm btn-info">Update Skills</a>
            </div>
        </div>
    </div>
    
    <!-- Profile completion reminder -->
    <div class="card bg-light mb-4">
        <div class="card-body">
            <h5 class="card-title">Complete Your Profile</h5>
            <p class="card-text">A complete profile increases your chances of being noticed by employers. Make sure to add your skills, education, and work experience.</p>
            <div class="progress">
                <div class="progress-bar" role="progressbar" style="width: 25%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">25%</div>
            </div>
            <a href="profile.php" class="btn btn-primary mt-3">Complete Profile</a>
        </div>
    </div>
</div>

<?php
// Include footer
include_once($includePath . "footer.php");
?>