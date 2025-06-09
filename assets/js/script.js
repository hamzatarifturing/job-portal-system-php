/**
 * Job Portal System - Main JavaScript file
 * Compatible with older browsers (PHP 5 era)
 */

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Mobile navigation toggle functionality can be added here in the future
    
    // Add smooth scrolling for anchor links
    var anchorLinks = document.querySelectorAll('a[href^="#"]');
    
    for (var i = 0; i < anchorLinks.length; i++) {
        anchorLinks[i].addEventListener('click', function(e) {
            var target = document.querySelector(this.getAttribute('href'));
            
            if (target) {
                e.preventDefault();
                
                // Simple smooth scroll
                window.scrollTo({
                    top: target.offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    }
    
    // Simple form validation (to be expanded in the future)
    var forms = document.querySelectorAll('form');
    
    for (var i = 0; i < forms.length; i++) {
        forms[i].addEventListener('submit', function(e) {
            var requiredFields = this.querySelectorAll('[required]');
            var isValid = true;
            
            for (var j = 0; j < requiredFields.length; j++) {
                if (!requiredFields[j].value.trim()) {
                    isValid = false;
                    // Add error class (styling to be defined in CSS)
                    requiredFields[j].classList.add('error');
                } else {
                    requiredFields[j].classList.remove('error');
                }
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill all required fields');
            }
        });
    }
    
    // Placeholder for future AJAX functionalities
    
    console.log('Job Portal System initialized');
});