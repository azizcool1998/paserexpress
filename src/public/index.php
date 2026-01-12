<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

$page = $_GET['page'] ?? 'welcome';

$routes = [
  'welcome' => __DIR__ . '/../pages/welcome.php',
  'tracking' => __DIR__ . '/../pages/tracking.php',
  'login' => __DIR__ . '/../pages/login.php',
  'logout' => __DIR__ . '/../pages/logout.php',

  // admin
  'admin_dashboard' => __DIR__ . '/../pages/admin/dashboard.php',
  'admin_users' => __DIR__ . '/../pages/admin/users.php',
  'admin_user_form' => __DIR__ . '/../pages/admin/user_form.php',
];

require __DIR__ . '/../includes/layout_header.php';

if (!isset($routes[$page])) {
    http_response_code(404);
    echo "<h1>404</h1><p>Halaman tidak ditemukan.</p>";
} else {
    require $routes[$page];
}

require __DIR__ . '/../includes/layout_footer.php';
