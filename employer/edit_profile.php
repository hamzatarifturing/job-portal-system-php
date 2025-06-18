<?php
// Start the session
session_start();

// Check if user is logged in and is an employer
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Include database configuration and header
include_once("../includes/db_config.php");
include_once("../includes/header.php");

// Get employer ID from session
$employer_id = $_SESSION['user_id'];

// Initialize variables
$success_message = "";
$error_message = "";

// Get current user data
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);

if ($user_stmt) {
    mysqli_stmt_bind_param($user_stmt, "i", $employer_id);
    mysqli_stmt_execute($user_stmt);
    
    // Bind results - PHP5 compatible method
    $user_result = array();
    
    // Create a result object
    $result = mysqli_stmt_get_result($user_stmt);
    
    // If mysqli_stmt_get_result doesn't work due to mysqlnd driver missing, use this alternative
    if (!$result) {
        mysqli_stmt_bind_result($user_stmt, 
            $user_result['id'], 
            $user_result['username'],
            $user_result['email'],
            $user_result['password'],
            $user_result['user_type'],
            $user_result['first_name'],
            $user_result['last_name'],
            $user_result['gender'],
            $user_result['date_of_birth'],
            $user_result['profile_image'],
            $user_result['phone'],
            $user_result['address'],
            $user_result['city'],
            $user_result['state'],
            $user_result['country'],
            $user_result['zip_code'],
            $user_result['bio'],
            $user_result['skills'],
            $user_result['resume'],
            $user_result['company_name'],
            $user_result['company_logo'],
            $user_result['company_description'],
            $user_result['industry'],
            $user_result['company_size'],
            $user_result['website'],
            $user_result['status'],
            $user_result['is_verified'],
            $user_result['verification_token'],
            $user_result['reset_token'],
            $user_result['reset_token_expiry'],
            $user_result['remember_token'],
            $user_result['last_login'],
            $user_result['created_at'],
            $user_result['updated_at']
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
    // Username and email are not included in the form since they're disabled,
    // so we'll use the current values from the database
    $username = $user_data['username'];
    $email = $user_data['email'];
    
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
    $date_of_birth = isset($_POST['date_of_birth']) ? trim($_POST['date_of_birth']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $city = isset($_POST['city']) ? trim($_POST['city']) : '';
    $state = isset($_POST['state']) ? trim($_POST['state']) : '';
    $country = isset($_POST['country']) ? trim($_POST['country']) : '';
    $zip_code = isset($_POST['zip_code']) ? trim($_POST['zip_code']) : '';
    $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
    
    // Validate required fields
    if (empty($first_name) || empty($last_name)) {
        $error_message = "First name and last name are required fields.";
    } else {
        // Additional server-side validation
        if (!empty($phone) && !preg_match('/^[0-9]{10,15}$/', $phone)) {
            $error_message = "Phone number should be between 10-15 digits with no spaces or special characters.";
        } elseif (!empty($zip_code) && !preg_match('/^[A-Za-z0-9- ]{3,20}$/', $zip_code)) {
            $error_message = "Invalid postal/zip code format.";
        } else {
            // Handle profile image upload
            $profile_image_path = isset($user_data['profile_image']) ? $user_data['profile_image'] : '';
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['size'] > 0) {
                $profile_image_dir = "../uploads/profile_images/";
                
                // Create directory if it doesn't exist
                if (!file_exists($profile_image_dir)) {
                    mkdir($profile_image_dir, 0755, true);
                }
                
                $profile_image_name = $employer_id . "_" . basename($_FILES["profile_image"]["name"]);
                $profile_image_path = $profile_image_dir . $profile_image_name;
                
                // Define allowed image types
                $allowed_types = array('jpg', 'jpeg', 'png');
                $file_ext = strtolower(pathinfo($profile_image_path, PATHINFO_EXTENSION));
                
                if (!in_array($file_ext, $allowed_types)) {
                    $error_message = "Only JPG, JPEG, and PNG files are allowed for profile image.";
                } elseif ($_FILES["profile_image"]["size"] > 2000000) { // 2MB max
                    $error_message = "Profile image file is too large. Max size is 2MB.";
                } else {
                    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $profile_image_path)) {
                        // Successfully uploaded
                    } else {
                        $error_message = "Failed to upload profile image.";
                        $profile_image_path = isset($user_data['profile_image']) ? $user_data['profile_image'] : ''; // keep existing image
                    }
                }
            }
            
            // If no errors, update the database
            if (empty($error_message)) {
                // Update the user record in the database
                $update_query = "UPDATE users SET 
                    first_name = ?, 
                    last_name = ?, 
                    gender = ?, 
                    date_of_birth = ?, 
                    profile_image = ?, 
                    phone = ?, 
                    address = ?, 
                    city = ?, 
                    state = ?, 
                    country = ?, 
                    zip_code = ?, 
                    bio = ?, 
                    updated_at = NOW() 
                WHERE id = ?";
                
                $update_stmt = mysqli_prepare($conn, $update_query);
                
                if ($update_stmt) {
                    mysqli_stmt_bind_param($update_stmt, "ssssssssssssi", 
                        $first_name,
                        $last_name,
                        $gender,
                        $date_of_birth,
                        $profile_image_path,
                        $phone,
                        $address,
                        $city,
                        $state,
                        $country,
                        $zip_code,
                        $bio,
                        $employer_id
                    );
                    
                    if (mysqli_stmt_execute($update_stmt)) {
                        $success_message = "Profile updated successfully!";
                        
                        // Update user data to reflect changes
                        $user_data['first_name'] = $first_name;
                        $user_data['last_name'] = $last_name;
                        $user_data['gender'] = $gender;
                        $user_data['date_of_birth'] = $date_of_birth;
                        $user_data['profile_image'] = $profile_image_path;
                        $user_data['phone'] = $phone;
                        $user_data['address'] = $address;
                        $user_data['city'] = $city;
                        $user_data['state'] = $state;
                        $user_data['country'] = $country;
                        $user_data['zip_code'] = $zip_code;
                        $user_data['bio'] = $bio;
                    } else {
                        $error_message = "Error updating profile: " . mysqli_error($conn);
                    }
                    
                    mysqli_stmt_close($update_stmt);
                } else {
                    $error_message = "Database error: " . mysqli_error($conn);
                }
            }
        }
    }
}

