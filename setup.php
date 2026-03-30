<?php
/**
 * Bank Management System - Setup Script
 * Run this file once to initialize the database
 */

// Database configuration
$host = 'localhost';
$dbname = 'bank_management_system';
$user = 'root';
$pass = '';

echo "=== Bank Management System Setup ===\n\n";

try {
    // Connect without database first
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database
    echo "Creating database...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $pdo->exec("USE $dbname");
    
    echo "Database '$dbname' created successfully!\n\n";
    
    // Create tables
    echo "Creating tables...\n";
    
    // Users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            full_name VARCHAR(100) NOT NULL,
            role ENUM('admin', 'teller', 'manager') DEFAULT 'teller',
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "  - users table created\n";
    
    // Customers table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id VARCHAR(20) NOT NULL UNIQUE,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) UNIQUE,
            phone VARCHAR(20),
            address TEXT,
            date_of_birth DATE,
            id_number VARCHAR(20) UNIQUE,
            occupation VARCHAR(100),
            employer VARCHAR(100),
            monthly_income DECIMAL(12, 2),
            status ENUM('active', 'inactive', 'closed') DEFAULT 'active',
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "  - customers table created\n";
    
    // Account types table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS account_types (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type_name VARCHAR(50) NOT NULL UNIQUE,
            description TEXT,
            interest_rate DECIMAL(5, 2) DEFAULT 0.00,
            min_balance DECIMAL(12, 2) DEFAULT 0.00,
            max_withdrawal DECIMAL(12, 2),
            monthly_fee DECIMAL(10, 2) DEFAULT 0.00,
            is_active BOOLEAN DEFAULT TRUE
        )
    ");
    echo "  - account_types table created\n";
    
    // Accounts table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS accounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            account_number VARCHAR(20) NOT NULL UNIQUE,
            customer_id INT NOT NULL,
            account_type_id INT NOT NULL,
            balance DECIMAL(15, 2) DEFAULT 0.00,
            status ENUM('active', 'inactive', 'frozen', 'closed') DEFAULT 'active',
            opened_date DATE NOT NULL,
            closed_date DATE,
            overdraft_limit DECIMAL(12, 2) DEFAULT 0.00,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
        )
    ");
    echo "  - accounts table created\n";
    
    // Transactions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            transaction_id VARCHAR(20) NOT NULL UNIQUE,
            account_id INT NOT NULL,
            transaction_type ENUM('deposit', 'withdrawal', 'transfer', 'payment', 'interest', 'fee') NOT NULL,
            amount DECIMAL(15, 2) NOT NULL,
            balance_before DECIMAL(15, 2) NOT NULL,
            balance_after DECIMAL(15, 2) NOT NULL,
            transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            description TEXT,
            reference_number VARCHAR(50),
            performed_by INT
        )
    ");
    echo "  - transactions table created\n";
    
    // Transfers table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS transfers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            transfer_id VARCHAR(20) NOT NULL UNIQUE,
            from_account_id INT NOT NULL,
            to_account_id INT NOT NULL,
            amount DECIMAL(15, 2) NOT NULL,
            transfer_fee DECIMAL(10, 2) DEFAULT 0.00,
            status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'completed',
            transfer_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            description TEXT,
            performed_by INT
        )
    ");
    echo "  - transfers table created\n";
    
    // Loans table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS loans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            loan_id VARCHAR(20) NOT NULL UNIQUE,
            customer_id INT NOT NULL,
            account_id INT NOT NULL,
            principal_amount DECIMAL(15, 2) NOT NULL,
            interest_rate DECIMAL(5, 2) NOT NULL,
            loan_term_months INT NOT NULL,
            monthly_payment DECIMAL(12, 2) NOT NULL,
            total_amount_due DECIMAL(15, 2) NOT NULL,
            amount_paid DECIMAL(15, 2) DEFAULT 0.00,
            outstanding_balance DECIMAL(15, 2) NOT NULL,
            status ENUM('pending', 'approved', 'active', 'completed', 'defaulted', 'rejected') DEFAULT 'pending',
            approved_by INT,
            approval_date DATE,
            start_date DATE,
            end_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "  - loans table created\n";
    
    // Loan payments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS loan_payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            payment_id VARCHAR(20) NOT NULL UNIQUE,
            loan_id INT NOT NULL,
            amount DECIMAL(12, 2) NOT NULL,
            principal_portion DECIMAL(12, 2) NOT NULL,
            interest_portion DECIMAL(12, 2) NOT NULL,
            balance_after DECIMAL(12, 2) NOT NULL,
            payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            performed_by INT
        )
    ");
    echo "  - loan_payments table created\n";
    
    // Notifications table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT,
            user_id INT,
            title VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "  - notifications table created\n";
    
    // Audit log table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS audit_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(100) NOT NULL,
            table_affected VARCHAR(50),
            record_id INT,
            old_value TEXT,
            new_value TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "  - audit_log table created\n";
    
    echo "\nAll tables created successfully!\n\n";
    
    // Insert default account types
    echo "Inserting default account types...\n";
    $stmt = $pdo->prepare("INSERT IGNORE INTO account_types (type_name, description, interest_rate, min_balance, max_withdrawal, monthly_fee) VALUES (?, ?, ?, ?, ?, ?)");
    $types = [
        ['Savings', 'Basic savings account with interest', 2.50, 100.00, 5000.00, 10.00],
        ['Checking', 'Current account for daily transactions', 0.00, 500.00, 10000.00, 25.00],
        ['Fixed Deposit', 'Term deposit with higher interest', 5.50, 1000.00, NULL, 0.00],
        ['Business', 'Business account for enterprises', 1.00, 1000.00, 25000.00, 50.00],
        ['Joint', 'Shared account for multiple owners', 1.50, 200.00, 7500.00, 15.00]
    ];
    
    foreach ($types as $type) {
        $stmt->execute($type);
        echo "  - {$type[0]} account type added\n";
    }
    
    echo "\nCreating admin user...\n";
    
    // Check if admin exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        // Create admin user with password 'admin123'
        // The hash below is for 'admin123'
        $adminPassword = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 10]);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['admin', $adminPassword, 'admin@bank.com', 'System Administrator', 'admin']);
        echo "  - Admin user created!\n";
        echo "    Username: admin\n";
        echo "    Password: admin123\n";
    } else {
        echo "  - Admin user already exists!\n";
    }
    
    echo "\n=== Setup Complete! ===\n";
    echo "\nYou can now login with:\n";
    echo "  Username: admin\n";
    echo "  Password: admin123\n";
    echo "\nAccess the application at: index.html\n";
    echo "\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Make sure MySQL is running\n";
    echo "2. Check your MySQL credentials in this file\n";
    echo "3. Make sure you have permission to create databases\n";
}
?>
