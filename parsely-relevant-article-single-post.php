<?php
/*
Plugin Name: Parsely Relevant Article Widget
Description: A custom Parsely Relevant Article widget displaying personalized content on single post page.
Version: 1.0
Author: Umar Khtab
*/

// Add CSS file for parsely widget
function my_plugin_enqueue_styles() {
    // Check if it's a single post page
    if (is_single()) {
        // Enqueue the stylesheet
        wp_enqueue_style('personalized-relevant-article-widget-style', plugins_url('/css/parsely-widget-style.css', __FILE__));
    }
}
add_action('wp_enqueue_scripts', 'my_plugin_enqueue_styles');


// Shortcode function
function fetchPersonalizedrelatedContent($uuid, $url, $apiKey, $apiSecret)
{

$profileEndpoint = 'https://api.parsely.com/v2/profile';
// Initiate cURL request with improved error handling
$ch = curl_init($profileEndpoint . '?apikey=' . $apiKey . '&uuid=' . $uuid . '&url=' . urlencode($url));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$apiKey:$apiSecret");

$response = curl_exec($ch);

if ($response === false) {
    $error = curl_error($ch);
    error_log("cURL error: $error");
    curl_close($ch);
    throw new Exception("cURL error: $error"); 
} else {
    // Process successful response
    $parsedResponse = json_decode($response, true);
    if (isset($parsedResponse['success']) && $parsedResponse['success'] === false) {
        $apiError = $parsedResponse['message'];

        error_log("API error: $apiError");

        echo "API error: " . $apiError;
    } else {
        error_log("API response: " . print_r($parsedResponse, true));
    }
}

curl_close($ch);

// This is used for Showing user History which posts he is seeing in last few days use it according to your need
// $historyEndpoint = 'https://api.parsely.com/v2/history';

// $ch = curl_init($historyEndpoint . '?apikey=' . $apiKey . '&uuid=' . $uuid);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_USERPWD, "$apiKey:$apiSecret");

// $response = curl_exec($ch);

// if ($response === false) {
//     // Detailed error handling (same as before)
// } else {
//     $parsedResponse = json_decode($response, true);
//     if ($parsedResponse['success'] === true) {
//         // Print the successful response
//         // print_r($parsedResponse);
//     } else {
//         // Handle API-specific errors (same as before)
//     }
// }

// curl_close($ch);



$relatedEndpoint = 'https://api.parsely.com/v2/related';

$ch = curl_init($relatedEndpoint . '?apikey=' . $apiKey . '&uuid=' . $uuid);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$apiKey:$apiSecret");

$response = curl_exec($ch);

if ($response === false) {
    // Detailed error handling (same as before)
} else {
    $parsedResponse = json_decode($response, true);
    if ($parsedResponse['success'] === true) {
        // Get only the first 5 recommended posts
        $recommendedPosts = array_slice($parsedResponse['data'], 0, 5);
        return $recommendedPosts ;
       

    } else {
        // Handle API-specific errors (same as before)
    }
}

curl_close($ch);
}

// Shortcode function
function personalized_sidebar_widget_shortcode($atts) {
    // Set your Parsely API credentials
    $apiKey = 'Your Parsely API Key';
    $apiSecret = "Your Parsely API Secret Key";

    // Get user ID or create/update a cookie for non-logged-in users
    if (is_user_logged_in()) {
        $uuid = get_current_user_id();
    } else {
        $uuid = isset($_COOKIE['parsely_uuid']) ? $_COOKIE['parsely_uuid'] : uniqid('parsely_anon_');
    }

    // Set or update the cookie
    setcookie('parsely_uuid', $uuid, time() + 60 * 60 * 24 * 180, '/');

    // Check if it's a single post page
    if (is_single()) {
        ?>
            <script>
                function moveSecondarySection() {
                    const commectSection = document.querySelector('body.single #comments');
                    const SecondarySection = document.querySelector('body.single #secondary');
                    if (window.innerWidth <= 480) {
                        commectSection.append(SecondarySection);
                    } else {
                        const originalLocation = document.querySelectorAll('body.single .g1-column.g1-column-2of3')[1];
                        originalLocation.insertAdjacentElement('afterend', SecondarySection);
                    }
                }
                moveSecondarySection();
                window.addEventListener('resize', () => {
                    moveSecondarySection();
                });
            </script>
        <?php
        global $post;
        $url = get_permalink($post->ID);
    
        // Fetch personalized related content
        $fetchPersonalizedrelatedContent = fetchPersonalizedrelatedContent($uuid, $url, $apiKey, $apiSecret);
        // Output HTML for the sidebar widget
        $output = '<div class="sidebar-widget">';
        $output .= '<header><h2 class="g1-delta g1-delta-2nd widgettitle"><span>Related Posts</span></h2></header>';
        // Loop through each related content item
        foreach ($fetchPersonalizedrelatedContent as $post) { 
            // Remove any additional parameters from the URL
            $cleanUrl = remove_query_arg('itm_source', $post['url']);
            $image_url = remove_query_arg('resize', $post['image_url']);
                   
            $output .= '<div class="widget-item">';
            $output .= '<a href="' . esc_url($cleanUrl) . '"><img src="' . esc_url($image_url) . '" alt="' . esc_attr($post['title']) . '"></a>';
            $output .= '<h3><a href="' . esc_url($cleanUrl) . '">' . esc_html($post['title']) . '</a></h3>';
            $output .= '</div>';
        }

        $output .= '</div>';

        return $output;
    }
}

// Register the shortcode
add_shortcode('personalized_sidebar_widget', 'personalized_sidebar_widget_shortcode');
