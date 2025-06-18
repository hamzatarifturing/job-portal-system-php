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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_company'])) {
    // Get form data
    $company_name = trim($_POST['company_name']);
    $industry = isset($_POST['industry']) ? trim($_POST['industry']) : '';
    $company_size = isset($_POST['company_size']) ? trim($_POST['company_size']) : '';
    $website = isset($_POST['website']) ? trim($_POST['website']) : '';
    $company_description = isset($_POST['company_description']) ? trim($_POST['company_description']) : '';
    
    // Validate required fields
    if (empty($company_name)) {
        $error_message = "Company name is a required field.";
    } else {
        // Validate website format if provided
        if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
            $error_message = "Please enter a valid website URL (including http:// or https://).";
        } else {
            // Handle company logo upload
            $company_logo_path = isset($user_data['company_logo']) ? $user_data['company_logo'] : '';
            if (isset($_FILES['company_logo']) && $_FILES['company_logo']['size'] > 0) {
                $company_logo_dir = "../uploads/company_logos/";
                
                // Create directory if it doesn't exist
                if (!file_exists($company_logo_dir)) {
                    mkdir($company_logo_dir, 0755, true);
                }
                
                $company_logo_name = $employer_id . "_" . basename($_FILES["company_logo"]["name"]);
                $company_logo_path = $company_logo_dir . $company_logo_name;
                
                // Define allowed image types
                $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
                $file_ext = strtolower(pathinfo($company_logo_path, PATHINFO_EXTENSION));
                
                if (!in_array($file_ext, $allowed_types)) {
                    $error_message = "Only JPG, JPEG, PNG, and GIF files are allowed for company logo.";
                } elseif ($_FILES["company_logo"]["size"] > 3000000) { // 3MB max
                    $error_message = "Company logo file is too large. Max size is 3MB.";
                } else {
                    if (move_uploaded_file($_FILES["company_logo"]["tmp_name"], $company_logo_path)) {
                        // Successfully uploaded
                    } else {
                        $error_message = "Failed to upload company logo.";
                        $company_logo_path = isset($user_data['company_logo']) ? $user_data['company_logo'] : ''; // keep existing logo
                    }
                }
            }
            
            // If no errors, update the database
            if (empty($error_message)) {
                // Update the company information in the database
                $update_query = "UPDATE users SET 
                    company_name = ?, 
                    company_logo = ?, 
                    company_description = ?, 
                    industry = ?, 
                    company_size = ?, 
                    website = ?, 
                    updated_at = NOW() 
                WHERE id = ?";
                
                $update_stmt = mysqli_prepare($conn, $update_query);
                
                if ($update_stmt) {
                    mysqli_stmt_bind_param($update_stmt, "ssssssi", 
                        $company_name,
                        $company_logo_path,
                        $company_description,
                        $industry,
                        $company_size,
                        $website,
                        $employer_id
                    );
                    
                    if (mysqli_stmt_execute($update_stmt)) {
                        $success_message = "Company profile updated successfully!";
                        
                        // Update user data to reflect changes
                        $user_data['company_name'] = $company_name;
                        $user_data['company_logo'] = $company_logo_path;
                        $user_data['company_description'] = $company_description;
                        $user_data['industry'] = $industry;
                        $user_data['company_size'] = $company_size;
                        $user_data['website'] = $website;
                    } else {
                        $error_message = "Error updating company profile: " . mysqli_error($conn);
                    }
                    
                    mysqli_stmt_close($update_stmt);
                } else {
                    $error_message = "Database error: " . mysqli_error($conn);
                }
            }
        }
    }
}

// Industry options
$industries = array(
    "Accounting & Finance",
    "Agriculture & Farming",
    "Automotive",
    "Banking",
    "Biotechnology",
    "Construction",
    "Consulting",
    "Consumer Goods",
    "Education & Training",
    "Energy & Utilities",
    "Entertainment & Media",
    "Government & Public Sector",
    "Healthcare & Pharmaceuticals",
    "Hospitality & Tourism",
    "Information Technology",
    "Insurance",
    "Legal Services",
    "Manufacturing",
    "Marketing & Advertising",
    "Mining & Metals",
    "Non-Profit & NGO",
    "Real Estate",
    "Retail & Wholesale",
    "Software Development",
    "Telecommunications",
    "Transportation & Logistics",
    "Other"
);

// Company size options
$company_sizes = array(
    "1-10 employees",
    "11-50 employees",
    "51-200 employees",
    "201-500 employees",
    "501-1,000 employees",
    "1,001-5,000 employees",
    "5,001-10,000 employees",
    "10,001+ employees"
);
?>

