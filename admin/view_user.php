<?php
// Include session handling and authentication check
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Include database configuration
require_once '../includes/db_config.php';

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$userId = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch user details
$query = "SELECT * FROM users WHERE id = '$userId' AND user_type != 'admin'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 0) {
    // User not found or is an admin - redirect back to users page
    header("Location: users.php");
    exit();
}

$user = mysqli_fetch_assoc($result);

// Set page title
$page_title = "User Details: " . $user['first_name'] . ' ' . $user['last_name'];

// Include header
include_once '../includes/header.php';

// Handle user status update if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = mysqli_real_escape_string($conn, $_POST['action']);
        
        if ($action === 'activate') {
            $updateQuery = "UPDATE users SET status = 'active' WHERE id = '$userId'";
        } elseif ($action === 'suspend') {
            $updateQuery = "UPDATE users SET status = 'suspended' WHERE id = '$userId'";
        } elseif ($action === 'deactivate') {
            $updateQuery = "UPDATE users SET status = 'inactive' WHERE id = '$userId'";
        } elseif ($action === 'verify') {
            $updateQuery = "UPDATE users SET is_verified = 1 WHERE id = '$userId'";
        }

        if (isset($updateQuery)) {
            if (mysqli_query($conn, $updateQuery)) {
                $success_message = "User status updated successfully.";
                // Refresh user data
                $result = mysqli_query($conn, $query);
                $user = mysqli_fetch_assoc($result);
            } else {
                $error_message = "Error updating user status: " . mysqli_error($conn);
            }
        }
    }
}
?>

