/**
 * DSC Registry Ledger - Standalone Frontend Application
 * Manages Excel grid rendering, dynamic filters, instant hardware auto-registration,
 * CSV export, and quick-alerts (WhatsApp/Email).
 */

// Global Application State
let state = {
    records: [],
    stats: {},
    filters: {
        search: '',
        status: 'all',
        possession: 'all'
    },
    editingId: null
};

// UI Element Selector Map
const els = {
    themeToggle: document.getElementById('theme-toggle'),
    metricsContainer: document.getElementById('metrics-container'),
    
    // Quick Actions
    btnDetectRegister: document.getElementById('btn-detect-register'),
    btnExportCSV: document.getElementById('btn-export-csv'),
    
    // Filters & Search
    searchInput: document.getElementById('search-input'),
    statusFilter: document.getElementById('status-filter'),
    possessionFilter: document.getElementById('possession-filter'),
    
    // Grid Table
    tableBody: document.getElementById('table-body'),
    showingEntriesText: document.getElementById('showing-entries-text'),
    
    // Modal Edit Form
    editModal: document.getElementById('edit-modal'),
    closeModalBtn: document.getElementById('close-modal-btn'),
    cancelModalBtn: document.getElementById('cancel-modal-btn'),
    editForm: document.getElementById('edit-dsc-form'),
    editUserId: document.getElementById('edit-user-id'),
    
    // Editable Spreadsheet Metadata Columns
    editClientName: document.getElementById('edit-client-name'),
    editPin: document.getElementById('edit-pin'),
    editTokenStatus: document.getElementById('edit-token-status'),
    editLocation: document.getElementById('edit-location'),
    editEmail: document.getElementById('edit-email'),
    editPhone: document.getElementById('edit-phone'),
    
    // Protected Hardware Fields
    editHolderName: document.getElementById('edit-holder-name'),
    editExpiryDate: document.getElementById('edit-expiry-date'),
    editClass: document.getElementById('edit-class'),
    editTokenSerial: document.getElementById('edit-token-serial'),
    
    // Modal Summaries
    modalClientName: document.getElementById('modal-client-name'),
    modalClientEmail: document.getElementById('modal-client-email'),
    
    // Toast Messages
    toastContainer: document.getElementById('toast-container')
};

