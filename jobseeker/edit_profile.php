<?php
// Start the session
session_start();

// Check if user is logged in and is a jobseeker
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'jobseeker') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Include database configuration and header
include_once("../includes/db_config.php");
include_once("../includes/header.php");

// Get jobseeker ID from session
$jobseeker_id = $_SESSION['user_id'];

// Initialize variables
$success_message = "";
$error_message = "";

// Get current user data
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);

if ($user_stmt) {
    mysqli_stmt_bind_param($user_stmt, "i", $jobseeker_id);
    mysqli_stmt_execute($user_stmt);
    
    // Bind results - PHP5 compatible method
    $user_result = array();
    
    // Create a result object
    $result = mysqli_stmt_get_result($user_stmt);
    
    // If mysqli_stmt_get_result doesn't work due to mysqlnd driver missing, use this alternative
    if (!$result) {
        mysqli_stmt_bind_result($user_stmt, 
            $user_result['id'], 
            $user_result['first_name'], 
            $user_result['last_name'], 
            $user_result['email'], 
            $user_result['phone'], 
            $user_result['address'], 
            $user_result['city'], 
            $user_result['state'], 
            $user_result['country'], 
            $user_result['zip_code'], 
            $user_result['skills'], 
            $user_result['education'], 
            $user_result['experience'], 
            $user_result['bio'], 
            $user_result['resume'], 
            $user_result['profile_picture'],
            $user_result['password'],
            $user_result['status'],
            $user_result['created_at'],
            $user_result['updated_at'],
            $user_result['last_login']
        );
        mysqli_stmt_fetch($user_stmt);
        $user_data = $user_result;
    } else {
        // If mysqli_stmt_get_result works, use it (requires mysqlnd driver)
        $user_data = mysqli_fetch_assoc($result);
    }
    
    mysqli_stmt_close($user_stmt);
} else {
    $error_message = "Database error: " . mysqli_error($conn);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get form data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $city = isset($_POST['city']) ? trim($_POST['city']) : '';
    $state = isset($_POST['state']) ? trim($_POST['state']) : '';
    $country = isset($_POST['country']) ? trim($_POST['country']) : '';
    $zip_code = isset($_POST['zip_code']) ? trim($_POST['zip_code']) : '';
    $skills = isset($_POST['skills']) ? trim($_POST['skills']) : '';
    $education = isset($_POST['education']) ? trim($_POST['education']) : '';
    $experience = isset($_POST['experience']) ? trim($_POST['experience']) : '';
    $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
    
    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error_message = "First name, last name, and email are required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // Check if email already exists for another user
        $email_check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $email_check_stmt = mysqli_prepare($conn, $email_check_query);
        
        if ($email_check_stmt) {
            mysqli_stmt_bind_param($email_check_stmt, "si", $email, $jobseeker_id);
            mysqli_stmt_execute($email_check_stmt);
            mysqli_stmt_store_result($email_check_stmt);
            
            if (mysqli_stmt_num_rows($email_check_stmt) > 0) {
                $error_message = "This email is already in use by another account.";
            } else {
                // Handle resume upload
                $resume_path = isset($user_data['resume']) ? $user_data['resume'] : '';
                if (isset($_FILES['resume']) && $_FILES['resume']['size'] > 0) {
                    $resume_dir = "../uploads/resumes/";
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($resume_dir)) {
                        mkdir($resume_dir, 0755, true);
                    }
                    
                    $resume_name = $jobseeker_id . "_" . basename($_FILES["resume"]["name"]);
                    $resume_path = $resume_dir . $resume_name;
                    
                    // Define allowed file types
                    $allowed_types = array('pdf', 'doc', 'docx');
                    $file_ext = strtolower(pathinfo($resume_path, PATHINFO_EXTENSION));
                    
                    if (!in_array($file_ext, $allowed_types)) {
                        $error_message = "Only PDF, DOC, and DOCX files are allowed for resume.";
                    } elseif ($_FILES["resume"]["size"] > 5000000) { // 5MB max
                        $error_message = "Resume file is too large. Max size is 5MB.";
                    } else {
                        if (move_uploaded_file($_FILES["resume"]["tmp_name"], $resume_path)) {
                            // Successfully uploaded
                        } else {
                            $error_message = "Failed to upload resume.";
                            $resume_path = isset($user_data['resume']) ? $user_data['resume'] : ''; // keep existing resume
                        }
                    }
                }
                
                // Handle profile picture upload
                $profile_pic_path = isset($user_data['profile_picture']) ? $user_data['profile_picture'] : '';
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['size'] > 0) {
                    $pic_dir = "../uploads/profile_pics/";
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($pic_dir)) {
                        mkdir($pic_dir, 0755, true);
                    }
                    
                    $pic_name = $jobseeker_id . "_" . basename($_FILES["profile_picture"]["name"]);
                    $profile_pic_path = $pic_dir . $pic_name;
                    
                    // Define allowed image types
                    $allowed_types = array('jpg', 'jpeg', 'png');
                    $file_ext = strtolower(pathinfo($profile_pic_path, PATHINFO_EXTENSION));
                    
                    if (!in_array($file_ext, $allowed_types)) {
                        $error_message = "Only JPG, JPEG, and PNG files are allowed for profile picture.";
                    } elseif ($_FILES["profile_picture"]["size"] > 2000000) { // 2MB max
                        $error_message = "Profile picture is too large. Max size is 2MB.";
                    } else {
                        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $profile_pic_path)) {
                            // Successfully uploaded
                        } else {
                            $error_message = "Failed to upload profile picture.";
                            $profile_pic_path = isset($user_data['profile_picture']) ? $user_data['profile_picture'] : ''; // keep existing picture
                        }
                    }
                }
                
                // If no errors, update the user data
                if (empty($error_message)) {
                    $update_query = "UPDATE users SET 
                                    first_name = ?, 
                                    last_name = ?, 
                                    email = ?, 
                                    phone = ?, 
                                    address = ?, 
                                    city = ?, 
                                    state = ?, 
                                    country = ?, 
                                    zip_code = ?, 
                                    skills = ?, 
                                    education = ?, 
                                    experience = ?, 
                                    bio = ?, 
                                    resume = ?, 
                                    profile_picture = ?, 
                                    updated_at = NOW() 
                                    WHERE id = ?";
                    
                    $update_stmt = mysqli_prepare($conn, $update_query);
                    
                    if ($update_stmt) {
                        mysqli_stmt_bind_param(
                            $update_stmt, 
                            "ssssssssssssssi", 
                            $first_name, 
                            $last_name, 
                            $email, 
                            $phone, 
                            $address, 
                            $city, 
                            $state, 
                            $country, 
                            $zip_code, 
                            $skills, 
                            $education, 
                            $experience, 
                            $bio, 
                            $resume_path, 
                            $profile_pic_path, 
                            $jobseeker_id
                        );
                        
                        if (mysqli_stmt_execute($update_stmt)) {
                            $success_message = "Your profile has been updated successfully!";
                            
                            // Refresh user data to display updated information
                            $user_stmt = mysqli_prepare($conn, $user_query);
                            
                            if ($user_stmt) {
                                mysqli_stmt_bind_param($user_stmt, "i", $jobseeker_id);
                                mysqli_stmt_execute($user_stmt);
                                
                                // Bind results - PHP5 compatible method
                                $user_result = array();
                                
                                // Create a result object
                                $result = mysqli_stmt_get_result($user_stmt);
                                
                                // If mysqli_stmt_get_result doesn't work due to mysqlnd driver missing, use this alternative
                                if (!$result) {
                                    mysqli_stmt_bind_result($user_stmt, 
                                        $user_result['id'], 
                                        $user_result['first_name'], 
                                        $user_result['last_name'], 
                                        $user_result['email'], 
                                        $user_result['phone'], 
                                        $user_result['address'], 
                                        $user_result['city'], 
                                        $user_result['state'], 
                                        $user_result['country'], 
                                        $user_result['zip_code'], 
                                        $user_result['skills'], 
                                        $user_result['education'], 
                                        $user_result['experience'], 
                                        $user_result['bio'], 
                                        $user_result['resume'], 
                                        $user_result['profile_picture'],
                                        $user_result['password'],
                                        $user_result['status'],
                                        $user_result['created_at'],
                                        $user_result['updated_at'],
                                        $user_result['last_login']
                                    );
                                    mysqli_stmt_fetch($user_stmt);
                                    $user_data = $user_result;
                                } else {
                                    // If mysqli_stmt_get_result works, use it (requires mysqlnd driver)
                                    $user_data = mysqli_fetch_assoc($result);
                                }
                                
                                mysqli_stmt_close($user_stmt);
                            }
                        } else {
                            $error_message = "Failed to update profile: " . mysqli_error($conn);
                        }
                        
                        mysqli_stmt_close($update_stmt);
                    } else {
                        $error_message = "Database error: " . mysqli_error($conn);
                    }
                }
            }
            
            mysqli_stmt_close($email_check_stmt);
        } else {
            $error_message = "Database error: " . mysqli_error($conn);
        }
    }
}
?>

