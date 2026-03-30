/**
 * Bank Management System - Frontend Application
 */

const API_URL = 'api.php';

// State management
let currentUser = null;
let customers = [];
let accounts = [];
let transactions = [];
let loans = [];
let users = [];
let accountTypes = [];

// ==================== INITIALIZATION ====================

document.addEventListener('DOMContentLoaded', function() {
    checkSession();
    setupEventListeners();
});

async function checkSession() {
    try {
        const response = await fetch(`${API_URL}?action=checkSession`);
        const result = await response.json();
        
        if (result.success && result.data.logged_in) {
            currentUser = result.data.user;
            showMainApp();
            loadDashboard();
        } else {
            showLoginPage();
        }
    } catch (error) {
        showLoginPage();
    }
}

function setupEventListeners() {
    // Login form
    document.getElementById('loginForm').addEventListener('submit', handleLogin);
    
    // Logout button
    document.getElementById('logoutBtn').addEventListener('click', handleLogout);
    
    // Navigation
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', handleNavigation);
    });
    
    // Menu toggle (mobile)
    document.querySelector('.menu-toggle').addEventListener('click', toggleSidebar);
    
    // Customer buttons
    document.getElementById('addCustomerBtn').addEventListener('click', () => openCustomerModal());
    document.getElementById('customerSearch').addEventListener('input', handleCustomerSearch);
    document.getElementById('customerForm').addEventListener('submit', handleCustomerSubmit);
    
    // Account buttons
    document.getElementById('addAccountBtn').addEventListener('click', openAccountModal);
    document.getElementById('accountForm').addEventListener('submit', handleAccountSubmit);
    
    // Transaction buttons
    document.getElementById('depositBtn').addEventListener('click', () => openModal('depositModal'));
    document.getElementById('withdrawBtn').addEventListener('click', () => openModal('withdrawModal'));
    document.getElementById('transferBtn').addEventListener('click', () => openModal('transferModal'));
    
    // Deposit form
    document.getElementById('depositForm').addEventListener('submit', handleDeposit);
    document.getElementById('depositAccount').addEventListener('blur', lookupAccount);
    
    // Withdraw form
    document.getElementById('withdrawForm').addEventListener('submit', handleWithdraw);
    document.getElementById('withdrawAccount').addEventListener('blur', lookupWithdrawAccount);
    
    // Transfer form
    document.getElementById('transferForm').addEventListener('submit', handleTransfer);
    document.getElementById('fromAccount').addEventListener('blur', lookupFromAccount);
    document.getElementById('toAccount').addEventListener('blur', lookupToAccount);
    
    // Loan buttons
    document.getElementById('addLoanBtn').addEventListener('click', openLoanModal);
    document.getElementById('loanForm').addEventListener('submit', handleLoanSubmit);
    document.getElementById('loanCustomer').addEventListener('change', loadCustomerAccounts);
    document.getElementById('principalAmount').addEventListener('input', calculateLoanPayment);
    document.getElementById('loanInterestRate').addEventListener('input', calculateLoanPayment);
    document.getElementById('loanTermMonths').addEventListener('input', calculateLoanPayment);
    
    // User buttons
    document.getElementById('addUserBtn').addEventListener('click', () => openUserModal());
    document.getElementById('userForm').addEventListener('submit', handleUserSubmit);
    
    // Report
    document.getElementById('generateReportBtn').addEventListener('click', generateReport);
    
    // Modal close buttons
    document.querySelectorAll('.modal-close, .modal-cancel').forEach(btn => {
        btn.addEventListener('click', closeModals);
    });
    
    // Close modal on background click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModals();
        });
    });
}

// ==================== AUTHENTICATION ====================

async function handleLogin(e) {
    e.preventDefault();
    
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
        });
        
        const result = await response.json();
        
        if (result.success) {
            currentUser = result.data.user;
            showMainApp();
            loadDashboard();
            showToast('Login successful!', 'success');
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('Login failed. Please try again.', 'error');
    }
}

async function handleLogout() {
    try {
        await fetch(API_URL + '?action=logout');
    } catch (error) {
        console.error('Logout error:', error);
    }
    
    currentUser = null;
    showLoginPage();
    showToast('Logged out successfully', 'success');
}

function showLoginPage() {
    document.getElementById('loginPage').classList.remove('hidden');
    document.getElementById('mainApp').classList.add('hidden');
}

