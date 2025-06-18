<?php
// Start the session
session_start();

// Check if user is logged in and is an admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Include database configuration and header
include_once("../includes/db_config.php");
include_once("../includes/header.php");

// Pagination variables
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Get total number of job postings
$count_query = "SELECT COUNT(*) as total FROM job_postings";
$count_result = mysqli_query($conn, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $limit);

// Get job postings with employer details
$query = "SELECT jp.*, u.username, u.company_name 
          FROM job_postings jp 
          LEFT JOIN users u ON jp.user_id = u.id 
          ORDER BY jp.created_at DESC 
          LIMIT $start, $limit";
$result = mysqli_query($conn, $query);
?>

<div class="container mt-5">
    <div class="row mb-4">
        <div class="col">
            <h1>Job Postings Management</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Job Postings</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Job Postings Table -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="m-0">All Job Postings (<?php echo $total_records; ?>)</h5>
            <div>
                <a href="dashboard.php" class="btn btn-light btn-sm">
                    <i class="fa fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if(mysqli_num_rows($result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th>
                                <th>Job Title</th>
                                <th>Company</th>
                                <th>Location</th>
                                <th>Job Type</th>
                                <th>Salary Range</th>
                                <th>Posted</th>
                                <th>Status</th>
                                <th>Expires</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['location']); ?></td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo htmlspecialchars($row['job_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                            $min = !empty($row['salary_min']) ? number_format($row['salary_min'], 2) : 'N/A';
                                            $max = !empty($row['salary_max']) ? number_format($row['salary_max'], 2) : 'N/A';
                                            $period = !empty($row['salary_period']) ? $row['salary_period'] : '';
                                            echo "$min - $max " . ($period ? "({$period})" : "");
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $date = new DateTime($row['created_at']);
                                            echo $date->format('M d, Y');
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                            switch($row['status']) {
                                                case 'Published':
                                                    echo '<span class="badge badge-success">Published</span>';
                                                    break;
                                                case 'Draft':
                                                    echo '<span class="badge badge-secondary">Draft</span>';
                                                    break;
                                                case 'Closed':
                                                    echo '<span class="badge badge-danger">Closed</span>';
                                                    break;
                                                case 'Filled':
                                                    echo '<span class="badge badge-primary">Filled</span>';
                                                    break;
                                                default:
                                                    echo '<span class="badge badge-light">Unknown</span>';
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                            if(!empty($row['expiry_date'])) {
                                                $expiry = new DateTime($row['expiry_date']);
                                                $today = new DateTime();
                                                
                                                if($today > $expiry) {
                                                    echo '<span class="text-danger">' . $expiry->format('M d, Y') . '</span>';
                                                } else {
                                                    echo $expiry->format('M d, Y');
                                                }
                                            } else {
                                                echo 'N/A';
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="../view_job.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-info" target="_blank">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="edit_job.php?id=<?php echo $row['id']; ?>"
                                               class="btn btn-warning">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger delete-job-btn"
                                                    data-id="<?php echo $row['id']; ?>"
                                                    data-title="<?php echo htmlspecialchars($row['title']); ?>">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                <nav aria-label="Job postings pagination">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo ($page <= 1) ? '#' : '?page='.($page-1); ?>">
                                Previous
                            </a>
                        </li>
                        
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo ($page >= $total_pages) ? '#' : '?page='.($page+1); ?>">
                                Next
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <p class="mb-0">No job postings found in the database.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteJobModal" tabindex="-1" role="dialog" aria-labelledby="deleteJobModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteJobModalLabel">Confirm Deletion</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the job posting: <strong id="job-title-placeholder"></strong>?</p>
                <p class="text-danger">This action cannot be undone!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form id="deleteJobForm" action="delete_job.php" method="POST">
                    <input type="hidden" name="job_id" id="job-id-input" value="">
                    <button type="submit" class="btn btn-danger">Delete Job</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Handle delete button clicks
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('.delete-job-btn');
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const jobId = this.getAttribute('data-id');
                const jobTitle = this.getAttribute('data-title');
                
                document.getElementById('job-id-input').value = jobId;
                document.getElementById('job-title-placeholder').textContent = jobTitle;
                
                $('#deleteJobModal').modal('show');
            });
        });
    });
</script>

<?php
// Include footer
include_once("../includes/footer.php");
?>