// Countries list for dropdown
$countries = array("Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", "Anguilla", "Antarctica", "Antigua and Barbuda", "Argentina", "Armenia", "Aruba", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia", "Bosnia and Herzegowina", "Botswana", "Bouvet Island", "Brazil", "British Indian Ocean Territory", "Brunei Darussalam", "Bulgaria", "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada", "Cape Verde", "Cayman Islands", "Central African Republic", "Chad", "Chile", "China", "Christmas Island", "Cocos (Keeling) Islands", "Colombia", "Comoros", "Congo", "Congo, the Democratic Republic of the", "Cook Islands", "Costa Rica", "Cote d'Ivoire", "Croatia (Hrvatska)", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "East Timor", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Falkland Islands (Malvinas)", "Faroe Islands", "Fiji", "Finland", "France", "France Metropolitan", "French Guiana", "French Polynesia", "French Southern Territories", "Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Gibraltar", "Greece", "Greenland", "Grenada", "Guadeloupe", "Guam", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana", "Haiti", "Heard and Mc Donald Islands", "Holy See (Vatican City State)", "Honduras", "Hong Kong", "Hungary", "Iceland", "India", "Indonesia", "Iran (Islamic Republic of)", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Korea, Democratic People's Republic of", "Korea, Republic of", "Kuwait", "Kyrgyzstan", "Lao, People's Democratic Republic", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libyan Arab Jamahiriya", "Liechtenstein", "Lithuania", "Luxembourg", "Macau", "Macedonia, The Former Yugoslav Republic of", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Martinique", "Mauritania", "Mauritius", "Mayotte", "Mexico", "Micronesia, Federated States of", "Moldova, Republic of", "Monaco", "Mongolia", "Montserrat", "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepal", "Netherlands", "Netherlands Antilles", "New Caledonia", "New Zealand", "Nicaragua", "Niger", "Nigeria", "Niue", "Norfolk Island", "Northern Mariana Islands", "Norway", "Oman", "Pakistan", "Palau", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Pitcairn", "Poland", "Portugal", "Puerto Rico", "Qatar", "Reunion", "Romania", "Russian Federation", "Rwanda", "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent and the Grenadines", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Seychelles", "Sierra Leone", "Singapore", "Slovakia (Slovak Republic)", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "South Georgia and the South Sandwich Islands", "Spain", "Sri Lanka", "St. Helena", "St. Pierre and Miquelon", "Sudan", "Suriname", "Svalbard and Jan Mayen Islands", "Swaziland", "Sweden", "Switzerland", "Syrian Arab Republic", "Taiwan, Province of China", "Tajikistan", "Tanzania, United Republic of", "Thailand", "Togo", "Tokelau", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Turks and Caicos Islands", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States", "United States Minor Outlying Islands", "Uruguay", "Uzbekistan", "Vanuatu", "Venezuela", "Vietnam", "Virgin Islands (British)", "Virgin Islands (U.S.)", "Wallis and Futuna Islands", "Western Sahara", "Yemen", "Yugoslavia", "Zambia", "Zimbabwe");
?>

<div class="container mt-4 mb-5">
    <!-- Profile Card Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3 text-center mb-3 mb-md-0">
                            <?php if(isset($user_data['profile_image']) && !empty($user_data['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($user_data['profile_image']); ?>" alt="Profile Image" class="img-fluid rounded-circle" style="max-width: 150px; height: 150px; object-fit: cover; border: 3px solid #007bff;">
                            <?php else: ?>
                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 150px; height: 150px; border: 3px solid #007bff;">
                                    <i class="fa fa-user fa-5x text-secondary"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-5">
                            <h3 class="mb-1"><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></h3>
                            <p class="text-muted mb-2">
                                <i class="fa fa-briefcase mr-2"></i> 
                                Employer at <?php echo htmlspecialchars($user_data['company_name'] ? $user_data['company_name'] : 'No company specified'); ?>
                            </p>
                            <?php if(!empty($user_data['city']) || !empty($user_data['country'])): ?>
                                <p class="mb-2">
                                    <i class="fa fa-map-marker-alt mr-2"></i>
                                    <?php 
                                        $location = [];
                                        if(!empty($user_data['city'])) $location[] = htmlspecialchars($user_data['city']);
                                        if(!empty($user_data['state'])) $location[] = htmlspecialchars($user_data['state']);
                                        if(!empty($user_data['country'])) $location[] = htmlspecialchars($user_data['country']);
                                        echo !empty($location) ? implode(', ', $location) : 'Location not specified';
                                    ?>
                                </p>
                            <?php endif; ?>
                            <?php if(!empty($user_data['phone'])): ?>
                                <p class="mb-2">
                                    <i class="fa fa-phone mr-2"></i> <?php echo htmlspecialchars($user_data['phone']); ?>
                                </p>
                            <?php endif; ?>
                            <p class="mb-0">
                                <i class="fa fa-envelope mr-2"></i> <?php echo htmlspecialchars($user_data['email']); ?>
                            </p>
                        </div>
                        <div class="col-md-4 mt-3 mt-md-0">
                            <div class="d-flex flex-column">
                                <a href="company_profile.php" class="btn btn-outline-primary mb-2">
                                    <i class="fa fa-building mr-1"></i> Company Profile
                                </a>
                                <a href="job_listings.php" class="btn btn-outline-success mb-2">
                                    <i class="fa fa-list mr-1"></i> My Job Listings
                                </a>
                                <a href="post_job.php" class="btn btn-outline-info">
                                    <i class="fa fa-plus-circle mr-1"></i> Post New Job
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">Account Navigation</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
                    <a href="job_listings.php" class="list-group-item list-group-item-action">Job Listings</a>
                    <a href="post_job.php" class="list-group-item list-group-item-action">Post a Job</a>
                    <a href="applications.php" class="list-group-item list-group-item-action">Applications</a>
                    <a href="edit_profile.php" class="list-group-item list-group-item-action active">Edit Profile</a>
                    <a href="company_profile.php" class="list-group-item list-group-item-action">Company Profile</a>
                    <a href="../logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">Edit Profile</h5>
                </div>
                <div class="card-body">
                    <?php if(!empty($success_message)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success_message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="post" enctype="multipart/form-data">
                        <!-- Account Details Section -->
                        <h4 class="mb-3">Account Details</h4>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" disabled>
                                    <small class="form-text text-muted">Username cannot be changed.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" disabled>
                                    <small class="form-text text-muted">Email cannot be changed. Contact support if needed.</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Personal Information Section -->
                        <h4 class="mb-3">Personal Information</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="first_name">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user_data['first_name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="last_name">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user_data['last_name']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="gender">Gender</label>
                                    <select class="form-control" id="gender" name="gender">
                                        <option value="">-- Select Gender --</option>
                                        <option value="male" <?php if(isset($user_data['gender']) && $user_data['gender'] == 'male') echo 'selected'; ?>>Male</option>
                                        <option value="female" <?php if(isset($user_data['gender']) && $user_data['gender'] == 'female') echo 'selected'; ?>>Female</option>
                                        <option value="other" <?php if(isset($user_data['gender']) && $user_data['gender'] == 'other') echo 'selected'; ?>>Other</option>
                                        <option value="prefer_not_to_say" <?php if(isset($user_data['gender']) && $user_data['gender'] == 'prefer_not_to_say') echo 'selected'; ?>>Prefer not to say</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date_of_birth">Date of Birth</label>
                                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($user_data['date_of_birth']); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone']); ?>">
                                    <small class="form-text text-muted">Format: 10-15 digits with no spaces or special characters</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="profile_image">Profile Image</label>
                                    <input type="file" class="form-control-file" id="profile_image" name="profile_image">
                                    <small class="form-text text-muted">Accepted formats: JPG, JPEG, PNG. Max size: 2MB</small>
                                    <?php if(isset($user_data['profile_image']) && !empty($user_data['profile_image'])): ?>
                                    <div class="mt-2">
                                        <label>Current Image:</label>
                                        <img src="<?php echo htmlspecialchars($user_data['profile_image']); ?>" alt="Profile Image" class="img-thumbnail" style="max-width: 100px;">
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Address Section -->
                        <h4 class="mb-3 mt-4">Address Information</h4>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($user_data['address']); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="city">City</label>
                                    <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($user_data['city']); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="state">State/Province</label>
                                    <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars($user_data['state']); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="country">Country</label>
                                    <select class="form-control" id="country" name="country">
                                        <option value="">-- Select Country --</option>
                                        <?php foreach($countries as $country_option): ?>
                                        <option value="<?php echo htmlspecialchars($country_option); ?>" <?php if(isset($user_data['country']) && $user_data['country'] == $country_option) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($country_option); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="zip_code">ZIP/Postal Code</label>
                                    <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($user_data['zip_code']); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Additional Information -->
                        <h4 class="mb-3 mt-4">Additional Information</h4>
                        <div class="form-group">
                            <label for="bio">Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo htmlspecialchars($user_data['bio']); ?></textarea>
                            <small class="form-text text-muted">Provide a brief description about yourself as an employer representative.</small>
                        </div>
                        
                        <div class="alert alert-info mt-3" role="alert">
                            <i class="fa fa-info-circle"></i> To update your company details including company name, logo, industry, description, etc., please go to 
                            <a href="company_profile.php" class="alert-link">Company Profile</a> page.
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="text-center mt-4">
                            <button type="submit" name="update_profile" class="btn btn-primary btn-lg">Update Profile</button>
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