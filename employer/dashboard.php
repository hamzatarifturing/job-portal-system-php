<?php
// Start the session
session_start();

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    // Redirect to login page with error message
    header("Location: ../login.php?message=" . urlencode("Please login as an employer to access the dashboard."));
    exit();
}

// Set page title
$pageTitle = "Dashboard - Employer";

// Calculate relative path for includes since we're in a subdirectory
$includePath = "../includes/";

// Include header (with adjusted path)
include_once($includePath . "header.php");

// Get user data from session
$userFirstName = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : '';
$userLastName = isset($_SESSION['last_name']) ? $_SESSION['last_name'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

// Fetch additional employer data from database
$employerId = $_SESSION['user_id'];
$query = "SELECT company_name, company_description, industry FROM users WHERE id = '$employerId'";
$result = mysqli_query($conn, $query);
$employerData = mysqli_fetch_assoc($result);

// Set company name variable
$companyName = isset($employerData['company_name']) ? $employerData['company_name'] : 'Your Company';
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
    <div class="jumbotron bg-dark text-white">
    <h1 class="display-4">Welcome, <?php echo htmlspecialchars($userFirstName . ' ' . $userLastName); ?>!</h1>
    <h3 class="text-warning mb-3">Managing: <span class="badge badge-warning"><?php echo htmlspecialchars($companyName); ?></span></h3>
        <p class="lead">This is your employer dashboard where you can post jobs, manage applications, and find the right talent for your company.</p>
        <hr class="my-4 bg-light">
        <p>Start posting jobs or review applications from potential candidates.</p>
        <div class="mt-4">
            <a class="btn btn-primary btn-lg" href="post_job.php" role="button">Post a Job</a>
            <a class="btn btn-outline-light btn-lg" href="company_profile.php" role="button">Edit Company Profile</a>
        </div>
    </div>
    
    <!-- Dashboard stats -->
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4 border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">Active Job Postings</h5>
                </div>
                <div class="card-body text-center">
                    <p class="card-text display-4">0</p>
                    <a href="job_listings.php" class="btn btn-outline-primary">Manage Jobs</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="m-0">Job Applications</h5>
                </div>
                <div class="card-body text-center">
                    <p class="card-text display-4">0</p>
                    <a href="applications.php" class="btn btn-outline-success">View Applications</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4 border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="m-0">Profile Views</h5>
                </div>
                <div class="card-body text-center">
                    <p class="card-text display-4">0</p>
                    <a href="company_profile.php" class="btn btn-outline-info">View Profile Stats</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="m-0">Quick Actions</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <a href="post_job.php" class="btn btn-block btn-outline-primary">
                        <i class="fa fa-plus-circle"></i> Post New Job
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="applications.php" class="btn btn-block btn-outline-success">
                        <i class="fa fa-users"></i> Review Applications
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="search_candidates.php" class="btn btn-block btn-outline-info">
                        <i class="fa fa-search"></i> Search Candidates
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Profile completion reminder -->
    <div class="row">
        <div class="col-md-6">
            <div class="card bg-light mb-4">
                <div class="card-body">
                    <h5 class="card-title">Complete Your Company Profile</h5>
                    <p class="card-text">A complete company profile attracts more qualified candidates. Add your company details, logo, and description.</p>
                    <div class="progress mb-3">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: 40%;" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100">40%</div>
                    </div>
                    <a href="company_profile.php" class="btn btn-warning">Complete Profile</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <p>Welcome to your employer dashboard!</p>
                        <p>This is where you'll see recent activities like new applications and messages.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Subscription Info -->
    <div class="card border-dark mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="m-0">Account Status</h5>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5>Current Plan: <span class="badge badge-primary">Free</span></h5>
                    <p>You're currently on our Free plan with basic features.</p>
                    <p>Upgrade your plan to access premium features like featured job postings, candidate search, and analytics.</p>
                </div>
                <div class="col-md-6 text-center">
                    <a href="subscription.php" class="btn btn-success btn-lg">Upgrade Your Plan</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Account Details -->
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="m-0">Account Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>User Information</h5>
                    <table class="table table-bordered">
                        <tr>
                            <th>Name:</th>
                            <td><?php echo htmlspecialchars($userFirstName . ' ' . $userLastName); ?></td>
                        </tr>
                        <tr>
                            <th>Username:</th>
                            <td><?php echo htmlspecialchars($username); ?></td>
                        </tr>
                        <tr>
                            <th>Account Type:</th>
                            <td><span class="badge badge-info">Employer</span></td>
                        </tr>
                        <tr>
                            <th>Last Login:</th>
                            <td><?php echo isset($_SESSION['last_login']) ? $_SESSION['last_login'] : 'First login'; ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5>Company Information</h5>
                    <table class="table table-bordered">
                        <tr>
                            <th>Company:</th>
                            <td><?php echo htmlspecialchars($companyName); ?></td>
                        </tr>
                        <tr>
                            <th>Industry:</th>
                            <td><?php echo isset($employerData['industry']) ? htmlspecialchars($employerData['industry']) : 'Not specified'; ?></td>
                        </tr>
                        <tr>
                            <th>Description:</th>
                            <td><?php echo isset($employerData['company_description']) && !empty($employerData['company_description']) ? 
                                substr(htmlspecialchars($employerData['company_description']), 0, 100) . '...' : 
                                'No description available'; ?></td>
                        </tr>
                    </table>
                    <div class="text-right">
                        <a href="company_profile.php" class="btn btn-outline-secondary">Edit Company Details</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once($includePath . "footer.php");
?>