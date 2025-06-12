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
$pageTitle = "Edit Job | Job Portal";

// Include header
include_once($includePath . "header.php");

// Get user ID from session
$userId = $_SESSION['user_id'];

// Initialize variables
$job_id = $title = $company_name = $description = $requirements = '';
$location = $job_type = $salary_min = $salary_max = $salary_period = '';
$status = $expiry_date = '';
$error = $success = '';

// Check if job ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $error = "Invalid job ID.";
} else {
    $job_id = intval($_GET['id']);
    
    // Fetch job details if not submitted
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        $query = "SELECT * FROM job_postings WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $job_id, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) > 0) {
            $job = mysqli_fetch_assoc($result);
            
            // Populate form variables
            $title = $job['title'];
            $company_name = $job['company_name'];
            $description = $job['description'];
            $requirements = $job['requirements'];
            $location = $job['location'];
            $job_type = $job['job_type'];
            $salary_min = $job['salary_min'];
            $salary_max = $job['salary_max'];
            $salary_period = $job['salary_period'];
            $status = $job['status'];
            $expiry_date = $job['expiry_date'];
        } else {
            $error = "Job not found or you don't have permission to edit it.";
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data and sanitize
    $job_id = intval($_POST['job_id']);
    $title = trim(mysqli_real_escape_string($conn, $_POST['title']));
    $company_name = trim(mysqli_real_escape_string($conn, $_POST['company_name']));
    $description = trim(mysqli_real_escape_string($conn, $_POST['description']));
    $requirements = trim(mysqli_real_escape_string($conn, $_POST['requirements']));
    $location = trim(mysqli_real_escape_string($conn, $_POST['location']));
    $job_type = trim(mysqli_real_escape_string($conn, $_POST['job_type']));
    
    // Handle numeric fields
    $salary_min = !empty($_POST['salary_min']) ? floatval($_POST['salary_min']) : NULL;
    $salary_max = !empty($_POST['salary_max']) ? floatval($_POST['salary_max']) : NULL;
    
    $salary_period = isset($_POST['salary_period']) ? 
        trim(mysqli_real_escape_string($conn, $_POST['salary_period'])) : NULL;
    $status = trim(mysqli_real_escape_string($conn, $_POST['status']));
    $expiry_date = !empty($_POST['expiry_date']) ? 
        trim(mysqli_real_escape_string($conn, $_POST['expiry_date'])) : NULL;
    
    // Validate required fields
    if(empty($title) || empty($company_name) || empty($description) || empty($job_type)) {
        $error = "Please fill in all required fields.";
    } else {
        // Verify job belongs to current user
        $checkQuery = "SELECT id FROM job_postings WHERE id = ? AND user_id = ?";
        $checkStmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "ii", $job_id, $userId);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);
        
        if(mysqli_stmt_num_rows($checkStmt) == 0) {
            $error = "You don't have permission to edit this job.";
        } else {
            // Update job posting
            $updateQuery = "UPDATE job_postings SET 
                            title = ?, company_name = ?, description = ?, 
                            requirements = ?, location = ?, job_type = ?,
                            salary_min = ?, salary_max = ?, salary_period = ?,
                            status = ?, expiry_date = ?
                            WHERE id = ? AND user_id = ?";
            
            $updateStmt = mysqli_prepare($conn, $updateQuery);
            
            mysqli_stmt_bind_param($updateStmt, "ssssssddsssii", 
                $title, $company_name, $description, $requirements, $location, 
                $job_type, $salary_min, $salary_max, $salary_period, $status, 
                $expiry_date, $job_id, $userId);
            
            if(mysqli_stmt_execute($updateStmt)) {
                $success = "Job updated successfully!";
            } else {
                $error = "Error updating job: " . mysqli_stmt_error($updateStmt);
            }
            
            mysqli_stmt_close($updateStmt);
        }
        
        mysqli_stmt_close($checkStmt);
    }
}
?>
<div>
<div class="row">
    <div class="col-md-12">
<h1>
Edit Job

</h1>
<nav>
<ol>
<li>
<a>
Dashboard

</a>
</li>
<li>
<a>
">View Job

</a>
</li>
<li>
Edit Job

</li>
</ol>
</nav>
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?>
</div>
            <?php if(strpos($error, "Invalid") !== false || strpos($error, "not found") !== false): ?>
<div>
<a>
Back to My Jobs

</a>
</div>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if(!empty($success)): ?>
<div>
                <?php echo $success; ?>
<a>
" class="alert-link">View this job posting

</a>
</div>
        <?php endif; ?>
        
        <?php if(empty($error) || strpos($error, "Invalid") === false && strpos($error, "not found") === false): ?>
<div>
                <div class="card-header bg-primary text-white">
<h5>
Job Details

</h5>
</div>
<div>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="needs-validation" novalidate>
                        <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
<label>
Company Name

<span>
</span>
</label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($company_name); ?>" required>
                                    <div class="invalid-feedback">Please provide your company name.
</div>
                                </div>
                            </div>
<div>
                                <div class="form-group">
<label>
Job Title

<span>
</span>
</label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                                    <div class="invalid-feedback">Please provide a job title.
</div>
                                </div>
                            </div>
                        </div>
<div>
<label>
Job Description

<span>
</span>
</label>
<textarea>
<?php echo htmlspecialchars($description); ?>
</textarea>
                            <div class="invalid-feedback">Please provide a job description.
</div>
                        </div>
<div>
<label>
Job Requirements

</label>
<textarea>
<?php echo htmlspecialchars($requirements); ?>
</textarea>
</div>
<div>
                            <div class="col-md-6">
                                <div class="form-group">
<label>
Location

</label>
                                    <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($location); ?>">
</div>
                            </div>
<div>
                                <div class="form-group">
<label>
Job Type

<span>
</span>
</label>
<select>
<option>
Select Job Type

</option>
<option>
Full-time

</option>
<option>
Part-time

</option>
<option>
Contract

</option>
<option>
Internship

</option>
<option>
Temporary

</option>
</select>
</div>
                            </div>
                        </div>
<div>
                            <div class="col-md-4">
                                <div class="form-group">
<label>
Minimum Salary

</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="salary_min" name="salary_min" value="<?php echo $salary_min > 0 ? htmlspecialchars($salary_min) : ''; ?>">
</div>
                            </div>
<div>
                                <div class="form-group">
<label>
Maximum Salary

</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="salary_max" name="salary_max" value="<?php echo $salary_max > 0 ? htmlspecialchars($salary_max) : ''; ?>">
</div>
                            </div>
<div>
                                <div class="form-group">
<label>
Salary Period

</label>
<select>
<option>
Select Period

</option>
<option>
Hourly

</option>
<option>
Daily

</option>
<option>
Weekly

</option>
<option>
Monthly

</option>
<option>
Yearly

</option>
</select>
</div>
                            </div>
                        </div>
<div>
                            <div class="col-md-6">
                                <div class="form-group">
<label>
Status

<span>
</span>
</label>
<select>
<option>
Draft

</option>
<option>
Published

</option>
<option>
Closed

</option>
<option>
Filled

</option>
</select>
</div>
                            </div>
<div>
                                <div class="form-group">
<label>
Expiry Date

</label>
                                    <input type="date" class="form-control" id="expiry_date" name="expiry_date" value="<?php echo htmlspecialchars($expiry_date); ?>">
</div>
                            </div>
                        </div>
<div>
<button type="submit">
Save Changes

</button>
<a href="#" class="btn btn-outline-secondary btn-lg ml-2">Cancel

</a>
</div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
</div> <?php // Include footer include_once($includePath . "footer.php"); ?>