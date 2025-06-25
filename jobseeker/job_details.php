<?php
        // Start the session
        session_start();

        /**
         * job_details.php - Display detailed information about a specific job
         * 
         * This script displays comprehensive information about a job posting
         * based on the job ID provided in the URL parameter.
         */

        // Check if user is logged in and is a job seeker
        if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'jobseeker') {
            header("Location: ../login.php?error=unauthorized");
            exit();
        }

        // Set include path for includes folder
        $includePath = "../includes/";

        // Include database configuration
        include_once($includePath . "db_config.php");

        // Set default page title
        $pageTitle = "Job Details";

        // Get user ID from session
        $userId = $_SESSION['user_id'];

        // Check if job ID is provided in the URL parameters
        if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            header("Location: dashboard.php?error=invalid_job_id");
            exit();
        }

        // Sanitize the job ID
        $jobId = intval($_GET['id']);

        // Query to fetch the job details
        $query = "SELECT j.*, 
                    u.company_name, u.company_logo, u.website, u.industry, u.company_size, u.company_description
                FROM job_postings j
                LEFT JOIN users u ON j.user_id = u.id
                WHERE j.id = ? AND j.status = 'Published' 
                      AND (j.expiry_date IS NULL OR j.expiry_date >= CURDATE())";

        $stmt = mysqli_prepare($conn, $query);

        if (!$stmt) {
            // Handle error
            header("Location: dashboard.php?error=database_error");
            exit();
        }

        // Bind parameters
        mysqli_stmt_bind_param($stmt, "i", $jobId);
        
        // Execute the query
        if (!mysqli_stmt_execute($stmt)) {
            // Handle execution error
            mysqli_stmt_close($stmt);
            header("Location: dashboard.php?error=database_error");
            exit();
        }

        $result = mysqli_stmt_get_result($stmt);
        
        // Check if job exists and is published
        if (mysqli_num_rows($result) == 0) {
            mysqli_stmt_close($stmt);
            header("Location: dashboard.php?error=job_not_found");
            exit();
        }

        // Fetch job details
        $job = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        // Update the page title with job title
        $pageTitle = htmlspecialchars($job['title']) . " - Job Details";

        // Check if the user has already applied for this job
        $hasApplied = false;
        $applyQuery = "SELECT id FROM job_applications WHERE job_id = ? AND user_id = ?";
        $applyStmt = mysqli_prepare($conn, $applyQuery);
        
        if ($applyStmt) {
            mysqli_stmt_bind_param($applyStmt, "ii", $jobId, $userId);
            if (mysqli_stmt_execute($applyStmt)) {
                mysqli_stmt_store_result($applyStmt);
                $hasApplied = (mysqli_stmt_num_rows($applyStmt) > 0);
            }
            mysqli_stmt_close($applyStmt);
        }

        // Include header
        include_once($includePath . "header.php");
        ?>

        <div class="container mt-4">
            <?php 
            // Display error or success message if present in URL
            if (isset($_GET['error'])) {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                echo htmlspecialchars($_GET['error']);
                echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                      </button>';
                echo '</div>';
            } elseif (isset($_GET['success'])) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                echo htmlspecialchars($_GET['success']);
                echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                      </button>';
                echo '</div>';
            }
            ?>

            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0"><?php echo htmlspecialchars($job['title']); ?></h3>
                </div>

                <div class="card-body">
                    <!-- Job header section -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h5>
                                <?php echo htmlspecialchars($job['company_name']); ?>
                                <?php if (!empty($job['location'])): ?>
                                    <small class="text-muted ml-2">
                                        <i class="fa fa-map-marker"></i> <?php echo htmlspecialchars($job['location']); ?>
                                    </small>
                                <?php endif; ?>
                            </h5>
                            <p>
                                <span class="badge badge-primary"><?php echo htmlspecialchars($job['job_type']); ?></span>
                                
                                <?php if (!empty($job['industry'])): ?>
                                    <span class="badge badge-secondary">
                                        <?php echo htmlspecialchars($job['industry']); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <span class="badge badge-info">
                                    Posted: <?php echo date('M d, Y', strtotime($job['created_at'])); ?>
                                </span>
                                
                                <?php if (!empty($job['expiry_date'])): ?>
                                    <span class="badge badge-warning">
                                        Expires: <?php echo date('M d, Y', strtotime($job['expiry_date'])); ?>
                                    </span>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div class="col-md-4 text-right">
                            <?php if ($hasApplied): ?>
                                <button class="btn btn-success" disabled>
                                    <i class="fa fa-check"></i> Already Applied
                                </button>
                            <?php else: ?>
                                <a href="apply_job.php?id=<?php echo $job['id']; ?>" class="btn btn-success">
                                    <i class="fa fa-paper-plane"></i> Apply Now
                                </a>
                            <?php endif; ?>
                            
                            <a href="save_job.php?id=<?php echo $job['id']; ?>" class="btn btn-outline-primary ml-2">
                                <i class="fa fa-bookmark"></i> Save Job
                            </a>
                        </div>
                    </div>
                    
                    <!-- Salary information -->
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h5>Salary Information</h5>
                            <?php
                            // Format and display salary information
                            if (!empty($job['salary_min']) || !empty($job['salary_max'])) {
                                echo '<p class="h4 text-success mb-1">';
                                
                                if (!empty($job['salary_min']) && !empty($job['salary_max'])) {
                                    echo '$' . number_format($job['salary_min'], 2) . ' - $' . number_format($job['salary_max'], 2);
                                } elseif (!empty($job['salary_min'])) {
                                    echo 'From $' . number_format($job['salary_min'], 2);
                                } elseif (!empty($job['salary_max'])) {
                                    echo 'Up to $' . number_format($job['salary_max'], 2);
                                }
                                
                                echo '</p>';
                                
                                // Display salary period
                                if (!empty($job['salary_period'])) {
                                    echo '<p class="text-muted">' . htmlspecialchars($job['salary_period']) . '</p>';
                                }
                            } else {
                                echo '<p class="text-muted">Salary not specified</p>';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <!-- Job details tabs -->
                    <ul class="nav nav-tabs" id="jobDetailsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="description-tab" data-toggle="tab" href="#description" role="tab" aria-controls="description" aria-selected="true">
                                Job Description
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="requirements-tab" data-toggle="tab" href="#requirements" role="tab" aria-controls="requirements" aria-selected="false">
                                Requirements
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="company-tab" data-toggle="tab" href="#company" role="tab" aria-controls="company" aria-selected="false">
                                Company Information
                            </a>
                        </li>
                    </ul>
                    
                    <div class="tab-content p-3 border border-top-0 mb-4" id="jobDetailsTabContent">
                        <!-- Job Description Tab -->
                        <div class="tab-pane fade show active" id="description" role="tabpanel" aria-labelledby="description-tab">
                            <div class="mb-4">
                                <h5 class="border-bottom pb-2">Job Description</h5>
                                <?php 
                                if (!empty($job['description'])) {
                                    echo '<div class="job-description">' . nl2br(htmlspecialchars($job['description'])) . '</div>';
                                } else {
                                    echo '<p class="text-muted">No description provided.</p>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- Requirements Tab -->
                        <div class="tab-pane fade" id="requirements" role="tabpanel" aria-labelledby="requirements-tab">
                            <div class="mb-4">
                                <h5 class="border-bottom pb-2">Job Requirements</h5>
                                <?php 
                                if (!empty($job['requirements'])) {
                                    echo '<div class="job-requirements">' . nl2br(htmlspecialchars($job['requirements'])) . '</div>';
                                } else {
                                    echo '<p class="text-muted">No specific requirements listed.</p>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- Company Information Tab -->
                        <div class="tab-pane fade" id="company" role="tabpanel" aria-labelledby="company-tab">
                            <div class="row">
                                <!-- Company logo if available -->
                                <?php if (!empty($job['company_logo'])): ?>
                                <div class="col-md-3 text-center mb-3">
                                    <img src="../uploads/company_logos/<?php echo htmlspecialchars($job['company_logo']); ?>" 
                                        alt="<?php echo htmlspecialchars($job['company_name']); ?> Logo" 
                                        class="img-fluid company-logo mt-2" style="max-height: 150px;">
                                </div>
                                <div class="col-md-9">
                                <?php else: ?>
                                <div class="col-md-12">
                                <?php endif; ?>
                                    <h5><?php echo htmlspecialchars($job['company_name']); ?></h5>
                                    
                                    <div class="company-details mb-3">
                                        <?php if (!empty($job['industry'])): ?>
                                            <p><strong>Industry:</strong> <?php echo htmlspecialchars($job['industry']); ?></p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($job['company_size'])): ?>
                                            <p><strong>Company Size:</strong> <?php echo htmlspecialchars($job['company_size']); ?></p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($job['website'])): ?>
                                            <p><strong>Website:</strong> 
                                                <a href="<?php echo htmlspecialchars($job['website']); ?>" target="_blank">
                                                    <?php echo htmlspecialchars($job['website']); ?>
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($job['company_description'])): ?>
                                        <h6>About the Company</h6>
                                        <div class="company-description">
                                            <?php echo nl2br(htmlspecialchars($job['company_description'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action buttons -->
                    <div class="row mt-4">
                        <div class="col-md-12 text-center">
                            <?php if ($hasApplied): ?>
                                <button class="btn btn-success btn-lg" disabled>
                                    <i class="fa fa-check"></i> Already Applied
                                </button>
                            <?php else: ?>
                                <a href="apply_job.php?id=<?php echo $job['id']; ?>" class="btn btn-success btn-lg">
                                    <i class="fa fa-paper-plane"></i> Apply for This Job
                                </a>
                            <?php endif; ?>
                            
                            <a href="dashboard.php" class="btn btn-outline-secondary btn-lg ml-2">
                                <i class="fa fa-arrow-left"></i> Back to Dashboard
                            </a>
                            
                            <!-- Report button with modal trigger -->
                            <button type="button" class="btn btn-outline-danger btn-lg ml-2" data-toggle="modal" data-target="#reportJobModal">
                                <i class="fa fa-flag"></i> Report Job
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Similar Jobs section -->
            <div class="card mt-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="card-title mb-0">Similar Jobs</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Query to fetch similar jobs based on job title or industry
                    $similarJobsQuery = "SELECT id, title, company_name, location, job_type, created_at
                                        FROM job_postings
                                        WHERE id != ? AND status = 'Published' 
                                              AND (expiry_date IS NULL OR expiry_date >= CURDATE())
                                              AND (
                                                  title LIKE ? 
                                                  OR industry = (SELECT industry FROM job_postings WHERE id = ?)
                                              )
                                        ORDER BY created_at DESC
                                        LIMIT 3";
                                        
                    $similarStmt = mysqli_prepare($conn, $similarJobsQuery);
                    
                    if ($similarStmt) {
                        // Extract keywords from job title
                        $keywords = '%' . preg_replace('/[^\w\s]/', '', $job['title']) . '%';
                        
                        mysqli_stmt_bind_param($similarStmt, "isi", $jobId, $keywords, $jobId);
                        
                        if (mysqli_stmt_execute($similarStmt)) {
                            $similarResult = mysqli_stmt_get_result($similarStmt);
                            
                            if (mysqli_num_rows($similarResult) > 0) {
                                echo '<div class="row">';
                                while ($similarJob = mysqli_fetch_assoc($similarResult)) {
                                    echo '<div class="col-md-4 mb-3">';
                                    echo '<div class="card h-100 border-secondary">';
                                    echo '<div class="card-body">';
                                    echo '<h6 class="card-title">' . htmlspecialchars($similarJob['title']) . '</h6>';
                                    echo '<p class="card-text">';
                                    echo htmlspecialchars($similarJob['company_name']);
                                    if (!empty($similarJob['location'])) {
                                        echo ' <small class="text-muted"><i class="fa fa-map-marker"></i> ' . htmlspecialchars($similarJob['location']) . '</small>';
                                    }
                                    echo '</p>';
                                    echo '<span class="badge badge-primary">' . htmlspecialchars($similarJob['job_type']) . '</span>';
                                    echo '<p class="small text-muted mt-2">Posted: ' . date('M d, Y', strtotime($similarJob['created_at'])) . '</p>';
                                    echo '</div>';
                                    echo '<div class="card-footer bg-transparent text-center">';
                                    echo '<a href="job_details.php?id=' . $similarJob['id'] . '" class="btn btn-sm btn-outline-primary">View Job</a>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                                echo '</div>';
                            } else {
                                echo '<p class="text-muted">No similar jobs found at the moment.</p>';
                            }
                        }
                        mysqli_stmt_close($similarStmt);
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Report Job Modal -->
        <div class="modal fade" id="reportJobModal" tabindex="-1" aria-labelledby="reportJobModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="reportJobModalLabel">Report this Job</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="report_job.php" method="post">
                        <div class="modal-body">
                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                            
                            <div class="form-group">
                                <label for="reportReason">Reason for reporting:</label>
                                <select class="form-control" id="reportReason" name="reason" required>
                                    <option value="">Select a reason</option>
                                    <option value="spam">Spam or misleading</option>
                                    <option value="scam">Fraudulent or scam</option>
                                    <option value="offensive">Offensive content</option>
                                    <option value="inappropriate">Inappropriate job posting</option>
                                    <option value="expired">Job no longer available</option>
                                    <option value="other">Other reason</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="reportDetails">Additional details:</label>
                                <textarea class="form-control" id="reportDetails" name="details" rows="3" placeholder="Please provide more information about the issue..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Submit Report</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Include Bootstrap JS and jQuery -->
        <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

        <?php
        // Include footer
        include_once($includePath . "footer.php");
        ?>