-- Bank Management System Database Schema
-- Database: bank_management_system

-- Create database
CREATE DATABASE IF NOT EXISTS bank_management_system;
USE bank_management_system;

-- Users table (staff/admin accounts)
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
);

-- Customers table
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Account types table
CREATE TABLE IF NOT EXISTS account_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    interest_rate DECIMAL(5, 2) DEFAULT 0.00,
    min_balance DECIMAL(12, 2) DEFAULT 0.00,
    max_withdrawal DECIMAL(12, 2),
    monthly_fee DECIMAL(10, 2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE
);

-- Accounts table
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
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (account_type_id) REFERENCES account_types(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Transactions table
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
    performed_by INT,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Transfers table (for transfer transactions)
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
    performed_by INT,
    FOREIGN KEY (from_account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (to_account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Loans table
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Loan payments table
CREATE TABLE IF NOT EXISTS loan_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id VARCHAR(20) NOT NULL UNIQUE,
    loan_id INT NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    principal_portion DECIMAL(12, 2) NOT NULL,
    interest_portion DECIMAL(12, 2) NOT NULL,
    balance_after DECIMAL(15, 2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    performed_by INT,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    user_id INT,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Audit log table
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default account types
INSERT INTO account_types (type_name, description, interest_rate, min_balance, max_withdrawal, monthly_fee) VALUES
('Savings', 'Basic savings account with interest', 2.50, 100.00, 5000.00, 10.00),
('Checking', 'Current account for daily transactions', 0.00, 500.00, 10000.00, 25.00),
('Fixed Deposit', 'Term deposit with higher interest', 5.50, 1000.00, NULL, 0.00),
('Business', 'Business account for enterprises', 1.00, 1000.00, 25000.00, 50.00),
('Joint', 'Shared account for multiple owners', 1.50, 200.00, 7500.00, 15.00);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, email, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@bank.com', 'System Administrator', 'admin');
