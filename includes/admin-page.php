<?php
if (!defined('ABSPATH')) {
    exit;
}

$scl = Simple_Changelog::get_instance();
$products = $scl->get_products();
$current_product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$current_product = null;
$changelog_content = '';

if ($current_product_id > 0) {
    $current_product = get_post($current_product_id);
    if ($current_product) {
        $changelog_content = get_post_meta($current_product_id, '_scl_changelog_content', true);
    }
}
?>

<div class="wrap scl-admin-wrap">
    <h1><?php _e('Simple Changelog', 'simple-changelog'); ?></h1>
    
    <div class="scl-admin-container">
        <div class="scl-sidebar">
            <h2><?php _e('Products', 'simple-changelog'); ?></h2>
            <a href="<?php echo admin_url('admin.php?page=simple-changelog'); ?>" class="button button-primary scl-add-new">
                <?php _e('+ Add New Product', 'simple-changelog'); ?>
            </a>
            
            <ul class="scl-product-list">
                <?php foreach ($products as $product) : ?>
                    <li class="<?php echo $current_product_id === $product->ID ? 'active' : ''; ?>">
                        <a href="<?php echo admin_url('admin.php?page=simple-changelog&product_id=' . $product->ID); ?>">
                            <?php echo esc_html($product->post_title); ?>
                        </a>
                        <span class="scl-shortcode-hint">[changelog id="<?php echo $product->ID; ?>"]</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="scl-main-content">
            <form id="scl-changelog-form">
                <input type="hidden" name="product_id" value="<?php echo esc_attr($current_product_id); ?>">
                
                <div class="scl-form-row">
                    <label for="product_name"><?php _e('Product Name', 'simple-changelog'); ?></label>
                    <input type="text" id="product_name" name="product_name" 
                           value="<?php echo $current_product ? esc_attr($current_product->post_title) : ''; ?>" 
                           placeholder="<?php _e('Enter product name...', 'simple-changelog'); ?>" required>
                </div>
                
                <?php if ($current_product_id > 0) : ?>
                <div class="scl-shortcode-box">
                    <div class="scl-shortcode-main">
                        <strong><?php _e('Shortcode:', 'simple-changelog'); ?></strong>
                        <code>[changelog id="<?php echo $current_product_id; ?>"]</code>
                        <button type="button" class="button scl-copy-shortcode" data-shortcode='[changelog id="<?php echo $current_product_id; ?>"]'>
                            <?php _e('Copy', 'simple-changelog'); ?>
                        </button>
                    </div>
                    <div class="scl-shortcode-options">
                        <strong><?php _e('Optional parameters:', 'simple-changelog'); ?></strong>
                        <ul>
                            <li><code>limit="3"</code> - <?php _e('Show only first 3 versions (with "Show more" button)', 'simple-changelog'); ?></li>
                            <li><code>version="2.0"</code> - <?php _e('Show only a specific version', 'simple-changelog'); ?></li>
                        </ul>
                        <span class="scl-example"><?php _e('Example:', 'simple-changelog'); ?> <code>[changelog id="<?php echo $current_product_id; ?>" limit="3"]</code></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="scl-form-row">
                    <label for="changelog_content"><?php _e('Changelog Content', 'simple-changelog'); ?></label>
                    <div class="scl-textarea-actions">
                        <button type="button" class="button scl-paste-sample">
                            <?php _e('Paste Sample', 'simple-changelog'); ?>
                        </button>
                        <button type="button" class="button scl-validate-syntax">
                            <?php _e('Validate Syntax', 'simple-changelog'); ?>
                        </button>
                    </div>
                    <textarea id="changelog_content" name="changelog_content" rows="20" 
                              placeholder="= 1.0 (01 Jan 2025) =
New: Added a new feature.
Fixed: Resolved a bug.
Tweaked: Improved performance."><?php echo esc_textarea($changelog_content); ?></textarea>
                    <div id="scl-validation-result" class="scl-validation-result" style="display:none;"></div>
                    <p class="description">
                        <?php _e('Format: Start each version with = version (date) = then list changes with Type: Description', 'simple-changelog'); ?><br>
                        <?php _e('Supported types: New, Fixed, Tweaked, Updated, Improvement, Security, Deprecated, Removed', 'simple-changelog'); ?>
                    </p>
                </div>
                
                <div class="scl-form-actions">
                    <button type="submit" class="button button-primary button-large">
                        <?php _e('Save Changelog', 'simple-changelog'); ?>
                    </button>
                    
                    <?php if ($current_product_id > 0) : ?>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=simple-changelog&action=delete&product_id=' . $current_product_id), 'scl_delete_product'); ?>" 
                       class="button button-link-delete" 
                       onclick="return confirm('<?php _e('Are you sure you want to delete this product?', 'simple-changelog'); ?>');">
                        <?php _e('Delete Product', 'simple-changelog'); ?>
                    </a>
                    <?php endif; ?>
                </div>
                
                <div id="scl-save-message" class="scl-message" style="display:none;"></div>
            </form>
        </div>
    </div>
</div>