<div class="container mt-4 mb-5">
    <!-- Company Profile Card Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3 text-center mb-3 mb-md-0">
                            <?php if(isset($user_data['company_logo']) && !empty($user_data['company_logo'])): ?>
                                <img src="<?php echo htmlspecialchars($user_data['company_logo']); ?>" alt="Company Logo" class="img-fluid" style="max-width: 150px; max-height: 150px; object-fit: contain;">
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center mx-auto" style="width: 150px; height: 150px; border: 2px dashed #ccc;">
                                    <i class="fa fa-building fa-4x text-secondary"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h3 class="mb-1"><?php echo htmlspecialchars($user_data['company_name'] ? $user_data['company_name'] : 'Company Name Not Set'); ?></h3>
                            <?php if(!empty($user_data['industry'])): ?>
                                <p class="text-muted mb-2">
                                    <i class="fa fa-industry mr-2"></i> <?php echo htmlspecialchars($user_data['industry']); ?>
                                </p>
                            <?php endif; ?>
                            <?php if(!empty($user_data['company_size'])): ?>
                                <p class="mb-2">
                                    <i class="fa fa-users mr-2"></i> <?php echo htmlspecialchars($user_data['company_size']); ?>
                                </p>
                            <?php endif; ?>
                            <?php if(!empty($user_data['website'])): ?>
                                <p class="mb-2">
                                    <i class="fa fa-globe mr-2"></i>
                                    <a href="<?php echo htmlspecialchars($user_data['website']); ?>" target="_blank"><?php echo htmlspecialchars(preg_replace("(^https?://)", "", $user_data['website'])); ?></a>
                                </p>
                            <?php endif; ?>
                            <?php if(!empty($user_data['company_description'])): ?>
                                <div class="mt-2">
                                    <small class="text-muted d-block mb-1">About:</small>
                                    <p class="small"><?php echo nl2br(htmlspecialchars(substr($user_data['company_description'], 0, 150))); ?>
                                    <?php if(strlen($user_data['company_description']) > 150): ?>
                                        <span class="text-muted">...</span>
                                    <?php endif; ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3 mt-3 mt-md-0">
                            <div class="d-flex flex-column">
                                <a href="edit_profile.php" class="btn btn-outline-primary mb-2">
                                    <i class="fa fa-user-edit mr-1"></i> Personal Profile
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
                    <a href="edit_profile.php" class="list-group-item list-group-item-action">Edit Profile</a>
                    <a href="company_profile.php" class="list-group-item list-group-item-action active">Company Profile</a>
                    <a href="../logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">Edit Company Profile</h5>
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
                        <!-- Basic Company Information Section -->
                        <h4 class="mb-3">Basic Company Information</h4>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="company_name">Company Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($user_data['company_name']); ?>" required>
                                    <div class="invalid-feedback">
                                        Company name is required.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="industry">Industry</label>
                                    <select class="form-control" id="industry" name="industry">
                                        <option value="">-- Select Industry --</option>
                                        <?php foreach($industries as $industry_option): ?>
                                        <option value="<?php echo htmlspecialchars($industry_option); ?>" <?php if(isset($user_data['industry']) && $user_data['industry'] == $industry_option) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($industry_option); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="company_size">Company Size</label>
                                    <select class="form-control" id="company_size" name="company_size">
                                        <option value="">-- Select Company Size --</option>
                                        <?php foreach($company_sizes as $size_option): ?>
                                        <option value="<?php echo htmlspecialchars($size_option); ?>" <?php if(isset($user_data['company_size']) && $user_data['company_size'] == $size_option) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($size_option); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="website">Company Website</label>
                                    <input type="url" class="form-control" id="website" name="website" value="<?php echo htmlspecialchars($user_data['website']); ?>" placeholder="https://example.com">
                                    <small class="form-text text-muted">Please include http:// or https:// in the URL</small>
                                    <div class="invalid-feedback" id="website-feedback">
                                        Please enter a valid website URL including http:// or https://.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="company_logo">Company Logo</label>
                                    <input type="file" class="form-control-file" id="company_logo" name="company_logo">
                                    <small class="form-text text-muted">Accepted formats: JPG, JPEG, PNG, GIF. Max size: 3MB. Recommended dimensions: 300x300 pixels.</small>
                                    <?php if(isset($user_data['company_logo']) && !empty($user_data['company_logo'])): ?>
                                    <div class="mt-2">
                                        <label>Current Logo:</label>
                                        <img src="<?php echo htmlspecialchars($user_data['company_logo']); ?>" alt="Company Logo" class="img-thumbnail" style="max-width: 150px;">
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Company Description Section -->
                        <h4 class="mb-3">Company Description</h4>
                        <div class="form-group">
                            <textarea class="form-control" id="company_description" name="company_description" rows="6" placeholder="Describe your company, mission, values, and what makes it a great place to work..."><?php echo htmlspecialchars($user_data['company_description']); ?></textarea>
                            <small class="form-text text-muted">
                                A detailed company description helps job seekers understand your company culture and values. 
                                This information will be visible on your job postings.
                            </small>
                        </div>
                        
                        <!-- SEO Tips for Company Profiles -->
                        <div class="alert alert-info mt-4" role="alert">
                            <h5 class="alert-heading"><i class="fa fa-lightbulb mr-2"></i>Tips for an Effective Company Profile</h5>
                            <p>A complete and compelling company profile helps attract top talent.</p>
                            <hr>
                            <ul class="mb-0">
                                <li>Include your company's mission, vision, and values</li>
                                <li>Highlight your company culture and work environment</li>
                                <li>Mention employee benefits and growth opportunities</li>
                                <li>Add relevant keywords for your industry to improve visibility</li>
                                <li>Keep your information current and accurate</li>
                            </ul>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="text-center mt-4">
                            <button type="submit" name="update_company" class="btn btn-primary btn-lg">Update Company Profile</button>
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

