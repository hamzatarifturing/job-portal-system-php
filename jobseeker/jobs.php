<?php
// Start the session
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Set include path for header/footer
$includePath = "../includes/";
$pageTitle = "Browse Jobs | Job Portal";

// Check if user is an employer (for informational alert only)
$isEmployer = isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'employer';

// Include header
include_once($includePath . "header.php");

// Get search params
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$job_type = isset($_GET['job_type']) ? $_GET['job_type'] : '';

// Build the query
$query = "SELECT * FROM job_postings WHERE status = 'Published'";

// Add search filters if provided
if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $query .= " AND (title LIKE '%$search%' OR description LIKE '%$search%' OR company_name LIKE '%$search%')";
}

if (!empty($location)) {
    $location = mysqli_real_escape_string($conn, $location);
    $query .= " AND location LIKE '%$location%'";
}

if (!empty($job_type)) {
    $job_type = mysqli_real_escape_string($conn, $job_type);
    $query .= " AND job_type = '$job_type'";
}

// Add ordering
$query .= " ORDER BY created_at DESC";

// Execute query
$result = mysqli_query($conn, $query);
$jobs = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $jobs[] = $row;
    }
}

// Get job types for filter dropdown
$typesQuery = "SELECT DISTINCT job_type FROM job_postings WHERE status = 'Published' ORDER BY job_type";
$typesResult = mysqli_query($conn, $typesQuery);
$jobTypes = [];

if ($typesResult && mysqli_num_rows($typesResult) > 0) {
    while ($row = mysqli_fetch_assoc($typesResult)) {
        $jobTypes[] = $row['job_type'];
    }
}

// Get locations for filter dropdown
$locationsQuery = "SELECT DISTINCT location FROM job_postings WHERE status = 'Published' AND location != '' ORDER BY location";
$locationsResult = mysqli_query($conn, $locationsQuery);
$locations = [];

if ($locationsResult && mysqli_num_rows($locationsResult) > 0) {
    while ($row = mysqli_fetch_assoc($locationsResult)) {
        $locations[] = $row['location'];
    }
}
?>

