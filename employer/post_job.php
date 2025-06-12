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
$pageTitle = "Post a New Job | Job Portal";

// Include header
include_once($includePath . "header.php");

// Get user ID from session
$userId = $_SESSION['user_id'];

// Initialize variables
$company_name = $title = $description = $requirements = $location = $job_type = '';
$salary_min = $salary_max = 0;
$salary_period = $status = '';
$expiry_date = '';
$error = $success = '';

// Get company info for pre-filling the form
$companyQuery = "SELECT company_name FROM employers WHERE user_id = $userId";
$companyResult = mysqli_query($conn, $companyQuery);

if($companyResult && mysqli_num_rows($companyResult) > 0) {
    $companyData = mysqli_fetch_assoc($companyResult);
    $company_name = $companyData['company_name'];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data and sanitize
    $company_name = trim(mysqli_real_escape_string($conn, $_POST['company_name']));
    $title = trim(mysqli_real_escape_string($conn, $_POST['title']));
    $description = trim(mysqli_real_escape_string($conn, $_POST['description']));
    $requirements = trim(mysqli_real_escape_string($conn, $_POST['requirements']));
    $location = trim(mysqli_real_escape_string($conn, $_POST['location']));
    $job_type = trim(mysqli_real_escape_string($conn, $_POST['job_type']));
    
    // Check if salary fields are provided
    if(!empty($_POST['salary_min'])) {
        $salary_min = floatval($_POST['salary_min']);
    } else {
        $salary_min = NULL;
    }
    
    if(!empty($_POST['salary_max'])) {
        $salary_max = floatval($_POST['salary_max']);
    } else {
        $salary_max = NULL;
    }
    
    $salary_period = isset($_POST['salary_period']) ? trim(mysqli_real_escape_string($conn, $_POST['salary_period'])) : NULL;
    $status = trim(mysqli_real_escape_string($conn, $_POST['status']));
    $expiry_date = !empty($_POST['expiry_date']) ? trim(mysqli_real_escape_string($conn, $_POST['expiry_date'])) : NULL;
    
    // Validate required fields
    if(empty($company_name) || empty($title) || empty($description) || empty($job_type) || empty($status)) {
        $error = "Please fill in all required fields.";
    } else {
        // Prepare and execute SQL query
        $query = "INSERT INTO job_postings (user_id, company_name, title, description, requirements, location, 
                 job_type, salary_min, salary_max, salary_period, status, expiry_date) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        
        if($stmt) {
            // Bind parameters to the prepared statement
            mysqli_stmt_bind_param($stmt, "issssssddsss", 
                $userId, $company_name, $title, $description, $requirements, $location, 
                $job_type, $salary_min, $salary_max, $salary_period, $status, $expiry_date);
            
            // Execute the statement
            if(mysqli_stmt_execute($stmt)) {
                $success = "Job posted successfully!";
                
                // Reset form fields after successful submission
                if($status != 'Draft') {
                    $title = $description = $requirements = $location = '';
                    $salary_min = $salary_max = 0;
                    $salary_period = '';
                    $expiry_date = '';
                }
            } else {
                $error = "Error posting job: " . mysqli_stmt_error($stmt);
            }
            
            mysqli_stmt_close($stmt);
        } else {
            $error = "Error preparing statement: " . mysqli_error($conn);
        }
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mt-4 mb-3">Post a New Job</h1>
            
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Post Job</li>
                </ol>
            </nav>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">Job Details</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="company_name">Company Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($company_name); ?>" required>
                                    <div class="invalid-feedback">Please provide your company name.</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="title">Job Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                                    <div class="invalid-feedback">Please provide a job title.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Job Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($description); ?></textarea>
                            <div class="invalid-feedback">Please provide a job description.</div>
                            <small class="form-text text-muted">Provide a detailed description of the job role, responsibilities, and what a typical day looks like.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="requirements">Job Requirements</label>
                            <textarea class="form-control" id="requirements" name="requirements" rows="4"><?php echo htmlspecialchars($requirements); ?></textarea>
                            <small class="form-text text-muted">List qualifications, skills, experience, education, certifications needed for this role.</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="location">Location</label>
                                    <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($location); ?>">
                                    <small class="form-text text-muted">City, state, or "Remote" if applicable.</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="job_type">Job Type <span class="text-danger">*</span></label>
                                    <select class="form-control" id="job_type" name="job_type" required>
                                        <option value="">Select Job Type</option>
                                        <option value="Full-time" <?php if($job_type == 'Full-time') echo 'selected'; ?>>Full-time</option>
                                        <option value="Part-time" <?php if($job_type == 'Part-time') echo 'selected'; ?>>Part-time</option>
                                        <option value="Contract" <?php if($job_type == 'Contract') echo 'selected'; ?>>Contract</option>
                                        <option value="Internship" <?php if($job_type == 'Internship') echo 'selected'; ?>>Internship</option>
                                        <option value="Temporary" <?php if($job_type == 'Temporary') echo 'selected'; ?>>Temporary</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a job type.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="salary_min">Minimum Salary</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="salary_min" name="salary_min" value="<?php echo $salary_min > 0 ? htmlspecialchars($salary_min) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="salary_max">Maximum Salary</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="salary_max" name="salary_max" value="<?php echo $salary_max > 0 ? htmlspecialchars($salary_max) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="salary_period">Salary Period</label>
                                    <select class="form-control" id="salary_period" name="salary_period">
                                        <option value="">Select Period</option>
                                        <option value="Hourly" <?php if($salary_period == 'Hourly') echo 'selected'; ?>>Hourly</option>
                                        <option value="Daily" <?php if($salary_period == 'Daily') echo 'selected'; ?>>Daily</option>
                                        <option value="Weekly" <?php if($salary_period == 'Weekly') echo 'selected'; ?>>Weekly</option>
                                        <option value="Monthly" <?php if($salary_period == 'Monthly') echo 'selected'; ?>>Monthly</option>
                                        <option value="Yearly" <?php if($salary_period == 'Yearly') echo 'selected'; ?>>Yearly</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">Status <span class="text-danger">*</span></label>
                                    <select class="form-control" id="status" name="status" required>
                                        <option value="">Select Status</option>
                                        <option value="Draft" <?php if($status == 'Draft') echo 'selected'; ?>>Draft</option>
                                        <option value="Published" <?php if($status == 'Published' || empty($status)) echo 'selected'; ?>>Published</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a status.</div>
                                    <small class="form-text text-muted">Draft jobs are only visible to you, not to job seekers.</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="expiry_date">Expiry Date</label>
                                    <input type="date" class="form-control" id="expiry_date" name="expiry_date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($expiry_date); ?>">
                                    <small class="form-text text-muted">The date when this job posting will no longer be active.</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">Post Job</button>
                            <a href="dashboard.php" class="btn btn-outline-secondary btn-lg ml-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="alert alert-info">
                <h5>Tips for creating an effective job posting:</h5>
                <ul>
                    <li>Be specific about the responsibilities and requirements for the role.</li>
                    <li>Include information about your company culture and benefits.</li>
                    <li>Provide clear salary information to attract appropriate candidates.</li>
                    <li>Specify if the position is remote, on-site, or hybrid.</li>
                    <li>Include details about the application process and timeline.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once($includePath . "footer.php");
?>