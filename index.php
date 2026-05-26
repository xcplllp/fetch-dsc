<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DSC Manager Control Panel</title>
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
                <div class="brand-icon">
                    <i class="fa-solid fa-key"></i>
                </div>
                <div>
                    <h1>DSC Registry</h1>
                    <p>Akanksha Shashank & Associates</p>
                </div>
            </div>
            
            <div class="header-actions">
                <button id="theme-toggle" class="btn-icon" title="Toggle Light/Dark Mode">
                    <i class="fa-solid fa-sun"></i>
                </button>
                <div class="db-status-badge success">
                    <span class="status-dot"></span> Connected
                </div>
            </div>
        </header>

        <!-- Main Dashboard Dashboard Content -->
        <main class="app-content">
            <!-- Metrics Row -->
            <section class="metrics-grid" id="metrics-container">
                <div class="metric-card glass-panel loading">
                    <div class="metric-info">
                        <h3>Total Clients/Users</h3>
                        <div class="metric-value">-</div>
                    </div>
                    <div class="metric-icon bg-primary">
                        <i class="fa-solid fa-users"></i>
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
                        <h3>Expired DSCs</h3>
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
                    <h2>DSC Directory</h2>
                    <div class="table-controls">
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="search-input" placeholder="Search by name, PAN, email...">
                        </div>
                        <div class="filter-dropdown">
                            <i class="fa-solid fa-filter"></i>
                            <select id="status-filter">
                                <option value="all">All Statuses</option>
                                <option value="active">Active DSC</option>
                                <option value="expiring_soon">Expiring Soon</option>
                                <option value="expired">Expired</option>
                                <option value="incomplete">Incomplete DSC</option>
                                <option value="none">No DSC</option>
                            </select>
                        </div>
                        <div class="filter-dropdown">
                            <i class="fa-solid fa-user-gear"></i>
                            <select id="role-filter">
                                <option value="all">All Roles</option>
                                <option value="client">Clients Only</option>
                                <option value="staff">Staff Only</option>
                                <option value="admin">Admins Only</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="dsc-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>DSC Holder</th>
                                <th>Expiry Date</th>
                                <th>Class</th>
                                <th>Token Serial</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                            <!-- Injected by JavaScript -->
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="spinner"></div>
                                    <p class="mt-2 text-muted">Loading DSC records...</p>
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
                <h3><i class="fa-solid fa-pen-to-square"></i> Edit DSC Details</h3>
                <button class="btn-close" id="close-modal-btn">&times;</button>
            </div>
            <form id="edit-dsc-form">
                <input type="hidden" id="edit-user-id">
                
                <div class="modal-body">
                    <div class="client-summary">
                        <div class="client-avatar" id="modal-client-avatar">U</div>
                        <div style="flex-grow: 1; margin-right: 12px;">
                            <h4 id="modal-client-name">-</h4>
                            <p id="modal-client-email">-</p>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" id="btn-detect-dsc" style="background: linear-gradient(135deg, #a855f7, #6366f1); border: none; box-shadow: 0 4px 12px rgba(168, 85, 247, 0.25);">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> Auto-Detect DSC
                        </button>
                    </div>

                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="edit-holder-name">DSC Holder Name</label>
                            <input type="text" id="edit-holder-name" placeholder="Enter exact name on DSC certificate">
                        </div>

                        <div class="form-group">
                            <label for="edit-expiry-date">DSC Expiry Date</label>
                            <input type="date" id="edit-expiry-date">
                        </div>

                        <div class="form-group">
                            <label for="edit-class">DSC Class</label>
                            <select id="edit-class">
                                <option value="">Select Class</option>
                                <option value="Class 3">Class 3</option>
                                <option value="Class 2">Class 2 (Legacy)</option>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label for="edit-token-serial">DSC Token Serial / USB ID</label>
                            <input type="text" id="edit-token-serial" placeholder="Enter USB cryptographic token serial number">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancel-modal-btn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
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