// Mount Application
document.addEventListener('DOMContentLoaded', () => {
    setupTheme();
    setupEventListeners();
    loadLedgerData();
    initAutoPolling();
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
   Data Fetching & Rendering
   ========================================================================== */
async function loadLedgerData() {
    try {
        setTableLoading(true);
        const data = await API.getDSCList();
        
        state.records = data.data.records;
        state.stats = data.data.stats;
        
        renderStats();
        applyFiltersAndRenderGrid();
    } catch (error) {
        showToast(error.message || 'Error loading ledger sheet', 'error');
        setTableError(error.message);
    }
}

function renderStats() {
    const cards = els.metricsContainer.children;
    
    // Total Registered
    cards[0].classList.remove('loading');
    cards[0].querySelector('.metric-value').textContent = state.stats.total || 0;
    
    // Active DSC
    cards[1].classList.remove('loading');
    cards[1].querySelector('.metric-value').textContent = state.stats.active || 0;
    
    // Expiring Soon
    cards[2].classList.remove('loading');
    cards[2].querySelector('.metric-value').textContent = state.stats.expiring_soon || 0;
    
    // Expired DSC
    cards[3].classList.remove('loading');
    cards[3].querySelector('.metric-value').textContent = state.stats.expired || 0;
}

function applyFiltersAndRenderGrid() {
    const { search, status, possession } = state.filters;
    
    const filtered = state.records.filter(row => {
        // 1. Search Query Match
        const searchLower = search.toLowerCase();
        const matchesSearch = !search ||
            (row.holder_name && row.holder_name.toLowerCase().includes(searchLower)) ||
            (row.client_name && row.client_name.toLowerCase().includes(searchLower)) ||
            (row.serial_number && row.serial_number.toLowerCase().includes(searchLower)) ||
            (row.location && row.location.toLowerCase().includes(searchLower)) ||
            (row.email && row.email.toLowerCase().includes(searchLower)) ||
            (row.pin && row.pin.toLowerCase().includes(searchLower));
            
        // 2. Expiry Status Match
        let matchesStatus = true;
        if (status !== 'all') {
            matchesStatus = (row.status === status);
        }
        
        // 3. Possession Match
        let matchesPossession = true;
        if (possession !== 'all') {
            matchesPossession = (row.token_status === possession);
        }
        
        return matchesSearch && matchesStatus && matchesPossession;
    });
    
    renderGrid(filtered);
}

function renderGrid(data) {
    els.tableBody.innerHTML = '';
    
    if (data.length === 0) {
        els.tableBody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-5 text-muted">
                    <i class="fa-solid fa-folder-open mb-2" style="font-size: 2.2rem; display: block; opacity: 0.4;"></i>
                    No ledger records found matching the filters.
                </td>
            </tr>
        `;
        els.showingEntriesText.textContent = `Showing 0 of ${state.records.length} entries`;
        return;
    }
    
    data.forEach(row => {
        const tr = document.createElement('tr');
        
        // Format dates
        const dateObj = new Date(row.expiry_date);
        const expiryDisplay = dateObj.toLocaleDateString('en-IN', {
            day: '2-digit', month: 'short', year: 'numeric'
        });
        
        // Expiry Status Tag
        let statusBadge = '<span class="badge badge-status none">Incomplete</span>';
        if (row.status === 'active') {
            statusBadge = '<span class="badge badge-status active"><i class="fa-solid fa-circle-check mr-1" style="margin-right:4px;"></i> Active</span>';
        } else if (row.status === 'expiring_soon') {
            statusBadge = '<span class="badge badge-status expiring_soon"><i class="fa-solid fa-circle-exclamation mr-1" style="margin-right:4px;"></i> Expiring Soon</span>';
        } else if (row.status === 'expired') {
            statusBadge = '<span class="badge badge-status expired"><i class="fa-solid fa-triangle-exclamation mr-1" style="margin-right:4px;"></i> Expired</span>';
        }
        
        // Possession Badge
        const possessionClass = row.token_status === 'In Office' ? 'client' : 'admin';
        const possessionIcon = row.token_status === 'In Office' ? 'fa-building-columns' : 'fa-handshake';
        const possessionBadge = `<span class="badge badge-role ${possessionClass}"><i class="fa-solid ${possessionIcon} mr-1" style="margin-right:4px;"></i> ${escapeHTML(row.token_status)}</span>`;
        
        tr.innerHTML = `
            <td class="client-name-cell">
                ${escapeHTML(row.holder_name)}
            </td>
            <td>
                <span class="client-name-cell" style="color: var(--primary);">${escapeHTML(row.client_name || '—')}</span>
            </td>
            <td>
                <span style="font-family: monospace; font-size: 0.85rem; background: rgba(0,0,0,0.12); padding: 4px 8px; border-radius: 4px; border: 1px solid var(--panel-border); font-weight: 600;">
                    ${escapeHTML(row.pin || '—')}
                </span>
            </td>
            <td class="client-name-cell">${expiryDisplay}</td>
            <td><span class="text-muted" style="font-size: 0.85rem;">${escapeHTML(row.dsc_class || 'Class 3')}</span></td>
            <td style="font-family: monospace; font-size: 0.8rem;" title="${escapeHTML(row.serial_number)}">
                ${escapeHTML(row.serial_number).substring(0, 16)}...
            </td>
            <td>${possessionBadge}</td>
            <td>
                <span class="text-muted" style="font-weight: 500;"><i class="fa-solid fa-box-open mr-1" style="margin-right:4px; font-size: 0.8rem;"></i> ${escapeHTML(row.location || '—')}</span>
            </td>
            <td>
                <div class="actions-cell">
                    <button class="btn-action btn-edit" title="Edit Sheet Details" onclick="openEditModal(${row.id})">
                        <i class="fa-solid fa-pencil"></i>
                    </button>
                    <button class="btn-action btn-remind-wa" title="WhatsApp Reminder" onclick="sendAlert(${row.id}, 'whatsapp')">
                        <i class="fa-brands fa-whatsapp"></i>
                    </button>
                    <button class="btn-action btn-remind-email" title="Email Reminder" onclick="sendAlert(${row.id}, 'email')">
                        <i class="fa-solid fa-envelope"></i>
                    </button>
                    <button class="btn-action" title="Delete Row" style="color: var(--danger); border-color: rgba(239, 68, 68, 0.15);" onclick="deleteRow(${row.id})">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </td>
        `;
        
        els.tableBody.appendChild(tr);
    });
    
    els.showingEntriesText.textContent = `Showing ${data.length} of ${state.records.length} entries`;
}

function setTableLoading(isLoading) {
    if (isLoading) {
        els.tableBody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-5">
                    <div class="spinner"></div>
                    <p class="mt-2 text-muted">Loading ledger sheet...</p>
                </td>
            </tr>
        `;
    }
}

function setTableError(message) {
    els.tableBody.innerHTML = `
        <tr>
            <td colspan="9" class="text-center py-5 text-danger">
                <i class="fa-solid fa-triangle-exclamation mb-2" style="font-size: 2.2rem; display: block;"></i>
                <p><strong>Database Connection Interrupted:</strong></p>
                <p class="text-muted" style="max-width: 400px; margin: 8px auto 0;">${escapeHTML(message)}</p>
                <button class="btn btn-secondary btn-sm mt-3" onclick="loadLedgerData()"><i class="fa-solid fa-rotate"></i> Retry Connection</button>
            </td>
        </tr>
    `;
}

/* ==========================================================================
   Spreadsheet Quick-Actions & Controls
   ========================================================================== */
function setupEventListeners() {
    // Instant search filtering
    if (els.searchInput) {
        els.searchInput.addEventListener('input', (e) => {
            state.filters.search = e.target.value;
            applyFiltersAndRenderGrid();
        });
    }
    
    // Expiry and possession filters
    if (els.statusFilter) {
        els.statusFilter.addEventListener('change', (e) => {
            state.filters.status = e.target.value;
            applyFiltersAndRenderGrid();
        });
    }
    
    if (els.possessionFilter) {
        els.possessionFilter.addEventListener('change', (e) => {
            state.filters.possession = e.target.value;
            applyFiltersAndRenderGrid();
        });
    }
    
    // Hardware Auto-Detect & Add Trigger
    if (els.btnDetectRegister) {
        els.btnDetectRegister.addEventListener('click', handleHardwareRegister);
    }
    
    // CSV Exporter Trigger
    if (els.btnExportCSV) {
        els.btnExportCSV.addEventListener('click', exportLedgerToCSV);
    }
    
    // Modal window controls
    if (els.closeModalBtn) {
        els.closeModalBtn.addEventListener('click', closeEditModal);
    }
    if (els.cancelModalBtn) {
        els.cancelModalBtn.addEventListener('click', closeEditModal);
    }
    window.addEventListener('click', (e) => {
        if (e.target === els.editModal) {
            closeEditModal();
        }
    });
    
    if (els.editForm) {
        els.editForm.addEventListener('submit', handleFormSubmit);
    }
}

/**
 * ⚡ "Plug & Register DSC" Action
 * Contacts local port 12345 to read plugged USB DSC and instantly appends it to the ledger
 */
async function handleHardwareRegister() {
    const originalHTML = els.btnDetectRegister.innerHTML;
    
    try {
        els.btnDetectRegister.disabled = true;
        els.btnDetectRegister.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Reading Hardware...';
        
        const response = await fetch('http://localhost:12345/get-dsc-info', {
            method: 'GET',
            mode: 'cors'
        });
        
        const data = await response.json();
        
        if (data.success && data.certificates && data.certificates.length > 0) {
            let processed = 0;
            let lastId = null;
            
            for (const cert of data.certificates) {
                const registerData = {
                    holder_name: cert.holderName,
                    serial_number: cert.serialNumber,
                    expiry_date: cert.expiryDate,
                    dsc_class: cert.class || 'Class 3'
                };
                
                const serverResponse = await API.registerDSC(registerData);
                if (serverResponse.success) {
                    processed++;
                    lastId = serverResponse.data.id;
                }
            }
            
            if (processed > 0) {
                showToast(`Successfully registered/updated ${processed} DSC token(s) from hardware!`, 'success');
                await loadLedgerData();
                if (lastId) {
                    openEditModal(lastId);
                }
            }
        } else {
            showToast(data.message || 'Make sure the USB DSC token is plugged in.', 'error');
        }
    } catch (error) {
        console.error('DSC Registration Error:', error);
        showToast('Connection failed. Make sure your background DSC helper is active.', 'error');
    } finally {
        els.btnDetectRegister.disabled = false;
        els.btnDetectRegister.innerHTML = originalHTML;
    }
}

/**
 * 📥 "Export Sheet (CSV)" Action
 * Compiles ledger rows into standard CSV spreadsheet file and downloads it
 */
function exportLedgerToCSV() {
    if (state.records.length === 0) {
        showToast('Ledger sheet is currently empty.', 'error');
        return;
    }
    
    // Header columns
    const headers = ['DSC Holder Name', 'Associated Client/Company', 'Token PIN', 'Expiry Date', 'Class', 'Token Serial', 'Possession Status', 'Storage Location', 'Email', 'Phone'];
    
    // Row mappings
    const rows = state.records.map(row => [
        row.holder_name || '',
        row.client_name || '',
        row.pin || '',
        row.expiry_date || '',
        row.dsc_class || 'Class 3',
        row.serial_number || '',
        row.token_status || 'In Office',
        row.location || '',
        row.email || '',
        row.phone || ''
    ]);
    
    // Combine to CSV format
    const csvContent = [
        headers.join(','),
        ...rows.map(e => e.map(val => `"${val.replace(/"/g, '""')}"`).join(','))
    ].join('\n');
    
    // Trigger browser download
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', `DSC_Ledger_Registry_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showToast('Ledger sheet successfully exported as CSV!', 'success');
}

/* ==========================================================================
   Modal Drawer Forms
   ========================================================================== */
window.openEditModal = function(id) {
    const row = state.records.find(r => r.id === id);
    if (!row) return;
    
    state.editingId = id;
    els.editUserId.value = row.id;
    
    // Map editable custom fields
    els.editClientName.value = row.client_name || '';
    els.editPin.value = row.pin || '';
    els.editTokenStatus.value = row.token_status || 'In Office';
    els.editLocation.value = row.location || '';
    els.editEmail.value = row.email || '';
    els.editPhone.value = row.phone || '';
    
    // Map protected hardware fields
    els.editHolderName.value = row.holder_name;
    els.editExpiryDate.value = row.expiry_date;
    els.editClass.value = row.dsc_class || 'Class 3';
    els.editTokenSerial.value = row.serial_number;
    
    // Modal Summary Header
    els.modalClientName.textContent = row.client_name || 'Unassigned Token';
    els.modalClientEmail.textContent = `Holder: ${row.holder_name} | Serial: ${row.serial_number.substring(0, 12)}...`;
    
    els.editModal.classList.add('active');
};

function closeEditModal() {
    els.editModal.classList.remove('active');
    els.editForm.reset();
    state.editingId = null;
}

async function handleFormSubmit(e) {
    e.preventDefault();
    
    const id = parseInt(els.editUserId.value);
    const payload = {
        client_name: els.editClientName.value,
        pin: els.editPin.value,
        token_status: els.editTokenStatus.value,
        location: els.editLocation.value,
        email: els.editEmail.value,
        phone: els.editPhone.value,
        
        // Also allow manual edits of core values
        holder_name: els.editHolderName.value,
        expiry_date: els.editExpiryDate.value,
        dsc_class: els.editClass.value,
        serial_number: els.editTokenSerial.value
    };
    
    try {
        const response = await API.updateDSC(id, payload);
        if (response.success) {
            showToast('Ledger sheet row updated successfully', 'success');
            closeEditModal();
            loadLedgerData();
        }
    } catch (error) {
        showToast(error.message || 'Failed to save changes.', 'error');
    }
}

/**
 * 🗑️ Row Deletion Action
 */
window.deleteRow = async function(id) {
    const row = state.records.find(r => r.id === id);
    if (!row) return;
    
    const label = row.client_name ? `${row.client_name} (${row.holder_name})` : row.holder_name;
    
    if (confirm(`Are you sure you want to delete the DSC ledger record for "${label}"?\nThis action cannot be undone.`)) {
        try {
            const response = await API.deleteDSC(id);
            if (response.success) {
                showToast('Ledger row deleted successfully', 'success');
                loadLedgerData();
            }
        } catch (error) {
            showToast(error.message || 'Error deleting row', 'error');
        }
    }
};

/* ==========================================================================
   Reminders & Notifications (WhatsApp & Email Alerts)
   ========================================================================== */
window.sendAlert = function(id, method) {
    const row = state.records.find(r => r.id === id);
    if (!row) return;
    
    if (!row.expiry_date) {
        showToast("Expiry date is required to draft reminders.", "error");
        return;
    }
    
    const expiryFormatted = new Date(row.expiry_date).toLocaleDateString('en-IN', {
        day: '2-digit', month: 'long', year: 'numeric'
    });
    
    const client = row.client_name || row.holder_name;
    const holder = row.holder_name;
    
    if (method === 'whatsapp') {
        if (!row.phone) {
            showToast("No phone number registered for this ledger entry.", "error");
            return;
        }
        
        let phone = row.phone.replace(/[^0-9]/g, '');
        if (phone.length === 10) {
            phone = '91' + phone;
        }
        
        const message = `Hello ${client},\n\nThis is a reminder from *Akanksha Shashank & Associates* regarding your Digital Signature Certificate (DSC) of holder *${holder}*. It is scheduled to expire on *${expiryFormatted}*.\n\nPlease contact us as soon as possible to proceed with the renewal process and prevent any e-filing delays.\n\nThank you!`;
        const waUrl = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
        
        window.open(waUrl, '_blank');
        showToast(`WhatsApp reminder drafted to ${client}`, 'success');
        
    } else if (method === 'email') {
        if (!row.email) {
            showToast("No email registered for this ledger entry.", "error");
            return;
        }
        
        const subject = `IMPORTANT: DSC Expiry Alert - Holder: ${holder}`;
        const body = `Dear ${client},\n\nThis is a notification from Akanksha Shashank & Associates that your Digital Signature Certificate (DSC) under holder name "${holder}" is expiring on ${expiryFormatted}.\n\nTo ensure your Income Tax, GST, or MCA company law filings continue without interruption, please confirm if we should initiate the DSC renewal on your behalf.\n\nWarm regards,\nAkanksha Shashank & Associates`;
        
        const mailUrl = `mailto:${row.email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
        
        window.open(mailUrl);
        showToast(`Email drafted successfully to ${row.email}`, 'success');
    }
};

/* ==========================================================================
   Global Utilities (Escaping & Toasts)
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
    
    // Trigger transition reflow
    toast.offsetHeight; 
    toast.classList.add('show');
    
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

/* ==========================================================================
   🕵️‍♂️ Silent Hardware Auto-Polling (Zero-Click Registration)
   ========================================================================== */
let isPolling = false;

function initAutoPolling() {
    // Check local listener every 4 seconds in the background
    setInterval(async () => {
        if (isPolling) return;
        
        try {
            isPolling = true;
            
            const response = await fetch('http://localhost:12345/get-dsc-info', {
                method: 'GET',
                mode: 'cors'
            });
            
            const data = await response.json();
            
            if (data.success && data.certificates && data.certificates.length > 0) {
                let anyNewAdded = false;
                
                for (const cert of data.certificates) {
                    // Check if this token serial already exists in our loaded state
                    const exists = state.records.some(r => r.serial_number === cert.serialNumber);
                    
                    if (!exists) {
                        const registerData = {
                            holder_name: cert.holderName,
                            serial_number: cert.serialNumber,
                            expiry_date: cert.expiryDate,
                            dsc_class: cert.class || 'Class 3'
                        };
                        
                        const serverResponse = await API.registerDSC(registerData);
                        
                        if (serverResponse.success) {
                            anyNewAdded = true;
                            showToast(`Auto-Detected & registered new DSC for: ${cert.holderName}!`, 'success');
                        }
                    }
                }
                
                if (anyNewAdded) {
                    // Instantly refresh grid without spinner disturbance
                    await loadLedgerData();
                }
            }
        } catch (error) {
            // Ignore connection errors (helper offline) silently during polling
        } finally {
            isPolling = false;
        }
    }, 4000);
}
