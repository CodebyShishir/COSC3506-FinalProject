<?php
require_once 'config/db.php';
requireAuth();

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        $stmt = $pdo->prepare('
            SELECT g.id, g.name, g.created_at 
            FROM groups g 
            JOIN group_members gm ON g.id = gm.group_id 
            WHERE gm.user_id = ?
            ORDER BY g.created_at DESC
        ');
        $stmt->execute([$user_id]);
        sendResponse(['groups' => $stmt->fetchAll()]);
    }
    
    if ($action === 'details') {
        $group_id = $_GET['group_id'] ?? 0;
        
        // Members list
        $stmt = $pdo->prepare('
            SELECT u.id, u.name 
            FROM group_members gm
            JOIN users u ON gm.user_id = u.id
            WHERE gm.group_id = ?
        ');
        $stmt->execute([$group_id]);
        $members = $stmt->fetchAll();
        
        sendResponse(['members' => $members]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($action === 'create') {
        $name = trim($data['name'] ?? '');
        if (!$name) sendResponse(['error' => 'Group name required'], 400);

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO groups (name, created_by) VALUES (?, ?)');
            $stmt->execute([$name, $user_id]);
            $group_id = $pdo->lastInsertId();

            // Add creator
            $stmt = $pdo->prepare('INSERT INTO group_members (group_id, user_id) VALUES (?, ?)');
            $stmt->execute([$group_id, $user_id]);
            
            // Add other members if emails provided
            $emails = $data['emails'] ?? [];
            if (!empty($emails)) {
                $findUser = $pdo->prepare('SELECT id FROM users WHERE email = ?');
                $addMember = $pdo->prepare('INSERT INTO group_members (group_id, user_id) VALUES (?, ?)');
                foreach ($emails as $email) {
                    $email = trim($email);
                    if (!$email) continue;
                    $findUser->execute([$email]);
                    $usr = $findUser->fetch();
                    if ($usr) {
                        try {
                            $addMember->execute([$group_id, $usr['id']]);
                        } catch (PDOException $e) { /* ignore dupes */ }
                    }
                }
            }

            $pdo->commit();
            sendResponse(['success' => true, 'group_id' => $group_id]);
        } catch (Exception $e) {
            $pdo->rollBack();
            sendResponse(['error' => 'Failed to create group'], 500);
        }
    }
}
?>
