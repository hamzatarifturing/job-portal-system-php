<?php
// Start the session
session_start();

// Check if user is logged in and is an employer
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Set include path for header/footer
$includePath = "../includes/";
$pageTitle = "Employer Dashboard | Job Portal";

// Include header
include_once($includePath . "header.php");

// Get user ID from session
$userId = $_SESSION['user_id'];

// Fetch employer data from database
$query = "SELECT u.first_name, u.last_name, u.username, u.email, u.phone, 
          e.company_name, e.industry, e.company_description 
          FROM users u 
          LEFT JOIN employers e ON u.id = e.user_id 
          WHERE u.id = $userId";

$result = mysqli_query($conn, $query);

if($result && mysqli_num_rows($result) > 0) {
    $employerData = mysqli_fetch_assoc($result);
    $userFirstName = $employerData['first_name'];
    $userLastName = $employerData['last_name'];
    $username = $employerData['username'];
    $companyName = $employerData['company_name'];
} else {
    // Handle error - couldn't find employer data
    $userFirstName = "User";
    $userLastName = "";
    $username = "user";
    $companyName = "Company";
}

// Count active jobs
$queryActiveJobs = "SELECT COUNT(*) as active_jobs FROM job_postings WHERE user_id = $userId AND status = 'Published'";
$resultActiveJobs = mysqli_query($conn, $queryActiveJobs);
$activeJobs = 0;

if($resultActiveJobs && mysqli_num_rows($resultActiveJobs) > 0) {
    $activeJobsData = mysqli_fetch_assoc($resultActiveJobs);
    $activeJobs = $activeJobsData['active_jobs'];
}

// Count applications (placeholder logic)
$applications = 0;
?>

<div class="container">
    <h1 class="mt-4">Employer Dashboard</h1>
    
    <?php 
    // Display success message if present in URL
    if(isset($_GET['success'])) {
        echo '<div class="alert alert-success alert-dismissible fade show mt-3" role="alert">';
        echo htmlspecialchars($_GET['success']);
        echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>';
        echo '</div>';
    }

    // Display error message if present in URL
    if(isset($_GET['error'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">';
        echo htmlspecialchars($_GET['error']);
        echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>';
        echo '</div>';
    }
    ?>
    
    <div class="jumbotron bg-primary text-white">
        <h2>Welcome, <?php echo htmlspecialchars($userFirstName . ' ' . $userLastName); ?>!</h2>
        <p>Manage your job postings and applications from your employer dashboard.</p>
        <div class="buttons mt-4">
            <a class="btn btn-light btn-lg" href="post_job.php" role="button">Post a Job</a>
            <a class="btn btn-outline-light btn-lg" href="company_profile.php" role="button">Edit Company Profile</a>
            <a class="btn btn-danger btn-lg" href="edit_profile.php" role="button">Edit Profile</a>
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
                <p class="card-text display-4"><?php echo $activeJobs; ?></p>
                <a href="job_listings.php" class="btn btn-outline-primary">View All Jobs</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="m-0">Job Applications</h5>
                </div>
                <div class="card-body text-center">
                    <p class="card-text display-4"><?php echo $applications; ?></p>
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
                <div class="col-md-3 mb-3">
                    <a href="post_job.php" class="btn btn-block btn-outline-primary">
                        <i class="fa fa-plus-circle"></i> Post New Job
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="applications.php" class="btn btn-block btn-outline-success">
                        <i class="fa fa-users"></i> Review Applications
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="search_candidates.php" class="btn btn-block btn-outline-info">
                        <i class="fa fa-search"></i> Search Candidates
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="edit_profile.php" class="btn btn-block btn-outline-danger">
                        <i class="fa fa-user-edit"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Your Job Postings Table -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="m-0">Your Job Postings</h5>
        </div>
        <div class="card-body">
            <?php
            // Fetch job postings for this employer
            $jobsQuery = "SELECT id, title, job_type, location, status, DATE_FORMAT(created_at, '%M %d, %Y') as posted_date, 
                          DATE_FORMAT(expiry_date, '%M %d, %Y') as expiry 
                          FROM job_postings 
                          WHERE user_id = $userId 
                          ORDER BY created_at DESC";
            
            $jobsResult = mysqli_query($conn, $jobsQuery);
            
            if ($jobsResult && mysqli_num_rows($jobsResult) > 0) {
                // Display the job listings in a table
            ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Posted Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($job = mysqli_fetch_assoc($jobsResult)) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($job['title']); ?></td>
                                <td><?php echo htmlspecialchars($job['job_type']); ?></td>
                                <td><?php echo htmlspecialchars($job['location']); ?></td>
                                <td>
                                    <?php 
                                    $statusClass = '';
                                    switch ($job['status']) {
                                        case 'Published':
                                            $statusClass = 'success';
                                            break;
                                        case 'Draft':
                                            $statusClass = 'secondary';
                                            break;
                                        case 'Expired':
                                            $statusClass = 'danger';
                                            break;
                                        default:
                                            $statusClass = 'primary';
                                    }
                                    ?>
                                    <span class="badge badge-<?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($job['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($job['posted_date']); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="view_job.php?id=<?php echo $job['id']; ?>" class="btn btn-info" title="View">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                        <a href="edit_job.php?id=<?php echo $job['id']; ?>" class="btn btn-primary" title="Edit">
                                            <i class="fa fa-edit"></i> Edit
                                        </a>
                                        <?php if ($job['status'] == 'Draft'): ?>
                                        <a href="publish_job.php?id=<?php echo $job['id']; ?>" class="btn btn-success" title="Publish">
                                            <i class="fa fa-check-circle"></i> Publish
                                        </a>
                                        <?php endif; ?>
                                        <a href="close_job.php?id=<?php echo $job['id']; ?>" class="btn btn-danger" 
                                           title="Delete" onclick="return confirm('Are you sure you want to delete this job posting?');">
                                            <i class="fa fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php } else { ?>
                <div class="alert alert-info">
                    <p>You haven't posted any jobs yet.</p>
                    <a href="post_job.php" class="btn btn-primary mt-2">Post Your First Job</a>
                </div>
            <?php } ?>
        </div>
        <div class="card-footer text-muted">
            <a href="post_job.php" class="btn btn-success">
                <i class="fa fa-plus-circle"></i> Post New Job
            </a>
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