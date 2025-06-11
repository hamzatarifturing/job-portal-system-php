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

// Process job posting form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_job'])) {
    // Get form data
    $jobTitle = trim($_POST['job_title']);
    $jobDescription = trim($_POST['job_description']);
    $jobRequirements = trim($_POST['job_requirements']);
    $jobLocation = trim($_POST['job_location']);
    $jobType = $_POST['job_type'];
    $companyNameFromForm = trim($_POST['company_name']);
    
    // Validate input
    if (empty($jobTitle) || empty($jobDescription) || empty($companyNameFromForm)) {
        $error = "Job title, description, and company name are required.";
    } else {
        // Insert job posting into database
        $userId = $_SESSION['user_id'];
        
        // Use prepared statement to prevent SQL injection
        $stmt = mysqli_prepare($conn, "INSERT INTO job_postings (user_id, company_name, title, description, requirements, location, job_type) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        mysqli_stmt_bind_param($stmt, "issssss", $userId, $companyNameFromForm, $jobTitle, $jobDescription, $jobRequirements, $jobLocation, $jobType);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Job posting created successfully!";
        } else {
            $error = "Error posting job: " . mysqli_error($conn);
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Get user data
$userId = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = $userId";
$result = mysqli_query($conn, $query);

if($result && mysqli_num_rows($result) > 0) {
    $userData = mysqli_fetch_assoc($result);
    $userFirstName = $userData['first_name'];
    $userLastName = $userData['last_name'];
    $username = $userData['username'];
    
    // Get employer specific data
    $employerQuery = "SELECT * FROM employers WHERE user_id = $userId";
    $employerResult = mysqli_query($conn, $employerQuery);
    
    if($employerResult && mysqli_num_rows($employerResult) > 0) {
        $employerData = mysqli_fetch_assoc($employerResult);
        $companyName = $employerData['company_name'];
    } else {
        $companyName = "Your Company";
    }
} else {
    $userFirstName = "User";
    $userLastName = "";
    $username = "user";
    $companyName = "Your Company";
}

// Get job count
$jobCountQuery = "SELECT COUNT(*) as count FROM job_postings WHERE user_id = $userId AND status != 'Closed'";
$jobCountResult = mysqli_query($conn, $jobCountQuery);
$activeJobs = 0;

if ($jobCountResult && mysqli_num_rows($jobCountResult) > 0) {
    $jobCountData = mysqli_fetch_assoc($jobCountResult);
    $activeJobs = $jobCountData['count'];
}

// Get recent job postings (limit to 5)
$recentJobsQuery = "SELECT id, title, job_type, location, created_at, status FROM job_postings 
                    WHERE user_id = $userId 
                    ORDER BY created_at DESC LIMIT 5";
$recentJobsResult = mysqli_query($conn, $recentJobsQuery);
$recentJobs = [];

if ($recentJobsResult && mysqli_num_rows($recentJobsResult) > 0) {
    while ($row = mysqli_fetch_assoc($recentJobsResult)) {
        $recentJobs[] = $row;
    }
}
?>

<div class="container">
    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            <?php echo $message; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
            <?php echo $error; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Welcome Banner -->
    <div class="jumbotron bg-dark text-white">
        <h2>Welcome, <?php echo htmlspecialchars($userFirstName); ?>!</h2>
        <h3 class="text-warning mb-3">Managing: <span class="badge badge-warning"><?php echo htmlspecialchars($companyName); ?></span></h3>
        <p class="lead">This is your employer dashboard where you can post jobs, manage applications, and find the right talent for your company.</p>
        <hr class="my-4 bg-light">
        <p>Start posting jobs or review applications from potential candidates.</p>
        <div class="mt-4">
            <a class="btn btn-primary btn-lg" href="#post-job-form" role="button">Post a Job</a>
            <a class="btn btn-outline-light btn-lg" href="company_profile.php" role="button">Edit Company Profile</a>
            <a class="btn btn-danger btn-lg" href="edit_profile.php" role="button">Edit Profile</a>
        </div>
    </div>
    
    <!-- View As Jobseeker Card -->
    <div class="card mb-4 border-info">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <div>
                    <h5 class="text-info"><i class="fa fa-eye"></i> Employer Tip</h5>
                    <p class="mb-0">See how your jobs appear to jobseekers by viewing the jobseeker interface.</p>
                </div>
                <div class="ml-auto">
                    <a href="../jobseeker/jobs.php" target="_blank" class="btn btn-info">
                        <i class="fa fa-external-link-alt"></i> View Jobs as Jobseekers
                    </a>
                </div>
            </div>
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

    <!-- My Jobs Table -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="m-0">My Job Postings</h5>
        </div>
        <div class="card-body">
            <?php if (empty($recentJobs)): ?>
                <div class="alert alert-info">
                    You haven't posted any jobs yet. Use the form below to post your first job!
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>Job Title</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Posted On</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recentJobs as $job): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($job['title']); ?></td>
                                    <td><span class="badge badge-primary"><?php echo htmlspecialchars($job['job_type']); ?></span></td>
                                    <td><?php echo htmlspecialchars($job['location'] ?: 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($job['created_at'])); ?></td>
                                    <td>
                                        <?php 
                                            $statusClass = '';
                                            switch($job['status']) {
                                                case 'Published': $statusClass = 'success'; break;
                                                case 'Draft': $statusClass = 'warning'; break;
                                                case 'Closed': $statusClass = 'danger'; break;
                                                case 'Filled': $statusClass = 'info'; break;
                                            }
                                        ?>
                                        <span class="badge badge-<?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($job['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit_job.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <a href="view_applications.php?job_id=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-success">Applications</a>
                                        <a href="../jobseeker/view_job.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-info" target="_blank">Preview</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (count($recentJobs) >= 5): ?>
                    <div class="text-center mt-3">
                        <a href="job_listings.php" class="btn btn-outline-primary">View All Job Postings</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Post Job Form Section -->
    <div id="post-job-form" class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="m-0">Post a New Job</h5>
        </div>
        <div class="card-body">
            <form action="dashboard.php" method="post">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="company_name">Company Name*</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($companyName); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="job_title">Job Title*</label>
                            <input type="text" class="form-control" id="job_title" name="job_title" placeholder="e.g. Senior Web Developer" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="job_description">Job Description*</label>
                    <textarea class="form-control" id="job_description" name="job_description" rows="4" required 
                        placeholder="Describe the job responsibilities, benefits, and other details"></textarea>
                </div>

                <div class="form-group">
                    <label for="job_requirements">Requirements</label>
                    <textarea class="form-control" id="job_requirements" name="job_requirements" rows="3" 
                        placeholder="List required qualifications, skills, and experience"></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="job_location">Location</label>
                            <input type="text" class="form-control" id="job_location" name="job_location" placeholder="e.g. New York, NY or Remote">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="job_type">Job Type*</label>
                            <select class="form-control" id="job_type" name="job_type" required>
                                <option value="Full-time">Full-time</option>
                                <option value="Part-time">Part-time</option>
                                <option value="Contract">Contract</option>
                                <option value="Internship">Internship</option>
                                <option value="Temporary">Temporary</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <button type="submit" name="post_job" class="btn btn-primary">Post Job</button>
                </div>
            </form>
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
                    <a href="#post-job-form" class="btn btn-block btn-outline-primary">
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
                        <a href="edit_profile.php" class="btn btn-outline-danger">Edit User Profile</a>
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