<!-- Display success/error messages -->
<div class="container mt-4">
    <?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success_message; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error_message; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- HTML form with Bootstrap styling -->
<div class="container my-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Edit Profile</h4>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                        
                        <!-- Personal Information Section -->
                        <h5 class="border-bottom pb-2 mb-4">Personal Information</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="first_name">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars(isset($user_data['first_name']) ? $user_data['first_name'] : ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="last_name">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars(isset($user_data['last_name']) ? $user_data['last_name'] : ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars(isset($user_data['email']) ? $user_data['email'] : ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars(isset($user_data['phone']) ? $user_data['phone'] : ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars(isset($user_data['address']) ? $user_data['address'] : ''); ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="city">City</label>
                                    <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars(isset($user_data['city']) ? $user_data['city'] : ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="state">State</label>
                                    <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars(isset($user_data['state']) ? $user_data['state'] : ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="country">Country</label>
                                    <input type="text" class="form-control" id="country" name="country" value="<?php echo htmlspecialchars(isset($user_data['country']) ? $user_data['country'] : ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="zip_code">Zip/Postal Code</label>
                                    <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars(isset($user_data['zip_code']) ? $user_data['zip_code'] : ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Profile Picture -->
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="profile_picture">Profile Picture (JPG, JPEG, PNG, max 2MB)</label>
                                    <input type="file" class="form-control-file" id="profile_picture" name="profile_picture">
                                    <?php if (isset($user_data['profile_picture']) && !empty($user_data['profile_picture'])): ?>
                                    <div class="mt-2">
                                        <img src="<?php echo htmlspecialchars($user_data['profile_picture']); ?>" alt="Profile Picture" class="img-thumbnail" style="max-width: 150px;">
                                        <p class="text-muted small">Current profile picture</p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Professional Information Section -->
                        <h5 class="border-bottom pb-2 mb-4 mt-5">Professional Information</h5>
                        
                        <div class="form-group">
                            <label for="skills">Skills (separate with commas)</label>
                            <input type="text" class="form-control" id="skills" name="skills" value="<?php echo htmlspecialchars(isset($user_data['skills']) ? $user_data['skills'] : ''); ?>">
                            <small class="form-text text-muted">E.g., PHP, JavaScript, HTML, CSS, Project Management</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="education">Education</label>
                            <textarea class="form-control" id="education" name="education" rows="3"><?php echo htmlspecialchars(isset($user_data['education']) ? $user_data['education'] : ''); ?></textarea>
                            <small class="form-text text-muted">Include your degrees, institutions, graduation years</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="experience">Work Experience</label>
                            <textarea class="form-control" id="experience" name="experience" rows="4"><?php echo htmlspecialchars(isset($user_data['experience']) ? $user_data['experience'] : ''); ?></textarea>
                            <small class="form-text text-muted">Include your previous job positions, companies, years</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="bio">Professional Summary</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo htmlspecialchars(isset($user_data['bio']) ? $user_data['bio'] : ''); ?></textarea>
                            <small class="form-text text-muted">A brief description about yourself, your career goals and achievements</small>
                        </div>
                        
                        <!-- Resume Upload -->
                        <div class="form-group mt-4">
                            <label for="resume">Resume (PDF, DOC, DOCX, max 5MB)</label>
                            <input type="file" class="form-control-file" id="resume" name="resume">
                            <?php if (isset($user_data['resume']) && !empty($user_data['resume'])): ?>
                            <div class="mt-2">
                                <p>Current resume: <a href="<?php echo htmlspecialchars($user_data['resume']); ?>" target="_blank"><?php echo basename($user_data['resume']); ?></a></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="form-group mt-5">
                            <button type="submit" name="update_profile" class="btn btn-primary btn-lg">Update Profile</button>
                            <a href="dashboard.php" class="btn btn-secondary btn-lg ml-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once("../includes/footer.php");
?>