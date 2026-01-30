(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Handle form submission
        $('#scl-changelog-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var $message = $('#scl-save-message');
            
            $submitBtn.prop('disabled', true).text('Saving...');
            $message.hide();
            
            $.ajax({
                url: scl_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'scl_save_changelog',
                    nonce: scl_ajax.nonce,
                    product_id: $form.find('input[name="product_id"]').val(),
                    product_name: $form.find('input[name="product_name"]').val(),
                    changelog_content: $form.find('textarea[name="changelog_content"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        $message.removeClass('error').addClass('success')
                            .text(response.data.message).show();
                        
                        // Update sidebar product name immediately
                        var currentProductId = $form.find('input[name="product_id"]').val();
                        var newName = $form.find('input[name="product_name"]').val();
                        if (currentProductId && currentProductId !== '0') {
                            $('.scl-product-list li.active a').text(newName);
                        }
                        
                        // If new product, redirect to edit page
                        if (!currentProductId || currentProductId === '0') {
                            window.location.href = 'admin.php?page=simple-changelog&product_id=' + response.data.product_id;
                        }
                    } else {
                        $message.removeClass('success').addClass('error')
                            .text(response.data || 'An error occurred').show();
                    }
                },
                error: function() {
                    $message.removeClass('success').addClass('error')
                        .text('An error occurred. Please try again.').show();
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text('Save Changelog');
                }
            });
        });
        
        // Paste Sample
        $('.scl-paste-sample').on('click', function() {
            var sample = '= 3.0 (01 April 2025) =\n' +
                'New: Added bulk edit feature for faster modifications.\n' +
                'New: Introduced dark mode support.\n' +
                'Improvement: Enhanced performance for faster load times.\n' +
                'Tweaked: Adjusted UI spacing for better readability.\n' +
                'Fixed: Resolved a bug causing layout shifts on mobile.\n\n' +
                '= 2.0 (01 March 2025) =\n' +
                'New: Added user dashboard with analytics.\n' +
                'Updated: Refreshed third-party dependencies for stability.\n' +
                'Security: Patched XSS vulnerability in form inputs.\n' +
                'Security: Added rate limiting to prevent brute force attacks.\n' +
                'Fixed: Corrected timezone handling in date displays.\n\n' +
                '= 1.0 (01 Feb 2025) =\n' +
                'New: Initial release with core functionality.\n' +
                'New: Added export to CSV feature.\n' +
                'Deprecated: Legacy API endpoints marked for removal in v2.0.\n' +
                'Removed: Dropped support for PHP 7.2.';
            
            var $textarea = $('#changelog_content');
            var currentContent = $textarea.val().trim();
            
            if (currentContent && !confirm('This will replace the current content. Continue?')) {
                return;
            }
            
            $textarea.val(sample).focus();
        });
        
        // Validate Syntax
        $('.scl-validate-syntax').on('click', function() {
            var content = $('#changelog_content').val();
            var $result = $('#scl-validation-result');
            var lines = content.split('\n');
            var errors = [];
            var warnings = [];
            var versionCount = 0;
            var itemCount = 0;
            var currentVersion = null;
            var validTypes = ['new', 'fixed', 'tweaked', 'updated', 'improvement', 'security', 'deprecated', 'removed'];
            
            lines.forEach(function(line, index) {
                var lineNum = index + 1;
                line = line.trim();
                
                if (!line) return;
                
                // Check for version header
                if (line.match(/^=\s*.+\s*=$/)) {
                    var versionMatch = line.match(/^=\s*(.+?)\s*\((.+?)\)\s*=$/);
                    if (!versionMatch) {
                        errors.push('Line ' + lineNum + ': Version header missing date. Use format: = 1.0 (01 Jan 2025) =');
                    } else {
                        versionCount++;
                        currentVersion = versionMatch[1];
                    }
                    return;
                }
                
                // Check for changelog item
                var itemMatch = line.match(/^(\w+):\s*(.+)$/);
                if (itemMatch) {
                    var type = itemMatch[1].toLowerCase();
                    if (validTypes.indexOf(type) === -1) {
                        warnings.push('Line ' + lineNum + ': Unknown type "' + itemMatch[1] + '". Valid types: ' + validTypes.join(', '));
                    }
                    if (!currentVersion) {
                        errors.push('Line ' + lineNum + ': Item found before any version header.');
                    }
                    itemCount++;
                    return;
                }
                
                // Unknown format
                errors.push('Line ' + lineNum + ': Unrecognized format. Use "Type: Description" or "= Version (Date) ="');
            });
            
            if (!content.trim()) {
                $result.removeClass('valid warning').addClass('error')
                    .html('<strong>Empty:</strong> No changelog content to validate.')
                    .show();
                return;
            }
            
            if (versionCount === 0) {
                errors.push('No version headers found. Add at least one: = 1.0 (01 Jan 2025) =');
            }
            
            var html = '';
            if (errors.length > 0) {
                html += '<div class="scl-errors"><strong>Errors:</strong><ul>';
                errors.forEach(function(e) { html += '<li>' + e + '</li>'; });
                html += '</ul></div>';
            }
            if (warnings.length > 0) {
                html += '<div class="scl-warnings"><strong>Warnings:</strong><ul>';
                warnings.forEach(function(w) { html += '<li>' + w + '</li>'; });
                html += '</ul></div>';
            }
            
            if (errors.length === 0 && warnings.length === 0) {
                html = '<strong>✓ Valid!</strong> Found ' + versionCount + ' version(s) with ' + itemCount + ' item(s).';
                $result.removeClass('error warning').addClass('valid');
            } else if (errors.length > 0) {
                $result.removeClass('valid warning').addClass('error');
            } else {
                $result.removeClass('valid error').addClass('warning');
            }
            
            $result.html(html).show();
        });
        
        // Copy shortcode to clipboard
        $('.scl-copy-shortcode').on('click', function() {
            var shortcode = $(this).data('shortcode');
            var $btn = $(this);
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(shortcode).then(function() {
                    $btn.text('Copied!');
                    setTimeout(function() {
                        $btn.text('Copy');
                    }, 2000);
                });
            } else {
                // Fallback for older browsers
                var $temp = $('<input>');
                $('body').append($temp);
                $temp.val(shortcode).select();
                document.execCommand('copy');
                $temp.remove();
                $btn.text('Copied!');
                setTimeout(function() {
                    $btn.text('Copy');
                }, 2000);
            }
        });
        
    });
    
})(jQuery);