function showMainApp() {
    document.getElementById('loginPage').classList.add('hidden');
    document.getElementById('mainApp').classList.remove('hidden');
    document.getElementById('currentUser').textContent = currentUser.full_name;
    
    // Hide users nav for non-admin
    if (currentUser.role !== 'admin') {
        document.getElementById('usersNav').style.display = 'none';
    } else {
        document.getElementById('usersNav').style.display = 'flex';
    }
}

// ==================== NAVIGATION ====================

function handleNavigation(e) {
    e.preventDefault();
    
    const page = e.currentTarget.dataset.page;
    
    // Update active nav item
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    e.currentTarget.classList.add('active');
    
    // Update page title
    const titles = {
        dashboard: 'Dashboard',
        customers: 'Customers',
        accounts: 'Accounts',
        transactions: 'Transactions',
        loans: 'Loans',
        reports: 'Reports',
        users: 'User Management'
    };
    document.getElementById('pageTitle').textContent = titles[page];
    
    // Show page
    document.querySelectorAll('.page').forEach(p => {
        p.classList.remove('active');
    });
    document.getElementById(`${page}Page`).classList.add('active');
    
    // Load page data
    switch (page) {
        case 'dashboard':
            loadDashboard();
            break;
        case 'customers':
            loadCustomers();
            break;
        case 'accounts':
            loadAccounts();
            break;
        case 'transactions':
            loadTransactions();
            break;
        case 'loans':
            loadLoans();
            break;
        case 'users':
            loadUsers();
            break;
    }
}

function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
}

// ==================== DASHBOARD ====================

async function loadDashboard() {
    try {
        const response = await fetch(API_URL + '?action=getDashboardStats');
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            
            document.getElementById('totalCustomers').textContent = data.total_customers;
            document.getElementById('totalAccounts').textContent = data.total_accounts;
            document.getElementById('totalBalance').textContent = '$' + formatNumber(data.total_balance);
            document.getElementById('todayTransactions').textContent = data.today_transactions;
            
            // Recent transactions
            const tbody = document.querySelector('#recentTransactionsTable tbody');
            tbody.innerHTML = data.recent_transactions.map(t => `
                <tr>
                    <td>${formatDate(t.transaction_date)}</td>
                    <td>${t.account_number}</td>
                    <td>${t.first_name} ${t.last_name}</td>
                    <td><span class="status-badge ${t.transaction_type}">${t.transaction_type}</span></td>
                    <td class="amount ${t.transaction_type === 'deposit' ? 'positive' : 'negative'}">
                        ${t.transaction_type === 'deposit' ? '+' : '-'}$${formatNumber(t.amount)}
                    </td>
                </tr>
            `).join('');
            
            // Accounts by type chart
            const chartContainer = document.getElementById('accountsByType');
            const maxBalance = Math.max(...data.accounts_by_type.map(a => parseFloat(a.total_balance)), 1);
            chartContainer.innerHTML = data.accounts_by_type.map(a => `
                <div class="chart-item">
                    <div class="chart-label">${a.type_name}</div>
                    <div class="chart-bar">
                        <div class="chart-fill" style="width: ${(parseFloat(a.total_balance) / maxBalance) * 100}%"></div>
                    </div>
                    <div class="chart-value">$${formatNumber(a.total_balance)}</div>
                </div>
            `).join('');
            
            document.getElementById('notificationBadge').textContent = data.pending_loans;
        }
    } catch (error) {
        console.error('Dashboard load error:', error);
    }
}

// ==================== CUSTOMERS ====================

async function loadCustomers() {
    try {
        const response = await fetch(API_URL + '?action=getCustomers');
        const result = await response.json();
        
        if (result.success) {
            customers = result.data;
            renderCustomersTable();
        }
    } catch (error) {
        showToast('Failed to load customers', 'error');
    }
}

function renderCustomersTable(filteredCustomers = customers) {
    const tbody = document.querySelector('#customersTable tbody');
    tbody.innerHTML = filteredCustomers.map(c => `
        <tr>
            <td>${c.customer_id}</td>
            <td>${c.first_name} ${c.last_name}</td>
            <td>${c.email || '-'}</td>
            <td>${c.phone || '-'}</td>
            <td><span class="status-badge ${c.status}">${c.status}</span></td>
            <td>${formatDate(c.created_at)}</td>
            <td class="actions">
                <button class="view-btn" onclick="viewCustomer(${c.id})"><i class="fas fa-eye"></i></button>
                <button class="edit-btn" onclick="editCustomer(${c.id})"><i class="fas fa-edit"></i></button>
            </td>
        </tr>
    `).join('');
}

