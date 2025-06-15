<?php
        // Start the session
        session_start();

        /**
         * dashboard.php - Job Seeker Dashboard
         * 
         * This is the main dashboard for job seekers after they log in.
         * It displays personalized information and recent job postings.
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

        // Set the page title
        $pageTitle = "Job Seeker Dashboard";

        // Get user information from session
        $userId = $_SESSION['user_id'];
        $userFirstName = $_SESSION['first_name'];
        $userLastName = $_SESSION['last_name'];
        $username = $_SESSION['username'];

        // Include header
        include_once($includePath . "header.php");
        ?>

        <div class="container mt-4">
            <div class="jumbotron">
                <h1 class="display-4">Welcome, <?php echo htmlspecialchars($userFirstName); ?>!</h1>
                <p class="lead">This is your job seeker dashboard. From here, you can manage your profile, search for jobs, and track your applications.</p>
                <hr class="my-4">
                <div class="btn-group" role="group">
                    <a class="btn btn-primary btn-lg" href="search_jobs.php" role="button">Search Jobs</a>
                    <a class="btn btn-outline-primary btn-lg" href="my_applications.php" role="button">My Applications</a>
                    <a class="btn btn-outline-secondary btn-lg" href="edit_profile.php" role="button">Edit Profile</a>
                </div>
            </div>
            
            <!-- Dashboard stats -->
            <div class="row">
                <div class="col-md-4">
                    <div class="card mb-4 border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="m-0">Active Applications</h5>
                        </div>
                        <div class="card-body text-center">
                            <p class="card-text display-4">0</p>
                            <a href="my_applications.php" class="btn btn-outline-primary">View Applications</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card mb-4 border-success">
                        <div class="card-header bg-success text-white">
                            <h5 class="m-0">Saved Jobs</h5>
                        </div>
                        <div class="card-body text-center">
                            <p class="card-text display-4">0</p>
                            <a href="saved_jobs.php" class="btn btn-outline-success">View Saved Jobs</a>
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
                            <a href="profile_stats.php" class="btn btn-outline-info">View Profile Stats</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Jobs Table with Filters -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">Recent Job Postings</h5>
                </div>
                <div class="card-body">
                    <!-- Job Filters -->
                    <div class="mb-4">
                        <form id="jobFilterForm" method="GET" action="">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="locationFilter">Location</label>
                                        <?php
                                        // Get list of distinct locations for the dropdown
                                        $locationsQuery = "SELECT DISTINCT location FROM job_postings 
                                                         WHERE status = 'Published' AND location IS NOT NULL AND location != '' 
                                                         ORDER BY location";
                                        $locationsResult = mysqli_query($conn, $locationsQuery);
                                        ?>
                                        <select class="form-control" id="locationFilter" name="location">
                                            <option value="">Any Location</option>
                                            <?php 
                                            if ($locationsResult && mysqli_num_rows($locationsResult) > 0) {
                                                while ($location = mysqli_fetch_assoc($locationsResult)) {
                                                    $selected = (isset($_GET['location']) && $_GET['location'] == $location['location']) ? 'selected' : '';
                                                    echo '<option value="' . htmlspecialchars($location['location']) . '" ' . $selected . '>' . 
                                                         htmlspecialchars($location['location']) . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="jobTypeFilter">Job Type</label>
                                        <select class="form-control" id="jobTypeFilter" name="job_type">
                                            <option value="">Any Type</option>
                                            <option value="Full-time" <?php echo (isset($_GET['job_type']) && $_GET['job_type'] == 'Full-time') ? 'selected' : ''; ?>>Full-time</option>
                                            <option value="Part-time" <?php echo (isset($_GET['job_type']) && $_GET['job_type'] == 'Part-time') ? 'selected' : ''; ?>>Part-time</option>
                                            <option value="Contract" <?php echo (isset($_GET['job_type']) && $_GET['job_type'] == 'Contract') ? 'selected' : ''; ?>>Contract</option>
                                            <option value="Internship" <?php echo (isset($_GET['job_type']) && $_GET['job_type'] == 'Internship') ? 'selected' : ''; ?>>Internship</option>
                                            <option value="Temporary" <?php echo (isset($_GET['job_type']) && $_GET['job_type'] == 'Temporary') ? 'selected' : ''; ?>>Temporary</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="salaryRangeFilter">Salary Range (Annual)</label>
                                        <select class="form-control" id="salaryRangeFilter" name="salary_range">
                                            <option value="">Any Salary</option>
                                            <option value="0-30000" <?php echo (isset($_GET['salary_range']) && $_GET['salary_range'] == '0-30000') ? 'selected' : ''; ?>>Under $30,000</option>
                                            <option value="30000-50000" <?php echo (isset($_GET['salary_range']) && $_GET['salary_range'] == '30000-50000') ? 'selected' : ''; ?>>$30,000 - $50,000</option>
                                            <option value="50000-70000" <?php echo (isset($_GET['salary_range']) && $_GET['salary_range'] == '50000-70000') ? 'selected' : ''; ?>>$50,000 - $70,000</option>
                                            <option value="70000-100000" <?php echo (isset($_GET['salary_range']) && $_GET['salary_range'] == '70000-100000') ? 'selected' : ''; ?>>$70,000 - $100,000</option>
                                            <option value="100000-150000" <?php echo (isset($_GET['salary_range']) && $_GET['salary_range'] == '100000-150000') ? 'selected' : ''; ?>>$100,000 - $150,000</option>
                                            <option value="150000-0" <?php echo (isset($_GET['salary_range']) && $_GET['salary_range'] == '150000-0') ? 'selected' : ''; ?>>$150,000+</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-2 d-flex align-items-end">
                                    <div class="form-group mb-0 w-100 d-flex">
                                        <button type="submit" class="btn btn-primary flex-grow-1 mr-2">
                                            <i class="fa fa-search"></i> Filter
                                        </button>
                                        <?php if(isset($_GET['location']) || isset($_GET['job_type']) || isset($_GET['salary_range'])): ?>
                                        <a href="dashboard.php" class="btn btn-outline-secondary">
                                            <i class="fa fa-times"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <?php
                    // Build the query with possible filters
                    $query = "SELECT jp.id, jp.title, jp.description, jp.location, 
                                    jp.company_name, jp.job_type, jp.salary_min, jp.salary_max, jp.salary_period,
                                    jp.created_at
                             FROM job_postings jp
                             WHERE jp.status = 'Published' 
                                   AND (jp.expiry_date IS NULL OR jp.expiry_date >= CURDATE())";
                    
                    $params = array();
                    $types = "";
                    
                    // Add location filter if specified
                    if (isset($_GET['location']) && !empty($_GET['location'])) {
                        $query .= " AND jp.location = ?";
                        $params[] = $_GET['location'];
                        $types .= "s";
                    }
                    
                    // Add job type filter if specified
                    if (isset($_GET['job_type']) && !empty($_GET['job_type'])) {
                        $query .= " AND jp.job_type = ?";
                        $params[] = $_GET['job_type'];
                        $types .= "s";
                    }
                    
                    // Add salary range filter if specified
                    if (isset($_GET['salary_range']) && !empty($_GET['salary_range'])) {
                        $salaryRange = explode('-', $_GET['salary_range']);
                        if (count($salaryRange) == 2) {
                            $minSalary = floatval($salaryRange[0]);
                            $maxSalary = floatval($salaryRange[1]);
                            
                            if ($maxSalary > 0) {
                                // Both min and max are specified
                                $query .= " AND (
                                    (jp.salary_period = 'Yearly' AND jp.salary_min >= ? AND jp.salary_max <= ?) OR
                                    (jp.salary_period = 'Monthly' AND jp.salary_min * 12 >= ? AND jp.salary_max * 12 <= ?) OR
                                    (jp.salary_period = 'Weekly' AND jp.salary_min * 52 >= ? AND jp.salary_max * 52 <= ?) OR
                                    (jp.salary_period = 'Hourly' AND jp.salary_min * 2080 >= ? AND jp.salary_max * 2080 <= ?)
                                )";
                                $params[] = $minSalary;
                                $params[] = $maxSalary;
                                $params[] = $minSalary;
                                $params[] = $maxSalary;
                                $params[] = $minSalary;
                                $params[] = $maxSalary;
                                $params[] = $minSalary;
                                $params[] = $maxSalary;
                                $types .= "dddddddd";
                            } else {
                                // Only min is specified (for salary ranges like "150000+")
                                $query .= " AND (
                                    (jp.salary_period = 'Yearly' AND jp.salary_min >= ?) OR
                                    (jp.salary_period = 'Monthly' AND jp.salary_min * 12 >= ?) OR
                                    (jp.salary_period = 'Weekly' AND jp.salary_min * 52 >= ?) OR
                                    (jp.salary_period = 'Hourly' AND jp.salary_min * 2080 >= ?)
                                )";
                                $params[] = $minSalary;
                                $params[] = $minSalary;
                                $params[] = $minSalary;
                                $params[] = $minSalary;
                                $types .= "dddd";
                            }
                        }
                    }
                    
                    // Add order and limit
                    $query .= " ORDER BY jp.created_at DESC LIMIT 5";
                    
                    // Use prepared statement if we have parameters
                    if (count($params) > 0) {
                        $stmt = mysqli_prepare($conn, $query);
                        
                        // Bind parameters if we have them
                        if ($stmt) {
                            // Create dynamic parameter binding
                            $bindParams = array();
                            $bindParams[] = $types;
                            
                            for ($i = 0; $i < count($params); $i++) {
                                $bindParams[] = &$params[$i];
                            }
                            
                            // Call bind_param with our dynamically created parameter array
                            call_user_func_array(array($stmt, 'bind_param'), $bindParams);
                            
                            // Execute the statement
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                        } else {
                            // If prepare failed
                            $result = false;
                        }
                    } else {
                        // No parameters, just run the query
                        $result = mysqli_query($conn, $query);
                    }
                    
                    // Display the filtered jobs
                    if ($result && mysqli_num_rows($result) > 0) {
                    ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Job Title</th>
                                    <th>Company</th>
                                    <th>Location</th>
                                    <th>Job Type</th>
                                    <th>Salary</th>
                                    <th>Posted Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result)) { 
                                    // Format salary display
                                    $salaryDisplay = '';
                                    if (!empty($row['salary_min']) || !empty($row['salary_max'])) {
                                        if (!empty($row['salary_min']) && !empty($row['salary_max'])) {
                                            $salaryDisplay = '$' . number_format($row['salary_min'], 2) . ' - $' . number_format($row['salary_max'], 2);
                                        } elseif (!empty($row['salary_min'])) {
                                            $salaryDisplay = 'From $' . number_format($row['salary_min'], 2);
                                        } elseif (!empty($row['salary_max'])) {
                                            $salaryDisplay = 'Up to $' . number_format($row['salary_max'], 2);
                                        }
                                        
                                        if (!empty($row['salary_period'])) {
                                            $salaryDisplay .= ' (' . $row['salary_period'] . ')';
                                        }
                                    } else {
                                        $salaryDisplay = 'Not specified';
                                    }
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['location']); ?></td>
                                        <td><?php echo htmlspecialchars($row['job_type']); ?></td>
                                        <td><?php echo $salaryDisplay; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <a href="job_details.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">View</a>
                                            <a href="apply_job.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success">Apply</a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <?php } else { ?>
                        <div class="alert alert-info">
                            <p>No job postings found matching your criteria. Try adjusting your filters or check back later!</p>
                        </div>
                    <?php } ?>
                    
                    <div class="text-right mt-3">
                        <a href="search_jobs.php" class="btn btn-primary">View All Job Postings</a>
                    </div>
                </div>
            </div>
            
            <!-- Profile completion reminder -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Complete Your Profile</h5>
                            <p class="card-text">A complete profile increases your chances of getting hired. Add your skills, education, and work experience.</p>
                            <div class="progress mb-3">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: 40%;" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100">40%</div>
                            </div>
                            <a href="edit_profile.php" class="btn btn-warning">Complete Profile</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="m-0">Job Recommendations</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <p>Welcome to your job seeker dashboard!</p>
                                <p>Complete your profile to see personalized job recommendations based on your skills and experience.</p>
                            </div>
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
                                    <td><span class="badge badge-primary">Job Seeker</span></td>
                                </tr>
                                <tr>
                                    <th>Last Login:</th>
                                    <td><?php echo isset($_SESSION['last_login']) ? $_SESSION['last_login'] : 'First login'; ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Resume Information</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Resume:</th>
                                    <td><?php echo isset($_SESSION['has_resume']) && $_SESSION['has_resume'] ? 
                                        '<span class="text-success">Uploaded</span>' : 
                                        '<span class="text-danger">Not uploaded</span>'; ?></td>
                                </tr>
                                <tr>
                                    <th>Skills:</th>
                                    <td><?php echo isset($_SESSION['skills']) ? htmlspecialchars($_SESSION['skills']) : 'Not specified'; ?></td>
                                </tr>
                            </table>
                            <div class="text-right">
                                <a href="upload_resume.php" class="btn btn-outline-primary">Upload Resume</a>
                                <a href="edit_profile.php" class="btn btn-outline-secondary">Edit Profile</a>
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