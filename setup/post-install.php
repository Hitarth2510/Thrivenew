<?php
// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// Create .htaccess for security
$htaccessContent = "
# Deny access to sensitive files
<Files ~ \"^\\.(htaccess|htpasswd)$\">
    deny from all
</Files>

# Protect config files
<Files config.php>
    deny from all
</Files>

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Cache static files
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css \"access plus 1 month\"
    ExpiresByType application/javascript \"access plus 1 month\"
    ExpiresByType image/png \"access plus 1 month\"
    ExpiresByType image/jpg \"access plus 1 month\"
    ExpiresByType image/jpeg \"access plus 1 month\"
    ExpiresByType image/gif \"access plus 1 month\"
    ExpiresByType image/ico \"access plus 1 month\"
    ExpiresByType image/icon \"access plus 1 month\"
    ExpiresByType text/plain \"access plus 1 month\"
    ExpiresByType application/pdf \"access plus 1 month\"
</IfModule>

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection \"1; mode=block\"
</IfModule>

# Default document
DirectoryIndex index.php

# Enable rewrite engine
RewriteEngine On

# Force HTTPS (uncomment if using HTTPS)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Pretty URLs for API (optional)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/([^/]+)/?$ api/$1.php [L,QSA]
";

file_put_contents(__DIR__ . '/.htaccess', $htaccessContent);

echo "✅ Project setup completed!\n";
echo "- Created logs directory\n";
echo "- Generated .htaccess file\n";
echo "- Ready to run system check\n";
?>
