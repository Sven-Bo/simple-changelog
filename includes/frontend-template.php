<?php
if (!defined('ABSPATH')) {
    exit;
}

$wrapper_id = 'scl-' . uniqid();
?>

<div class="scl-changelog-wrapper" id="<?php echo esc_attr($wrapper_id); ?>">
    <?php foreach ($display_releases as $index => $release) : ?>
    <div class="scl-release">
        <div class="scl-release-meta">
            <div class="scl-release-date"><?php echo esc_html($release['date']); ?></div>
            <div class="scl-release-version-name">Version <?php echo esc_html($release['version']); ?></div>
        </div>
        
        <div class="scl-release-version-badge">
            <span><?php echo esc_html($release['version']); ?></span>
        </div>
        
        <div class="scl-release-items">
            <?php foreach ($release['items'] as $item) : 
                $type_class = 'scl-type-' . esc_attr($item['type']);
                $type_label = ucfirst($item['type']);
            ?>
            <div class="scl-item">
                <span class="scl-item-type <?php echo $type_class; ?>"><?php echo esc_html($type_label); ?></span>
                <span class="scl-item-text"><?php echo esc_html($item['text']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php if ($show_more && !empty($hidden_releases)) : ?>
    <div class="scl-hidden-releases" style="display: none;">
        <?php foreach ($hidden_releases as $index => $release) : ?>
        <div class="scl-release">
            <div class="scl-release-meta">
                <div class="scl-release-date"><?php echo esc_html($release['date']); ?></div>
                <div class="scl-release-version-name">Version <?php echo esc_html($release['version']); ?></div>
            </div>
            
            <div class="scl-release-version-badge">
                <span><?php echo esc_html($release['version']); ?></span>
            </div>
            
            <div class="scl-release-items">
                <?php foreach ($release['items'] as $item) : 
                    $type_class = 'scl-type-' . esc_attr($item['type']);
                    $type_label = ucfirst($item['type']);
                ?>
                <div class="scl-item">
                    <span class="scl-item-type <?php echo $type_class; ?>"><?php echo esc_html($type_label); ?></span>
                    <span class="scl-item-text"><?php echo esc_html($item['text']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="scl-show-more-wrapper">
        <button type="button" class="scl-show-more-btn" data-target="<?php echo esc_attr($wrapper_id); ?>">
            Show <?php echo count($hidden_releases); ?> more version<?php echo count($hidden_releases) > 1 ? 's' : ''; ?>
        </button>
    </div>
    <?php endif; ?>
</div>
