/**
 * DSC Manager - Core Application Logic
 * Integrates metrics calculations, table search/filter queries, quick-reminders, and modal transitions.
 */

// App State
let state = {
    users: [],
    stats: {},
    filters: {
        search: '',
        status: 'all',
        role: 'all'
    },
    editingUserId: null
};

// UI Elements
const els = {
    themeToggle: document.getElementById('theme-toggle'),
    metricsContainer: document.getElementById('metrics-container'),
    searchInput: document.getElementById('search-input'),
    statusFilter: document.getElementById('status-filter'),
    roleFilter: document.getElementById('role-filter'),
    tableBody: document.getElementById('table-body'),
    showingEntriesText: document.getElementById('showing-entries-text'),
    
    // Modal
    editModal: document.getElementById('edit-modal'),
    closeModalBtn: document.getElementById('close-modal-btn'),
    cancelModalBtn: document.getElementById('cancel-modal-btn'),
    editForm: document.getElementById('edit-dsc-form'),
    editUserId: document.getElementById('edit-user-id'),
    editHolderName: document.getElementById('edit-holder-name'),
    editExpiryDate: document.getElementById('edit-expiry-date'),
    editClass: document.getElementById('edit-class'),
    editTokenSerial: document.getElementById('edit-token-serial'),
    modalClientName: document.getElementById('modal-client-name'),
    modalClientEmail: document.getElementById('modal-client-email'),
    modalClientAvatar: document.getElementById('modal-client-avatar'),
    
    // Toast
    toastContainer: document.getElementById('toast-container')
};

// Init Application
document.addEventListener('DOMContentLoaded', () => {
    setupTheme();
    setupEventListeners();
    loadDashboardData();
});

/* ==========================================================================
   Theme Management (Light / Dark Mode)
   ========================================================================== */
function setupTheme() {
    const savedTheme = localStorage.getItem('dsc-theme') || 'dark';
    if (savedTheme === 'light') {
        document.body.classList.remove('dark-theme');
        document.body.classList.add('light-theme');
        els.themeToggle.innerHTML = '<i class="fa-solid fa-moon"></i>';
    } else {
        document.body.classList.remove('light-theme');
        document.body.classList.add('dark-theme');
        els.themeToggle.innerHTML = '<i class="fa-solid fa-sun"></i>';
    }

    els.themeToggle.addEventListener('click', () => {
        if (document.body.classList.contains('dark-theme')) {
            document.body.classList.remove('dark-theme');
            document.body.classList.add('light-theme');
            els.themeToggle.innerHTML = '<i class="fa-solid fa-moon"></i>';
            localStorage.setItem('dsc-theme', 'light');
            showToast('Switched to Light Theme', 'info');
        } else {
            document.body.classList.remove('light-theme');
            document.body.classList.add('dark-theme');
            els.themeToggle.innerHTML = '<i class="fa-solid fa-sun"></i>';
            localStorage.setItem('dsc-theme', 'dark');
            showToast('Switched to Dark Theme', 'info');
        }
    });
}

/* ==========================================================================
   Data Load & Render
   ========================================================================== */
async function loadDashboardData() {
    try {
        setTableLoading(true);
        const data = await API.getDSCList();
        
        state.users = data.data.users;
        state.stats = data.data.stats;
        
        renderStats();
        applyFiltersAndRenderTable();
    } catch (error) {
        showToast(error.message || 'Error loading dashboard data', 'error');
        setTableError(error.message);
    }
}

function renderStats() {
    const containers = els.metricsContainer.children;
    
    // Total Clients
    containers[0].classList.remove('loading');
    containers[0].querySelector('.metric-value').textContent = state.stats.total_users || 0;
    
    // Active DSC
    containers[1].classList.remove('loading');
    containers[1].querySelector('.metric-value').textContent = state.stats.active_dsc || 0;
    
    // Expiring Soon
    containers[2].classList.remove('loading');
    containers[2].querySelector('.metric-value').textContent = state.stats.expiring_soon || 0;
    
    // Expired DSC
    containers[3].classList.remove('loading');
    containers[3].querySelector('.metric-value').textContent = state.stats.expired_dsc || 0;
}

