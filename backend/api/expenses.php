<?php
require_once 'config/db.php';
requireAuth();

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        $group_id = $_GET['group_id'] ?? 0;
        
        $stmt = $pdo->prepare('
            SELECT e.id, e.amount, e.description, e.receipt_url, e.created_at, u.name as payer_name 
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
    // Support both JSON body and multipart FormData
    $data = empty($_POST) ? json_decode(file_get_contents('php://input'), true) : $_POST;

    if ($action === 'add') {
        $group_id = $data['group_id'] ?? 0;
        $amount = (float)($data['amount'] ?? 0);
        $description = trim($data['description'] ?? '');
        $receipt_url = null;

        // Handle File Upload
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/receipts/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($_FILES['receipt']['name']));
            $target = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $target)) {
                $receipt_url = 'uploads/receipts/' . $filename;
            }
        }

        if (!$group_id || $amount <= 0 || !$description) {
            sendResponse(['error' => 'Invalid expense details'], 400);
        }

        try {
            $pdo->beginTransaction();
            
            // Insert parent expense
            $stmt = $pdo->prepare('INSERT INTO expenses (group_id, payer_id, amount, description, receipt_url) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$group_id, $user_id, $amount, $description, $receipt_url]);
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
