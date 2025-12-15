<?php
/**
 * Helper function to render screenshot gallery
 * Shows both uploaded images and fallback icons
 */
function render_screenshot_gallery($section, $step_number, $fallback_items = []) {
    global $screenshots_by_section;
    
    $key = $section . '_' . $step_number;
    $screenshots = $screenshots_by_section[$key] ?? [];
    
    // If no screenshots uploaded, use fallback icons
    if (empty($screenshots)) {
        return render_icon_gallery($fallback_items);
    }
    
    // Render actual uploaded images
    $html = '<div class="screenshot-gallery">';
    foreach ($screenshots as $shot) {
        $html .= '<div class="gallery-item">';
        $html .= '<div class="gallery-item-img" style="height: auto; overflow: auto; background: #f9f9f9;">';
        $html .= '<img src="' . htmlspecialchars($shot['file_path']) . '" alt="' . htmlspecialchars($shot['title']) . '" style="width: 100%; height: auto; min-height: 150px; object-fit: cover;" onerror="this.style.display=\'none\'; this.parentElement.innerHTML += \'<i class=\\\"fa fa-image\\\" style=\\\"font-size: 40px; color: #999;\\\"></i>\';">';
        $html .= '</div>';
        $html .= '<div class="gallery-item-title">' . htmlspecialchars($shot['title']) . '</div>';
        $html .= '</div>';
    }
    $html .= '</div>';
    
    return $html;
}

function render_icon_gallery($items = []) {
    $html = '<div class="screenshot-gallery">';
    foreach ($items as $item) {
        $html .= '<div class="gallery-item">';
        $html .= '<div class="gallery-item-img"><i class="' . $item['icon'] . '"></i></div>';
        $html .= '<div class="gallery-item-title">' . $item['title'] . '</div>';
        $html .= '</div>';
    }
    $html .= '</div>';
    
    return $html;
}
?>
