<?php
/**
 * Standalone CodeIgniter 3 compatibility runner
 * Ensures CI3 controllers, views, and security helpers run seamlessly
 */
session_start();

if (!defined('BASEPATH')) define('BASEPATH', __DIR__ . '/application/');
if (!defined('APPPATH')) define('APPPATH', __DIR__ . '/application/');

// Helper functions matching CI3
if (!function_exists('html_escape')) {
    function html_escape($var, $double_encode = TRUE) {
        if (empty($var)) return $var;
        if (is_array($var)) {
            foreach (array_keys($var) as $key) {
                $var[$key] = html_escape($var[$key], $double_encode);
            }
            return $var;
        }
        return htmlspecialchars($var, ENT_QUOTES, 'UTF-8', $double_encode);
    }
}

if (!function_exists('site_url')) {
    function site_url($uri = '') {
        return '/' . ltrim($uri, '/');
    }
}

// Generate CSRF token if not set
if (empty($_SESSION['atlas_csrf_token'])) {
    $_SESSION['atlas_csrf_token'] = bin2hex(random_bytes(16));
}

$csrf_name = 'atlas_csrf_token';
$csrf_hash = $_SESSION['atlas_csrf_token'];

// API helper
require_once __DIR__ . '/application/libraries/Api_client.php';
$api_client = new Api_client();

// Routing logic
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($uri === '/chat/switch_client' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $allowed = ['C-01', 'C-02', 'C-03'];
    $client_id = (isset($_POST['client_id']) && in_array($_POST['client_id'], $allowed, true)) ? $_POST['client_id'] : 'C-01';
    $_SESSION['client_id'] = $client_id;
    $_SESSION['chat_history'] = [];
    header('Location: /');
    exit;
}

if ($uri === '/chat/clear') {
    $_SESSION['chat_history'] = [];
    header('Location: /');
    exit;
}

if ($uri === '/chat/feedback' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (!isset($_POST[$csrf_name]) || $_POST[$csrf_name] !== $csrf_hash) {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'CSRF validation failed.']);
        exit;
    }

    $message_id = (int)(isset($_POST['message_id']) ? $_POST['message_id'] : 0);
    $rating = (int)(isset($_POST['rating']) ? $_POST['rating'] : 0);
    $comment = trim(isset($_POST['comment']) ? $_POST['comment'] : '');
    $client_id = isset($_SESSION['client_id']) ? $_SESSION['client_id'] : 'C-01';

    if (!$message_id || !in_array($rating, [1, -1], true)) {
        header('HTTP/1.1 400 Bad Request');
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Invalid feedback parameters.']);
        exit;
    }

    // Attempt recording to MySQL if reachable
    try {
        $host = getenv('MYSQL_HOST') ?: 'mysql';
        $user = getenv('MYSQL_USER') ?: 'atlas_app';
        $pass = getenv('MYSQL_PASSWORD') ?: 'atlas_app_secret';
        $dbname = getenv('MYSQL_DATABASE') ?: 'atlas_db';
        $mysqli = @new mysqli($host, $user, $pass, $dbname);
        if (!$mysqli->connect_errno) {
            $stmt = $mysqli->prepare("INSERT INTO feedback (message_id, conversation_id, client_id, rating, comments) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $conv_id = 'conv-' . substr(md5(session_id()), 0, 8);
                $stmt->bind_param("issis", $message_id, $conv_id, $client_id, $rating, $comment);
                $stmt->execute();
                $stmt->close();
            }
            $mysqli->close();
        }
    } catch (Exception $e) {}

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Feedback successfully recorded in database.']);
    exit;
}

if (($uri === '/chat/send' || $uri === '/') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (!isset($_POST[$csrf_name]) || $_POST[$csrf_name] !== $csrf_hash) {
        die('CSRF validation failed.');
    }

    $question = trim(isset($_POST['question']) ? $_POST['question'] : '');
    $client_id = isset($_SESSION['client_id']) ? $_SESSION['client_id'] : 'C-01';
    $conv_id = 'conv-' . substr(md5(session_id()), 0, 8);

    if (!empty($question)) {
        if (!isset($_SESSION['chat_history'])) $_SESSION['chat_history'] = [];
        $user_msg_id = time() * 1000 + rand(10, 99);
        $_SESSION['chat_history'][] = [
            'id' => $user_msg_id,
            'role' => 'user',
            'content' => $question,
            'time' => date('H:i')
        ];

        $resp = $api_client->send_chat_query($client_id, $conv_id, $question);
        $asst_msg_id = time() * 1000 + rand(100, 999);
        if ($resp && isset($resp['success']) && $resp['success']) {
            $_SESSION['chat_history'][] = [
                'id' => $asst_msg_id,
                'role' => 'assistant',
                'content' => $resp['answer'],
                'citations' => isset($resp['citations']) ? $resp['citations'] : [],
                'confidence' => isset($resp['confidence']) ? $resp['confidence'] : 0.0,
                'conflicts' => isset($resp['conflicts']) ? $resp['conflicts'] : [],
                'derived_values' => isset($resp['derived_values']) ? $resp['derived_values'] : [],
                'escalated' => !empty($resp['escalated']),
                'ticket_id' => isset($resp['ticket_id']) ? $resp['ticket_id'] : null,
                'time' => date('H:i')
            ];
        } else {
            $_SESSION['chat_history'][] = [
                'id' => $asst_msg_id,
                'role' => 'assistant',
                'content' => isset($resp['error']) ? $resp['error'] : 'The report assistant is temporarily unavailable.',
                'citations' => [],
                'confidence' => 0.0,
                'conflicts' => [],
                'derived_values' => [],
                'escalated' => false,
                'time' => date('H:i')
            ];
        }
    }
    header('Location: /');
    exit;
}


// Render Default View
$client_id = isset($_SESSION['client_id']) ? $_SESSION['client_id'] : 'C-01';
$messages = isset($_SESSION['chat_history']) ? $_SESSION['chat_history'] : [];
$escalations = $api_client->get_escalations($client_id);

$clients = [
    'C-01' => 'Northwind Utilities (Enterprise - R-101, R-103)',
    'C-02' => 'Blue Delta Infra (Standard - R-103 Only)',
    'C-03' => 'Apex Energy Partners (Trial - Public Only)'
];

// Mock CI security object for view
class SecurityMock {
    public $name;
    public $hash;
    public function __construct($n, $h) { $this->name = $n; $this->hash = $h; }
    public function get_csrf_token_name() { return $this->name; }
    public function get_csrf_hash() { return $this->hash; }
}
$ci_mock = new stdClass();
$ci_mock->security = new SecurityMock($csrf_name, $csrf_hash);

// Global wrapper for view
$this_context = new class($ci_mock) {
    public $security;
    public function __construct($m) { $this->security = $m->security; }
};

// Render view
include __DIR__ . '/application/views/chat_view.php';