<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-md-12">
            <a href="users.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Back to Users</a>
        </div>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5>Profile Information</h5>
                </div>
                <div class="card-body text-center">
                    <?php if (!empty($user['profile_image']) && $user['profile_image'] != 'default.jpg'): ?>
                        <img src="../uploads/profile/<?php echo $user['profile_image']; ?>" alt="Profile Image" class="img-fluid rounded-circle mb-3" style="max-width: 150px; max-height: 150px;">
                    <?php else: ?>
                        <img src="../assets/images/default.jpg" alt="Default Profile" class="img-fluid rounded-circle mb-3" style="max-width: 150px; max-height: 150px;">
                    <?php endif; ?>
                    
                    <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                    <p>
                        <?php if ($user['user_type'] == 'jobseeker'): ?>
                            <span class="badge badge-info">Job Seeker</span>
                        <?php elseif ($user['user_type'] == 'employer'): ?>
                            <span class="badge badge-primary">Employer</span>
                        <?php endif; ?>
                    </p>
                    
                    <p>
                        <?php if ($user['status'] == 'active'): ?>
                            <span class="badge badge-success">Active</span>
                        <?php elseif ($user['status'] == 'inactive'): ?>
                            <span class="badge badge-warning">Inactive</span>
                        <?php elseif ($user['status'] == 'suspended'): ?>
                            <span class="badge badge-danger">Suspended</span>
                        <?php endif; ?>
                        
                        <?php if ($user['is_verified']): ?>
                            <span class="badge badge-success">Verified</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Not Verified</span>
                        <?php endif; ?>
                    </p>

                    <!-- Action buttons -->
                    <div class="mt-3">
                        <form method="POST" style="display: inline-block;">
                            <?php if ($user['status'] != 'active'): ?>
                                <button type="submit" name="action" value="activate" class="btn btn-success btn-sm">Activate</button>
                            <?php endif; ?>
                            
                            <?php if ($user['status'] != 'suspended'): ?>
                                <button type="submit" name="action" value="suspend" class="btn btn-danger btn-sm">Suspend</button>
                            <?php endif; ?>
                            
                            <?php if ($user['status'] != 'inactive'): ?>
                                <button type="submit" name="action" value="deactivate" class="btn btn-warning btn-sm">Deactivate</button>
                            <?php endif; ?>
                            
                            <?php if (!$user['is_verified']): ?>
                                <button type="submit" name="action" value="verify" class="btn btn-primary btn-sm">Verify</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></li>
                    <li class="list-group-item"><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></li>
                    <li class="list-group-item"><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></li>
                    <li class="list-group-item"><strong>Gender:</strong> <?php echo ucfirst(htmlspecialchars($user['gender'] ?: 'Not provided')); ?></li>
                    <li class="list-group-item"><strong>Date of Birth:</strong> <?php echo $user['date_of_birth'] ? date('F d, Y', strtotime($user['date_of_birth'])) : 'Not provided'; ?></li>
                    <li class="list-group-item"><strong>Member Since:</strong> <?php echo date('F d, Y', strtotime($user['created_at'])); ?></li>
                    <li class="list-group-item"><strong>Last Login:</strong> <?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></li>
                </ul>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5>Contact Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Address:</strong> <?php echo htmlspecialchars($user['address'] ?: 'Not provided'); ?></p>
                            <p><strong>City:</strong> <?php echo htmlspecialchars($user['city'] ?: 'Not provided'); ?></p>
                            <p><strong>State:</strong> <?php echo htmlspecialchars($user['state'] ?: 'Not provided'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Country:</strong> <?php echo htmlspecialchars($user['country'] ?: 'Not provided'); ?></p>
                            <p><strong>Zip Code:</strong> <?php echo htmlspecialchars($user['zip_code'] ?: 'Not provided'); ?></p>
                            <p><strong>Website:</strong> <?php echo !empty($user['website']) ? '<a href="' . htmlspecialchars($user['website']) . '" target="_blank">' . htmlspecialchars($user['website']) . '</a>' : 'Not provided'; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($user['user_type'] == 'jobseeker'): ?>
            <!-- Job Seeker Specific Information -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5>Professional Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Skills:</strong></p>
                    <?php if (!empty($user['skills'])): ?>
                        <div class="mb-3">
                            <?php 
                            $skills = explode(',', $user['skills']);
                            foreach ($skills as $skill): ?>
                                <span class="badge badge-info p-2 mr-2 mb-2"><?php echo htmlspecialchars(trim($skill)); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No skills listed</p>
                    <?php endif; ?>
                    
                    <p><strong>Resume:</strong> 
                        <?php if (!empty($user['resume'])): ?>
                            <a href="../uploads/resumes/<?php echo $user['resume']; ?>" target="_blank" class="btn btn-sm btn-secondary">View Resume</a>
                        <?php else: ?>
                            Not uploaded
                        <?php endif; ?>
                    </p>
                    
                    <p><strong>Bio:</strong></p>
                    <div class="border rounded p-3 bg-light">
                        <?php echo !empty($user['bio']) ? nl2br(htmlspecialchars($user['bio'])) : 'No bio provided'; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($user['user_type'] == 'employer'): ?>
            <!-- Employer Specific Information -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5>Company Information</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex mb-3">
                        <?php if (!empty($user['company_logo']) && $user['company_logo'] != 'default.jpg'): ?>
                            <img src="../uploads/company_logos/<?php echo $user['company_logo']; ?>" alt="Company Logo" class="img-thumbnail mr-3" style="max-width: 100px; max-height: 100px;">
                        <?php else: ?>
                            <img src="../assets/images/company_default.jpg" alt="Default Company Logo" class="img-thumbnail mr-3" style="max-width: 100px; max-height: 100px;">
                        <?php endif; ?>
                        <div>
                            <h4><?php echo htmlspecialchars($user['company_name'] ?: 'Company Name Not Provided'); ?></h4>
                            <p><strong>Industry:</strong> <?php echo htmlspecialchars($user['industry'] ?: 'Not specified'); ?></p>
                            <p><strong>Company Size:</strong> <?php echo htmlspecialchars($user['company_size'] ?: 'Not specified'); ?></p>
                        </div>
                    </div>
                    
                    <p><strong>Company Description:</strong></p>
                    <div class="border rounded p-3 bg-light">
                        <?php echo !empty($user['company_description']) ? nl2br(htmlspecialchars($user['company_description'])) : 'No company description provided'; ?>
                    </div>
                </div>
            </div>
            
            <!-- Employer's Job Postings -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5>Job Postings</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Fetch job postings by this employer
                    $jobsQuery = "SELECT id, title, status, created_at FROM jobs WHERE employer_id = '$userId' ORDER BY created_at DESC";
                    $jobsResult = mysqli_query($conn, $jobsQuery);
                    
                    if (mysqli_num_rows($jobsResult) > 0):
                    ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Job Title</th>
                                    <th>Status</th>
                                    <th>Posted Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($job = mysqli_fetch_assoc($jobsResult)): ?>
                                <tr>
                                    <td><?php echo $job['id']; ?></td>
                                    <td><?php echo htmlspecialchars($job['title']); ?></td>
                                    <td>
                                        <?php if ($job['status'] == 'active'): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php elseif ($job['status'] == 'inactive'): ?>
                                            <span class="badge badge-warning">Inactive</span>
                                        <?php elseif ($job['status'] == 'closed'): ?>
                                            <span class="badge badge-danger">Closed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($job['created_at'])); ?></td>
                                    <td>
                                        <a href="job_details.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-info">View</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                        <p>No job postings found for this employer.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<?php
// Include footer
include_once '../includes/footer.php';

// Close database connection
mysqli_close($conn);
?>