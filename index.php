<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DSC Ledger - Standalone Registry Spreadsheet</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Style -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dark-theme">
    <div class="glass-bg"></div>
    
    <div class="app-container">
        <!-- Sidebar / Navigation -->
        <header class="app-header">
            <div class="brand">
                <div class="brand-icon" style="background: linear-gradient(135deg, #a855f7, #6366f1); box-shadow: 0 4px 15px rgba(168, 85, 247, 0.4);">
                    <i class="fa-solid fa-table-list"></i>
                </div>
                <div>
                    <h1>DSC Registry Ledger</h1>
                    <p>Standalone Hardware Token Management Sheet</p>
                </div>
            </div>
            
            <div class="header-actions">
                <!-- Theme Toggle -->
                <button id="theme-toggle" class="btn-icon" title="Toggle Light/Dark Mode">
                    <i class="fa-solid fa-sun"></i>
                </button>
                <div class="db-status-badge success">
                    <span class="status-dot"></span> Active
                </div>
            </div>
        </header>

        <!-- Main Dashboard Content -->
        <main class="app-content">
            <!-- Metrics Row -->
            <section class="metrics-grid" id="metrics-container">
                <div class="metric-card glass-panel loading">
                    <div class="metric-info">
                        <h3>Total Registered Tokens</h3>
                        <div class="metric-value">-</div>
                    </div>
                    <div class="metric-icon bg-primary" style="background: linear-gradient(135deg, #a855f7, #6366f1);">
                        <i class="fa-solid fa-hard-drive"></i>
                    </div>
                </div>
                <div class="metric-card glass-panel loading">
                    <div class="metric-info">
                        <h3>Active DSCs</h3>
                        <div class="metric-value">-</div>
                    </div>
                    <div class="metric-icon bg-success">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>
                <div class="metric-card glass-panel loading">
                    <div class="metric-info">
                        <h3>Expiring < 30 Days</h3>
                        <div class="metric-value">-</div>
                    </div>
                    <div class="metric-icon bg-warning">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                </div>
                <div class="metric-card glass-panel loading">
                    <div class="metric-info">
                        <h3>Expired Tokens</h3>
                        <div class="metric-value">-</div>
                    </div>
                    <div class="metric-icon bg-danger">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </div>
                </div>
            </section>

            <!-- Table Section -->
            <section class="table-section glass-panel">
                <div class="table-header">
                    <div style="display: flex; flex-direction: column; gap: 4px;">
                        <h2>Spreadsheet Ledger Grid</h2>
                        <p class="text-muted" style="font-size: 0.8rem; font-weight: 500;">Plug in your token and click Register to instantly add it to the ledger!</p>
                    </div>
                    
                    <div class="table-controls">
                        <!-- Plug & Add Button -->
                        <button type="button" class="btn btn-primary" id="btn-detect-register" style="background: linear-gradient(135deg, #a855f7, #6366f1); border: none; box-shadow: 0 4px 15px rgba(168, 85, 247, 0.3);">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> Plug & Register DSC
                        </button>
                        
                        <!-- CSV Export -->
                        <button type="button" class="btn btn-secondary" id="btn-export-csv" title="Export Ledger as CSV">
                            <i class="fa-solid fa-file-csv"></i> Export Sheet
                        </button>
                    </div>
                </div>
                
                <!-- Search & Filters -->
                <div class="table-header" style="border-top: 1px solid var(--panel-border); padding-top: 16px; margin-top: -4px;">
                    <div class="search-box" style="flex-grow: 1; min-width: 320px;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="search-input" placeholder="Search by holder name, serial, associated client, location...">
                    </div>
                    
                    <div class="table-controls">
                        <div class="filter-dropdown">
                            <i class="fa-solid fa-filter"></i>
                            <select id="status-filter">
                                <option value="all">All Expiries</option>
                                <option value="active">Active DSC</option>
                                <option value="expiring_soon">Expiring Soon</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>
                        <div class="filter-dropdown">
                            <i class="fa-solid fa-circle-question"></i>
                            <select id="possession-filter">
                                <option value="all">All Locations</option>
                                <option value="In Office">In Office</option>
                                <option value="With Client">With Client</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="dsc-table">
                        <thead>
                            <tr>
                                <th>DSC Holder Name</th>
                                <th>Client/Entity Name</th>
                                <th>Token PIN</th>
                                <th>Expiry Date</th>
                                <th>Class</th>
                                <th>Token Serial</th>
                                <th>Possession</th>
                                <th>Storage Location</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                            <!-- Injected by JavaScript -->
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="spinner"></div>
                                    <p class="mt-2 text-muted">Loading DSC ledger records...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="table-footer">
                    <span id="showing-entries-text">Showing 0 of 0 entries</span>
                </div>
            </section>
        </main>
    </div>

    <!-- Edit DSC Details Modal -->
    <div class="modal-backdrop" id="edit-modal">
        <div class="modal-panel glass-panel">
            <div class="modal-header">
                <h3><i class="fa-solid fa-pen-to-square"></i> Edit Ledger Record</h3>
                <button class="btn-close" id="close-modal-btn">&times;</button>
            </div>
            <form id="edit-dsc-form">
                <input type="hidden" id="edit-user-id">
                
                <div class="modal-body">
                    <div class="client-summary" style="background: rgba(168, 85, 247, 0.08); border-color: rgba(168, 85, 247, 0.2);">
                        <div class="client-avatar" style="background: linear-gradient(135deg, #a855f7, #6366f1);">
                            <i class="fa-solid fa-hard-drive"></i>
                        </div>
                        <div style="flex-grow: 1; margin-right: 12px;">
                            <h4 id="modal-client-name">Auto-Detected Token</h4>
                            <p id="modal-client-email">Hardware parameters locked</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="edit-client-name">Associated Client / Company Name</label>
                            <input type="text" id="edit-client-name" placeholder="E.g., Akanksha Shashank & Associates">
                        </div>

                        <div class="form-group">
                            <label for="edit-pin">Token PIN / Password</label>
                            <div style="position: relative;">
                                <input type="text" id="edit-pin" placeholder="Enter USB PIN" style="width: 100%; padding-right: 36px;">
                                <i class="fa-solid fa-key" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none;"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="edit-token-status">Possession Status</label>
                            <select id="edit-token-status">
                                <option value="In Office">In Office</option>
                                <option value="With Client">With Client</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="edit-location">Storage Location (e.g. Cabinet / Drawer)</label>
                            <input type="text" id="edit-location" placeholder="E.g., Cabinet 2, Shelf 3">
                        </div>

                        <div class="form-group">
                            <label for="edit-holder-name">DSC Holder Name (Locked)</label>
                            <input type="text" id="edit-holder-name" placeholder="Name on certificate" required>
                        </div>

                        <div class="form-group">
                            <label for="edit-expiry-date">DSC Expiry Date (Locked)</label>
                            <input type="date" id="edit-expiry-date" required>
                        </div>

                        <div class="form-group">
                            <label for="edit-class">DSC Class</label>
                            <select id="edit-class">
                                <option value="Class 3">Class 3</option>
                                <option value="Class 2">Class 2</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="edit-token-serial">DSC Token Serial (Locked)</label>
                            <input type="text" id="edit-token-serial" placeholder="Unique serial key" required>
                        </div>

                        <div class="form-group">
                            <label for="edit-email">Client Email (For Reminders)</label>
                            <input type="email" id="edit-email" placeholder="Enter recipient email">
                        </div>

                        <div class="form-group">
                            <label for="edit-phone">Client Phone (For WhatsApp)</label>
                            <input type="text" id="edit-phone" placeholder="Enter 10-digit mobile number">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancel-modal-btn">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #a855f7, #6366f1); border: none;">Save Sheet Row</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification System -->
    <div class="toast-container" id="toast-container"></div>

    <!-- Scripts -->
    <script src="assets/js/api-client.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
