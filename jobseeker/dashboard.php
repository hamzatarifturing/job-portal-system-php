<?php
// Start the session
session_start();

// Check if user is logged in and is a jobseeker
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'jobseeker') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Set include path for header/footer
$includePath = "../includes/";
$pageTitle = "Jobseeker Dashboard | Job Portal";

// Include header
include_once($includePath . "header.php");

// Get user data
$userId = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = $userId";
$result = mysqli_query($conn, $query);

if($result && mysqli_num_rows($result) > 0) {
    $userData = mysqli_fetch_assoc($result);
    $userFirstName = $userData['first_name'];
    $userLastName = $userData['last_name'];
    $username = $userData['username'];
} else {
    $userFirstName = "User";
    $userLastName = "";
    $username = "user";
}

// Get application count
$applicationCountQuery = "SELECT COUNT(*) as count FROM job_applications WHERE user_id = $userId";
$applicationCountResult = mysqli_query($conn, $applicationCountQuery);
$applicationCount = 0;

if ($applicationCountResult && mysqli_num_rows($applicationCountResult) > 0) {
    $applicationCountData = mysqli_fetch_assoc($applicationCountResult);
    $applicationCount = $applicationCountData['count'];
}

// Get latest job postings
$latestJobsQuery = "SELECT * FROM job_postings WHERE status = 'Published' ORDER BY created_at DESC LIMIT 3";
$latestJobsResult = mysqli_query($conn, $latestJobsQuery);
$latestJobs = [];

if ($latestJobsResult && mysqli_num_rows($latestJobsResult) > 0) {
    while ($row = mysqli_fetch_assoc($latestJobsResult)) {
        $latestJobs[] = $row;
    }
}
?>

<div class="container">
    <?php if(isset($_GET['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            <?php echo htmlspecialchars($_GET['message']); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Welcome Banner -->
    <div class="jumbotron bg-primary text-white">
        <h2>Welcome, <?php echo htmlspecialchars($userFirstName); ?>!</h2>
        <p class="lead">This is your jobseeker dashboard where you can search for jobs, track applications, and manage your profile.</p>
        <hr class="my-4 bg-light">
        <p>Start by browsing available jobs or completing your profile to attract potential employers.</p>
        <div class="mt-4">
            <a class="btn btn-light btn-lg" href="jobs.php" role="button">Search Jobs</a>
            <a class="btn btn-outline-light btn-lg" href="profile.php" role="button">Update Profile</a>
        </div>
    </div>
    
    <!-- Dashboard stats -->
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4 border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">Job Applications</h5>
                </div>
                <div class="card-body text-center">
                    <p class="card-text display-4"><?php echo $applicationCount; ?></p>
                    <a href="applications.php" class="btn btn-outline-primary">View Applications</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="m-0">Profile Completion</h5>
                </div>
                <div class="card-body text-center">
                    <p class="card-text display-4">60%</p>
                    <div class="progress mb-3">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 60%;" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100">60%</div>
                    </div>
                    <a href="edit_profile.php" class="btn btn-outline-success">Complete Profile</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4 border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="m-0">Saved Jobs</h5>
                </div>
                <div class="card-body text-center">
                    <p class="card-text display-4">0</p>
                    <a href="saved_jobs.php" class="btn btn-outline-info">View Saved Jobs</a>
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
                    <a href="jobs.php" class="btn btn-block btn-outline-primary">
                        <i class="fa fa-search"></i> Find Jobs
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="applications.php" class="btn btn-block btn-outline-success">
                        <i class="fa fa-list-alt"></i> My Applications
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="saved_jobs.php" class="btn btn-block btn-outline-info">
                        <i class="fa fa-bookmark"></i> Saved Jobs
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
    
    <!-- Latest Job Postings -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="m-0">Latest Job Postings</h5>
        </div>
        <div class="card-body">
            <?php if(empty($latestJobs)): ?>
                <div class="alert alert-info">
                    No job postings available at the moment. Please check back later.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach($latestJobs as $job): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($job['title']); ?></h5>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($job['company_name']); ?></h6>
                                    <p class="card-text small">
                                        <?php echo substr(htmlspecialchars($job['description']), 0, 100) . '...'; ?>
                                    </p>
                                    <div class="mb-2">
                                        <span class="badge badge-primary"><?php echo htmlspecialchars($job['job_type']); ?></span>
                                        <?php if(!empty($job['location'])): ?>
                                            <span class="badge badge-secondary"><?php echo htmlspecialchars($job['location']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-footer bg-white border-top-0">
                                    <a href="view_job.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                    <a href="apply_job.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-success">Apply</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-3">
                    <a href="jobs.php" class="btn btn-outline-primary">View All Jobs</a>
                </div>
            <?php endif; ?>
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
                    <h5>Profile Information</h5>
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
                            <td><span class="badge badge-info">Jobseeker</span></td>
                        </tr>
                        <tr>
                            <th>Last Login:</th>
                            <td><?php echo isset($_SESSION['last_login']) ? $_SESSION['last_login'] : 'First login'; ?></td>
                        </tr>
                    </table>
                    <a href="edit_profile.php" class="btn btn-outline-primary">Edit Profile</a>
                </div>
                <div class="col-md-6">
                    <h5>Profile Completion Tips</h5>
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Upload your resume
                            <span class="badge badge-danger badge-pill">Missing</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Add your skills
                            <span class="badge badge-warning badge-pill">Incomplete</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Add your education
                            <span class="badge badge-success badge-pill">Complete</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Add work experience
                            <span class="badge badge-warning badge-pill">Incomplete</span>
                        </li>
                    </ul>
                    <div class="alert alert-info mt-3">
                        <small>Complete your profile to increase your chances of getting noticed by employers.</small>
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