# Bank Management System

A comprehensive PHP-based bank management system with HTML, CSS, JavaScript frontend and MySQL database.

## Features

### Core Banking Features
- **Customer Management**: Create, view, edit, and search customers
- **Account Management**: Open new accounts with multiple account types
- **Transactions**: 
  - Deposits
  - Withdrawals
  - Transfers between accounts
- **Loans**: Loan applications, approvals, and payment tracking
- **User Management**: Staff accounts with role-based access

### Account Types
- Savings Account (2.5% interest)
- Checking Account (Current account)
- Fixed Deposit (5.5% interest)
- Business Account
- Joint Account

### User Roles
- **Admin**: Full system access, user management
- **Manager**: Approve loans, manage accounts
- **Teller**: Process transactions (deposits, withdrawals, transfers)

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

### Setup Steps (IMPORTANT)

1. **Configure Database Credentials**
   Edit `config.php` and `setup.php` if your MySQL credentials are different:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'bank_management_system');
   define('DB_USER', 'root');      // Your MySQL username
   define('DB_PASS', '');           // Your MySQL password
   ```

2. **Run the Setup Script**
   Open your browser and navigate to:
   ```
   http://localhost/bank-system/setup.php
   ```
   
   This will:
   - Create the database automatically
   - Create all required tables
   - Insert default account types
   - Create the admin user

3. **Start the Application**
   - Using PHP built-in server:
     ```bash
     cd bank-system
     php -S localhost:8000
     ```
   - Or use XAMPP/WAMP: Place the bank-system folder in your htdocs/www folder

4. **Login Credentials**
   - Username: `admin`
   - Password: `admin123`

### Troubleshooting

**"Database connection failed" error:**
- Make sure MySQL is running
- Check your MySQL credentials in `config.php`
- For XAMPP: default is `root` with empty password
- For WAMP: default is `root` with empty password
- For MAMP: default is `root` with `root`

**Login still failing after setup:**
- Run `setup.php` again to recreate the admin user
- Make sure you're using: `admin` / `admin123`

## Project Structure

```
bank-system/
├── database.sql      # Database schema
├── config.php       # Configuration
├── api.php          # Backend API
├── index.html       # Frontend interface
├── style.css        # Styling
├── app.js           # Frontend logic
└── README.md        # Documentation
```

## API Endpoints

### Authentication
- `POST api.php?action=login` - User login
- `POST api.php?action=logout` - User logout
- `GET api.php?action=checkSession` - Check session status

### Users
- `GET api.php?action=getUsers` - List all users
- `POST api.php?action=createUser` - Create new user
- `POST api.php?action=updateUser` - Update user
- `POST api.php?action=deleteUser` - Delete user

### Customers
- `GET api.php?action=getCustomers` - List all customers
- `POST api.php?action=getCustomer` - Get customer details
- `POST api.php?action=createCustomer` - Create customer
- `POST api.php?action=updateCustomer` - Update customer
- `POST api.php?action=searchCustomers` - Search customers

### Accounts
- `GET api.php?action=getAccounts` - List all accounts
- `POST api.php?action=getAccount` - Get account details
- `POST api.php?action=createAccount` - Create account
- `POST api.php?action=closeAccount` - Close account
- `POST api.php?action=freezeAccount` - Freeze account
- `GET api.php?action=getAccountTypes` - List account types

### Transactions
- `POST api.php?action=deposit` - Make deposit
- `POST api.php?action=withdraw` - Make withdrawal
- `POST api.php?action=transfer` - Transfer funds
- `GET api.php?action=getTransactions` - List transactions
- `POST api.php?action=getAccountTransactions` - Account transaction history
- `GET api.php?action=getRecentTransactions` - Recent transactions

### Loans
- `GET api.php?action=getLoans` - List all loans
- `POST api.php?action=createLoan` - Create loan application
- `POST api.php?action=approveLoan` - Approve loan
- `POST api.php?action=makeLoanPayment` - Make loan payment

### Reports & Dashboard
- `GET api.php?action=getDashboardStats` - Dashboard statistics
- `POST api.php?action=getMonthlyReport` - Monthly report

## Database Schema

### Tables
- `users` - System users (admins, tellers, managers)
- `customers` - Customer information
- `account_types` - Types of bank accounts
- `accounts` - Customer bank accounts
- `transactions` - Transaction history
- `transfers` - Transfer records
- `loans` - Loan applications and active loans
- `loan_payments` - Loan payment history
- `notifications` - System notifications
- `audit_log` - Activity audit trail

## Security Features

- Password hashing with bcrypt
- Session management
- Input sanitization
- SQL injection prevention (prepared statements)
- Role-based access control
- Activity logging

## Screenshots

### Login Page
Professional login interface with secure authentication.

### Dashboard
- Overview statistics (customers, accounts, total balance, transactions)
- Recent transactions table
- Accounts by type visualization

### Customer Management
- Searchable customer list
- Add/Edit customer forms
- Customer details view with account information

### Account Management
- Account listing with status indicators
- Open new account functionality
- Account details with transaction history

### Transactions
- Quick action buttons (Deposit, Withdraw, Transfer)
- Full transaction history
- Real-time balance updates

### Loan Management
- Loan application submission
- Approval workflow
- Payment processing

## License

This project is open source and available for educational purposes.

## Support

For issues or questions, please create an issue in the repository.
