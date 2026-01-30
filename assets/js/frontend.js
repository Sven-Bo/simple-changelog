(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        
        // Show more button
        document.querySelectorAll('.scl-show-more-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var targetId = this.getAttribute('data-target');
                var wrapper = document.getElementById(targetId);
                if (!wrapper) return;
                
                var hiddenReleases = wrapper.querySelector('.scl-hidden-releases');
                var showMoreWrapper = wrapper.querySelector('.scl-show-more-wrapper');
                
                if (hiddenReleases) {
                    // Move hidden releases into main wrapper before the button
                    while (hiddenReleases.firstChild) {
                        wrapper.insertBefore(hiddenReleases.firstChild, showMoreWrapper);
                    }
                    hiddenReleases.remove();
                }
                
                // Hide the button
                showMoreWrapper.style.display = 'none';
            });
        });
        
    });
    
})();
