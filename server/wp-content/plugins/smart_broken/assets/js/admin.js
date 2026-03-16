jQuery(document).ready(function($) {
    'use strict';
    
    // Start Scan
    $('#sblf-start-scan').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Scanning...');
        
        $.ajax({
            url: sblf_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sblf_start_scan',
                nonce: sblf_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Scan started successfully! Check scan history for results.');
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('Failed to start scan. Please try again.');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Start New Scan');
            }
        });
    });
    
    // AI Suggest
    $('.sblf-ai-suggest').on('click', function() {
        const $btn = $(this);
        const linkId = $btn.data('link-id');
        
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');
        
        $.ajax({
            url: sblf_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sblf_auto_fix',
                nonce: sblf_ajax.nonce,
                link_id: linkId
            },
            success: function(response) {
                if (response.success) {
                    alert('AI Suggestion: ' + response.data.suggested_url);
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('Failed to generate suggestion.');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-lightbulb"></span> AI Fix');
            }
        });
    });
    
    // Confirm Redirect
    $('.sblf-confirm-redirect').on('click', function() {
        const $btn = $(this);
        const linkId = $btn.data('link-id');
        const $modal = $btn.closest('.modal');
        const redirectUrl = $modal.find('.redirect-url').val();
        const redirectType = $modal.find('.redirect-type').val();
        
        if (!redirectUrl) {
            alert('Please enter a redirect URL');
            return;
        }
        
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Applying...');
        
        $.ajax({
            url: sblf_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sblf_apply_redirect',
                nonce: sblf_ajax.nonce,
                link_id: linkId,
                redirect_url: redirectUrl,
                redirect_type: redirectType
            },
            success: function(response) {
                if (response.success) {
                    alert('Redirect applied successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('Failed to apply redirect.');
            },
            complete: function() {
                $btn.prop('disabled', false).html('Apply Redirect');
            }
        });
    });
    
    // Ignore Link
    $('.sblf-ignore-link').on('click', function() {
        if (!confirm('Are you sure you want to ignore this link?')) {
            return;
        }
        
        const $btn = $(this);
        const linkId = $btn.data('link-id');
        
        $btn.prop('disabled', true);
        
        $.ajax({
            url: sblf_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sblf_ignore_link',
                nonce: sblf_ajax.nonce,
                link_id: linkId
            },
            success: function(response) {
                if (response.success) {
                    alert('Link ignored successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('Failed to ignore link.');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
    
    // Status Filter
    $('#sblf-status-filter').on('change', function() {
        const status = $(this).val();
        const url = new URL(window.location.href);
        
        if (status) {
            url.searchParams.set('status', status);
        } else {
            url.searchParams.delete('status');
        }
        
        window.location.href = url.toString();
    });
});