function applyFiltersAndRenderTable() {
    const { search, status, role } = state.filters;
    const today = new Date().toISOString().split('T')[0];
    
    const filtered = state.users.filter(user => {
        // 1. Search Filter
        const searchLower = search.toLowerCase();
        const matchesSearch = !search || 
            (user.name && user.name.toLowerCase().includes(searchLower)) ||
            (user.email && user.email.toLowerCase().includes(searchLower)) ||
            (user.phone && user.phone.includes(searchLower)) ||
            (user.pan_number && user.pan_number.toLowerCase().includes(searchLower)) ||
            (user.dsc_holder_name && user.dsc_holder_name.toLowerCase().includes(searchLower)) ||
            (user.dsc_token_serial && user.dsc_token_serial.toLowerCase().includes(searchLower));
            
        // 2. Status Filter
        let matchesStatus = true;
        if (status !== 'all') {
            matchesStatus = (user.dsc_status === status);
        }
        
        // 3. Role Filter
        let matchesRole = true;
        if (role !== 'all') {
            matchesRole = (user.role === role);
        }
        
        return matchesSearch && matchesStatus && matchesRole;
    });
    
    renderTable(filtered);
}

function renderTable(data) {
    els.tableBody.innerHTML = '';
    
    if (data.length === 0) {
        els.tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                    <i class="fa-solid fa-folder-open mb-2" style="font-size: 2rem; display: block; opacity: 0.5;"></i>
                    No clients found matching the selected filters.
                </td>
            </tr>
        `;
        els.showingEntriesText.textContent = `Showing 0 of ${state.users.length} entries`;
        return;
    }
    
    data.forEach(user => {
        const tr = document.createElement('tr');
        
        // Formatted Expiry Date
        let expiryDisplay = '<span class="text-muted">—</span>';
        if (user.dsc_expiry_date) {
            const date = new Date(user.dsc_expiry_date);
            expiryDisplay = date.toLocaleDateString('en-IN', {
                day: '2-digit', month: 'short', year: 'numeric'
            });
        }
        
        // Expiry Status Badge
        let statusBadge = '<span class="badge badge-status none">No DSC</span>';
        if (user.dsc_status === 'active') {
            statusBadge = '<span class="badge badge-status active"><i class="fa-solid fa-circle-check mr-1" style="margin-right:4px;"></i> Active</span>';
        } else if (user.dsc_status === 'expiring_soon') {
            statusBadge = '<span class="badge badge-status expiring_soon"><i class="fa-solid fa-clock-rotate-left mr-1" style="margin-right:4px;"></i> Expiring Soon</span>';
        } else if (user.dsc_status === 'expired') {
            statusBadge = '<span class="badge badge-status expired"><i class="fa-solid fa-triangle-exclamation mr-1" style="margin-right:4px;"></i> Expired</span>';
        } else if (user.dsc_status === 'incomplete') {
            statusBadge = '<span class="badge badge-status incomplete"><i class="fa-solid fa-circle-info mr-1" style="margin-right:4px;"></i> Details Missing</span>';
        }
        
        tr.innerHTML = `
            <td>
                <div class="client-name-cell">${escapeHTML(user.name)}</div>
                <div class="client-sub">${escapeHTML(user.email || 'No email')} | ${escapeHTML(user.phone || 'No phone')}</div>
            </td>
            <td>
                <span class="badge badge-role ${user.role}">${escapeHTML(user.role)}</span>
            </td>
            <td class="client-name-cell">${escapeHTML(user.dsc_holder_name || '—')}</td>
            <td>${expiryDisplay}</td>
            <td><span class="text-muted">${escapeHTML(user.dsc_class || '—')}</span></td>
            <td style="font-family: monospace; font-size: 0.8rem;">${escapeHTML(user.dsc_token_serial || '—')}</td>
            <td>${statusBadge}</td>
            <td>
                <div class="actions-cell">
                    <button class="btn-action btn-edit" title="Edit DSC Details" onclick="openEditModal(${user.id})">
                        <i class="fa-solid fa-pencil"></i>
                    </button>
                    ${user.has_dsc ? `
                        <button class="btn-action btn-remind-wa" title="WhatsApp Reminder" onclick="sendReminder(${user.id}, 'whatsapp')">
                            <i class="fa-brands fa-whatsapp"></i>
                        </button>
                        <button class="btn-action btn-remind-email" title="Email Reminder" onclick="sendReminder(${user.id}, 'email')">
                            <i class="fa-solid fa-envelope"></i>
                        </button>
                    ` : ''}
                </div>
            </td>
        `;
        
        els.tableBody.appendChild(tr);
    });
    
    els.showingEntriesText.textContent = `Showing ${data.length} of ${state.users.length} entries`;
}

function setTableLoading(isLoading) {
    if (isLoading) {
        els.tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5">
                    <div class="spinner"></div>
                    <p class="mt-2 text-muted">Loading DSC records...</p>
                </td>
            </tr>
        `;
    }
}

