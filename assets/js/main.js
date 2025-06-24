/**
 * Job Portal - Main JavaScript File
 * Compatible with older browsers (IE8+) for PHP5-era projects
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips if Bootstrap is available
    if (typeof $().tooltip === 'function') {
        $('[data-toggle="tooltip"]').tooltip();
    }
    
    // Form validation (basic)
    var forms = document.querySelectorAll('.needs-validation');
    
    if (forms.length > 0) {
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        });
    }
    
    // Job search functionality (placeholder for future implementation)
    var searchForm = document.getElementById('job-search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(event) {
            // We'll implement actual search in later chapters
            console.log('Search functionality will be implemented soon');
        });
    }
    
    // Responsive navigation toggle (backup in case Bootstrap JS fails)
    var navbarToggler = document.querySelector('.navbar-toggler');
    if (navbarToggler) {
        navbarToggler.addEventListener('click', function() {
            var target = document.querySelector(this.getAttribute('data-target'));
            if (target) {
                target.classList.toggle('show');
            }
        });
    }
});

// Helper function to show alerts
function showAlert(message, type) {
    var alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-' + (type || 'info');
    alertDiv.setAttribute('role', 'alert');
    alertDiv.innerHTML = message;
    
    // Add close button
    var closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'close';
    closeButton.setAttribute('data-dismiss', 'alert');
    closeButton.setAttribute('aria-label', 'Close');
    closeButton.innerHTML = '<span aria-hidden="true">&times;</span>';
    
    alertDiv.appendChild(closeButton);
    
    // Find alert container
    var container = document.querySelector('.alert-container');
    if (!container) {
        container = document.querySelector('.container');
    }
    
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto-remove after 5 seconds
        setTimeout(function() {
            alertDiv.style.opacity = '0';
            alertDiv.style.transition = 'opacity 1s';
            
            setTimeout(function() {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 1000);
        }, 5000);
    }
}