<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($paged - 1) * $per_page;

$where = '';
if ($status_filter) {
    $where = $wpdb->prepare(' WHERE status = %s', $status_filter);
}

$wordpress_headlesss = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}sblf_wordpress_headlesss $where ORDER BY created_at DESC LIMIT %d OFFSET %d",
    $per_page,
    $offset
));

$total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sblf_wordpress_headlesss $where");
$total_pages = ceil($total / $per_page);
?>

<div class="wrap sblf-broken-links">
    <h1 class="wp-heading-inline">
        <?php _e('Broken Links', 'smart-broken-link-fixer'); ?>
    </h1>

    <div class="card mt-4">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3><?php _e('All Broken Links', 'smart-broken-link-fixer'); ?></h3>
                </div>
                <div class="col-md-6 text-end">
                    <select id="sblf-status-filter" class="form-select d-inline-block w-auto">
                        <option value=""><?php _e('All Status', 'smart-broken-link-fixer'); ?></option>
                        <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php _e('Pending', 'smart-broken-link-fixer'); ?></option>
                        <option value="fixed" <?php selected($status_filter, 'fixed'); ?>><?php _e('Fixed', 'smart-broken-link-fixer'); ?></option>
                        <option value="ignored" <?php selected($status_filter, 'ignored'); ?>><?php _e('Ignored', 'smart-broken-link-fixer'); ?></option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($wordpress_headlesss)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><?php _e('Broken URL', 'smart-broken-link-fixer'); ?></th>
                                <th><?php _e('Source', 'smart-broken-link-fixer'); ?></th>
                                <th><?php _e('Status Code', 'smart-broken-link-fixer'); ?></th>
                                <th><?php _e('Status', 'smart-broken-link-fixer'); ?></th>
                                <th><?php _e('Actions', 'smart-broken-link-fixer'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($wordpress_headlesss as $link): ?>
                                <tr data-link-id="<?php echo esc_attr($link->id); ?>">
                                    <td>
                                        <small class="text-break"><?php echo esc_html($link->url); ?></small>
                                        <?php if ($link->suggested_url): ?>
                                            <br><small class="text-success">
                                                <span class="dashicons dashicons-lightbulb"></span>
                                                <?php _e('Suggested:', 'smart-broken-link-fixer'); ?> 
                                                <?php echo esc_html($link->suggested_url); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td><a href="<?php echo esc_url($link->source_url); ?>" target="_blank"><?php _e('View Source', 'smart-broken-link-fixer'); ?></a></td>
                                    <td><span class="badge bg-danger"><?php echo esc_html($link->status_code); ?></span></td>
                                    <td>
                                        <?php
                                        $badge_class = 'secondary';
                                        if ($link->status === 'pending') $badge_class = 'warning';
                                        if ($link->status === 'fixed') $badge_class = 'success';
                                        ?>
                                        <span class="badge bg-<?php echo esc_attr($badge_class); ?>">
                                            <?php echo esc_html(ucfirst($link->status)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-primary sblf-ai-suggest" data-link-id="<?php echo esc_attr($link->id); ?>">
                                                <span class="dashicons dashicons-lightbulb"></span> <?php _e('AI Fix', 'smart-broken-link-fixer'); ?>
                                            </button>
                                            <button class="btn btn-success sblf-apply-redirect" data-link-id="<?php echo esc_attr($link->id); ?>" data-bs-toggle="modal" data-bs-target="#redirectModal<?php echo esc_attr($link->id); ?>">
                                                <span class="dashicons dashicons-admin-links"></span> <?php _e('Redirect', 'smart-broken-link-fixer'); ?>
                                            </button>
                                            <button class="btn btn-secondary sblf-ignore-link" data-link-id="<?php echo esc_attr($link->id); ?>">
                                                <span class="dashicons dashicons-dismiss"></span> <?php _e('Ignore', 'smart-broken-link-fixer'); ?>
                                            </button>
                                        </div>
                                        
                                        <!-- Redirect Modal -->
                                        <div class="modal fade" id="redirectModal<?php echo esc_attr($link->id); ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title"><?php _e('Apply Redirect', 'smart-broken-link-fixer'); ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label"><?php _e('Redirect URL', 'smart-broken-link-fixer'); ?></label>
                                                            <input type="text" class="form-control redirect-url" value="<?php echo esc_attr($link->suggested_url ?? ''); ?>" placeholder="https://example.com/new-page">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label"><?php _e('Redirect Type', 'smart-broken-link-fixer'); ?></label>
                                                            <select class="form-select redirect-type">
                                                                <option value="301"><?php _e('301 Permanent', 'smart-broken-link-fixer'); ?></option>
                                                                <option value="302"><?php _e('302 Temporary', 'smart-broken-link-fixer'); ?></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Cancel', 'smart-broken-link-fixer'); ?></button>
                                                        <button type="button" class="btn btn-primary sblf-confirm-redirect" data-link-id="<?php echo esc_attr($link->id); ?>"><?php _e('Apply Redirect', 'smart-broken-link-fixer'); ?></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $paged ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=sblf-broken-links&paged=<?php echo $i; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <?php _e('No broken links found. Great job!', 'smart-broken-link-fixer'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