function setTableError(message) {
    els.tableBody.innerHTML = `
        <tr>
            <td colspan="8" class="text-center py-5 text-danger">
                <i class="fa-solid fa-triangle-exclamation mb-2" style="font-size: 2rem; display: block;"></i>
                <p><strong>Failed to load data:</strong></p>
                <p class="text-muted" style="max-width: 400px; margin: 8px auto 0;">${escapeHTML(message)}</p>
                <button class="btn btn-secondary btn-sm mt-3" onclick="loadDashboardData()"><i class="fa-solid fa-rotate"></i> Retry Connection</button>
            </td>
        </tr>
    `;
}

/* ==========================================================================
   Search & Filter Event Listeners
   ========================================================================== */
function setupEventListeners() {
    // Instant search input on keyup
    els.searchInput.addEventListener('input', (e) => {
        state.filters.search = e.target.value;
        applyFiltersAndRenderTable();
    });
    
    // Dropdown filters change triggers re-render
    els.statusFilter.addEventListener('change', (e) => {
        state.filters.status = e.target.value;
        applyFiltersAndRenderTable();
    });
    
    els.roleFilter.addEventListener('change', (e) => {
        state.filters.role = e.target.value;
        applyFiltersAndRenderTable();
    });
    
    // Modal controls
    els.closeModalBtn.addEventListener('click', closeEditModal);
    els.cancelModalBtn.addEventListener('click', closeEditModal);
    
    // Close modal on outside click
    window.addEventListener('click', (e) => {
        if (e.target === els.editModal) {
            closeEditModal();
        }
    });
    
    // Form submit for DSC edit
    els.editForm.addEventListener('submit', handleFormSubmit);
}

/* ==========================================================================
   Modal Operations
   ========================================================================== */
window.openEditModal = function(userId) {
    const user = state.users.find(u => u.id === userId);
    if (!user) return;
    
    state.editingUserId = userId;
    els.editUserId.value = user.id;
    els.editHolderName.value = user.dsc_holder_name || '';
    els.editExpiryDate.value = user.dsc_expiry_date || '';
    els.editClass.value = user.dsc_class || '';
    els.editTokenSerial.value = user.dsc_token_serial || '';
    
    // Populate client summary details
    els.modalClientName.textContent = user.name;
    els.modalClientEmail.textContent = `${user.email || 'No email'} | ${user.phone || 'No phone'}`;
    els.modalClientAvatar.textContent = user.name.charAt(0).toUpperCase();
    
    els.editModal.classList.add('active');
};

