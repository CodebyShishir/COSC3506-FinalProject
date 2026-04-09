// Global state
let currentUser = null;
let currentGroupId = null;

// Helper to make API calls expecting JSON
async function apiCall(endpoint, method = 'GET', body = null) {
    const config = { method, headers: {} };
    if (body) {
        config.headers['Content-Type'] = 'application/json';
        config.body = JSON.stringify(body);
    }
    const res = await fetch(`../backend/api/${endpoint}`, config);
    const data = await res.json();
    if (!res.ok) {
        throw new Error(data.error || 'Server error occurred');
    }
    return data;
}

// ------ AUTH ------
const authFormLogin = document.getElementById('loginForm');
const authFormReg = document.getElementById('registerForm');
const authFormForgot = document.getElementById('forgotPasswordForm');
const authError = document.getElementById('authError');

if (authFormLogin) {
    authFormLogin.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            await apiCall('auth.php?action=login', 'POST', {
                email: document.getElementById('loginEmail').value,
                password: document.getElementById('loginPassword').value
            });
            window.location.href = 'dashboard.html';
        } catch (err) {
            authError.className = 'badge badge-owed'; // Reset styles
            authError.textContent = err.message;
            authError.classList.remove('hidden');
        }
    });

    authFormReg.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            await apiCall('auth.php?action=register', 'POST', {
                name: document.getElementById('regName').value,
                email: document.getElementById('regEmail').value,
                password: document.getElementById('regPassword').value
            });
            window.location.href = 'dashboard.html';
        } catch (err) {
            authError.className = 'badge badge-owed';
            authError.textContent = err.message;
            authError.classList.remove('hidden');
        }
    });

    if (authFormForgot) {
        authFormForgot.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                const res = await apiCall('auth.php?action=forgot_password', 'POST', {
                    email: document.getElementById('forgotEmail').value,
                    new_password: document.getElementById('forgotNewPassword').value
                });
                authError.className = 'badge badge-success'; // Success style
                authError.textContent = res.message || 'Password reset successfully. You can now login.';
                authError.classList.remove('hidden');
                document.getElementById('forgotPasswordForm').reset();
            } catch (err) {
                authError.className = 'badge badge-owed';
                authError.textContent = err.message;
                authError.classList.remove('hidden');
            }
        });
    }
}

async function fetchUser() {
    try {
        const res = await apiCall('auth.php?action=me');
        if (!res.user) window.location.href = 'index.html'; // Redirect to login
        currentUser = res.user;
        const nameDisp = document.getElementById('userNameDisplay');
        if(nameDisp) nameDisp.textContent = 'Hello, ' + currentUser.name;
    } catch {
        window.location.href = 'index.html';
    }
}

async function logout() {
    await apiCall('auth.php?action=logout');
    window.location.href = 'index.html';
}

// ------ UI STATES ------
function showGroupsView() {
    document.getElementById('groupsView').classList.remove('hidden');
    document.getElementById('groupDetailView').classList.add('hidden');
    currentGroupId = null;
    loadGroups();
}

function showGroupDetailView(id, name) {
    document.getElementById('groupsView').classList.add('hidden');
    document.getElementById('groupDetailView').classList.remove('hidden');
    currentGroupId = id;
    document.getElementById('groupTitle').textContent = name;
    loadGroupView();
}

// ------ MODALS ------
function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

// ------ GROUPS ------
async function loadGroups() {
    try {
        const data = await apiCall('groups.php?action=list');
        const container = document.getElementById('groupsList');
        if (!data.groups.length) {
            container.innerHTML = '<p class="text-muted">You have no groups yet.</p>';
            return;
        }
        container.innerHTML = data.groups.map(g => `
            <div class="glass-panel list-item" onclick="showGroupDetailView(${g.id}, '${g.name}')">
                <span style="font-size: 1.1rem; font-weight: 600;">${g.name}</span>
                <span class="text-muted" style="font-size: 0.85rem;">Created: ${new Date(g.created_at).toLocaleDateString()}</span>
            </div>
        `).join('');
    } catch (e) {
        console.error(e);
    }
}

