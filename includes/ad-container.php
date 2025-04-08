<?php
/**
 * Ad container template
 * This file provides a standardized way to include ads across the site
 */
?>
<div id="ad-container" class="ad-container" style="text-align: center; margin: 20px auto; max-width: 300px; min-height: 250px; overflow: hidden;">
    <!-- Ad content will be loaded by JavaScript -->
</div>

<!-- Load the ad using our handler -->
<script>
    // Wait for the ad handler to load
    document.addEventListener('DOMContentLoaded', function() {
        // Check if loadAd function exists
        if (typeof window.loadAd === 'function') {
            window.loadAd('ad-container');
        }
    });
</script>