</main>
    <footer class="bg-dark text-white mt-5">
        <div class="container py-4">
            <div class="row">
                <div class="col-md-4">
                    <h5>Job Portal</h5>
                    <p>Find your dream job or hire the best talent with our easy-to-use job portal platform.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="jobs.php" class="text-white">Browse Jobs</a></li>
                        <li><a href="about.php" class="text-white">About Us</a></li>
                        <li><a href="contact.php" class="text-white">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <address>
                        123 Job Street<br>
                        Employmentville, EMP 12345<br>
                        Email: info@jobportal.com<br>
                        Phone: (123) 456-7890
                    </address>
                </div>
            </div>
            <div class="text-center pt-3 border-top border-secondary">
                <p>&copy; <?php echo date('Y'); ?> Job Portal. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- jQuery and Bootstrap Bundle (includes Popper) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?php echo $jsPath; ?>"></script>
</body>
</html>