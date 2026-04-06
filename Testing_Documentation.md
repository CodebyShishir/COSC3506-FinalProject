# SplitEase: Testing Documentation

During Sprint 2, the development team performed System and Integration Testing to ensure the frontend application correctly communicates with the PHP API and MySQL database. The following test cases verify the core functionality of the Minimum Viable Product (MVP).

## 1. Unit & Integration Testing (Postman)
**Objective**: Validate REST API Endpoint logic and data integrity.

| Test ID | Endpoint | Action & Input Data | Expected Result | Actual Result | Status |
|---|---|---|---|---|---|
| API-01 | `/api/auth.php` | POST Reg: `{name:"Test", email:"t@t.com", password:"123"}` | Returns 200 OK & Success message. | JSON: `{success: true}` | **PASS** |
| API-02 | `/api/auth.php` | POST Reg: `{email:"t@t.com"}` (Same email) | Returns 400 Bad Request. | JSON: `{error: "Email already registered"}` | **PASS** |
| API-03 | `/api/groups.php`| POST Create: `{name:"Squad", emails:["user2@test.com"]}` | Returns 200 OK & new `group_id`. | JSON: `{success: true, group_id: 3}` | **PASS** |
| API-04 | `/api/expenses.php`| POST Add: `{group_id:3, description:"Gas", amount:100}` | Divides cost by 2, saves splits. | JSON: `{success: true, expense_id: 2}` | **PASS** |
| API-05 | `/api/balances.php`| GET Fetch: `?group_id=3` | Returns unsettled splits. User2 owes Test. | JSON: array showing $50 debt. | **PASS** |

## 2. Manual User Interface Testing
**Objective**: Validate End-to-End user workflows using a modern web browser.

### Test Case UI-01: User Registration & Login Flow
- **Pre-condition**: User does not exist.
- **Steps**: 
  1. Open `index.html`. 
  2. Toggle to "Create Account".
  3. Enter Name, Email, Password and hit "Register".
- **Expected Outcome**: Form submits, user is successfully registered, and redirected to `dashboard.html`.
- **Status**: **PASS**

### Test Case UI-02: Group Generation
- **Pre-condition**: User is authenticated on the Dashboard.
- **Steps**: 
  1. Click "+ New Group".
  2. Enter Group Name "Trip to Montreal".
  3. Leave emails blank and click "Create".
- **Expected Outcome**: Modal closes, and "Trip to Montreal" appears as a clickable item on the dashboard list.
- **Status**: **PASS**

### Test Case UI-03: Expense Splitting Engine
- **Pre-condition**: User is viewing the newly created "Trip to Montreal" group.
- **Steps**: 
  1. Click "Add Expense".
  2. Enter Description: `Groceries`
  3. Enter Amount: `150`
  4. Submit.
- **Expected Outcome**: The total $150.00 expense appears on the "Recent Expenses" list. The ledger correctly calculates the split among participating active users. 
- **Status**: **PASS**

### Test Case UI-04: Debt Settlement
- **Pre-condition**: The group ledger currently shows a user owing a debt.
- **Steps**: 
  1. User clicks the "Settle Up" action button beside the recorded debt.
  2. User confirms the browser dialog matching "Mark this debt as fully settled?".
- **Expected Outcome**: The interface reloads via API, the `is_settled` flag is set to 1 in the database, and the debt disappears from the active ledger, reflecting a $0.00 clear balance.
- **Status**: **PASS**

## Conclusion
All core use cases required for the SplitEase minimum viable product have passed Integration and UI testing with zero critical severity bugs remaining. System behaves securely and predictably across Chrome, Edge, and Firefox.
