<?php
require_once 'config/db.php';
requireAuth();

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        $group_id = $_GET['group_id'] ?? 0;
        
        $stmt = $pdo->prepare('
            SELECT e.id, e.amount, e.description, e.created_at, u.name as payer_name 
            FROM expenses e 
            JOIN users u ON e.payer_id = u.id 
            WHERE e.group_id = ? 
            ORDER BY e.created_at DESC
        ');
        $stmt->execute([$group_id]);
        sendResponse(['expenses' => $stmt->fetchAll()]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($action === 'add') {
        $group_id = $data['group_id'] ?? 0;
        $amount = (float)($data['amount'] ?? 0);
        $description = trim($data['description'] ?? '');

        if (!$group_id || $amount <= 0 || !$description) {
            sendResponse(['error' => 'Invalid expense details'], 400);
        }

        try {
            $pdo->beginTransaction();
            
            // Insert parent expense
            $stmt = $pdo->prepare('INSERT INTO expenses (group_id, payer_id, amount, description) VALUES (?, ?, ?, ?)');
            $stmt->execute([$group_id, $user_id, $amount, $description]);
            $expense_id = $pdo->lastInsertId();

            // Fetch members
            $stmt = $pdo->prepare('SELECT user_id FROM group_members WHERE group_id = ?');
            $stmt->execute([$group_id]);
            $members = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($members)) {
                throw new Exception("No members found");
            }

            $split_amount = round($amount / count($members), 2);
            $stmtInsertSplit = $pdo->prepare('INSERT INTO expense_splits (expense_id, user_id, amount_owed, is_settled) VALUES (?, ?, ?, ?)');

            foreach ($members as $member_id) {
                // If the member is the payer, setting 'is_settled' = 1 automatically
                $is_settled = ($member_id == $user_id) ? 1 : 0;
                $stmtInsertSplit->execute([$expense_id, $member_id, $split_amount, $is_settled]);
            }

            $pdo->commit();
            sendResponse(['success' => true, 'expense_id' => $expense_id]);
        } catch (Exception $e) {
            $pdo->rollBack();
            sendResponse(['error' => 'Failed to log expense'], 500);
        }
    }
}
?>