<div class="container mt-4">
    <?php if ($isEmployer): ?>
    <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
        <div class="d-flex align-items-center">
            <div>
                <strong><i class="fa fa-eye"></i> Employer Preview Mode</strong>
                <p class="mb-0">You are viewing the Jobs page as it appears to jobseekers. This view helps you understand how your job postings are seen by potential candidates.</p>
            </div>
            <div class="ml-auto">
                <a href="../employer/dashboard.php" class="btn btn-sm btn-outline-primary">Return to Dashboard</a>
            </div>
        </div>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title text-primary mb-3">Search Jobs</h2>
                    <form action="jobs.php" method="get" class="form-row">
                        <div class="col-md-4 mb-3">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-search"></i></span>
                                </div>
                                <input type="text" class="form-control" name="search" placeholder="Job title, keywords, or company" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-map-marker-alt"></i></span>
                                </div>
                                <input type="text" class="form-control" name="location" placeholder="Location" list="location-list" value="<?php echo htmlspecialchars($location); ?>">
                                <datalist id="location-list">
                                    <?php foreach($locations as $loc): ?>
                                        <option value="<?php echo htmlspecialchars($loc); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <select name="job_type" class="form-control">
                                <option value="">All Job Types</option>
                                <?php foreach($jobTypes as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $job_type === $type ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2 mb-3">
                            <button type="submit" class="btn btn-primary btn-block">Search</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <!-- Sidebar with filters -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">Filters</h5>
                </div>
                <div class="card-body">
                    <form action="jobs.php" method="get">
                        <div class="form-group">
                            <label for="refine-search">Refine Search</label>
                            <input type="text" class="form-control" id="refine-search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Keywords">
                        </div>
                        
                        <div class="form-group">
                            <label>Job Type</label>
                            <?php foreach($jobTypes as $type): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="job_type" id="type-<?php echo htmlspecialchars($type); ?>" value="<?php echo htmlspecialchars($type); ?>" <?php echo $job_type === $type ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="type-<?php echo htmlspecialchars($type); ?>">
                                        <?php echo htmlspecialchars($type); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="job_type" id="type-all" value="" <?php echo $job_type === '' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="type-all">
                                    All Job Types
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="location-filter">Location</label>
                            <input type="text" class="form-control" id="location-filter" name="location" value="<?php echo htmlspecialchars($location); ?>" placeholder="City, State, or Remote">
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
                        <a href="jobs.php" class="btn btn-outline-secondary btn-block mt-2">Clear Filters</a>
                    </form>
                </div>
            </div>
            
            <?php if (!$isEmployer): ?>
            <!-- Jobseeker Menu -->
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    Jobseeker Menu
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
                    <a href="jobs.php" class="list-group-item list-group-item-action active">Browse Jobs</a>
                    <a href="applications.php" class="list-group-item list-group-item-action">My Applications</a>
                    <a href="saved_jobs.php" class="list-group-item list-group-item-action">Saved Jobs</a>
                    <a href="profile.php" class="list-group-item list-group-item-action">My Profile</a>
                    <a href="edit_profile.php" class="list-group-item list-group-item-action">Edit Profile</a>
                </div>
            </div>
            <?php else: ?>
            <!-- Employer Actions -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    Employer Actions
                </div>
                <div class="list-group list-group-flush">
                    <a href="../employer/dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fa fa-arrow-left"></i> Return to Dashboard
                    </a>
                    <a href="../employer/post_job.php" class="list-group-item list-group-item-action">
                        <i class="fa fa-plus-circle"></i> Post a New Job
                    </a>
                    <a href="../employer/job_listings.php" class="list-group-item list-group-item-action">
                        <i class="fa fa-list"></i> Manage Your Jobs
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-9">
            <!-- Job Listings -->
            <div class="card">
                <div class="card-header bg-white">
                    <div class="row align-items-center">
                        <div class="col">
                            <h4 class="m-0">Available Jobs</h4>
                        </div>
                        <div class="col text-right">
                            <?php echo count($jobs); ?> jobs found
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if(empty($jobs)): ?>
                        <div class="alert alert-info m-3">
                            No jobs found matching your criteria. Try adjusting your search filters.
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach($jobs as $job): ?>
                                <div class="list-group-item list-group-item-action p-3 job-item">
                                    <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                        <h5 class="mb-1 text-primary"><?php echo htmlspecialchars($job['title']); ?></h5>
                                        <small class="text-muted">Posted <?php echo date('M d, Y', strtotime($job['created_at'])); ?></small>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <span class="badge badge-primary mr-2"><?php echo htmlspecialchars($job['job_type']); ?></span>
                                        <?php if(!empty($job['location'])): ?>
                                            <span class="text-muted"><i class="fa fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <p class="mb-2">
                                        <strong class="text-secondary"><?php echo htmlspecialchars($job['company_name']); ?></strong>
                                    </p>
                                    
                                    <p class="mb-1">
                                        <?php echo substr(htmlspecialchars($job['description']), 0, 200) . '...'; ?>
                                    </p>
                                    
                                    <div class="mt-2">
                                        <a href="view_job.php?id=<?php echo $job['id']; ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                                        <?php if (!$isEmployer): ?>
                                        <a href="apply_job.php?id=<?php echo $job['id']; ?>" class="btn btn-success btn-sm">Apply Now</a>
                                        <button type="button" class="btn btn-outline-secondary btn-sm save-job-btn" data-job-id="<?php echo $job['id']; ?>">
                                            <i class="fa fa-bookmark"></i> Save
                                        </button>
                                        <?php else: ?>
                                        <a href="../employer/edit_job.php?id=<?php echo $job['id']; ?>" class="btn btn-outline-warning btn-sm">Edit Job</a>
                                        <span class="badge badge-light ml-2" data-toggle="tooltip" title="You're viewing this page as an employer. This is how jobseekers see your listings.">Preview Mode</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Simple JavaScript for job saving functionality (placeholder)
document.addEventListener('DOMContentLoaded', function() {
    const saveButtons = document.querySelectorAll('.save-job-btn');
    
    saveButtons.forEach(button => {
        button.addEventListener('click', function() {
            const jobId = this.getAttribute('data-job-id');
            this.innerHTML = '<i class="fa fa-bookmark"></i> Saved';
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-secondary');
            
            // Here you would typically make an AJAX request to save the job
            console.log('Job saved:', jobId);
            alert('Job saved successfully!');
        });
    });
});
</script>

<?php
// Include footer
include_once($includePath . "footer.php");
?>