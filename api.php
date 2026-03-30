<?php
/**
 * Bank Management System - API
 * Handles all banking operations
 */

require_once 'config.php';

// Set content type
header('Content-Type: application/json');

// Start session
startSession();

// Get request method and data
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Route handling
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        // Authentication
        case 'login':
            handleLogin($conn, $input);
            break;
            
        case 'logout':
            handleLogout();
            break;
            
        case 'checkSession':
            handleCheckSession();
            break;
            
        // User Management
        case 'getUsers':
            requireAuth();
            handleGetUsers($conn);
            break;
            
        case 'createUser':
            requireAuth(['admin']);
            handleCreateUser($conn, $input);
            break;
            
        case 'updateUser':
            requireAuth(['admin']);
            handleUpdateUser($conn, $input);
            break;
            
        case 'deleteUser':
            requireAuth(['admin']);
            handleDeleteUser($conn, $input['id']);
            break;
            
        // Customer Management
        case 'getCustomers':
            requireAuth();
            handleGetCustomers($conn);
            break;
            
        case 'getCustomer':
            requireAuth();
            handleGetCustomer($conn, $input['id'] ?? $_GET['id'] ?? null);
            break;
            
        case 'createCustomer':
            requireAuth(['admin', 'teller', 'manager']);
            handleCreateCustomer($conn, $input);
            break;
            
        case 'updateCustomer':
            requireAuth(['admin', 'teller', 'manager']);
            handleUpdateCustomer($conn, $input);
            break;
            
        case 'searchCustomers':
            requireAuth();
            handleSearchCustomers($conn, $input['query'] ?? '');
            break;
            
        // Account Management
        case 'getAccounts':
            requireAuth();
            handleGetAccounts($conn);
            break;
            
        case 'getAccount':
            requireAuth();
            handleGetAccount($conn, $input['id'] ?? $_GET['id'] ?? null);
            break;
            
        case 'getAccountByNumber':
            requireAuth();
            handleGetAccountByNumber($conn, $input['account_number'] ?? '');
            break;
            
        case 'getCustomerAccounts':
            requireAuth();
            handleGetCustomerAccounts($conn, $input['customer_id'] ?? null);
            break;
            
        case 'createAccount':
            requireAuth(['admin', 'teller', 'manager']);
            handleCreateAccount($conn, $input);
            break;
            
        case 'updateAccount':
            requireAuth(['admin', 'manager']);
            handleUpdateAccount($conn, $input);
            break;
            
        case 'closeAccount':
            requireAuth(['admin', 'manager']);
            handleCloseAccount($conn, $input['id']);
            break;
            
        case 'freezeAccount':
            requireAuth(['admin', 'manager']);
            handleFreezeAccount($conn, $input['id']);
            break;
            
        case 'getAccountTypes':
            requireAuth();
            handleGetAccountTypes($conn);
            break;
            
        // Transactions
        case 'deposit':
            requireAuth(['admin', 'teller', 'manager']);
            handleDeposit($conn, $input);
            break;
            
        case 'withdraw':
            requireAuth(['admin', 'teller', 'manager']);
            handleWithdraw($conn, $input);
            break;
            
        case 'transfer':
            requireAuth(['admin', 'teller', 'manager']);
            handleTransfer($conn, $input);
            break;
            
        case 'getTransactions':
            requireAuth();
            handleGetTransactions($conn, $input);
            break;
            
        case 'getAccountTransactions':
            requireAuth();
            handleGetAccountTransactions($conn, $input['account_id'] ?? null);
            break;
            
        case 'getCustomerTransactions':
            requireAuth();
            handleGetCustomerTransactions($conn, $input['customer_id'] ?? null);
            break;
            
        case 'getRecentTransactions':
            requireAuth();
            handleGetRecentTransactions($conn);
            break;
            
        // Dashboard & Reports
        case 'getDashboardStats':
            requireAuth();
            handleGetDashboardStats($conn);
            break;
            
        case 'getMonthlyReport':
            requireAuth(['admin', 'manager']);
            handleGetMonthlyReport($conn, $input['year'] ?? date('Y'), $input['month'] ?? date('m'));
            break;
            
        // Notifications
        case 'getNotifications':
            requireAuth();
            handleGetNotifications($conn);
            break;
            
        case 'markNotificationRead':
            requireAuth();
            handleMarkNotificationRead($conn, $input['id']);
            break;
            
        // Loans
        case 'getLoans':
            requireAuth();
            handleGetLoans($conn);
            break;
            
        case 'createLoan':
            requireAuth(['admin', 'manager']);
            handleCreateLoan($conn, $input);
            break;
            
        case 'approveLoan':
            requireAuth(['admin', 'manager']);
            handleApproveLoan($conn, $input);
            break;
            
        case 'makeLoanPayment':
            requireAuth(['admin', 'teller', 'manager']);
            handleMakeLoanPayment($conn, $input);
            break;
            
        default:
            jsonResponse(false, null, 'Invalid action', 400);
    }
} catch (Exception $e) {
    jsonResponse(false, null, $e->getMessage(), 500);
}

