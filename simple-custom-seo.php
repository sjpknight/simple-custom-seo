<?php
/*
Plugin Name: Simple Custom SEO
Description: Set custom SEO titles and meta descriptions for posts and pages.
Version: 1.0
Author: Simon Knight | Black Country Tech Support
Author URI: https://blackcountrytechsupport.co.uk
Plugin URI: https://blackcountrytechsupport.co.uk/resources/simple-custom-seo
License: GPL2
*/

add_action('add_meta_boxes', function() {
    add_meta_box(
        'simple_custom_seo',
        'Custom SEO Settings',
        'simple_custom_seo_meta_box_callback',
        ['post', 'page'],
        'normal',
        'high'
    );
});

add_action('admin_footer', function () {
    global $post;
    $screen = get_current_screen();
    if ($screen->post_type !== 'post' && $screen->post_type !== 'page') {
        return;
    }
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const descField = document.getElementById('custom_meta_description');
            if (!descField) return;

            const counter = document.createElement('div');
            counter.id = 'meta-desc-counter';
            counter.style.marginTop = '4px';
            counter.style.fontSize = '12px';
            counter.style.color = '#666';
            descField.parentNode.appendChild(counter);

            const updateCounter = () => {
                const length = descField.value.length;
                counter.textContent = `Characters: ${length}/160`;
                counter.style.color = length > 160 ? 'red' : '#666';
            };

            descField.addEventListener('input', updateCounter);
            updateCounter();
        });
    </script>
    <?php
});

function simple_custom_seo_meta_box_callback($post) {
    $seo_title = get_post_meta($post->ID, '_custom_seo_title', true);
    $meta_description = get_post_meta($post->ID, '_custom_meta_description', true);
    wp_nonce_field('save_custom_seo_data', 'custom_seo_nonce');
    ?>
    <p><label for="custom_seo_title">SEO Title</label></p>
    <input type="text" id="custom_seo_title" name="custom_seo_title" value="<?php echo esc_attr($seo_title); ?>" style="width:100%;" />
    <p><label for="custom_meta_description">Meta Description</label></p>
    <textarea id="custom_meta_description" name="custom_meta_description" rows="3" style="width:100%;"><?php echo esc_textarea($meta_description); ?></textarea>
    <?php
}

add_action('save_post', function($post_id) {
    if (!isset($_POST['custom_seo_nonce'])) {
        return;
    }

    $nonce = isset($_POST['custom_seo_nonce']) ? sanitize_text_field(wp_unslash($_POST['custom_seo_nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'save_custom_seo_data')) {
            return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    if (isset($_POST['custom_seo_title'])) {
        $title = sanitize_text_field(wp_unslash($_POST['custom_seo_title']));
        update_post_meta($post_id, '_custom_seo_title', $title);
    }

    if (isset($_POST['custom_meta_description'])) {
        $desc = sanitize_textarea_field(wp_unslash($_POST['custom_meta_description']));
        update_post_meta($post_id, '_custom_meta_description', $desc);
    }
});

add_action('wp_head', function() {
    if (is_singular()) {
        global $post;
        $seo_title = get_post_meta($post->ID, '_custom_seo_title', true);
        $meta_description = get_post_meta($post->ID, '_custom_meta_description', true);

        if ($seo_title) {
            echo '<title>' . esc_html($seo_title) . '</title>' . PHP_EOL;
        }

        if ($meta_description) {
            // Trim meta description to 160 characters without cutting off mid-word
            $trimmed = wp_trim_words($meta_description, 30, '');
            if (strlen($trimmed) > 160) {
                $trimmed = mb_substr($trimmed, 0, 157) . '...';
            }
            echo '<meta name="description" content="' . esc_attr($trimmed) . '">' . PHP_EOL;
        }
    }
});

