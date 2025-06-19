<?php
// Start the session
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Include database configuration and header
include_once("../includes/db_config.php");
include_once("../includes/header.php");

// Handle application status update if submitted
if (isset($_POST['update_status']) && isset($_POST['application_id']) && isset($_POST['new_status'])) {
    $application_id = $_POST['application_id'];
    $new_status = $_POST['new_status'];
    
    // Valid statuses according to the database schema
    $valid_statuses = ['pending', 'reviewed', 'shortlisted', 'rejected', 'hired'];
    if (in_array($new_status, $valid_statuses)) {
        $update_query = "UPDATE job_applications SET status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "si", $new_status, $application_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Application status updated to " . ucfirst($new_status);
        } else {
            $error_message = "Failed to update application status: " . mysqli_error($conn);
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $error_message = "Invalid status value provided.";
    }
}

// Configure pagination
$records_per_page = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Configure filtering
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$where_clause = "1=1"; // This will always be true, allowing us to add conditions with AND

if (in_array($filter, ['pending', 'reviewed', 'shortlisted', 'rejected', 'hired'])) {
    $where_clause .= " AND ja.status = '$filter'";
}

// Count total applications for pagination
$count_sql = "SELECT COUNT(*) as total FROM job_applications ja WHERE $where_clause";
$count_result = mysqli_query($conn, $count_sql);
$row = mysqli_fetch_assoc($count_result);
$total_applications = $row['total'];
$total_pages = ceil($total_applications / $records_per_page);