function handleCustomerSearch(e) {
    const query = e.target.value.toLowerCase();
    const filtered = customers.filter(c => 
        c.customer_id.toLowerCase().includes(query) ||
        c.first_name.toLowerCase().includes(query) ||
        c.last_name.toLowerCase().includes(query) ||
        (c.email && c.email.toLowerCase().includes(query))
    );
    renderCustomersTable(filtered);
}

function openCustomerModal(customer = null) {
    const modal = document.getElementById('customerModal');
    const form = document.getElementById('customerForm');
    const title = document.getElementById('customerModalTitle');
    
    form.reset();
    document.getElementById('customerId').value = '';
    
    if (customer) {
        title.textContent = 'Edit Customer';
        document.getElementById('customerId').value = customer.id;
        document.getElementById('firstName').value = customer.first_name;
        document.getElementById('lastName').value = customer.last_name;
        document.getElementById('email').value = customer.email || '';
        document.getElementById('phone').value = customer.phone || '';
        document.getElementById('address').value = customer.address || '';
        document.getElementById('dateOfBirth').value = customer.date_of_birth || '';
        document.getElementById('idNumber').value = customer.id_number || '';
        document.getElementById('occupation').value = customer.occupation || '';
        document.getElementById('employer').value = customer.employer || '';
        document.getElementById('monthlyIncome').value = customer.monthly_income || '';
    } else {
        title.textContent = 'Add Customer';
    }
    
    modal.classList.add('active');
}

async function editCustomer(id) {
    const customer = customers.find(c => c.id === id);
    if (customer) {
        openCustomerModal(customer);
    }
}

async function viewCustomer(id) {
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, action: 'getCustomer' })
        });
        const result = await response.json();
        
        if (result.success) {
            const c = result.data;
            const modal = document.getElementById('customerDetailsModal');
            
            // Get customer accounts
            const accountsResponse = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ customer_id: c.id, action: 'getCustomerAccounts' })
            });
            const accountsResult = await accountsResponse.json();
            const customerAccounts = accountsResult.success ? accountsResult.data : [];
            
            document.getElementById('customerDetailsContent').innerHTML = `
                <div class="details-section">
                    <h4>Personal Information</h4>
                    <div class="detail-item">
                        <span class="label">Customer ID</span>
                        <span class="value">${c.customer_id}</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Full Name</span>
                        <span class="value">${c.first_name} ${c.last_name}</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Email</span>
                        <span class="value">${c.email || '-'}</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Phone</span>
                        <span class="value">${c.phone || '-'}</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Address</span>
                        <span class="value">${c.address || '-'}</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Date of Birth</span>
                        <span class="value">${c.date_of_birth ? formatDate(c.date_of_birth) : '-'}</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">ID Number</span>
                        <span class="value">${c.id_number || '-'}</span>
                    </div>
                </div>
                
                <div class="details-section">
                    <h4>Employment</h4>
                    <div class="detail-item">
                        <span class="label">Occupation</span>
                        <span class="value">${c.occupation || '-'}</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Employer</span>
                        <span class="value">${c.employer || '-'}</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Monthly Income</span>
                        <span class="value">$${formatNumber(c.monthly_income)}</span>
                    </div>
                </div>
                
                <div class="details-section">
                    <h4>Accounts (${customerAccounts.length})</h4>
                    ${customerAccounts.length > 0 ? customerAccounts.map(a => `
                        <div class="detail-item">
                            <span class="label">${a.account_number} (${a.type_name})</span>
                            <span class="value">$${formatNumber(a.balance)}</span>
                        </div>
                    `).join('') : '<p style="color: gray; padding: 10px 0;">No accounts</p>'}
                </div>
                
                <div class="details-section">
                    <h4>Status</h4>
                    <div class="detail-item">
                        <span class="label">Status</span>
                        <span class="value"><span class="status-badge ${c.status}">${c.status}</span></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Created</span>
                        <span class="value">${formatDate(c.created_at)}</span>
                    </div>
                </div>
            `;
            
            modal.classList.add('active');
        }
    } catch (error) {
        showToast('Failed to load customer details', 'error');
    }
}

async function handleCustomerSubmit(e) {
    e.preventDefault();
    
    const id = document.getElementById('customerId').value;
    const data = {
        action: id ? 'updateCustomer' : 'createCustomer',
        first_name: document.getElementById('firstName').value,
        last_name: document.getElementById('lastName').value,
        email: document.getElementById('email').value,
        phone: document.getElementById('phone').value,
        address: document.getElementById('address').value,
        date_of_birth: document.getElementById('dateOfBirth').value,
        id_number: document.getElementById('idNumber').value,
        occupation: document.getElementById('occupation').value,
        employer: document.getElementById('employer').value,
        monthly_income: document.getElementById('monthlyIncome').value
    };
    
    if (id) {
        data.id = id;
    }
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(id ? 'Customer updated successfully' : 'Customer created successfully', 'success');
            closeModals();
            loadCustomers();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('Operation failed', 'error');
    }
}

