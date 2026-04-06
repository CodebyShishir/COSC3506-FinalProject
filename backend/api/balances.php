<?php
require_once 'config/db.php';
requireAuth();

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'group') {
        $group_id = $_GET['group_id'] ?? 0;
        
        // This query fetches who owes what directly linked up.
        // It sums all unsettled splits (amount_owed) for a specific group.
        $stmt = $pdo->prepare('
            SELECT 
                s.user_id as debtor_id, 
                du.name as debtor_name, 
                e.payer_id as creditor_id, 
                cu.name as creditor_name, 
                SUM(s.amount_owed) as total_owed 
            FROM expense_splits s
            JOIN expenses e ON s.expense_id = e.id
            JOIN users du ON s.user_id = du.id
            JOIN users cu ON e.payer_id = cu.id
            WHERE e.group_id = ? AND s.is_settled = 0 AND s.user_id != e.payer_id
            GROUP BY s.user_id, e.payer_id
        ');
        $stmt->execute([$group_id]);
        $balancesRAW = $stmt->fetchAll();
        
        // We can serve it straight to the UI
        sendResponse(['balances' => $balancesRAW]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($action === 'settle') {
        $group_id = $data['group_id'] ?? 0;
        $debtor_id = $data['debtor_id'] ?? 0;
        $creditor_id = $data['creditor_id'] ?? 0;

        // Security check: only the creditor or debtor can mark it settled
        if ($user_id != $debtor_id && $user_id != $creditor_id) {
            sendResponse(['error' => 'Unauthorized to settle this debt'], 403);
        }

        try {
            $stmt = $pdo->prepare('
                UPDATE expense_splits s
                JOIN expenses e ON s.expense_id = e.id
                SET s.is_settled = 1
                WHERE e.group_id = ? AND s.user_id = ? AND e.payer_id = ?
            ');
            $stmt->execute([$group_id, $debtor_id, $creditor_id]);
            
            sendResponse(['success' => true]);
        } catch (Exception $e) {
            sendResponse(['error' => 'Failed to settle debt'], 500);
        }
    }
}
?>
