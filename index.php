<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Thrive Cafe - POS System</title>
    <meta name="description" content="Thrive Cafe Point of Sale System - Mobile Responsive">
    <meta name="theme-color" content="#0d6efd">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    
    <!-- iOS PWA Support -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Thrive POS">
    <link rel="apple-touch-icon" href="assets/icons/icon-152x152.png">
    
    <!-- Android PWA Support -->
    <meta name="mobile-web-app-capable" content="yes">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/icons/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/icons/icon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/icons/icon-16x16.png">
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-dark bg-primary">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="fas fa-coffee"></i> 
                <span class="d-none d-sm-inline">Thrive Cafe</span>
                <span class="d-inline d-sm-none">Thrive</span>
            </span>
            
            <!-- Mobile Menu Button (Only for phones) -->
            <button class="btn btn-outline-light d-md-none" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Desktop Navigation Buttons -->
            <div class="navbar-nav flex-row d-none d-md-flex">
                <button class="btn btn-outline-light me-2 desktop-nav-btn active" data-section="dashboard">
                    <i class="fas fa-chart-line"></i> Dashboard
                </button>
                <button class="btn btn-outline-light me-2 desktop-nav-btn" data-section="orders">
                    <i class="fas fa-shopping-cart"></i> Orders
                </button>
                <button class="btn btn-outline-light me-2 desktop-nav-btn" data-section="menu">
                    <i class="fas fa-utensils"></i> Menu
                </button>
                <button class="btn btn-outline-light desktop-nav-btn" data-section="offers">
                    <i class="fas fa-tags"></i> Offers
                </button>
            </div>
        </div>
    </nav>

    <!-- Mobile Sidebar -->
    <div class="mobile-sidebar" id="mobileSidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <i class="fas fa-coffee"></i>
                <span>Thrive Cafe</span>
            </div>
            <button class="btn btn-link text-white" id="closeSidebarBtn">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="sidebar-content">
            <div class="sidebar-nav">
                <a href="#" class="sidebar-item active" data-section="dashboard">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="sidebar-item" data-section="orders">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Orders</span>
                </a>
                <a href="#" class="sidebar-item" data-section="menu">
                    <i class="fas fa-utensils"></i>
                    <span>Menu</span>
                </a>
                <a href="#" class="sidebar-item" data-section="offers">
                    <i class="fas fa-tags"></i>
                    <span>Offers</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="container-fluid mt-3">
        <!-- Dashboard Section -->
        <div id="dashboard-section" class="content-section active">
            <div class="row mb-3">
                <div class="col-12 col-md-6 mb-2 mb-md-0">
                    <h2 class="h4 h-md-2">
                        <i class="fas fa-chart-line"></i> 
                        <span class="d-none d-sm-inline">Analytics Dashboard</span>
                        <span class="d-inline d-sm-none">Dashboard</span>
                    </h2>
                </div>
                <div class="col-12 col-md-6">
                    <div class="btn-group w-100 w-md-auto" role="group">
                        <button type="button" class="btn btn-outline-primary date-filter active" data-filter="today">
                            <span class="d-none d-sm-inline">Today</span>
                            <span class="d-inline d-sm-none">1D</span>
                        </button>
                        <button type="button" class="btn btn-outline-primary date-filter" data-filter="7days">
                            <span class="d-none d-sm-inline">7 Days</span>
                            <span class="d-inline d-sm-none">7D</span>
                        </button>
                        <button type="button" class="btn btn-outline-primary date-filter" data-filter="28days">
                            <span class="d-none d-sm-inline">28 Days</span>
                            <span class="d-inline d-sm-none">28D</span>
                        </button>
                        <button type="button" class="btn btn-outline-primary" id="customDateBtn">
                            <span class="d-none d-sm-inline">Custom</span>
                            <span class="d-inline d-sm-none"><i class="fas fa-calendar"></i></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Custom Date Range Modal -->
            <div class="collapse mb-3" id="customDateRange">
                <div class="card card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="startDate" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="startDate">
                        </div>
                        <div class="col-md-4">
                            <label for="endDate" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="endDate">
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-primary mt-4" id="applyCustomDate">Apply</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-subtitle mb-2 text-muted">Total Sales</h6>
                                    <h3 class="card-title mb-0" id="totalSales">₹0.00</h3>
                                </div>
                                <div class="stat-icon bg-success">
                                    <i class="fas fa-rupee-sign"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-subtitle mb-2 text-muted">Total Profit</h6>
                                    <h3 class="card-title mb-0" id="totalProfit">₹0.00</h3>
                                </div>
                                <div class="stat-icon bg-info">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-subtitle mb-2 text-muted">Total Orders</h6>
                                    <h3 class="card-title mb-0" id="totalOrders">0</h3>
                                </div>
                                <div class="stat-icon bg-warning">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-subtitle mb-2 text-muted">Avg Order Value</h6>
                                    <h3 class="card-title mb-0" id="avgOrderValue">₹0.00</h3>
                                </div>
                                <div class="stat-icon bg-danger">
                                    <i class="fas fa-calculator"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="row mb-4">
                <div class="col-lg-8 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-line"></i> Sales & Profit Trend</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="salesChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-pie"></i> Payment Methods</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="paymentChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Selling Items -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-trophy"></i> Top Selling Products</h5>
                        </div>
                        <div class="card-body">
                            <div id="topProducts">
                                <p class="text-muted">Loading...</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-star"></i> Top Selling Combos</h5>
                        </div>
                        <div class="card-body">
                            <div id="topCombos">
                                <p class="text-muted">Loading...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Export Section -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-download"></i> Export Data</h5>
                        </div>
                        <div class="card-body">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-success export-btn" data-type="sales">
                                    <i class="fas fa-file-csv"></i> Sales Report
                                </button>
                                <button type="button" class="btn btn-outline-success export-btn" data-type="products">
                                    <i class="fas fa-file-csv"></i> Product List
                                </button>
                                <button type="button" class="btn btn-outline-success export-btn" data-type="combos">
                                    <i class="fas fa-file-csv"></i> Combo List
                                </button>
                                <button type="button" class="btn btn-outline-success export-btn" data-type="customers">
                                    <i class="fas fa-file-csv"></i> Customer Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Section -->
        <div id="orders-section" class="content-section">
            <div class="row">
                <div class="col-md-8">
                    <!-- Order Tabs -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <ul class="nav nav-tabs card-header-tabs" id="orderTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="order-1-tab" data-bs-toggle="tab" data-bs-target="#order-1" type="button" role="tab">
                                        Bill #1 <span class="badge bg-secondary ms-1" id="order-1-count">0</span>
                                    </button>
                                </li>
                            </ul>
                            <button class="btn btn-sm btn-outline-primary" id="addNewBill">
                                <i class="fas fa-plus"></i> New Bill
                            </button>
                        </div>
                        <div class="card-body">
                            <!-- Search Bar -->
                            <div class="mb-3">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" id="itemSearch" placeholder="Search products or combos...">
                                </div>
                                <div id="searchResults" class="list-group mt-2" style="display: none;"></div>
                            </div>

                            <!-- Order Content Tabs -->
                            <div class="tab-content" id="orderTabsContent">
                                <div class="tab-pane fade show active" id="order-1" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Item</th>
                                                    <th>Price</th>
                                                    <th>Qty</th>
                                                    <th>Total</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody class="order-items">
                                                <tr class="no-items">
                                                    <td colspan="5" class="text-center text-muted">No items added yet</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Bill Summary -->
                    <div class="card sticky-top">
                        <div class="card-header">
                            <h5><i class="fas fa-receipt"></i> Bill Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-6">Subtotal:</div>
                                <div class="col-6 text-end" id="billSubtotal">₹0.00</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-6">Discount:</div>
                                <div class="col-6 text-end" id="billDiscount">₹0.00</div>
                            </div>
                            <hr>
                            <div class="row mb-3">
                                <div class="col-6"><strong>Total:</strong></div>
                                <div class="col-6 text-end"><strong id="billTotal">₹0.00</strong></div>
                            </div>
                            
                            <button class="btn btn-success w-100" id="checkoutBtn" disabled>
                                <i class="fas fa-credit-card"></i> Checkout
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menu Management Section -->
        <div id="menu-section" class="content-section">
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-coffee"></i> Products</h5>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
                                <i class="fas fa-plus"></i> Add Product
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Price</th>
                                            <th>Cost</th>
                                            <th>Profit</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="productsTable">
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Loading products...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-layer-group"></i> Combos</h5>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#comboModal">
                                <i class="fas fa-plus"></i> Add Combo
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Price</th>
                                            <th>Cost</th>
                                            <th>Profit</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="combosTable">
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Loading combos...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Offers Section -->
        <div id="offers-section" class="content-section">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-tags"></i> Promotional Offers</h5>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#offerModal">
                        <i class="fas fa-plus"></i> Create Offer
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Offer Name</th>
                                    <th>Date Range</th>
                                    <th>Time Range</th>
                                    <th>Discount</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="offersTable">
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Loading offers...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals will be included here -->
    <?php include_once 'includes/modals.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
