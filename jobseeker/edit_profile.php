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
    $user_result = mysqli_stmt_get_result($user_stmt);
    $user_data = mysqli_fetch_assoc($user_result);
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
                // Update user data
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
                                updated_at = NOW() 
                                WHERE id = ?";
                
                $update_stmt = mysqli_prepare($conn, $update_query);
                
                if ($update_stmt) {
                    mysqli_stmt_bind_param(
                        $update_stmt, 
                        "sssssssssssssi", 
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
                        $jobseeker_id
                    );
                    
                    if (mysqli_stmt_execute($update_stmt)) {
                        // Handle resume upload if provided
                        if (isset($_FILES['resume']) && $_FILES['resume']['error'] == 0) {
                            $resume_name = $_FILES['resume']['name'];
                            $resume_tmp = $_FILES['resume']['tmp_name'];
                            $resume_size = $_FILES['resume']['size'];
                            $resume_ext = strtolower(pathinfo($resume_name, PATHINFO_EXTENSION));
                            
                            // Check file extension
                            $allowed_extensions = array('pdf', 'doc', 'docx');
                            if (in_array($resume_ext, $allowed_extensions)) {
                                // Check file size (max 5MB)
                                if ($resume_size <= 5000000) {
                                    // Generate unique filename
                                    $new_resume_name = "resume_" . $jobseeker_id . "_" . time() . "." . $resume_ext;
                                    $upload_path = "../uploads/resumes/" . $new_resume_name;
                                    
                                    // Move uploaded file
                                    if (move_uploaded_file($resume_tmp, $upload_path)) {
                                        // Update resume path in database
                                        $resume_update_query = "UPDATE users SET resume_path = ? WHERE id = ?";
                                        $resume_update_stmt = mysqli_prepare($conn, $resume_update_query);
                                        
                                        if ($resume_update_stmt) {
                                            mysqli_stmt_bind_param($resume_update_stmt, "si", $new_resume_name, $jobseeker_id);
                                            mysqli_stmt_execute($resume_update_stmt);
                                            mysqli_stmt_close($resume_update_stmt);
                                        }
                                    } else {
                                        $error_message = "Failed to upload resume. Please try again.";
                                    }
                                } else {
                                    $error_message = "Resume file size must be less than 5MB.";
                                }
                            } else {
                                $error_message = "Only PDF, DOC, and DOCX files are allowed for resume.";
                            }
                        }
                        
                        // Handle profile picture upload if provided
                        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                            $pic_name = $_FILES['profile_picture']['name'];
                            $pic_tmp = $_FILES['profile_picture']['tmp_name'];
                            $pic_size = $_FILES['profile_picture']['size'];
                            $pic_ext = strtolower(pathinfo($pic_name, PATHINFO_EXTENSION));
                            
                            // Check file extension
                            $allowed_pic_extensions = array('jpg', 'jpeg', 'png');
                            if (in_array($pic_ext, $allowed_pic_extensions)) {
                                // Check file size (max 2MB)
                                if ($pic_size <= 2000000) {
                                    // Generate unique filename
                                    $new_pic_name = "profile_" . $jobseeker_id . "_" . time() . "." . $pic_ext;
                                    $pic_upload_path = "../uploads/profile_pictures/" . $new_pic_name;
                                    
                                    // Move uploaded file
                                    if (move_uploaded_file($pic_tmp, $pic_upload_path)) {
                                        // Update profile picture path in database
                                        $pic_update_query = "UPDATE users SET profile_picture = ? WHERE id = ?";
                                        $pic_update_stmt = mysqli_prepare($conn, $pic_update_query);
                                        
                                        if ($pic_update_stmt) {
                                            mysqli_stmt_bind_param($pic_update_stmt, "si", $new_pic_name, $jobseeker_id);
                                            mysqli_stmt_execute($pic_update_stmt);
                                            mysqli_stmt_close($pic_update_stmt);
                                        }
                                    } else {
                                        $error_message = "Failed to upload profile picture. Please try again.";
                                    }
                                } else {
                                    $error_message = "Profile picture size must be less than 2MB.";
                                }
                            } else {
                                $error_message = "Only JPG, JPEG, and PNG files are allowed for profile picture.";
                            }
                        }
                        
                        if (empty($error_message)) {
                            $success_message = "Your profile has been updated successfully.";
                            
                            // Refresh user data after update
                            mysqli_stmt_execute($user_stmt);
                            $user_result = mysqli_stmt_get_result($user_stmt);
                            $user_data = mysqli_fetch_assoc($user_result);
                        }
                    } else {
                        $error_message = "Failed to update profile: " . mysqli_error($conn);
                    }
                    
                    mysqli_stmt_close($update_stmt);
                } else {
                    $error_message = "Database error: " . mysqli_error($conn);
                }
            }
            
            mysqli_stmt_close($email_check_stmt);
        } else {
            $error_message = "Database error: " . mysqli_error($conn);
        }
    }
}
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Edit Profile</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success">
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <!-- Personal Information -->
                            <div class="col-md-6">
                                <h5 class="mb-3">Personal Information</h5>
                                
                                <div class="form-group">
                                    <label for="first_name">First Name *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="last_name">Last Name *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($user_data['address'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="city">City</label>
                                        <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($user_data['city'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="state">State/Province</label>
                                        <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars($user_data['state'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="country">Country</label>
                                        <input type="text" class="form-control" id="country" name="country" value="<?php echo htmlspecialchars($user_data['country'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="zip_code">ZIP/Postal Code</label>
                                        <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($user_data['zip_code'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Professional Information -->
                            <div class="col-md-6">
                                <h5 class="mb-3">Professional Information</h5>
                                
                                <div class="form-group">
                                    <label for="skills">Skills</label>
                                    <textarea class="form-control" id="skills" name="skills" rows="2" placeholder="Enter your skills separated by commas"><?php echo htmlspecialchars($user_data['skills'] ?? ''); ?></textarea>
                                    <small class="form-text text-muted">E.g., JavaScript, PHP, Project Management, Communication</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="education">Education</label>
                                    <textarea class="form-control" id="education" name="education" rows="3" placeholder="Enter your educational background"><?php echo htmlspecialchars($user_data['education'] ?? ''); ?></textarea>
                                    <small class="form-text text-muted">Include degrees, institutions, and graduation years</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="experience">Work Experience</label>
                                    <textarea class="form-control" id="experience" name="experience" rows="4" placeholder="Describe your work experience"><?php echo htmlspecialchars($user_data['experience'] ?? ''); ?></textarea>
                                    <small class="form-text text-muted">Include job titles, companies, dates, and responsibilities</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="bio">Bio/Summary</label>
                                    <textarea class="form-control" id="bio" name="bio" rows="3" placeholder="Write a short bio or summary about yourself"><?php echo htmlspecialchars($user_data['bio'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="resume">Resume (PDF, DOC, DOCX, max 5MB)</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="resume" name="resume">
                                        <label class="custom-file-label" for="resume">Choose file</label>
                                    </div>
                                    <?php if (!empty($user_data['resume_path'])): ?>
                                        <small class="form-text text-muted">
                                            Current resume: <a href="../uploads/resumes/<?php echo htmlspecialchars($user_data['resume_path']); ?>" target="_blank"><?php echo htmlspecialchars($user_data['resume_path']); ?></a>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label for="profile_picture">Profile Picture (JPG, JPEG, PNG, max 2MB)</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="profile_picture" name="profile_picture">
                                        <label class="custom-file-label" for="profile_picture">Choose file</label>
                                    </div>
                                    <?php if (!empty($user_data['profile_picture'])): ?>
                                        <div class="mt-2">
                                            <img src="../uploads/profile_pictures/<?php echo htmlspecialchars($user_data['profile_picture']); ?>" alt="Profile Picture" class="img-thumbnail" style="max-width: 150px;">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="form-group text-center">
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