// ==================== AUTHENTICATION ====================

function requireAuth($roles = []) {
    if (!isLoggedIn()) {
        jsonResponse(false, null, 'Authentication required', 401);
    }
    
    if (!empty($roles) && !hasRole($roles[0])) {
        jsonResponse(false, null, 'Insufficient permissions', 403);
    }
}

function handleLogin($conn, $input) {
    $username = sanitize($input['username'] ?? '');
    $password = $input['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        jsonResponse(false, null, 'Username and password are required', 400);
    }
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user || !verifyPassword($password, $user['password'])) {
        jsonResponse(false, null, 'Invalid username or password', 401);
    }
    
    startSession();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    
    logActivity($conn, $user['id'], 'LOGIN');
    
    jsonResponse(true, [
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'role' => $user['role'],
            'email' => $user['email']
        ]
    ], 'Login successful');
}

function handleLogout() {
    if (isset($_SESSION['user_id'])) {
        logActivity($conn ?? null, $_SESSION['user_id'], 'LOGOUT');
    }
    session_destroy();
    jsonResponse(true, null, 'Logged out successfully');
}

function handleCheckSession() {
    if (isLoggedIn()) {
        jsonResponse(true, [
            'logged_in' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'full_name' => $_SESSION['full_name'],
                'role' => $_SESSION['role']
            ]
        ]);
    }
    jsonResponse(true, ['logged_in' => false]);
}

// ==================== USER MANAGEMENT ====================

function handleGetUsers($conn) {
    $stmt = $conn->query("SELECT id, username, email, full_name, role, status, created_at FROM users ORDER BY created_at DESC");
    jsonResponse(true, $stmt->fetchAll());
}

function handleCreateUser($conn, $input) {
    $username = sanitize($input['username'] ?? '');
    $password = $input['password'] ?? '';
    $email = sanitize($input['email'] ?? '');
    $fullName = sanitize($input['full_name'] ?? '');
    $role = sanitize($input['role'] ?? 'teller');
    
    if (empty($username) || empty($password) || empty($email) || empty($fullName)) {
        jsonResponse(false, null, 'All fields are required', 400);
    }
    
    // Check if username exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        jsonResponse(false, null, 'Username already exists', 400);
    }
    
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$username, hashPassword($password), $email, $fullName, $role]);
    
    logActivity($conn, $_SESSION['user_id'], 'CREATE_USER', 'users', $conn->lastInsertId());
    
    jsonResponse(true, ['id' => $conn->lastInsertId()], 'User created successfully');
}

