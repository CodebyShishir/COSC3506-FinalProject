<?php
require_once 'config/db.php';

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($action === 'register') {
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (!$name || !$email || !$password) {
            sendResponse(['error' => 'All fields are required'], 400);
        }

        // Check if email exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            sendResponse(['error' => 'Email already registered'], 400);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$name, $email, $hash]);

        session_regenerate_id();
        $_SESSION['user_id'] = $pdo->lastInsertId();
        sendResponse(['success' => true, 'message' => 'Registered successfully']);
    }

    if ($action === 'login') {
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id();
            $_SESSION['user_id'] = $user['id'];
            sendResponse(['success' => true, 'message' => 'Logged in successfully']);
        } else {
            sendResponse(['error' => 'Invalid email or password'], 401);
        }
    }

    if ($action === 'forgot_password') {
        $email = trim($data['email'] ?? '');
        $new_password = $data['new_password'] ?? '';

        if (!$email || !$new_password) {
            sendResponse(['error' => 'Email and new password are required'], 400);
        }

        // Check if email exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            sendResponse(['error' => 'No account found with that email address'], 404);
        }

        // Hash the new password and update
        $hash = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$hash, $user['id']]);

        sendResponse(['success' => true, 'message' => 'Password reset successfully']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'me') {
    if (isLoggedIn()) {
        $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        sendResponse(['user' => $stmt->fetch()]);
    } else {
        sendResponse(['user' => null]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'logout') {
    session_destroy();
    sendResponse(['success' => true, 'message' => 'Logged out successfully']);
}

sendResponse(['error' => 'Invalid action'], 400);
?>