async function createGroup() {
    const name = document.getElementById('newGroupName').value;
    const emailsRaw = document.getElementById('newGroupEmails').value;
    const emails = emailsRaw.split(',').map(e => e.trim()).filter(e => e);
    try {
        await apiCall('groups.php?action=create', 'POST', { name, emails });
        closeModal('groupModal');
        loadGroups();
    } catch(e) { alert(e.message); }
}

// ------ GROUP DETAILS & EXPENSES & BALANCES ------
async function loadGroupView() {
    // Load Members
    const mm = await apiCall(`groups.php?action=details&group_id=${currentGroupId}`);
    document.getElementById('groupMembers').textContent = 'Members: ' + mm.members.map(m => m.name).join(', ');

    // Load Expenses
    const exData = await apiCall(`expenses.php?action=list&group_id=${currentGroupId}`);
    const exList = document.getElementById('expensesList');
    if(!exData.expenses.length) {
        exList.innerHTML = '<p class="text-muted">No expenses recorded yet.</p>';
    } else {
        exList.innerHTML = exData.expenses.map(e => {
            let receiptBadge = e.receipt_url ? `<a href="../backend/${e.receipt_url}" target="_blank" class="badge badge-success" style="font-size:0.75rem; padding: 0.2rem 0.5rem; display:inline-block; margin-top:0.4rem; text-decoration:none;">&#129534; View Receipt</a>` : '';
            return `
            <div class="list-item" style="padding: 1rem; border-color:var(--glass-border)">
                <div>
                    <strong style="display:block;">${e.description}</strong>
                    <small class="text-muted">Paid by ${e.payer_name} on ${new Date(e.created_at).toLocaleDateString()}</small>
                    <br>${receiptBadge}
                </div>
                <div style="font-size: 1.2rem; font-weight: 800; color: var(--text-main);">$${parseFloat(e.amount).toFixed(2)}</div>
            </div>
            `;
        }).join('');
    }

    // Load Balances
    const balData = await apiCall(`balances.php?action=group&group_id=${currentGroupId}`);
    const balList = document.getElementById('balancesList');
    
    if(!balData.balances.length) {
        balList.innerHTML = '<p class="text-success" style="font-weight: 600;">All settled up! 🎉</p>';
    } else {
        balList.innerHTML = balData.balances.map(b => {
            const amount = parseFloat(b.total_owed).toFixed(2);
            // If the logged in user is involved, make it obvious
            let debtString = `${b.debtor_name} owes ${b.creditor_name} <strong>$${amount}</strong>`;
            
            // Show Mark Paid button if the current user is the creditor OR debtor
            let settleBtn = '';
            if(currentUser.id == b.creditor_id || currentUser.id == b.debtor_id) {
                settleBtn = `<button class="btn btn-success" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;" onclick="settleDebt(${b.debtor_id}, ${b.creditor_id})">Settle Up</button>`;
            }
            
            return `<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1rem;">
                <span>${debtString}</span>
                ${settleBtn}
            </div>`;
        }).join('');
    }
}

async function addExpense() {
    const desc = document.getElementById('expDesc').value;
    const amount = document.getElementById('expAmount').value;
    const receiptFile = document.getElementById('expReceipt').files[0];
    
    try {
        const formData = new FormData();
        formData.append('group_id', currentGroupId);
        formData.append('description', desc);
        formData.append('amount', amount);
        if (receiptFile) formData.append('receipt', receiptFile);

        const res = await fetch(`../backend/api/expenses.php?action=add`, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if(!res.ok) throw new Error(data.error || 'Failed to log expense');

        closeModal('expenseModal');
        document.getElementById('expDesc').value = '';
        document.getElementById('expAmount').value = '';
        document.getElementById('expReceipt').value = '';
        loadGroupView(); // Reload the UI
    } catch(e) { alert(e.message); }
}

async function settleDebt(debtorId, creditorId) {
    if(!confirm('Mark this debt as fully settled?')) return;
    try {
        await apiCall('balances.php?action=settle', 'POST', {
            group_id: currentGroupId,
            debtor_id: debtorId,
            creditor_id: creditorId
        });
        loadGroupView(); // refresh balances
    } catch(e) { alert(e.message); }
}