<!-- Client-side validation script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form elements
    const form = document.querySelector('form');
    const companyNameInput = document.getElementById('company_name');
    const websiteInput = document.getElementById('website');
    const companyLogoInput = document.getElementById('company_logo');
    
    // Form submission validation
    form.addEventListener('submit', function(event) {
        let isValid = true;
        
        // Company name validation (required)
        if (!companyNameInput.value.trim()) {
            companyNameInput.classList.add('is-invalid');
            isValid = false;
        } else {
            companyNameInput.classList.remove('is-invalid');
            companyNameInput.classList.add('is-valid');
        }
        
        // Website URL validation (if provided)
        if (websiteInput.value.trim() && !isValidURL(websiteInput.value.trim())) {
            websiteInput.classList.add('is-invalid');
            document.getElementById('website-feedback').textContent = 'Please enter a valid website URL including http:// or https://';
            isValid = false;
        } else {
            websiteInput.classList.remove('is-invalid');
            if (websiteInput.value.trim()) {
                websiteInput.classList.add('is-valid');
            }
        }
        
        // Company logo file validation
        if (companyLogoInput.files.length > 0) {
            const file = companyLogoInput.files[0];
            const fileSize = file.size / 1024 / 1024; // size in MB
            const fileName = file.name.toLowerCase();
            const validExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            // Check file extension
            const fileExtension = fileName.split('.').pop();
            const isValidExtension = validExtensions.includes(fileExtension);
            
            // Check file size (max 3MB)
            const isValidSize = fileSize <= 3;
            
            if (!isValidExtension || !isValidSize) {
                isValid = false;
                companyLogoInput.classList.add('is-invalid');
                
                // Add feedback message if not already present
                let feedback = companyLogoInput.parentNode.querySelector('.invalid-feedback');
                if (!feedback) {
                    feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    companyLogoInput.parentNode.appendChild(feedback);
                }
                
                if (!isValidExtension) {
                    feedback.textContent = 'Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.';
                } else if (!isValidSize) {
                    feedback.textContent = 'File size too large. Maximum size is 3MB.';
                }
            } else {
                companyLogoInput.classList.remove('is-invalid');
                companyLogoInput.classList.add('is-valid');
            }
        }
        
        // Prevent form submission if validation fails
        if (!isValid) {
            event.preventDefault();
            // Scroll to the top of the form to see errors
            window.scrollTo(0, form.offsetTop - 100);
        }
    });
    
    // Validate URL format
    function isValidURL(url) {
        try {
            new URL(url);
            return true;
        } catch (_) {
            return false;
        }
    }
    
    // Real-time validation for company name
    companyNameInput.addEventListener('input', function() {
        if (!this.value.trim()) {
            this.classList.add('is-invalid');
            this.classList.remove('is-valid');
        } else {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        }
    });
    
    // Real-time validation for website URL
    websiteInput.addEventListener('input', function() {
        const value = this.value.trim();
        if (value && !isValidURL(value)) {
            this.classList.add('is-invalid');
            this.classList.remove('is-valid');
            document.getElementById('website-feedback').textContent = 'Please enter a valid website URL including http:// or https://';
        } else {
            this.classList.remove('is-invalid');
            if (value) this.classList.add('is-valid');
        }
    });
    
    // File input change validation
    companyLogoInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            const file = this.files[0];
            const fileSize = file.size / 1024 / 1024; // size in MB
            const fileName = file.name.toLowerCase();
            const validExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            // Check file extension
            const fileExtension = fileName.split('.').pop();
            const isValidExtension = validExtensions.includes(fileExtension);
            
            // Check file size (max 3MB)
            const isValidSize = fileSize <= 3;
            
            // Add or remove feedback message
            let feedback = this.parentNode.querySelector('.invalid-feedback');
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                this.parentNode.appendChild(feedback);
            }
            
            if (!isValidExtension || !isValidSize) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
                
                if (!isValidExtension) {
                    feedback.textContent = 'Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.';
                } else if (!isValidSize) {
                    feedback.textContent = 'File size too large. Maximum size is 3MB.';
                }
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                feedback.textContent = '';
            }
        } else {
            this.classList.remove('is-invalid');
            this.classList.remove('is-valid');
        }
    });
});
</script>