// Fetch job applications with related information based on the schema
$sql = "SELECT 
            ja.id, 
            ja.status, 
            ja.cover_letter,
            ja.resume_path,
            ja.application_date,
            jp.id as job_id,
            jp.title as job_title,
            jp.description as job_description,
            jp.location as job_location,
            jp.job_type,
            jp.salary_min,
            jp.salary_max,
            jp.salary_period,
            jp.company_name,
            applicant.id as applicant_id,
            CONCAT(applicant.first_name, ' ', applicant.last_name) as applicant_name,
            applicant.email as applicant_email,
            applicant.phone as applicant_phone,
            applicant.resume as applicant_resume,
            employer.id as employer_id,
            CONCAT(employer.first_name, ' ', employer.last_name) as employer_name,
            employer.email as employer_email,
            employer.company_name as employer_company_name
        FROM job_applications ja
        JOIN job_postings jp ON ja.job_id = jp.id
        JOIN users applicant ON ja.user_id = applicant.id
        JOIN users employer ON jp.user_id = employer.id
        WHERE $where_clause
        ORDER BY ja.application_date DESC
        LIMIT ?, ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $offset, $records_per_page);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="m-0">Job Applications Management</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Success!</strong> <?php echo $success_message; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error!</strong> <?php echo $error_message; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Filter buttons -->
                    <div class="mb-3">
                        <div class="btn-group" role="group">
                            <a href="job_applications.php" class="btn <?php echo $filter == '' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                All Applications
                            </a>
                            <a href="job_applications.php?filter=pending" class="btn <?php echo $filter == 'pending' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                Pending
                            </a>
                            <a href="job_applications.php?filter=reviewed" class="btn <?php echo $filter == 'reviewed' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                Reviewed
                            </a>
                            <a href="job_applications.php?filter=shortlisted" class="btn <?php echo $filter == 'shortlisted' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                Shortlisted
                            </a>
                            <a href="job_applications.php?filter=rejected" class="btn <?php echo $filter == 'rejected' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                Rejected
                            </a>
                            <a href="job_applications.php?filter=hired" class="btn <?php echo $filter == 'hired' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                Hired
                            </a>
                        </div>
                    </div>
                    
                    <!-- Applications count -->
                    <div class="alert alert-info">
                        Total Applications: <strong><?php echo $total_applications; ?></strong>
                        <?php if ($filter): ?>
                        (Filtered by: <strong><?php echo ucfirst($filter); ?></strong>)
                        <?php endif; ?>
                    </div>
                    
                    <!-- Applications table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="thead-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Job Title</th>
                                    <th>Applicant</th>
                                    <th>Employer/Company</th>
                                    <th>Applied On</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['job_title']); ?></strong><br>
                                            <small>Type: <?php echo htmlspecialchars($row['job_type']); ?></small><br>
                                            <small>Location: <?php echo htmlspecialchars($row['job_location']); ?></small>
                                            <?php if ($row['salary_min'] && $row['salary_max']): ?>
                                            <br>
                                            <small>Salary: 
                                                <?php echo number_format($row['salary_min'], 2) . ' - ' . number_format($row['salary_max'], 2); ?> 
                                                <?php echo $row['salary_period'] ? '(' . $row['salary_period'] . ')' : ''; ?>
                                            </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($row['applicant_name']); ?><br>
                                            <small>Email: <?php echo htmlspecialchars($row['applicant_email']); ?></small><br>
                                            <?php if ($row['applicant_phone']): ?>
                                            <small>Phone: <?php echo htmlspecialchars($row['applicant_phone']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['employer_company_name']): ?>
                                                <strong><?php echo htmlspecialchars($row['employer_company_name']); ?></strong><br>
                                            <?php elseif ($row['company_name']): ?>
                                                <strong><?php echo htmlspecialchars($row['company_name']); ?></strong><br>
                                            <?php endif; ?>
                                            <small>Contact: <?php echo htmlspecialchars($row['employer_name']); ?></small><br>
                                            <small>Email: <?php echo htmlspecialchars($row['employer_email']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($row['application_date'])); ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($row['status']) {
                                                case 'pending':
                                                    $status_class = 'warning';
                                                    break;
                                                case 'reviewed':
                                                    $status_class = 'info';
                                                    break;
                                                case 'shortlisted':
                                                    $status_class = 'primary';
                                                    break;
                                                case 'rejected':
                                                    $status_class = 'danger';
                                                    break;
                                                case 'hired':
                                                    $status_class = 'success';
                                                    break;
                                                default:
                                                    $status_class = 'secondary';
                                            }
                                            ?>
                                            <span class="badge badge-<?php echo $status_class; ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info mb-1" data-toggle="modal" data-target="#viewApplicationModal<?php echo $row['id']; ?>">
                                                View Details
                                            </button>
                                            <button type="button" class="btn btn-sm btn-primary mb-1" data-toggle="modal" data-target="#updateStatusModal<?php echo $row['id']; ?>">
                                                Update Status
                                            </button>
                                            
                                            <!-- View Application Modal -->
                                            <div class="modal fade" id="viewApplicationModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewApplicationModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog modal-lg" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="viewApplicationModalLabel<?php echo $row['id']; ?>">
                                                                Application Details #<?php echo $row['id']; ?>
                                                            </h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <h5>Job Details</h5>
                                                                    <p><strong>Title:</strong> <?php echo htmlspecialchars($row['job_title']); ?></p>
                                                                    <p><strong>Type:</strong> <?php echo htmlspecialchars($row['job_type']); ?></p>
                                                                    <p><strong>Location:</strong> <?php echo htmlspecialchars($row['job_location']); ?></p>
                                                                    <?php if ($row['salary_min'] && $row['salary_max']): ?>
                                                                    <p><strong>Salary Range:</strong> 
                                                                        <?php echo number_format($row['salary_min'], 2) . ' - ' . number_format($row['salary_max'], 2); ?> 
                                                                        <?php echo $row['salary_period'] ? '(' . $row['salary_period'] . ')' : ''; ?>
                                                                    </p>
                                                                    <?php endif; ?>
                                                                    
                                                                    <h5 class="mt-3">Employer Details</h5>
                                                                    <?php if ($row['employer_company_name']): ?>
                                                                        <p><strong>Company:</strong> <?php echo htmlspecialchars($row['employer_company_name']); ?></p>
                                                                    <?php elseif ($row['company_name']): ?>
                                                                        <p><strong>Company:</strong> <?php echo htmlspecialchars($row['company_name']); ?></p>
                                                                    <?php endif; ?>
                                                                    <p><strong>Contact Person:</strong> <?php echo htmlspecialchars($row['employer_name']); ?></p>
                                                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($row['employer_email']); ?></p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <h5>Applicant Details</h5>
                                                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($row['applicant_name']); ?></p>
                                                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($row['applicant_email']); ?></p>
                                                                    <?php if ($row['applicant_phone']): ?>
                                                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($row['applicant_phone']); ?></p>
                                                                    <?php endif; ?>
                                                                    
                                                                    <h5 class="mt-3">Application Details</h5>
                                                                    <p><strong>Status:</strong> 
                                                                        <span class="badge badge-<?php echo $status_class; ?>">
                                                                            <?php echo ucfirst($row['status']); ?>
                                                                        </span>
                                                                    </p>
                                                                    <p><strong>Applied On:</strong> <?php echo date('F d, Y', strtotime($row['application_date'])); ?></p>
                                                                    
                                                                    <?php if ($row['resume_path'] || $row['applicant_resume']): ?>
                                                                    <p>
                                                                        <strong>Resume:</strong> 
                                                                        <a href="../<?php echo htmlspecialchars($row['resume_path'] ?: 'uploads/resumes/'.$row['applicant_resume']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                            View Resume
                                                                        </a>
                                                                    </p>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            
                                                            <hr>
                                                            
                                                            <?php if ($row['cover_letter']): ?>
                                                            <h5>Cover Letter</h5>
                                                            <div class="card">
                                                                <div class="card-body">
                                                                    <?php echo nl2br(htmlspecialchars($row['cover_letter'])); ?>
                                                                </div>
                                                            </div>
                                                            <?php endif; ?>
                                                            
                                                            <hr>
                                                            
                                                            <h5>Job Description</h5>
                                                            <div class="card">
                                                                <div class="card-body">
                                                                    <?php echo nl2br(htmlspecialchars($row['job_description'])); ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Update Status Modal -->
                                            <div class="modal fade" id="updateStatusModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="updateStatusModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="updateStatusModalLabel<?php echo $row['id']; ?>">
                                                                Update Application Status
                                                            </h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <form method="post">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="application_id" value="<?php echo $row['id']; ?>">
                                                                
                                                                <div class="form-group">
                                                                    <label for="new_status<?php echo $row['id']; ?>">New Status</label>
                                                                    <select class="form-control" id="new_status<?php echo $row['id']; ?>" name="new_status" required>
                                                                        <option value="">Select Status</option>
                                                                        <option value="pending" <?php echo ($row['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                                        <option value="reviewed" <?php echo ($row['status'] == 'reviewed') ? 'selected' : ''; ?>>Reviewed</option>
                                                                        <option value="shortlisted" <?php echo ($row['status'] == 'shortlisted') ? 'selected' : ''; ?>>Shortlisted</option>
                                                                        <option value="rejected" <?php echo ($row['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                                                        <option value="hired" <?php echo ($row['status'] == 'hired') ? 'selected' : ''; ?>>Hired</option>
                                                                    </select>
                                                                </div>
                                                                
                                                                <div class="alert alert-warning">
                                                                    <small>
                                                                        <strong>Note:</strong> Updating the application status will notify the applicant and employer via email.
                                                                    </small>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No job applications found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="job_applications.php?page=<?php echo $page-1; ?><?php echo $filter ? '&filter='.$filter : ''; ?>">
                                    Previous
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="job_applications.php?page=<?php echo $i; ?><?php echo $filter ? '&filter='.$filter : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="job_applications.php?page=<?php echo $page+1; ?><?php echo $filter ? '&filter='.$filter : ''; ?>">
                                    Next
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once("../includes/footer.php");
?>