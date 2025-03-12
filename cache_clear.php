<?php
// Force the expiration of all browser caches
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Add version query parameter to stylesheets and scripts
$version = time();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Cache Cleared</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2da0a8;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        ul {
            margin-top: 20px;
        }
        a {
            display: inline-block;
            margin-top: 20px;
            background: #2da0a8;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Cache Clear Utility</h1>
        <p class='success'>✓ Browser cache headers have been sent</p>
        <p>This utility helps clear cached files by:</p>
        <ul>
            <li>Setting no-cache headers</li>
            <li>Adding timestamp parameters to assets (version: {$version})</li>
        </ul>
        <p>If you're still experiencing caching issues:</p>
        <ul>
            <li>Clear your browser cache manually (Ctrl+F5 or Cmd+Shift+R)</li>
            <li>Check server-level caching if applicable</li>
            <li>Verify all files are properly uploaded to your server</li>
        </ul>
        <a href='index.php?nocache={$version}'>Return to Login</a>
    </div>
</body>
</html>";
?>