function closeEditModal() {
    els.editModal.classList.remove('active');
    els.editForm.reset();
    state.editingUserId = null;
}

async function handleFormSubmit(e) {
    e.preventDefault();
    
    const id = parseInt(els.editUserId.value);
    const data = {
        dsc_holder_name: els.editHolderName.value,
        dsc_expiry_date: els.editExpiryDate.value,
        dsc_class: els.editClass.value,
        dsc_token_serial: els.editTokenSerial.value
    };
    
    try {
        const response = await API.updateDSC(id, data);
        if (response.success) {
            showToast('DSC details updated successfully', 'success');
            closeEditModal();
            // Reload all data so that counters reflect new state
            loadDashboardData();
        }
    } catch (error) {
        showToast(error.message || 'Error updating DSC details', 'error');
    }
}

/* ==========================================================================
   Reminders & Alerts Actions
   ========================================================================== */
window.sendReminder = function(userId, method) {
    const user = state.users.find(u => u.id === userId);
    if (!user) return;
    
    if (!user.dsc_expiry_date) {
        showToast("Expiry date is required to send reminders.", "error");
        return;
    }
    
    const expiryDateFormatted = new Date(user.dsc_expiry_date).toLocaleDateString('en-IN', {
        day: '2-digit', month: 'long', year: 'numeric'
    });
    
    const holder = user.dsc_holder_name || user.name;
    
    if (method === 'whatsapp') {
        if (!user.phone) {
            showToast("No phone number registered for this user.", "error");
            return;
        }
        
        // Clean phone number (strip whitespace and format for India code +91 if needed)
        let phone = user.phone.replace(/[^0-9]/g, '');
        if (phone.length === 10) {
            phone = '91' + phone;
        }
        
        const message = `Hello ${user.name},\n\nThis is a friendly reminder from *Akanksha Shashank & Associates* regarding your Digital Signature Certificate (DSC) of holder *${holder}*. It is scheduled to expire on *${expiryDateFormatted}*.\n\nPlease reply or get in touch with us at your earliest convenience so we can initiate the renewal process and prevent any downtime or delays in your statutory filing obligations.\n\nThank you!`;
        const waUrl = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
        
        window.open(waUrl, '_blank');
        showToast(`Opened WhatsApp Reminder to ${user.name}`, 'success');
        
    } else if (method === 'email') {
        if (!user.email) {
            showToast("No email address registered for this user.", "error");
            return;
        }
        
        const subject = `IMPORTANT: Digital Signature Certificate (DSC) Expiry Reminder - ${holder}`;
        const body = `Dear ${user.name},\n\nThis is an automated reminder regarding the Digital Signature Certificate (DSC) registered under holder "${holder}". Our records show that this certificate is scheduled to expire on ${expiryDateFormatted}.\n\nTo ensure there are no interruptions in your tax e-filings, MCA compliance, or other regulatory submissions, we kindly request you to authorize the renewal process. Please confirm if we should proceed with the DSC renewal.\n\nWarm regards,\nAkanksha Shashank & Associates`;
        
        const mailUrl = `mailto:${user.email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
        
        window.open(mailUrl);
        showToast(`Drafted Email Reminder to ${user.email}`, 'success');
    }
};

/* ==========================================================================
   Utilities (Toasts & Escaping)
   ========================================================================== */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let iconClass = 'fa-circle-info';
    if (type === 'success') iconClass = 'fa-circle-check';
    if (type === 'error') iconClass = 'fa-triangle-exclamation';
    
    toast.innerHTML = `
        <i class="fa-solid ${iconClass}"></i>
        <span>${escapeHTML(message)}</span>
    `;
    
    els.toastContainer.appendChild(toast);
    
    // Trigger transition Reflow
    toast.offsetHeight; 
    toast.classList.add('show');
    
    // Auto-remove after 4 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 250);
    }, 4000);
}

function escapeHTML(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
