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
            
            <!-- Recent Jobs Table -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">Recent Job Postings</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Query to fetch the 5 most recent job postings based on the updated schema
                    $query = "SELECT jp.id, jp.title, jp.description, jp.location, 
                                    jp.company_name, jp.job_type, jp.salary_min, jp.salary_max, jp.salary_period,
                                    jp.created_at
                             FROM job_postings jp
                             WHERE jp.status = 'Published' 
                                   AND (jp.expiry_date IS NULL OR jp.expiry_date >= CURDATE())
                             ORDER BY jp.created_at DESC
                             LIMIT 5";
                             
                    $result = mysqli_query($conn, $query);
                    
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
                            <p>No job postings found at the moment. Please check back later!</p>
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