// ==================== ACCOUNTS ====================

async function loadAccounts() {
    try {
        const response = await fetch(API_URL + '?action=getAccounts');
        const result = await response.json();
        
        if (result.success) {
            accounts = result.data;
            renderAccountsTable();
        }
    } catch (error) {
        showToast('Failed to load accounts', 'error');
    }
}

function renderAccountsTable(filteredAccounts = accounts) {
    const tbody = document.querySelector('#accountsTable tbody');
    tbody.innerHTML = filteredAccounts.map(a => `
        <tr>
            <td>${a.account_number}</td>
            <td>${a.first_name} ${a.last_name}</td>
            <td>${a.type_name}</td>
            <td class="amount">$${formatNumber(a.balance)}</td>
            <td><span class="status-badge ${a.status}">${a.status}</span></td>
            <td>${formatDate(a.opened_date)}</td>
            <td class="actions">
                <button class="view-btn" onclick="viewAccount(${a.id})"><i class="fas fa-eye"></i></button>
            </td>
        </tr>
    `).join('');
}

async function openAccountModal() {
    const modal = document.getElementById('accountModal');
    
    // Load customers
    await loadCustomersForSelect();
    
    // Load account types
    await loadAccountTypes();
    
    document.getElementById('accountForm').reset();
    modal.classList.add('active');
}

async function loadCustomersForSelect() {
    try {
        const response = await fetch(API_URL + '?action=getCustomers');
        const result = await response.json();
        
        if (result.success) {
            const select = document.getElementById('accountCustomer');
            select.innerHTML = '<option value="">Select Customer</option>' +
                result.data.filter(c => c.status === 'active').map(c => 
                    `<option value="${c.id}">${c.customer_id} - ${c.first_name} ${c.last_name}</option>`
                ).join('');
        }
    } catch (error) {
        console.error('Failed to load customers:', error);
    }
}

async function loadAccountTypes() {
    try {
        const response = await fetch(API_URL + '?action=getAccountTypes');
        const result = await response.json();
        
        if (result.success) {
            accountTypes = result.data;
            const select = document.getElementById('accountType');
            select.innerHTML = '<option value="">Select Type</option>' +
                result.data.map(t => 
                    `<option value="${t.id}">${t.type_name} (Min: $${formatNumber(t.min_balance)})</option>`
                ).join('');
        }
    } catch (error) {
        console.error('Failed to load account types:', error);
    }
}

async function handleAccountSubmit(e) {
    e.preventDefault();
    
    const data = {
        action: 'createAccount',
        customer_id: document.getElementById('accountCustomer').value,
        account_type_id: document.getElementById('accountType').value,
        initial_deposit: document.getElementById('initialDeposit').value,
        overdraft_limit: document.getElementById('overdraftLimit').value
    };
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(`Account created successfully! Account Number: ${result.data.account_number}`, 'success');
            closeModals();
            loadAccounts();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('Failed to create account', 'error');
    }
}