function handleUpdateUser($conn, $input) {
    $id = intval($input['id'] ?? 0);
    $email = sanitize($input['email'] ?? '');
    $fullName = sanitize($input['full_name'] ?? '');
    $role = sanitize($input['role'] ?? '');
    $status = sanitize($input['status'] ?? '');
    
    $sql = "UPDATE users SET email = ?, full_name = ?";
    $params = [$email, $fullName];
    
    if (!empty($role)) {
        $sql .= ", role = ?";
        $params[] = $role;
    }
    
    if (!empty($status)) {
        $sql .= ", status = ?";
        $params[] = $status;
    }
    
    if (!empty($input['password'])) {
        $sql .= ", password = ?";
        $params[] = hashPassword($input['password']);
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $id;
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    logActivity($conn, $_SESSION['user_id'], 'UPDATE_USER', 'users', $id);
    
    jsonResponse(true, null, 'User updated successfully');
}

function handleDeleteUser($conn, $id) {
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND id != ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    
    logActivity($conn, $_SESSION['user_id'], 'DELETE_USER', 'users', $id);
    
    jsonResponse(true, null, 'User deleted successfully');
}

// ==================== CUSTOMER MANAGEMENT ====================

function handleGetCustomers($conn) {
    $stmt = $conn->query("SELECT c.*, u.full_name as created_by_name 
                          FROM customers c 
                          LEFT JOIN users u ON c.created_by = u.id 
                          ORDER BY c.created_at DESC");
    jsonResponse(true, $stmt->fetchAll());
}

function handleGetCustomer($conn, $id) {
    if (!$id) {
        jsonResponse(false, null, 'Customer ID is required', 400);
    }
    
    $stmt = $conn->prepare("SELECT c.*, u.full_name as created_by_name 
                           FROM customers c 
                           LEFT JOIN users u ON c.created_by = u.id 
                           WHERE c.id = ?");
    $stmt->execute([$id]);
    $customer = $stmt->fetch();
    
    if (!$customer) {
        jsonResponse(false, null, 'Customer not found', 404);
    }
    
    jsonResponse(true, $customer);
}

function handleCreateCustomer($conn, $input) {
    $customerId = generateUniqueId('CUS');
    
    $stmt = $conn->prepare("INSERT INTO customers 
        (customer_id, first_name, last_name, email, phone, address, date_of_birth, id_number, occupation, employer, monthly_income, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $customerId,
        sanitize($input['first_name'] ?? ''),
        sanitize($input['last_name'] ?? ''),
        sanitize($input['email'] ?? ''),
        sanitize($input['phone'] ?? ''),
        sanitize($input['address'] ?? ''),
        $input['date_of_birth'] ?? null,
        sanitize($input['id_number'] ?? ''),
        sanitize($input['occupation'] ?? ''),
        sanitize($input['employer'] ?? ''),
        floatval($input['monthly_income'] ?? 0),
        $_SESSION['user_id']
    ]);
    
    logActivity($conn, $_SESSION['user_id'], 'CREATE_CUSTOMER', 'customers', $conn->lastInsertId());
    
    jsonResponse(true, ['id' => $conn->lastInsertId(), 'customer_id' => $customerId], 'Customer created successfully');
}

function handleUpdateCustomer($conn, $input) {
    $id = intval($input['id'] ?? 0);
    
    $stmt = $conn->prepare("UPDATE customers SET 
        first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, 
        date_of_birth = ?, id_number = ?, occupation = ?, employer = ?, 
        monthly_income = ?, status = ? 
        WHERE id = ?");
    
    $stmt->execute([
        sanitize($input['first_name'] ?? ''),
        sanitize($input['last_name'] ?? ''),
        sanitize($input['email'] ?? ''),
        sanitize($input['phone'] ?? ''),
        sanitize($input['address'] ?? ''),
        $input['date_of_birth'] ?? null,
        sanitize($input['id_number'] ?? ''),
        sanitize($input['occupation'] ?? ''),
        sanitize($input['employer'] ?? ''),
        floatval($input['monthly_income'] ?? 0),
        sanitize($input['status'] ?? 'active'),
        $id
    ]);
    
    logActivity($conn, $_SESSION['user_id'], 'UPDATE_CUSTOMER', 'customers', $id);
    
    jsonResponse(true, null, 'Customer updated successfully');
}

function handleSearchCustomers($conn, $query) {
    $query = "%" . sanitize($query) . "%";
    $stmt = $conn->prepare("SELECT c.*, u.full_name as created_by_name 
                           FROM customers c 
                           LEFT JOIN users u ON c.created_by = u.id 
                           WHERE c.customer_id LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ? 
                           OR c.email LIKE ? OR c.phone LIKE ? OR c.id_number LIKE ?
                           ORDER BY c.created_at DESC LIMIT 20");
    $stmt->execute([$query, $query, $query, $query, $query, $query]);
    jsonResponse(true, $stmt->fetchAll());
}

// ==================== ACCOUNT MANAGEMENT ====================

function handleGetAccounts($conn) {
    $stmt = $conn->query("SELECT a.*, c.first_name, c.last_name, c.customer_id, 
                          at.type_name, at.interest_rate,
                          u.full_name as created_by_name
                          FROM accounts a
                          JOIN customers c ON a.customer_id = c.id
                          JOIN account_types at ON a.account_type_id = at.id
                          LEFT JOIN users u ON a.created_by = u.id
                          ORDER BY a.created_at DESC");
    jsonResponse(true, $stmt->fetchAll());
}

function handleGetAccount($conn, $id) {
    if (!$id) {
        jsonResponse(false, null, 'Account ID is required', 400);
    }
    
    $stmt = $conn->prepare("SELECT a.*, c.first_name, c.last_name, c.customer_id, c.email, c.phone,
                          at.type_name, at.interest_rate, at.min_balance, at.max_withdrawal,
                          u.full_name as created_by_name
                          FROM accounts a
                          JOIN customers c ON a.customer_id = c.id
                          JOIN account_types at ON a.account_type_id = at.id
                          LEFT JOIN users u ON a.created_by = u.id
                          WHERE a.id = ?");
    $stmt->execute([$id]);
    $account = $stmt->fetch();
    
    if (!$account) {
        jsonResponse(false, null, 'Account not found', 404);
    }
    
    jsonResponse(true, $account);
}

function handleGetAccountByNumber($conn, $accountNumber) {
    if (empty($accountNumber)) {
        jsonResponse(false, null, 'Account number is required', 400);
    }
    
    $stmt = $conn->prepare("SELECT a.*, c.first_name, c.last_name, c.customer_id, c.email, c.phone,
                          at.type_name, at.interest_rate, at.min_balance, at.max_withdrawal
                          FROM accounts a
                          JOIN customers c ON a.customer_id = c.id
                          JOIN account_types at ON a.account_type_id = at.id
                          WHERE a.account_number = ?");
    $stmt->execute([$accountNumber]);
    $account = $stmt->fetch();
    
    if (!$account) {
        jsonResponse(false, null, 'Account not found', 404);
    }
    
    jsonResponse(true, $account);
}

function handleGetCustomerAccounts($conn, $customerId) {
    if (!$customerId) {
        jsonResponse(false, null, 'Customer ID is required', 400);
    }
    
    $stmt = $conn->prepare("SELECT a.*, at.type_name, at.interest_rate, at.min_balance
                          FROM accounts a
                          JOIN account_types at ON a.account_type_id = at.id
                          WHERE a.customer_id = ?
                          ORDER BY a.opened_date DESC");
    $stmt->execute([$customerId]);
    jsonResponse(true, $stmt->fetchAll());
}

function handleCreateAccount($conn, $input) {
    $accountNumber = generateUniqueId('ACC');
    
    // Validate customer exists
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ? AND status = 'active'");
    $stmt->execute([intval($input['customer_id'] ?? 0)]);
    if (!$stmt->fetch()) {
        jsonResponse(false, null, 'Invalid or inactive customer', 400);
    }
    
    // Validate account type
    $stmt = $conn->prepare("SELECT * FROM account_types WHERE id = ? AND is_active = 1");
    $stmt->execute([intval($input['account_type_id'] ?? 0)]);
    $accountType = $stmt->fetch();
    if (!$accountType) {
        jsonResponse(false, null, 'Invalid account type', 400);
    }
    
    $initialDeposit = floatval($input['initial_deposit'] ?? 0);
    if ($initialDeposit < $accountType['min_balance']) {
        jsonResponse(false, null, 'Initial deposit must be at least ' . $accountType['min_balance'], 400);
    }
    
    $conn->beginTransaction();
    
    try {
        // Create account
        $stmt = $conn->prepare("INSERT INTO accounts 
            (account_number, customer_id, account_type_id, balance, overdraft_limit, opened_date, created_by) 
            VALUES (?, ?, ?, ?, ?, CURDATE(), ?)");
        $stmt->execute([
            $accountNumber,
            intval($input['customer_id']),
            intval($input['account_type_id']),
            $initialDeposit,
            floatval($input['overdraft_limit'] ?? 0),
            $_SESSION['user_id']
        ]);
        
        $accountId = $conn->lastInsertId();
        
        // Record initial deposit transaction
        if ($initialDeposit > 0) {
            $transactionId = generateUniqueId('TXN');
            $stmt = $conn->prepare("INSERT INTO transactions 
                (transaction_id, account_id, transaction_type, amount, balance_before, balance_after, description, performed_by) 
                VALUES (?, ?, 'deposit', ?, 0.00, ?, 'Initial deposit', ?)");
            $stmt->execute([$transactionId, $accountId, $initialDeposit, $initialDeposit, $_SESSION['user_id']]);
        }
        
        logActivity($conn, $_SESSION['user_id'], 'CREATE_ACCOUNT', 'accounts', $accountId);
        
        $conn->commit();
        jsonResponse(true, ['id' => $accountId, 'account_number' => $accountNumber], 'Account created successfully');
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

function handleUpdateAccount($conn, $input) {
    $id = intval($input['id'] ?? 0);
    
    $stmt = $conn->prepare("UPDATE accounts SET overdraft_limit = ? WHERE id = ?");
    $stmt->execute([floatval($input['overdraft_limit'] ?? 0), $id]);
    
    logActivity($conn, $_SESSION['user_id'], 'UPDATE_ACCOUNT', 'accounts', $id);
    
    jsonResponse(true, null, 'Account updated successfully');
}

function handleCloseAccount($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM accounts WHERE id = ?");
    $stmt->execute([$id]);
    $account = $stmt->fetch();
    
    if (!$account) {
        jsonResponse(false, null, 'Account not found', 404);
    }
    
    if ($account['balance'] > 0) {
        jsonResponse(false, null, 'Cannot close account with balance. Please withdraw all funds first.', 400);
    }
    
    $stmt = $conn->prepare("UPDATE accounts SET status = 'closed', closed_date = CURDATE() WHERE id = ?");
    $stmt->execute([$id]);
    
    logActivity($conn, $_SESSION['user_id'], 'CLOSE_ACCOUNT', 'accounts', $id);
    
    jsonResponse(true, null, 'Account closed successfully');
}

function handleFreezeAccount($conn, $id) {
    $stmt = $conn->prepare("UPDATE accounts SET status = 'frozen' WHERE id = ?");
    $stmt->execute([$id]);
    
    logActivity($conn, $_SESSION['user_id'], 'FREEZE_ACCOUNT', 'accounts', $id);
    
    jsonResponse(true, null, 'Account frozen successfully');
}

function handleGetAccountTypes($conn) {
    $stmt = $conn->query("SELECT * FROM account_types WHERE is_active = 1 ORDER BY type_name");
    jsonResponse(true, $stmt->fetchAll());
}

// ==================== TRANSACTIONS ====================

function handleDeposit($conn, $input) {
    $accountId = intval($input['account_id'] ?? 0);
    $amount = floatval($input['amount'] ?? 0);
    $description = sanitize($input['description'] ?? 'Deposit');
    
    if ($amount <= 0) {
        jsonResponse(false, null, 'Amount must be greater than 0', 400);
    }
    
    // Get account
    $stmt = $conn->prepare("SELECT * FROM accounts WHERE id = ? AND status = 'active'");
    $stmt->execute([$accountId]);
    $account = $stmt->fetch();
    
    if (!$account) {
        jsonResponse(false, null, 'Account not found or inactive', 400);
    }
    
    $conn->beginTransaction();
    
    try {
        $balanceBefore = $account['balance'];
        $balanceAfter = $balanceBefore + $amount;
        
        // Update account balance
        $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
        $stmt->execute([$balanceAfter, $accountId]);
        
        // Record transaction
        $transactionId = generateUniqueId('TXN');
        $stmt = $conn->prepare("INSERT INTO transactions 
            (transaction_id, account_id, transaction_type, amount, balance_before, balance_after, description, performed_by) 
            VALUES (?, ?, 'deposit', ?, ?, ?, ?, ?)");
        $stmt->execute([$transactionId, $accountId, $amount, $balanceBefore, $balanceAfter, $description, $_SESSION['user_id']]);
        
        logActivity($conn, $_SESSION['user_id'], 'DEPOSIT', 'transactions', $conn->lastInsertId());
        
        $conn->commit();
        jsonResponse(true, [
            'transaction_id' => $transactionId,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter
        ], 'Deposit successful');
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

function handleWithdraw($conn, $input) {
    $accountId = intval($input['account_id'] ?? 0);
    $amount = floatval($input['amount'] ?? 0);
    $description = sanitize($input['description'] ?? 'Withdrawal');
    
    if ($amount <= 0) {
        jsonResponse(false, null, 'Amount must be greater than 0', 400);
    }
    
    // Get account with type info
    $stmt = $conn->prepare("SELECT a.*, at.max_withdrawal, at.min_balance 
                          FROM accounts a 
                          JOIN account_types at ON a.account_type_id = at.id
                          WHERE a.id = ? AND a.status = 'active'");
    $stmt->execute([$accountId]);
    $account = $stmt->fetch();
    
    if (!$account) {
        jsonResponse(false, null, 'Account not found or inactive', 400);
    }
    
    // Check max withdrawal
    if ($account['max_withdrawal'] && $amount > $account['max_withdrawal']) {
        jsonResponse(false, null, 'Amount exceeds maximum withdrawal limit of ' . $account['max_withdrawal'], 400);
    }
    
    $availableBalance = $account['balance'] + $account['overdraft_limit'];
    
    // Check minimum balance
    $newBalance = $account['balance'] - $amount;
    if ($newBalance < $account['min_balance']) {
        jsonResponse(false, null, 'Withdrawal would bring balance below minimum required (' . $account['min_balance'] . ')', 400);
    }
    
    if ($amount > $availableBalance) {
        jsonResponse(false, null, 'Insufficient funds', 400);
    }
    
    $conn->beginTransaction();
    
    try {
        $balanceBefore = $account['balance'];
        $balanceAfter = $balanceBefore - $amount;
        
        // Update account balance
        $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
        $stmt->execute([$balanceAfter, $accountId]);
        
        // Record transaction
        $transactionId = generateUniqueId('TXN');
        $stmt = $conn->prepare("INSERT INTO transactions 
            (transaction_id, account_id, transaction_type, amount, balance_before, balance_after, description, performed_by) 
            VALUES (?, ?, 'withdrawal', ?, ?, ?, ?, ?)");
        $stmt->execute([$transactionId, $accountId, $amount, $balanceBefore, $balanceAfter, $description, $_SESSION['user_id']]);
        
        logActivity($conn, $_SESSION['user_id'], 'WITHDRAWAL', 'transactions', $conn->lastInsertId());
        
        $conn->commit();
        jsonResponse(true, [
            'transaction_id' => $transactionId,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter
        ], 'Withdrawal successful');
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

function handleTransfer($conn, $input) {
    $fromAccountId = intval($input['from_account_id'] ?? 0);
    $toAccountId = intval($input['to_account_id'] ?? 0);
    $amount = floatval($input['amount'] ?? 0);
    $description = sanitize($input['description'] ?? 'Transfer');
    
    if ($fromAccountId === $toAccountId) {
        jsonResponse(false, null, 'Cannot transfer to the same account', 400);
    }
    
    if ($amount <= 0) {
        jsonResponse(false, null, 'Amount must be greater than 0', 400);
    }
    
    // Get source account
    $stmt = $conn->prepare("SELECT * FROM accounts WHERE id = ? AND status = 'active'");
    $stmt->execute([$fromAccountId]);
    $fromAccount = $stmt->fetch();
    
    if (!$fromAccount) {
        jsonResponse(false, null, 'Source account not found or inactive', 400);
    }
    
    // Get destination account
    $stmt = $conn->prepare("SELECT * FROM accounts WHERE id = ? AND status = 'active'");
    $stmt->execute([$toAccountId]);
    $toAccount = $stmt->fetch();
    
    if (!$toAccount) {
        jsonResponse(false, null, 'Destination account not found or inactive', 400);
    }
    
    // Check sufficient funds
    $availableBalance = $fromAccount['balance'] + $fromAccount['overdraft_limit'];
    if ($amount > $availableBalance) {
        jsonResponse(false, null, 'Insufficient funds', 400);
    }
    
    $conn->beginTransaction();
    
    try {
        // Debit source account
        $balanceBeforeFrom = $fromAccount['balance'];
        $balanceAfterFrom = $balanceBeforeFrom - $amount;
        
        $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
        $stmt->execute([$balanceAfterFrom, $fromAccountId]);
        
        // Credit destination account
        $balanceBeforeTo = $toAccount['balance'];
        $balanceAfterTo = $balanceBeforeTo + $amount;
        
        $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
        $stmt->execute([$balanceAfterTo, $toAccountId]);
        
        // Record transfer
        $transferId = generateUniqueId('TRF');
        $stmt = $conn->prepare("INSERT INTO transfers 
            (transfer_id, from_account_id, to_account_id, amount, performed_by, description) 
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$transferId, $fromAccountId, $toAccountId, $amount, $_SESSION['user_id'], $description]);
        
        // Record debit transaction
        $transactionIdDebit = generateUniqueId('TXN');
        $stmt = $conn->prepare("INSERT INTO transactions 
            (transaction_id, account_id, transaction_type, amount, balance_before, balance_after, description, reference_number, performed_by) 
            VALUES (?, ?, 'transfer', ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$transactionIdDebit, $fromAccountId, $amount, $balanceBeforeFrom, $balanceAfterFrom, 'Transfer to ' . $toAccount['account_number'], $transferId, $_SESSION['user_id']]);
        
        // Record credit transaction
        $transactionIdCredit = generateUniqueId('TXN');
        $stmt = $conn->prepare("INSERT INTO transactions 
            (transaction_id, account_id, transaction_type, amount, balance_before, balance_after, description, reference_number, performed_by) 
            VALUES (?, ?, 'transfer', ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$transactionIdCredit, $toAccountId, $amount, $balanceBeforeTo, $balanceAfterTo, 'Transfer from ' . $fromAccount['account_number'], $transferId, $_SESSION['user_id']]);
        
        logActivity($conn, $_SESSION['user_id'], 'TRANSFER', 'transfers', $conn->lastInsertId());
        
        $conn->commit();
        jsonResponse(true, [
            'transfer_id' => $transferId,
            'from_account' => [
                'balance_before' => $balanceBeforeFrom,
                'balance_after' => $balanceAfterFrom
            ],
            'to_account' => [
                'balance_before' => $balanceBeforeTo,
                'balance_after' => $balanceAfterTo
            ]
        ], 'Transfer successful');
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

function handleGetTransactions($conn, $input) {
    $limit = intval($input['limit'] ?? 50);
    $offset = intval($input['offset'] ?? 0);
    
    $stmt = $conn->prepare("SELECT t.*, a.account_number, c.first_name, c.last_name, c.customer_id,
                          u.full_name as performed_by_name
                          FROM transactions t
                          JOIN accounts a ON t.account_id = a.id
                          JOIN customers c ON a.customer_id = c.id
                          LEFT JOIN users u ON t.performed_by = u.id
                          ORDER BY t.transaction_date DESC
                          LIMIT ? OFFSET ?");
    $stmt->execute([$limit, $offset]);
    jsonResponse(true, $stmt->fetchAll());
}

function handleGetAccountTransactions($conn, $accountId) {
    if (!$accountId) {
        jsonResponse(false, null, 'Account ID is required', 400);
    }
    
    $stmt = $conn->prepare("SELECT t.*, u.full_name as performed_by_name
                          FROM transactions t
                          LEFT JOIN users u ON t.performed_by = u.id
                          WHERE t.account_id = ?
                          ORDER BY t.transaction_date DESC
                          LIMIT 100");
    $stmt->execute([$accountId]);
    jsonResponse(true, $stmt->fetchAll());
}

function handleGetCustomerTransactions($conn, $customerId) {
    if (!$customerId) {
        jsonResponse(false, null, 'Customer ID is required', 400);
    }
    
    $stmt = $conn->prepare("SELECT t.*, a.account_number, u.full_name as performed_by_name
                          FROM transactions t
                          JOIN accounts a ON t.account_id = a.id
                          LEFT JOIN users u ON t.performed_by = u.id
                          WHERE a.customer_id = ?
                          ORDER BY t.transaction_date DESC
                          LIMIT 100");
    $stmt->execute([$customerId]);
    jsonResponse(true, $stmt->fetchAll());
}

function handleGetRecentTransactions($conn) {
    $stmt = $conn->query("SELECT t.*, a.account_number, c.first_name, c.last_name,
                        u.full_name as performed_by_name
                        FROM transactions t
                        JOIN accounts a ON t.account_id = a.id
                        JOIN customers c ON a.customer_id = c.id
                        LEFT JOIN users u ON t.performed_by = u.id
                        ORDER BY t.transaction_date DESC
                        LIMIT 10");
    jsonResponse(true, $stmt->fetchAll());
}

// ==================== DASHBOARD & REPORTS ====================

function handleGetDashboardStats($conn) {
    // Total customers
    $stmt = $conn->query("SELECT COUNT(*) as total FROM customers WHERE status = 'active'");
    $totalCustomers = $stmt->fetch()['total'];
    
    // Total accounts
    $stmt = $conn->query("SELECT COUNT(*) as total FROM accounts WHERE status = 'active'");
    $totalAccounts = $stmt->fetch()['total'];
    
    // Total balance
    $stmt = $conn->query("SELECT COALESCE(SUM(balance), 0) as total FROM accounts WHERE status = 'active'");
    $totalBalance = $stmt->fetch()['total'];
    
    // Today's transactions
    $stmt = $conn->query("SELECT COUNT(*) as total FROM transactions WHERE DATE(transaction_date) = CURDATE()");
    $todayTransactions = $stmt->fetch()['total'];
    
    // Pending loans
    $stmt = $conn->query("SELECT COUNT(*) as total FROM loans WHERE status = 'pending'");
    $pendingLoans = $stmt->fetch()['total'];
    
    // Recent transactions
    $stmt = $conn->query("SELECT t.*, a.account_number, c.first_name, c.last_name
                        FROM transactions t
                        JOIN accounts a ON t.account_id = a.id
                        JOIN customers c ON a.customer_id = c.id
                        ORDER BY t.transaction_date DESC
                        LIMIT 5");
    $recentTransactions = $stmt->fetchAll();
    
    // Accounts by type
    $stmt = $conn->query("SELECT at.type_name, COUNT(*) as count, COALESCE(SUM(a.balance), 0) as total_balance
                        FROM account_types at
                        LEFT JOIN accounts a ON at.id = a.account_type_id AND a.status = 'active'
                        GROUP BY at.id, at.type_name");
    $accountsByType = $stmt->fetchAll();
    
    jsonResponse(true, [
        'total_customers' => $totalCustomers,
        'total_accounts' => $totalAccounts,
        'total_balance' => $totalBalance,
        'today_transactions' => $todayTransactions,
        'pending_loans' => $pendingLoans,
        'recent_transactions' => $recentTransactions,
        'accounts_by_type' => $accountsByType
    ]);
}

function handleGetMonthlyReport($conn, $year, $month) {
    $stmt = $conn->prepare("SELECT 
        DATE(transaction_date) as date,
        transaction_type,
        COUNT(*) as count,
        SUM(amount) as total_amount
        FROM transactions 
        WHERE YEAR(transaction_date) = ? AND MONTH(transaction_date) = ?
        GROUP BY DATE(transaction_date), transaction_type
        ORDER BY date");
    $stmt->execute([$year, $month]);
    $dailyReport = $stmt->fetchAll();
    
    $stmt = $conn->prepare("SELECT 
        transaction_type,
        COUNT(*) as count,
        SUM(amount) as total_amount
        FROM transactions 
        WHERE YEAR(transaction_date) = ? AND MONTH(transaction_date) = ?
        GROUP BY transaction_type");
    $stmt->execute([$year, $month]);
    $typeReport = $stmt->fetchAll();
    
    jsonResponse(true, [
        'daily_report' => $dailyReport,
        'type_report' => $typeReport,
        'year' => $year,
        'month' => $month
    ]);
}

// ==================== NOTIFICATIONS ====================

function handleGetNotifications($conn) {
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM notifications 
                          WHERE user_id = ? OR user_id IS NULL 
                          ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$userId]);
    jsonResponse(true, $stmt->fetchAll());
}

function handleMarkNotificationRead($conn, $id) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->execute([$id]);
    jsonResponse(true, null, 'Notification marked as read');
}

// ==================== LOANS ====================

function handleGetLoans($conn) {
    $stmt = $conn->query("SELECT l.*, c.first_name, c.last_name, c.customer_id, a.account_number,
                        u.full_name as approved_by_name
                        FROM loans l
                        JOIN customers c ON l.customer_id = c.id
                        JOIN accounts a ON l.account_id = a.id
                        LEFT JOIN users u ON l.approved_by = u.id
                        ORDER BY l.created_at DESC");
    jsonResponse(true, $stmt->fetchAll());
}

function handleCreateLoan($conn, $input) {
    $loanId = generateUniqueId('LON');
    
    // Validate customer and account
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ? AND status = 'active'");
    $stmt->execute([intval($input['customer_id'] ?? 0)]);
    if (!$stmt->fetch()) {
        jsonResponse(false, null, 'Invalid customer', 400);
    }
    
    $stmt = $conn->prepare("SELECT * FROM accounts WHERE id = ? AND status = 'active'");
    $stmt->execute([intval($input['account_id'] ?? 0)]);
    if (!$stmt->fetch()) {
        jsonResponse(false, null, 'Invalid account', 400);
    }
    
    $principal = floatval($input['principal_amount'] ?? 0);
    $interestRate = floatval($input['interest_rate'] ?? 0);
    $termMonths = intval($input['loan_term_months'] ?? 0);
    
    // Calculate total amount due (simple interest)
    $totalInterest = $principal * ($interestRate / 100) * ($termMonths / 12);
    $totalAmountDue = $principal + $totalInterest;
    $monthlyPayment = $totalAmountDue / $termMonths;
    
    $stmt = $conn->prepare("INSERT INTO loans 
        (loan_id, customer_id, account_id, principal_amount, interest_rate, loan_term_months, 
        monthly_payment, total_amount_due, outstanding_balance) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $loanId,
        intval($input['customer_id']),
        intval($input['account_id']),
        $principal,
        $interestRate,
        $termMonths,
        $monthlyPayment,
        $totalAmountDue,
        $totalAmountDue
    ]);
    
    logActivity($conn, $_SESSION['user_id'], 'CREATE_LOAN', 'loans', $conn->lastInsertId());
    
    jsonResponse(true, ['id' => $conn->lastInsertId(), 'loan_id' => $loanId], 'Loan application created');
}

function handleApproveLoan($conn, $input) {
    $loanId = intval($input['loan_id'] ?? 0);
    
    $stmt = $conn->prepare("UPDATE loans SET 
        status = 'approved',
        approved_by = ?,
        approval_date = CURDATE(),
        start_date = CURDATE(),
        status = 'active'
        WHERE id = ? AND status = 'pending'");
    $stmt->execute([$_SESSION['user_id'], $loanId]);
    
    // Get loan details to credit account
    $stmt = $conn->prepare("SELECT * FROM loans WHERE id = ?");
    $stmt->execute([$loanId]);
    $loan = $stmt->fetch();
    
    if ($loan) {
        // Credit the loan amount to account
        $stmt = $conn->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$loan['principal_amount'], $loan['account_id']]);
        
        // Record transaction
        $transactionId = generateUniqueId('TXN');
        $stmt = $conn->prepare("SELECT balance FROM accounts WHERE id = ?");
        $stmt->execute([$loan['account_id']]);
        $account = $stmt->fetch();
        
        $stmt = $conn->prepare("INSERT INTO transactions 
            (transaction_id, account_id, transaction_type, amount, balance_before, balance_after, description, performed_by) 
            VALUES (?, ?, 'deposit', ?, ?, ?, 'Loan disbursement', ?)");
        $stmt->execute([$transactionId, $loan['account_id'], $loan['principal_amount'], 
            $account['balance'] - $loan['principal_amount'], $account['balance'], $_SESSION['user_id']]);
    }
    
    logActivity($conn, $_SESSION['user_id'], 'APPROVE_LOAN', 'loans', $loanId);
    
    jsonResponse(true, null, 'Loan approved successfully');
}

function handleMakeLoanPayment($conn, $input) {
    $loanId = intval($input['loan_id'] ?? 0);
    $amount = floatval($input['amount'] ?? 0);
    
    if ($amount <= 0) {
        jsonResponse(false, null, 'Amount must be greater than 0', 400);
    }
    
    $stmt = $conn->prepare("SELECT * FROM loans WHERE id = ? AND status = 'active'");
    $stmt->execute([$loanId]);
    $loan = $stmt->fetch();
    
    if (!$loan) {
        jsonResponse(false, null, 'Loan not found or not active', 400);
    }
    
    if ($amount > $loan['outstanding_balance']) {
        $amount = $loan['outstanding_balance'];
    }
    
    $conn->beginTransaction();
    
    try {
        // Calculate principal and interest portions
        $interestPortion = min($amount * ($loan['interest_rate'] / 100 / 12), $loan['outstanding_balance'] - ($amount - ($amount * ($loan['interest_rate'] / 100 / 12))));
        $principalPortion = $amount - $interestPortion;
        
        // Update loan
        $newBalance = $loan['outstanding_balance'] - $principalPortion;
        $amountPaid = $loan['amount_paid'] + $principalPortion;
        
        $status = 'active';
        if ($newBalance <= 0) {
            $status = 'completed';
            $newBalance = 0;
        }
        
        $stmt = $conn->prepare("UPDATE loans SET 
            outstanding_balance = ?, amount_paid = ?, status = ? 
            WHERE id = ?");
        $stmt->execute([$newBalance, $amountPaid, $status, $loanId]);
        
        // Deduct from account
        $stmt = $conn->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$amount, $loan['account_id']]);
        
        // Get new balance
        $stmt = $conn->prepare("SELECT balance FROM accounts WHERE id = ?");
        $stmt->execute([$loan['account_id']]);
        $account = $stmt->fetch();
        
        // Record payment
        $paymentId = generateUniqueId('LPMT');
        $stmt = $conn->prepare("INSERT INTO loan_payments 
            (payment_id, loan_id, amount, principal_portion, interest_portion, balance_after, performed_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$paymentId, $loanId, $amount, $principalPortion, $interestPortion, $newBalance, $_SESSION['user_id']]);
        
        logActivity($conn, $_SESSION['user_id'], 'LOAN_PAYMENT', 'loan_payments', $conn->lastInsertId());
        
        $conn->commit();
        jsonResponse(true, [
            'payment_id' => $paymentId,
            'principal_portion' => $principalPortion,
            'interest_portion' => $interestPortion,
            'outstanding_balance' => $newBalance
        ], 'Loan payment successful');
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}
