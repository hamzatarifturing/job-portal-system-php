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
$pageTitle = "My Job Listings | Job Portal";

// Include header
include_once($includePath . "header.php");

// Get user ID from session
$userId = $_SESSION['user_id'];

// Fetch employer data for page heading
$employerQuery = "SELECT u.first_name, u.last_name, e.company_name 
                  FROM users u 
                  LEFT JOIN employers e ON u.id = e.user_id 
                  WHERE u.id = $userId";

$employerResult = mysqli_query($conn, $employerQuery);
$companyName = '';

if($employerResult && mysqli_num_rows($employerResult) > 0) {
    $employerData = mysqli_fetch_assoc($employerResult);
    $companyName = $employerData['company_name'];
}
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <h1>My Job Listings</h1>
        <a href="post_job.php" class="btn btn-primary">
            <i class="fa fa-plus-circle"></i> Post New Job
        </a>
    </div>

    <?php 
    // Display success message if present in URL
    if(isset($_GET['success'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($_GET['success']);
        echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>';
        echo '</div>';
    }

    // Display error message if present in URL
    if(isset($_GET['error'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($_GET['error']);
        echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>';
        echo '</div>';
    }
    ?>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="m-0">Job Postings for <?php echo htmlspecialchars($companyName); ?></h5>
        </div>
        <div class="card-body">
            <?php
            // Fetch job postings for this employer with detailed information
            $jobsQuery = "SELECT id, title, job_type, location, status, 
                          DATE_FORMAT(created_at, '%M %d, %Y') as posted_date, 
                          DATE_FORMAT(expiry_date, '%M %d, %Y') as expiry, 
                          (SELECT COUNT(*) FROM job_applications WHERE job_id = job_postings.id) as applications_count
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
                            <th>Expiry Date</th>
                            <th>Applications</th>
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
                                <td><?php echo !empty($job['expiry']) ? htmlspecialchars($job['expiry']) : 'No expiry'; ?></td>
                                <td>
                                    <span class="badge badge-info"><?php echo $job['applications_count']; ?></span>
                                    <?php if($job['applications_count'] > 0): ?>
                                    <a href="view_applications.php?job_id=<?php echo $job['id']; ?>" class="btn btn-sm btn-link p-0 ml-2">View</a>
                                    <?php endif; ?>
                                </td>
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
        <div class="card-footer d-flex justify-content-between">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Dashboard
            </a>
            <a href="post_job.php" class="btn btn-success">
                <i class="fa fa-plus-circle"></i> Post New Job
            </a>
        </div>
    </div>
</div>

<?php
// Include footer
include_once($includePath . "footer.php");
?>