async function viewAccount(id) {
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, action: 'getAccount' })
        });
        const result = await response.json();
        
        if (result.success) {
            const a = result.data;
            const modal = document.getElementById('accountDetailsModal');
            
            // Get transactions
            const txResponse = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ account_id: id, action: 'getAccountTransactions' })
            });
            const txResult = await txResponse.json();
            const transactions = txResult.success ? txResult.data : [];
            
            document.getElementById('accountDetailsContent').innerHTML = `
                <div class="details-grid">
                    <div class="details-section">
                        <h4>Account Information</h4>
                        <div class="detail-item">
                            <span class="label">Account Number</span>
                            <span class="value">${a.account_number}</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Account Type</span>
                            <span class="value">${a.type_name}</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Current Balance</span>
                            <span class="value" style="color: var(--success); font-size: 18px;">$${formatNumber(a.balance)}</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Interest Rate</span>
                            <span class="value">${a.interest_rate}%</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Min Balance</span>
                            <span class="value">$${formatNumber(a.min_balance)}</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Overdraft Limit</span>
                            <span class="value">$${formatNumber(a.overdraft_limit)}</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Status</span>
                            <span class="value"><span class="status-badge ${a.status}">${a.status}</span></span>
                        </div>
                    </div>
                    
                    <div class="details-section">
                        <h4>Owner Information</h4>
                        <div class="detail-item">
                            <span class="label">Customer ID</span>
                            <span class="value">${a.customer_id}</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Name</span>
                            <span class="value">${a.first_name} ${a.last_name}</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Email</span>
                            <span class="value">${a.email || '-'}</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Phone</span>
                            <span class="value">${a.phone || '-'}</span>
                        </div>
                    </div>
                </div>
                
                <div class="details-section">
                    <h4>Recent Transactions (${transactions.length})</h4>
                    ${transactions.length > 0 ? `
                        <table class="data-table" style="margin-top: 10px;">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Balance</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${transactions.slice(0, 10).map(t => `
                                    <tr>
                                        <td>${formatDate(t.transaction_date)}</td>
                                        <td><span class="status-badge ${t.transaction_type}">${t.transaction_type}</span></td>
                                        <td class="amount ${t.transaction_type === 'deposit' ? 'positive' : 'negative'}">
                                            ${t.transaction_type === 'deposit' ? '+' : '-'}$${formatNumber(t.amount)}
                                        </td>
                                        <td class="amount">$${formatNumber(t.balance_after)}</td>
                                        <td>${t.description || '-'}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    ` : '<p style="color: gray; padding: 10px 0;">No transactions yet</p>'}
                </div>
            `;
            
            modal.classList.add('active');
        }
    } catch (error) {
        showToast('Failed to load account details', 'error');
    }
}

// ==================== TRANSACTIONS ====================

async function loadTransactions() {
    try {
        const response = await fetch(API_URL + '?action=getTransactions');
        const result = await response.json();
        
        if (result.success) {
            transactions = result.data;
            renderTransactionsTable();
        }
    } catch (error) {
        showToast('Failed to load transactions', 'error');
    }
}

function renderTransactionsTable(filteredTransactions = transactions) {
    const tbody = document.querySelector('#transactionsTable tbody');
    tbody.innerHTML = filteredTransactions.map(t => `
        <tr>
            <td>${t.transaction_id}</td>
            <td>${formatDate(t.transaction_date)}</td>
            <td>${t.account_number}</td>
            <td><span class="status-badge ${t.transaction_type}">${t.transaction_type}</span></td>
            <td class="amount ${t.transaction_type === 'deposit' || t.transaction_type === 'transfer' && t.reference_number ? 'positive' : 'negative'}">
                ${t.transaction_type === 'deposit' ? '+' : '-'}$${formatNumber(t.amount)}
            </td>
            <td class="amount">$${formatNumber(t.balance_after)}</td>
            <td>${t.performed_by_name || '-'}</td>
        </tr>
    `).join('');
}

async function lookupAccount(e) {
    const accountNumber = e.target.value.trim();
    const infoDiv = document.getElementById('depositAccountInfo');
    
    if (!accountNumber) {
        infoDiv.innerHTML = '';
        return;
    }
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ account_number: accountNumber, action: 'getAccountByNumber' })
        });
        const result = await response.json();
        
        if (result.success) {
            const a = result.data;
            infoDiv.innerHTML = `
                <div class="name">${a.first_name} ${a.last_name}</div>
                <div class="balance">Current Balance: $${formatNumber(a.balance)}</div>
            `;
            infoDiv.dataset.accountId = a.id;
        } else {
            infoDiv.innerHTML = '<div style="color: red;">Account not found</div>';
            delete infoDiv.dataset.accountId;
        }
    } catch (error) {
        infoDiv.innerHTML = '<div style="color: red;">Error looking up account</div>';
    }
}

async function lookupWithdrawAccount(e) {
    const accountNumber = e.target.value.trim();
    const infoDiv = document.getElementById('withdrawAccountInfo');
    
    if (!accountNumber) {
        infoDiv.innerHTML = '';
        return;
    }
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ account_number: accountNumber, action: 'getAccountByNumber' })
        });
        const result = await response.json();
        
        if (result.success) {
            const a = result.data;
            infoDiv.innerHTML = `
                <div class="name">${a.first_name} ${a.last_name}</div>
                <div class="balance">Available Balance: $${formatNumber(parseFloat(a.balance) + parseFloat(a.overdraft_limit))}</div>
            `;
            infoDiv.dataset.accountId = a.id;
        } else {
            infoDiv.innerHTML = '<div style="color: red;">Account not found</div>';
            delete infoDiv.dataset.accountId;
        }
    } catch (error) {
        infoDiv.innerHTML = '<div style="color: red;">Error looking up account</div>';
    }
}

async function lookupFromAccount(e) {
    const accountNumber = e.target.value.trim();
    const infoDiv = document.getElementById('fromAccountInfo');
    
    if (!accountNumber) {
        infoDiv.innerHTML = '';
        return;
    }
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ account_number: accountNumber, action: 'getAccountByNumber' })
        });
        const result = await response.json();
        
        if (result.success) {
            const a = result.data;
            infoDiv.innerHTML = `
                <div class="name">${a.first_name} ${a.last_name}</div>
                <div class="balance">Available: $${formatNumber(parseFloat(a.balance) + parseFloat(a.overdraft_limit))}</div>
            `;
            infoDiv.dataset.accountId = a.id;
        } else {
            infoDiv.innerHTML = '<div style="color: red;">Account not found</div>';
            delete infoDiv.dataset.accountId;
        }
    } catch (error) {
        infoDiv.innerHTML = '<div style="color: red;">Error looking up account</div>';
    }
}

async function lookupToAccount(e) {
    const accountNumber = e.target.value.trim();
    const infoDiv = document.getElementById('toAccountInfo');
    
    if (!accountNumber) {
        infoDiv.innerHTML = '';
        return;
    }
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ account_number: accountNumber, action: 'getAccountByNumber' })
        });
        const result = await response.json();
        
        if (result.success) {
            const a = result.data;
            infoDiv.innerHTML = `
                <div class="name">${a.first_name} ${a.last_name}</div>
            `;
            infoDiv.dataset.accountId = a.id;
        } else {
            infoDiv.innerHTML = '<div style="color: red;">Account not found</div>';
            delete infoDiv.dataset.accountId;
        }
    } catch (error) {
        infoDiv.innerHTML = '<div style="color: red;">Error looking up account</div>';
    }
}

async function handleDeposit(e) {
    e.preventDefault();
    
    const accountId = document.getElementById('depositAccountInfo').dataset.accountId;
    if (!accountId) {
        showToast('Please enter a valid account number', 'error');
        return;
    }
    
    const data = {
        action: 'deposit',
        account_id: accountId,
        amount: document.getElementById('depositAmount').value,
        description: document.getElementById('depositDescription').value
    };
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(`Deposit successful! Transaction ID: ${result.data.transaction_id}`, 'success');
            closeModals();
            document.getElementById('depositForm').reset();
            document.getElementById('depositAccountInfo').innerHTML = '';
            loadTransactions();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('Deposit failed', 'error');
    }
}

async function handleWithdraw(e) {
    e.preventDefault();
    
    const accountId = document.getElementById('withdrawAccountInfo').dataset.accountId;
    if (!accountId) {
        showToast('Please enter a valid account number', 'error');
        return;
    }
    
    const data = {
        action: 'withdraw',
        account_id: accountId,
        amount: document.getElementById('withdrawAmount').value,
        description: document.getElementById('withdrawDescription').value
    };
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(`Withdrawal successful! Transaction ID: ${result.data.transaction_id}`, 'success');
            closeModals();
            document.getElementById('withdrawForm').reset();
            document.getElementById('withdrawAccountInfo').innerHTML = '';
            loadTransactions();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('Withdrawal failed', 'error');
    }
}

async function handleTransfer(e) {
    e.preventDefault();
    
    const fromAccountId = document.getElementById('fromAccountInfo').dataset.accountId;
    const toAccountId = document.getElementById('toAccountInfo').dataset.accountId;
    
    if (!fromAccountId || !toAccountId) {
        showToast('Please enter valid account numbers', 'error');
        return;
    }
    
    if (fromAccountId === toAccountId) {
        showToast('Cannot transfer to the same account', 'error');
        return;
    }
    
    const data = {
        action: 'transfer',
        from_account_id: fromAccountId,
        to_account_id: toAccountId,
        amount: document.getElementById('transferAmount').value,
        description: document.getElementById('transferDescription').value
    };
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(`Transfer successful! Transfer ID: ${result.data.transfer_id}`, 'success');
            closeModals();
            document.getElementById('transferForm').reset();
            document.getElementById('fromAccountInfo').innerHTML = '';
            document.getElementById('toAccountInfo').innerHTML = '';
            loadTransactions();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('Transfer failed', 'error');
    }
}

// ==================== LOANS ====================

async function loadLoans() {
    try {
        const response = await fetch(API_URL + '?action=getLoans');
        const result = await response.json();
        
        if (result.success) {
            loans = result.data;
            renderLoansTable();
        }
    } catch (error) {
        showToast('Failed to load loans', 'error');
    }
}

function renderLoansTable(filteredLoans = loans) {
    const tbody = document.querySelector('#loansTable tbody');
    tbody.innerHTML = filteredLoans.map(l => `
        <tr>
            <td>${l.loan_id}</td>
            <td>${l.first_name} ${l.last_name}</td>
            <td>${l.account_number}</td>
            <td class="amount">$${formatNumber(l.principal_amount)}</td>
            <td>${l.interest_rate}%</td>
            <td class="amount">$${formatNumber(l.monthly_payment)}</td>
            <td class="amount">$${formatNumber(l.outstanding_balance)}</td>
            <td><span class="status-badge ${l.status}">${l.status}</span></td>
            <td class="actions">
                ${l.status === 'pending' ? `<button class="view-btn" onclick="approveLoan(${l.id})"><i class="fas fa-check"></i></button>` : ''}
                ${l.status === 'active' ? `<button class="view-btn" onclick="makeLoanPayment(${l.id})"><i class="fas fa-dollar-sign"></i></button>` : ''}
            </td>
        </tr>
    `).join('');
}

async function openLoanModal() {
    const modal = document.getElementById('loanModal');
    
    await loadCustomersForLoanSelect();
    
    document.getElementById('loanForm').reset();
    document.getElementById('loanAccount').innerHTML = '<option value="">Select Account</option>';
    document.getElementById('monthlyPayment').textContent = '$0.00';
    document.getElementById('totalAmountDue').textContent = '$0.00';
    
    modal.classList.add('active');
}

async function loadCustomersForLoanSelect() {
    try {
        const response = await fetch(API_URL + '?action=getCustomers');
        const result = await response.json();
        
        if (result.success) {
            const select = document.getElementById('loanCustomer');
            select.innerHTML = '<option value="">Select Customer</option>' +
                result.data.filter(c => c.status === 'active').map(c => 
                    `<option value="${c.id}">${c.customer_id} - ${c.first_name} ${c.last_name}</option>`
                ).join('');
        }
    } catch (error) {
        console.error('Failed to load customers:', error);
    }
}

async function loadCustomerAccounts() {
    const customerId = document.getElementById('loanCustomer').value;
    const select = document.getElementById('loanAccount');
    
    if (!customerId) {
        select.innerHTML = '<option value="">Select Account</option>';
        return;
    }
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ customer_id: customerId, action: 'getCustomerAccounts' })
        });
        const result = await response.json();
        
        if (result.success) {
            select.innerHTML = '<option value="">Select Account</option>' +
                result.data.filter(a => a.status === 'active').map(a => 
                    `<option value="${a.id}">${a.account_number} - ${a.type_name} ($${formatNumber(a.balance)})</option>`
                ).join('');
        }
    } catch (error) {
        console.error('Failed to load accounts:', error);
    }
}

function calculateLoanPayment() {
    const principal = parseFloat(document.getElementById('principalAmount').value) || 0;
    const rate = parseFloat(document.getElementById('loanInterestRate').value) || 0;
    const months = parseInt(document.getElementById('loanTermMonths').value) || 0;
    
    if (principal > 0 && rate > 0 && months > 0) {
        const totalInterest = principal * (rate / 100) * (months / 12);
        const totalDue = principal + totalInterest;
        const monthly = totalDue / months;
        
        document.getElementById('monthlyPayment').textContent = '$' + formatNumber(monthly);
        document.getElementById('totalAmountDue').textContent = '$' + formatNumber(totalDue);
    } else {
        document.getElementById('monthlyPayment').textContent = '$0.00';
        document.getElementById('totalAmountDue').textContent = '$0.00';
    }
}

async function handleLoanSubmit(e) {
    e.preventDefault();
    
    const data = {
        action: 'createLoan',
        customer_id: document.getElementById('loanCustomer').value,
        account_id: document.getElementById('loanAccount').value,
        principal_amount: document.getElementById('principalAmount').value,
        interest_rate: document.getElementById('loanInterestRate').value,
        loan_term_months: document.getElementById('loanTermMonths').value
    };
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Loan application submitted successfully!', 'success');
            closeModals();
            loadLoans();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('Failed to submit loan application', 'error');
    }
}

async function approveLoan(id) {
    if (!confirm('Are you sure you want to approve this loan?')) return;
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ loan_id: id, action: 'approveLoan' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Loan approved successfully!', 'success');
            loadLoans();
            loadDashboard();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('Failed to approve loan', 'error');
    }
}

async function makeLoanPayment(id) {
    const amount = prompt('Enter payment amount:');
    if (!amount || isNaN(amount) || parseFloat(amount) <= 0) return;
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ loan_id: id, amount: amount, action: 'makeLoanPayment' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Loan payment processed successfully!', 'success');
            loadLoans();
            loadDashboard();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('Failed to process payment', 'error');
    }
}

// ==================== USERS ====================

async function loadUsers() {
    try {
        const response = await fetch(API_URL + '?action=getUsers');
        const result = await response.json();
        
        if (result.success) {
            users = result.data;
            renderUsersTable();
        }
    } catch (error) {
        showToast('Failed to load users', 'error');
    }
}

function renderUsersTable(filteredUsers = users) {
    const tbody = document.querySelector('#usersTable tbody');
    tbody.innerHTML = filteredUsers.map(u => `
        <tr>
            <td>${u.username}</td>
            <td>${u.full_name}</td>
            <td>${u.email}</td>
            <td><span class="status-badge ${u.role}">${u.role}</span></td>
            <td><span class="status-badge ${u.status}">${u.status}</span></td>
            <td>${formatDate(u.created_at)}</td>
            <td class="actions">
                <button class="edit-btn" onclick="editUser(${u.id})"><i class="fas fa-edit"></i></button>
            </td>
        </tr>
    `).join('');
}

function openUserModal(user = null) {
    const modal = document.getElementById('userModal');
    const form = document.getElementById('userForm');
    const title = document.getElementById('userModalTitle');
    
    form.reset();
    document.getElementById('userId').value = '';
    document.getElementById('userPassword').required = false;
    
    if (user) {
        title.textContent = 'Edit User';
        document.getElementById('userId').value = user.id;
        document.getElementById('userUsername').value = user.username;
        document.getElementById('userUsername').disabled = true;
        document.getElementById('userFullName').value = user.full_name;
        document.getElementById('userEmail').value = user.email;
        document.getElementById('userRole').value = user.role;
        document.getElementById('userStatus').value = user.status;
    } else {
        title.textContent = 'Add User';
        document.getElementById('userUsername').disabled = false;
        document.getElementById('userPassword').required = true;
    }
    
    modal.classList.add('active');
}

function editUser(id) {
    const user = users.find(u => u.id === id);
    if (user) {
        openUserModal(user);
    }
}

async function handleUserSubmit(e) {
    e.preventDefault();
    
    const id = document.getElementById('userId').value;
    const data = {
        action: id ? 'updateUser' : 'createUser',
        username: document.getElementById('userUsername').value,
        full_name: document.getElementById('userFullName').value,
        email: document.getElementById('userEmail').value,
        role: document.getElementById('userRole').value,
        status: document.getElementById('userStatus').value
    };
    
    if (id) {
        data.id = id;
    }
    
    const password = document.getElementById('userPassword').value;
    if (password) {
        data.password = password;
    }
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(id ? 'User updated successfully' : 'User created successfully', 'success');
            closeModals();
            loadUsers();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('Operation failed', 'error');
    }
}

// ==================== REPORTS ====================

async function generateReport() {
    const year = document.getElementById('reportYear').value;
    const month = document.getElementById('reportMonth').value;
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ year, month, action: 'getMonthlyReport' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Type report
            const typeBody = document.querySelector('#reportTypeTable tbody');
            typeBody.innerHTML = result.data.type_report.map(r => `
                <tr>
                    <td><span class="status-badge ${r.transaction_type}">${r.transaction_type}</span></td>
                    <td>${r.count}</td>
                    <td class="amount">$${formatNumber(r.total_amount)}</td>
                </tr>
            `).join('') || '<tr><td colspan="3" style="text-align: center; color: gray;">No data</td></tr>';
            
            // Daily report
            const dailyBody = document.querySelector('#reportDailyTable tbody');
            dailyBody.innerHTML = result.data.daily_report.map(r => `
                <tr>
                    <td>${formatDate(r.date)}</td>
                    <td><span class="status-badge ${r.transaction_type}">${r.transaction_type}</span></td>
                    <td>${r.count}</td>
                    <td class="amount">$${formatNumber(r.total_amount)}</td>
                </tr>
            `).join('') || '<tr><td colspan="4" style="text-align: center; color: gray;">No data</td></tr>';
            
            showToast('Report generated successfully', 'success');
        }
    } catch (error) {
        showToast('Failed to generate report', 'error');
    }
}

// ==================== UTILITY FUNCTIONS ====================

function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
}

function closeModals() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.classList.remove('active');
    });
}

function formatNumber(num) {
    return parseFloat(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = 'toast ' + type;
    toast.style.display = 'block';
    
    setTimeout(() => {
        toast.style.display = 'none';
    }, 4000);
}

// Helper to make API requests (for global use)
async function api(action, data = {}) {
    const response = await fetch(API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ...data, action })
    });
    return response.json();
}
