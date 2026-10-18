<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Dynamic base URL detection supporting localhost, 127.0.0.1, reverse proxies, and Cloudflare tunnels
$protocol = 'http://';
if ((isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1))
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')) {
    $protocol = 'https://';
}

$host = !empty($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
if (!empty($host)) {
    if (strpos($host, ',') !== false) {
        $host_parts = explode(',', $host);
        $host = trim($host_parts[0]);
    }
    if (preg_match('#^https?://#i', $host)) {
        $config['base_url'] = rtrim($host, '/') . '/';
    } else {
        $config['base_url'] = $protocol . rtrim($host, '/') . '/';
    }
} else {
    $config['base_url'] = getenv('CI_BASE_URL') ?: 'http://localhost:8080/';
}
$config['index_page'] = '';
$config['uri_protocol'] = 'REQUEST_URI';
$config['url_suffix'] = '';
$config['language'] = 'english';
$config['charset'] = 'UTF-8';
$config['enable_hooks'] = FALSE;
$config['subclass_prefix'] = 'MY_';
$config['composer_autoload'] = FALSE;
$config['permitted_uri_chars'] = 'a-z 0-9~%.:_\-';

$config['allow_get_array'] = TRUE;
$config['enable_query_strings'] = FALSE;
$config['controller_trigger'] = 'c';
$config['function_trigger'] = 'm';
$config['directory_trigger'] = 'd';

$config['log_threshold'] = 1;
$config['log_path'] = '';
$config['log_file_extension'] = '';
$config['log_file_permissions'] = 0644;
$config['log_date_format'] = 'Y-m-d H:i:s';

$config['error_views_path'] = '';

$config['cache_path'] = '';
$config['cache_query_string'] = FALSE;

$config['encryption_key'] = 'atlas_super_secret_encryption_key_2025';

$config['sess_driver'] = 'files';
$config['sess_cookie_name'] = 'atlas_ci_session';
$config['sess_expiration'] = 7200;
$config['sess_save_path'] = NULL;
$config['sess_match_ip'] = FALSE;
$config['sess_time_to_update'] = 300;
$config['sess_regenerate_destroy'] = FALSE;

$config['cookie_prefix'] = '';
$config['cookie_domain'] = '';
$config['cookie_path'] = '/';
$config['cookie_secure'] = FALSE;
$config['cookie_httponly'] = TRUE;

$config['standardize_newlines'] = FALSE;
$config['global_xss_filtering'] = FALSE;

// CSRF Protection Requirement
$config['csrf_protection'] = TRUE;
$config['csrf_token_name'] = 'atlas_csrf_token';
$config['csrf_cookie_name'] = 'atlas_csrf_cookie';
$config['csrf_expire'] = 7200;
$config['csrf_regenerate'] = FALSE;
$config['csrf_exclude_uris'] = array();

$config['compress_output'] = FALSE;
$config['time_reference'] = 'local';
$config['rewrite_short_tags'] = FALSE;
$config['proxy_ips'] = '127.0.0.1,172.16.0.0/12,192.168.0.0/16,10.0.0.0/8';
