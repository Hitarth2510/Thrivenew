// Thrive Cafe Billing Software - Main JavaScript Application

class ThriveCafe {
    constructor() {
        this.currentSection = 'dashboard';
        this.currentDateFilter = 'today';
        this.currentBillTab = 1;
        this.billCounter = 1;
        this.bills = {};
        this.searchTimeout = null;
        this.charts = {};
        
        this.init();
    }

    init() {
        console.log('📱 Thrive Cafe POS - Mobile Enhanced');
        
        // Detect mobile device and touch support
        this.isMobile = window.innerWidth <= 768;
        this.isTouch = 'ontouchstart' in window;
        
        this.bindEvents();
        this.initializeBills();
        this.loadDashboard();
        this.loadMenuData();
        this.loadOffers();
        this.setDateDefaults();
        
        // Handle window resize for responsive behavior
        $(window).on('resize', () => {
            this.isMobile = window.innerWidth <= 768;
            this.handleResponsiveChanges();
        });
        
        // Add touch support for better mobile experience
        if (this.isTouch) {
            this.initTouchSupport();
        }
        
        // Initial responsive setup
        this.handleResponsiveChanges();
    }

    handleResponsiveChanges() {
        // Update table display based on screen size
        if (this.isMobile) {
            $('.table-responsive').addClass('mobile-optimized');
            $('body').addClass('mobile-device');
        } else {
            $('.table-responsive').removeClass('mobile-optimized');
            $('body').removeClass('mobile-device');
        }
    }

    initTouchSupport() {
        // Add touch feedback for buttons
        $(document).on('touchstart', '.btn', function() {
            $(this).addClass('touching');
        });
        
        $(document).on('touchend touchcancel', '.btn', function() {
            const $this = $(this);
            setTimeout(() => $this.removeClass('touching'), 150);
        });
        
        // Improve form input experience on mobile
        $('input[type="number"]').attr('inputmode', 'decimal');
        $('input[type="tel"]').attr('inputmode', 'tel');
    }

    bindEvents() {
        console.log('Binding events...');
        
        // Mobile Sidebar Events
        $('#mobileMenuBtn').on('click', (e) => {
            console.log('Mobile menu button clicked');
            e.preventDefault();
            this.openSidebar();
        });
        
        $('#closeSidebarBtn').on('click', (e) => {
            console.log('Close sidebar button clicked');
            e.preventDefault();
            this.closeSidebar();
        });
        
        $('#sidebarOverlay').on('click', (e) => {
            console.log('Sidebar overlay clicked');
            e.preventDefault();
            this.closeSidebar();
        });
        
        // Sidebar navigation (mobile)
        $('.sidebar-item').on('click', (e) => {
            console.log('Sidebar item clicked');
            e.preventDefault();
            const section = $(e.currentTarget).data('section');
            this.navigateToSection(section);
            this.closeSidebar();
        });

        // Desktop navbar navigation
        $('.desktop-nav-btn').on('click', (e) => {
            console.log('Desktop nav button clicked');
            const section = $(e.currentTarget).data('section');
            
            // Update active state for desktop navigation
            $('.desktop-nav-btn').removeClass('active');
            $(e.currentTarget).addClass('active');
            
            this.showSection(section);
        });

        // Dashboard events
        $('.date-filter').on('click', (e) => this.setDateFilter($(e.target).data('filter')));
        $('#customDateBtn').on('click', () => this.toggleCustomDateRange());
        $('#applyCustomDate').on('click', () => this.applyCustomDateRange());
        $('.export-btn').on('click', (e) => this.exportData($(e.target).data('type')));

        // Order events
        $('#addNewBill').on('click', () => this.addNewBill());
        $('#itemSearch').on('input', (e) => this.searchItems($(e.target).val()));
        $(document).on('click', '.search-result-item', (e) => this.addItemToBill(e));
        $(document).on('click', '.remove-item', (e) => this.removeItemFromBill(e));
        $(document).on('change', '.quantity-input', (e) => this.updateItemQuantity(e));
        $(document).on('click', '.close-tab', (e) => this.closeBillTab(e));
        $('#checkoutBtn').on('click', () => this.showCheckoutModal());

        // Menu events
        $('#saveProduct').on('click', () => this.saveProduct());
        $('#saveCombo').on('click', () => this.saveCombo());
        $(document).on('click', '.edit-product', (e) => this.editProduct(e));
        $(document).on('click', '.delete-product', (e) => this.deleteProduct(e));
        $(document).on('click', '.edit-combo', (e) => this.editCombo(e));
        $(document).on('click', '.delete-combo', (e) => this.deleteCombo(e));

        // Offer events
        $('#saveOffer').on('click', () => this.saveOffer());
        $(document).on('click', '.edit-offer', (e) => this.editOffer(e));
        $(document).on('click', '.delete-offer', (e) => this.deleteOffer(e));
        $(document).on('click', '.toggle-offer', (e) => this.toggleOffer(e));

        // Checkout events
        $('#processPayment').on('click', () => this.processPayment());
        $('#quickDiscount').on('change', () => this.updateCheckoutSummary());
        $(document).on('change', '.offer-checkbox', () => this.updateCheckoutSummary());

        // Hide search results when clicking outside
        $(document).on('click', (e) => {
            if (!$(e.target).closest('#itemSearch, #searchResults').length) {
                $('#searchResults').hide();
            }
        });
    }

    // Section Management
    showSection(section) {
        $('.content-section').removeClass('active');
        $(`#${section}-section`).addClass('active');
        this.currentSection = section;

        // Load section-specific data
        if (section === 'dashboard') {
            this.loadDashboard();
        } else if (section === 'menu') {
            this.loadMenuData();
        } else if (section === 'offers') {
            this.loadOffers();
        } else if (section === 'orders') {
            this.loadOrdersData();
        }
    }

    // Mobile Sidebar Management
    openSidebar() {
        $('#mobileSidebar').addClass('open');
        $('#sidebarOverlay').addClass('active');
        $('body').addClass('sidebar-open');
    }

    closeSidebar() {
        $('#mobileSidebar').removeClass('open');
        $('#sidebarOverlay').removeClass('active');
        $('body').removeClass('sidebar-open');
    }

    navigateToSection(section) {
        // Update sidebar active state
        $('.sidebar-item').removeClass('active');
        $(`.sidebar-item[data-section="${section}"]`).addClass('active');
        
        // Show the section using existing showSection method
        this.showSection(section);
    }

    // Mobile Sidebar Management
    openSidebar() {
        console.log('Opening sidebar...');
        $('#mobileSidebar').addClass('open');
        $('#sidebarOverlay').addClass('active');
        $('body').addClass('sidebar-open');
    }

    closeSidebar() {
        console.log('Closing sidebar...');
        $('#mobileSidebar').removeClass('open');
        $('#sidebarOverlay').removeClass('active');
        $('body').removeClass('sidebar-open');
    }

    navigateToSection(section) {
        // Update sidebar active state
        $('.sidebar-item').removeClass('active');
        $(`.sidebar-item[data-section="${section}"]`).addClass('active');
        
        // Show the section using existing showSection method
        this.showSection(section);
    }

    // Dashboard Functions
    setDateFilter(filter) {
        this.currentDateFilter = filter;
        $('.date-filter').removeClass('active');
        $(`.date-filter[data-filter="${filter}"]`).addClass('active');
        $('#customDateRange').collapse('hide');
        this.loadDashboard();
    }

    toggleCustomDateRange() {
        $('#customDateRange').collapse('toggle');
    }

    applyCustomDateRange() {
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        
        if (!startDate || !endDate) {
            this.showAlert('Please select both start and end dates', 'warning');
            return;
        }

        this.currentDateFilter = 'custom';
        this.customStartDate = startDate;
        this.customEndDate = endDate;
        
        $('.date-filter').removeClass('active');
        $('#customDateBtn').addClass('active');
        $('#customDateRange').collapse('hide');
        
        this.loadDashboard();
    }

    setDateDefaults() {
        const today = new Date().toISOString().split('T')[0];
        $('#startDate').val(today);
        $('#endDate').val(today);
    }

    async loadDashboard() {
        try {
            const params = this.getDateParams();
            const response = await $.get('api/dashboard.php', params);
            
            if (response.success) {
                this.updateDashboardStats(response.data);
                this.updateCharts(response.data);
                this.updateTopItems(response.data);
            } else {
                this.showAlert('Failed to load dashboard data', 'error');
            }
        } catch (error) {
            console.error('Dashboard load error:', error);
            this.showAlert('Failed to load dashboard data', 'error');
        }
    }

    getDateParams() {
        const params = { filter: this.currentDateFilter };
        
        if (this.currentDateFilter === 'custom') {
            params.start_date = this.customStartDate;
            params.end_date = this.customEndDate;
        }
        
        return params;
    }

    updateDashboardStats(data) {
        const stats = data.stats || {};
        $('#totalSales').text('₹' + this.formatNumber(stats.total_revenue || 0));
        $('#totalProfit').text('₹' + this.formatNumber(stats.total_revenue * 0.3 || 0)); // Assuming 30% profit margin
        $('#totalOrders').text(stats.total_orders || 0);
        $('#avgOrderValue').text('₹' + this.formatNumber(stats.avg_order_value || 0));
        
        // Update recent orders
        this.updateRecentOrders(data.recent_orders || []);
        
        // Update low stock items
        this.updateLowStock(data.low_stock || []);
    }

    updateCharts(data) {
        this.updateSalesChart(data.sales_chart || []);
        this.updatePaymentMethodChart(data.recent_orders || []);
    }

    updateSalesChart(salesData) {
        const ctx = document.getElementById('salesChart');
        if (!ctx) return;

        if (this.charts.sales) {
            this.charts.sales.destroy();
        }

        const labels = salesData.map(item => item.period);
        const revenues = salesData.map(item => parseFloat(item.revenue || 0));
        const orders = salesData.map(item => parseInt(item.order_count || 0));

        this.charts.sales = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: revenues,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.1,
                    yAxisID: 'y'
                }, {
                    label: 'Orders',
                    data: orders,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    tension: 0.1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        display: true,
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        ticks: {
                            callback: function(value) {
                                return '₹' + value.toFixed(0);
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                        ticks: {
                            callback: function(value) {
                                return value + ' orders';
                            }
                        }
                    }
                }
            }
        });
    }

    updatePaymentMethodChart(ordersData) {
        const ctx = document.getElementById('paymentChart');
        if (!ctx) return;

        if (this.charts.payment) {
            this.charts.payment.destroy();
        }

        // Group orders by payment method
        const paymentGroups = {};
        ordersData.forEach(order => {
            const method = order.payment_method || 'Cash';
            const amount = parseFloat(order.final_amount || 0);
            
            if (paymentGroups[method]) {
                paymentGroups[method] += amount;
            } else {
                paymentGroups[method] = amount;
            }
        });

        const labels = Object.keys(paymentGroups);
        const data = Object.values(paymentGroups);

        this.charts.payment = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        '#0d6efd',
                        '#198754',
                        '#ffc107',
                        '#dc3545',
                        '#6f42c1',
                        '#20c997',
                        '#fd7e14'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.raw / total) * 100).toFixed(1);
                                return context.label + ': ₹' + context.raw.toFixed(2) + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    updateRecentOrders(orders) {
        const container = $('#recentOrders');
        if (!orders || orders.length === 0) {
            container.html('<p class="text-muted">No recent orders</p>');
            return;
        }

        const html = orders.map(order => `
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <div>
                    <div class="fw-bold">#${order.order_number || order.id}</div>
                    <div class="text-muted small">${order.customer_name || 'Walk-in Customer'}</div>
                </div>
                <div class="text-end">
                    <div class="fw-bold">₹${parseFloat(order.final_amount || 0).toFixed(2)}</div>
                    <div class="text-muted small">${order.payment_method || 'Cash'}</div>
                </div>
            </div>
        `).join('');

        container.html(html);
    }

    updateLowStock(items) {
        const container = $('#lowStock');
        if (!items || items.length === 0) {
            container.html('<p class="text-muted">All items are well stocked</p>');
            return;
        }

        const html = items.map(item => `
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <div>
                    <div class="fw-bold">${item.name}</div>
                    <div class="text-muted small">Min: ${item.min_stock_level || 10}</div>
                </div>
                <div class="text-end">
                    <span class="badge bg-${item.stock_quantity === 0 ? 'danger' : 'warning'}">${item.stock_quantity}</span>
                </div>
            </div>
        `).join('');

        container.html(html);
    }

    updateTopItems(data) {
        this.updateTopProducts(data.top_products || []);
    }

    updateTopProducts(products) {
        const container = $('#topProducts');
        if (products.length === 0) {
            container.html('<p class="text-muted">No sales data available</p>');
            return;
        }

        const html = products.map(product => `
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <div>
                    <div class="fw-bold">${product.name}</div>
                    <div class="text-muted small">₹${parseFloat(product.total_revenue || 0).toFixed(2)}</div>
                </div>
                <div class="text-end">
                    <span class="badge bg-primary">${product.total_sold || 0}</span>
                </div>
            </div>
        `).join('');

        container.html(html);
    }

    updateTopCombos(combos) {
        const container = $('#topCombos');
        if (combos.length === 0) {
            container.html('<p class="text-muted">No sales data available</p>');
            return;
        }

        const html = combos.map(combo => `
            <div class="top-item">
                <div class="top-item-name">${combo.name}</div>
                <div class="top-item-count">${combo.quantity}</div>
            </div>
        `).join('');

        container.html(html);
    }

    // Order Management Functions
    initializeBills() {
        this.bills[1] = {
            items: [],
            subtotal: 0,
            discount: 0,
            total: 0
        };
    }

    addNewBill() {
        this.billCounter++;
        const newBillId = this.billCounter;
        
        this.bills[newBillId] = {
            items: [],
            subtotal: 0,
            discount: 0,
            total: 0
        };

        // Add new tab
        const tabHtml = `
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="order-${newBillId}-tab" data-bs-toggle="tab" 
                        data-bs-target="#order-${newBillId}" type="button" role="tab">
                    Bill #${newBillId} <span class="badge bg-secondary ms-1" id="order-${newBillId}-count">0</span>
                    <button class="btn btn-sm btn-outline-danger ms-1 close-tab" data-bill="${newBillId}">
                        <i class="fas fa-times"></i>
                    </button>
                </button>
            </li>
        `;
        $('#orderTabs').append(tabHtml);

        // Add tab content
        const contentHtml = `
            <div class="tab-pane fade" id="order-${newBillId}" role="tabpanel">
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
        `;
        $('#orderTabsContent').append(contentHtml);

        // Switch to new tab
        $(`#order-${newBillId}-tab`).tab('show');
        this.currentBillTab = newBillId;
    }

    closeBillTab(e) {
        e.stopPropagation();
        const billId = $(e.target).closest('.close-tab').data('bill');
        
        if (Object.keys(this.bills).length === 1) {
            this.showAlert('Cannot close the last bill tab', 'warning');
            return;
        }

        // Remove from bills object
        delete this.bills[billId];

        // Remove tab and content
        $(`#order-${billId}-tab`).closest('li').remove();
        $(`#order-${billId}`).remove();

        // Switch to first available tab
        const firstTab = $('#orderTabs .nav-link').first();
        if (firstTab.length) {
            firstTab.tab('show');
            this.currentBillTab = parseInt(firstTab.attr('id').match(/order-(\d+)-tab/)[1]);
        }

        this.updateBillSummary();
    }

    async searchItems(query) {
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }

        if (query.length < 2) {
            $('#searchResults').hide();
            return;
        }

        this.searchTimeout = setTimeout(async () => {
            try {
                const response = await $.get('api/search.php', { query: query });
                
                if (response.success) {
                    this.displaySearchResults(response.data);
                }
            } catch (error) {
                console.error('Search error:', error);
            }
        }, 300);
    }

    displaySearchResults(items) {
        if (items.length === 0) {
            $('#searchResults').hide();
            return;
        }

        const html = items.map(item => `
            <div class="list-group-item search-result-item" 
                 data-id="${item.id}" data-type="${item.type}" 
                 data-name="${item.name}" data-price="${item.price}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${item.name}</strong>
                        <span class="badge item-type-${item.type} ms-2">${item.type}</span>
                    </div>
                    <span class="item-price">₹${item.price}</span>
                </div>
            </div>
        `).join('');

        $('#searchResults').html(html).show();
    }

    addItemToBill(e) {
        const $item = $(e.currentTarget);
        const itemData = {
            id: $item.data('id'),
            type: $item.data('type'),
            name: $item.data('name'),
            price: parseFloat($item.data('price')),
            quantity: 1
        };

        const bill = this.bills[this.currentBillTab];
        const existingItem = bill.items.find(item => 
            item.id === itemData.id && item.type === itemData.type
        );

        if (existingItem) {
            existingItem.quantity++;
        } else {
            bill.items.push(itemData);
        }

        this.updateBillDisplay();
        this.updateBillSummary();
        $('#itemSearch').val('');
        $('#searchResults').hide();
    }

    removeItemFromBill(e) {
        const $row = $(e.target).closest('tr');
        const itemId = $row.data('item-id');
        const itemType = $row.data('item-type');

        const bill = this.bills[this.currentBillTab];
        bill.items = bill.items.filter(item => 
            !(item.id == itemId && item.type === itemType)
        );

        this.updateBillDisplay();
        this.updateBillSummary();
    }

    updateItemQuantity(e) {
        const $input = $(e.target);
        const $row = $input.closest('tr');
        const itemId = $row.data('item-id');
        const itemType = $row.data('item-type');
        const newQuantity = parseInt($input.val()) || 1;

        const bill = this.bills[this.currentBillTab];
        const item = bill.items.find(item => 
            item.id == itemId && item.type === itemType
        );

        if (item) {
            item.quantity = Math.max(1, newQuantity);
            $input.val(item.quantity);
            this.updateBillDisplay();
            this.updateBillSummary();
        }
    }

    updateBillDisplay() {
        const bill = this.bills[this.currentBillTab];
        const $tbody = $(`#order-${this.currentBillTab} .order-items`);

        if (bill.items.length === 0) {
            $tbody.html(`
                <tr class="no-items">
                    <td colspan="5" class="text-center text-muted">No items added yet</td>
                </tr>
            `);
        } else {
            const html = bill.items.map(item => `
                <tr class="order-item-row" data-item-id="${item.id}" data-item-type="${item.type}">
                    <td>
                        <strong>${item.name}</strong>
                        <span class="badge item-type-${item.type} ms-2">${item.type}</span>
                    </td>
                    <td class="price-display">₹${item.price.toFixed(2)}</td>
                    <td>
                        <div class="quantity-controls">
                            <input type="number" class="form-control quantity-input" 
                                   value="${item.quantity}" min="1">
                        </div>
                    </td>
                    <td class="price-display">₹${(item.price * item.quantity).toFixed(2)}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-danger remove-item">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
            $tbody.html(html);
        }

        // Update tab badge
        $(`#order-${this.currentBillTab}-count`).text(bill.items.length);
    }

    updateBillSummary() {
        const bill = this.bills[this.currentBillTab];
        let subtotal = 0;
        let discount = 0;

        // Calculate subtotal
        bill.items.forEach(item => {
            subtotal += item.price * item.quantity;
        });

        // Apply quick discount
        if ($('#quickDiscount').is(':checked')) {
            discount += subtotal * 0.1;
        }

        // Apply offer discounts
        $('.offer-checkbox:checked').each((index, checkbox) => {
            const offerDiscount = parseFloat($(checkbox).data('discount')) || 0;
            discount += subtotal * (offerDiscount / 100);
        });

        const total = subtotal - discount;

        bill.subtotal = subtotal;
        bill.discount = discount;
        bill.total = total;

        $('#billSubtotal').text('₹' + subtotal.toFixed(2));
        $('#billDiscount').text('₹' + discount.toFixed(2));
        $('#billTotal').text('₹' + total.toFixed(2));

        // Enable/disable checkout button
        $('#checkoutBtn').prop('disabled', bill.items.length === 0);
    }

    showCheckoutModal() {
        const bill = this.bills[this.currentBillTab];
        if (bill.items.length === 0) return;

        // Load checkout offers
        this.loadCheckoutOffers();
        
        // Set modal values
        this.updateCheckoutSummary();

        $('#checkoutModal').modal('show');
    }

    async loadCheckoutOffers() {
        try {
            const response = await $.get('api/offers.php', { checkout_offers: true });
            if (response.success) {
                const offersHtml = response.data.map(offer => `
                    <div class="form-check">
                        <input class="form-check-input offer-checkbox" type="checkbox" 
                               id="offer-${offer.id}" 
                               data-offer-id="${offer.id}"
                               data-discount="${offer.discount_percent}"
                               data-discount-type="${offer.discount_type}"
                               data-apply-to-all="${offer.apply_to_all}"
                               data-applicable-items='${JSON.stringify(offer.applicable_items || [])}'>
                        <label class="form-check-label" for="offer-${offer.id}">
                            ${offer.name} (${offer.discount_percent}${offer.discount_type === 'fixed' ? '₹' : '%'} off)
                        </label>
                    </div>
                `).join('');
                
                if (offersHtml) {
                    $('#activeOffers').html(offersHtml);
                } else {
                    $('#activeOffers').html('<div class="text-muted">No active offers available</div>');
                }
                
                // Bind offer checkbox events
                $('.offer-checkbox').on('change', () => this.updateCheckoutSummary());
            }
        } catch (error) {
            console.error('Error loading checkout offers:', error);
            $('#activeOffers').html('<div class="text-danger">Error loading offers</div>');
        }
    }

    updateCheckoutSummary() {
        const bill = this.bills[this.currentBillTab];
        let subtotal = bill.subtotal;
        let totalDiscount = 0;

        // Calculate quick discount
        if ($('#quickDiscount').is(':checked')) {
            totalDiscount += subtotal * 0.10; // 10% quick discount
        }

        // Calculate offer discounts
        $('.offer-checkbox:checked').each((index, checkbox) => {
            const $checkbox = $(checkbox);
            const discountValue = parseFloat($checkbox.data('discount'));
            const discountType = $checkbox.data('discount-type');
            const applyToAll = $checkbox.data('apply-to-all');
            const applicableItems = $checkbox.data('applicable-items') || [];

            let offerDiscount = 0;
            
            if (applyToAll) {
                // Apply to entire bill
                if (discountType === 'percentage') {
                    offerDiscount = subtotal * (discountValue / 100);
                } else {
                    offerDiscount = discountValue;
                }
            } else {
                // Apply to specific items only
                bill.items.forEach(item => {
                    if (applicableItems.includes(item.id.toString()) || 
                        applicableItems.includes(item.name)) {
                        if (discountType === 'percentage') {
                            offerDiscount += item.total * (discountValue / 100);
                        } else {
                            offerDiscount += Math.min(discountValue, item.total);
                        }
                    }
                });
            }
            
            totalDiscount += offerDiscount;
        });

        // Ensure discount doesn't exceed subtotal
        totalDiscount = Math.min(totalDiscount, subtotal);
        
        const finalTotal = Math.max(0, subtotal - totalDiscount);
        
        // Update checkout modal display
        $('#checkoutSubtotal').text('₹' + subtotal.toFixed(2));
        $('#checkoutDiscount').text('₹' + totalDiscount.toFixed(2));
        $('#checkoutTotal').text('₹' + finalTotal.toFixed(2));
        
        // Also update the main bill summary
        bill.discount = totalDiscount;
        bill.total = finalTotal;
        this.updateBillSummary();
    }

    async processPayment() {
        const bill = this.bills[this.currentBillTab];
        
        if (!bill) {
            this.showAlert('No active bill found. Please add items to cart.', 'warning');
            return;
        }
        
        if (!bill.items || bill.items.length === 0) {
            this.showAlert('Please add items to the bill before checkout.', 'warning');
            return;
        }
        
        const customerName = $('#customerName').val();
        const customerMobile = $('#customerMobile').val();
        const paymentMethod = $('#paymentMethod').val();

        if (!paymentMethod) {
            this.showAlert('Please select a payment method', 'warning');
            return;
        }

        const orderData = {
            items: bill.items,
            customer_name: customerName,
            customer_mobile: customerMobile,
            subtotal: bill.subtotal,
            discount_amount: bill.discount,
            final_amount: bill.total,
            payment_type: paymentMethod
        };

        try {
            const response = await $.ajax({
                url: 'api/orders.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(orderData),
                dataType: 'json'
            });
            
            if (response.success) {
                this.showAlert('Order saved successfully!', 'success');
                $('#checkoutModal').modal('hide');
                
                // Clear current bill
                this.bills[this.currentBillTab] = {
                    items: [],
                    subtotal: 0,
                    discount: 0,
                    total: 0
                };
                
                this.updateBillDisplay();
                this.updateBillSummary();
                
                // Refresh dashboard if on dashboard
                if (this.currentSection === 'dashboard') {
                    this.loadDashboard();
                }
            } else {
                this.showAlert('Failed to save order: ' + response.message, 'error');
            }
        } catch (error) {
            console.error('Save order error:', error);
            this.showAlert('Failed to save order: ' + error.responseText, 'error');
        }
    }

    // Menu Management Functions
    async loadMenuData() {
        this.loadProducts();
        this.loadCombos();
        this.populateComboProductsDropdown();
        this.populateOfferItemsDropdown();
    }

    async populateComboProductsDropdown() {
        try {
            const response = await $.get('api/products.php', { active_only: true });
            if (response.success) {
                const select = $('#comboProducts');
                select.empty();
                
                response.data.forEach(product => {
                    select.append(`<option value="${product.id}" data-cost="${product.cost || 0}">${product.name} (₹${product.price})</option>`);
                });
                
                // Add event listener to calculate cost when products are selected
                select.on('change', () => this.calculateComboCost());
            }
        } catch (error) {
            console.error('Error loading products for combo dropdown:', error);
        }
    }

    async populateOfferItemsDropdown() {
        try {
            // Load products and combos for offer items
            const [productsResponse, combosResponse] = await Promise.all([
                $.get('api/products.php', { active_only: true }),
                $.get('api/combos.php', { active_only: true })
            ]);

            const select = $('#offerItems');
            select.empty();

            if (productsResponse.success) {
                const productGroup = $('<optgroup label="Products"></optgroup>');
                productsResponse.data.forEach(product => {
                    productGroup.append(`<option value="product_${product.id}">${product.name} (₹${product.price})</option>`);
                });
                select.append(productGroup);
            }

            if (combosResponse.success) {
                const comboGroup = $('<optgroup label="Combos"></optgroup>');
                combosResponse.data.forEach(combo => {
                    comboGroup.append(`<option value="combo_${combo.id}">${combo.name} (₹${combo.price})</option>`);
                });
                select.append(comboGroup);
            }
        } catch (error) {
            console.error('Error loading items for offer dropdown:', error);
        }
    }

    calculateComboCost() {
        const selectedProducts = $('#comboProducts').val() || [];
        let totalCost = 0;
        
        selectedProducts.forEach(productId => {
            const option = $(`#comboProducts option[value="${productId}"]`);
            const cost = parseFloat(option.data('cost')) || 0;
            totalCost += cost;
        });
        
        $('#comboCost').val(totalCost.toFixed(2));
    }

    async loadProducts() {
        try {
            const response = await $.get('api/products.php');
            if (response.success) {
                this.displayProducts(response.data);
            }
        } catch (error) {
            console.error('Error loading products:', error);
        }
    }

    displayProducts(products) {
        const tbody = $('#productsTable');
        
        if (products.length === 0) {
            tbody.html('<tr><td colspan="5" class="text-center text-muted">No products found</td></tr>');
            return;
        }

        const html = products.map(product => `
            <tr>
                <td>${product.name}</td>
                <td class="price-display">₹${parseFloat(product.price).toFixed(2)}</td>
                <td class="cost-display">₹${parseFloat(product.cost || 0).toFixed(2)}</td>
                <td class="profit-display">₹${(parseFloat(product.price) - parseFloat(product.cost || 0)).toFixed(2)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-outline-primary edit-product" data-id="${product.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-product" data-id="${product.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');

        tbody.html(html);
    }

    async loadCombos() {
        try {
            const response = await $.get('api/combos.php');
            if (response.success) {
                this.displayCombos(response.data);
            }
        } catch (error) {
            console.error('Error loading combos:', error);
        }
    }

    displayCombos(combos) {
        const tbody = $('#combosTable');
        
        if (combos.length === 0) {
            tbody.html('<tr><td colspan="5" class="text-center text-muted">No combos found</td></tr>');
            return;
        }

        const html = combos.map(combo => `
            <tr>
                <td>${combo.name}</td>
                <td class="price-display">₹${parseFloat(combo.price).toFixed(2)}</td>
                <td class="cost-display">₹${parseFloat(combo.cost || 0).toFixed(2)}</td>
                <td class="profit-display">₹${(parseFloat(combo.price) - parseFloat(combo.cost || 0)).toFixed(2)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-outline-primary edit-combo" data-id="${combo.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-combo" data-id="${combo.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');

        tbody.html(html);
    }

    async saveProduct() {
        const name = $('#productName').val().trim();
        const price = $('#productPrice').val().trim();
        const cost = $('#productCost').val().trim();
        const stock = $('#productStock').val().trim();
        const description = $('#productDescription').val().trim();
        const isActive = $('#productActive').is(':checked');
        const productId = $('#productId').val();

        // Validation
        if (!name || !price || !cost) {
            this.showAlert('Please fill all required fields', 'warning');
            return;
        }

        if (parseFloat(price) <= 0 || parseFloat(cost) < 0) {
            this.showAlert('Please enter valid price and cost values', 'warning');
            return;
        }

        const formData = {
            name: name,
            price: parseFloat(price),
            cost: parseFloat(cost),
            stock_quantity: parseInt(stock) || 0,
            description: description,
            is_active: isActive
        };

        if (productId) {
            formData.id = parseInt(productId);
        }

        try {
            const method = productId ? 'PUT' : 'POST';
            
            const response = await $.ajax({
                url: 'api/products.php',
                method: method,
                contentType: 'application/json',
                data: JSON.stringify(formData),
                dataType: 'json'
            });
            
            if (response.success) {
                this.showAlert(`Product ${productId ? 'updated' : 'created'} successfully!`, 'success');
                $('#productModal').modal('hide');
                this.loadProducts();
                this.resetProductForm();
            } else {
                let errorMessage = 'Error saving product';
                if (response.message) {
                    errorMessage += ': ' + response.message;
                } else if (response.errors && Array.isArray(response.errors)) {
                    errorMessage += ': ' + response.errors.join(', ');
                }
                this.showAlert(errorMessage, 'error');
            }
        } catch (error) {
            console.error('Save product error:', error);
            let errorMessage = 'Error saving product';
            if (error.responseJSON && error.responseJSON.message) {
                errorMessage += ': ' + error.responseJSON.message;
            }
            this.showAlert(errorMessage, 'error');
        }
    }

    async saveCombo() {
        const name = $('#comboName').val().trim();
        const price = $('#comboPrice').val().trim();
        const cost = $('#comboCost').val().trim();
        const description = $('#comboDescription').val().trim();
        const products = $('#comboProducts').val() || [];
        const isActive = $('#comboActive').is(':checked');
        const comboId = $('#comboId').val();

        // Validation
        if (!name || !price || products.length === 0) {
            this.showAlert('Please fill all required fields and select at least one product', 'warning');
            return;
        }

        if (parseFloat(price) <= 0) {
            this.showAlert('Please enter a valid price', 'warning');
            return;
        }

        const formData = {
            name: name,
            price: parseFloat(price),
            cost: parseFloat(cost) || 0,
            description: description,
            products: products,
            is_active: isActive
        };

        if (comboId) {
            formData.id = parseInt(comboId);
        }

        try {
            const method = comboId ? 'PUT' : 'POST';
            
            const response = await $.ajax({
                url: 'api/combos.php',
                method: method,
                contentType: 'application/json',
                data: JSON.stringify(formData),
                dataType: 'json'
            });
            
            if (response.success) {
                this.showAlert(`Combo ${comboId ? 'updated' : 'created'} successfully!`, 'success');
                $('#comboModal').modal('hide');
                this.loadCombos();
                this.resetComboForm();
            } else {
                let errorMessage = 'Error saving combo';
                if (response.message) {
                    errorMessage += ': ' + response.message;
                } else if (response.errors && Array.isArray(response.errors)) {
                    errorMessage += ': ' + response.errors.join(', ');
                }
                this.showAlert(errorMessage, 'error');
            }
        } catch (error) {
            console.error('Save combo error:', error);
            let errorMessage = 'Error saving combo';
            if (error.responseJSON && error.responseJSON.message) {
                errorMessage += ': ' + error.responseJSON.message;
            }
            this.showAlert(errorMessage, 'error');
        }
    }

    resetProductForm() {
        $('#productForm')[0].reset();
        $('#productId').val('');
    }

    resetComboForm() {
        $('#comboForm')[0].reset();
        $('#comboId').val('');
    }

    // Product Edit and Delete Functions
    async editProduct(e) {
        const productId = $(e.target).closest('button').data('id');
        
        try {
            // Get product data from the API
            const response = await $.get(`api/products.php?id=${productId}`);
            
            if (response.success && response.data.length > 0) {
                const product = response.data[0];
                
                // Populate the form
                $('#productId').val(product.id);
                $('#productName').val(product.name);
                $('#productPrice').val(product.price);
                $('#productCost').val(product.cost);
                $('#productStock').val(product.stock_quantity || 0);
                $('#productDescription').val(product.description || '');
                $('#productActive').prop('checked', product.is_active);
                
                // Show the modal
                $('#productModal').modal('show');
            } else {
                this.showAlert('Product not found', 'error');
            }
        } catch (error) {
            console.error('Edit product error:', error);
            this.showAlert('Error loading product data', 'error');
        }
    }

    async deleteProduct(e) {
        const productId = $(e.target).closest('button').data('id');
        
        if (!confirm('Are you sure you want to delete this product?')) {
            return;
        }
        
        try {
            const response = await $.ajax({
                url: 'api/products.php',
                method: 'DELETE',
                contentType: 'application/json',
                data: JSON.stringify({ id: productId })
            });
            
            if (response.success) {
                this.showAlert('Product deleted successfully!', 'success');
                this.loadProducts();
            } else {
                this.showAlert('Error deleting product: ' + response.message, 'error');
            }
        } catch (error) {
            console.error('Delete product error:', error);
            this.showAlert('Error deleting product', 'error');
        }
    }

    // Combo Edit and Delete Functions  
    async editCombo(e) {
        const comboId = $(e.target).closest('button').data('id');
        
        try {
            const response = await $.get(`api/combos.php?id=${comboId}`);
            
            if (response.success && response.data.length > 0) {
                const combo = response.data[0];
                
                $('#comboId').val(combo.id);
                $('#comboName').val(combo.name);
                $('#comboPrice').val(combo.price);
                $('#comboDescription').val(combo.description || '');
                $('#comboActive').prop('checked', combo.is_active);
                
                // Set selected products
                if (combo.product_ids) {
                    const productIds = combo.product_ids.split(',');
                    $('#comboProducts').val(productIds);
                }
                
                // Calculate cost
                this.calculateComboCost();
                
                $('#comboModal').modal('show');
            } else {
                this.showAlert('Combo not found', 'error');
            }
        } catch (error) {
            console.error('Edit combo error:', error);
            this.showAlert('Error loading combo data', 'error');
        }
    }

    async deleteCombo(e) {
        const comboId = $(e.target).closest('button').data('id');
        
        if (!confirm('Are you sure you want to delete this combo?')) {
            return;
        }
        
        try {
            const response = await $.ajax({
                url: 'api/combos.php',
                method: 'DELETE',
                contentType: 'application/json',
                data: JSON.stringify({ id: comboId })
            });
            
            if (response.success) {
                this.showAlert('Combo deleted successfully!', 'success');
                this.loadCombos();
            } else {
                this.showAlert('Error deleting combo: ' + response.message, 'error');
            }
        } catch (error) {
            console.error('Delete combo error:', error);
            this.showAlert('Error deleting combo', 'error');
        }
    }

    // Offer Edit, Delete and Toggle Functions
    async editOffer(e) {
        const offerId = $(e.target).closest('button').data('id');
        
        try {
            const response = await $.get(`api/offers.php?id=${offerId}`);
            
            if (response.success && response.data.length > 0) {
                const offer = response.data[0];
                
                $('#offerId').val(offer.id);
                $('#offerName').val(offer.name);
                $('#offerStartDate').val(offer.start_date);
                $('#offerEndDate').val(offer.end_date);
                $('#offerStartTime').val(offer.start_time);
                $('#offerEndTime').val(offer.end_time);
                $('#offerDiscount').val(offer.discount_percent);
                $('#offerApplyAll').prop('checked', offer.apply_to_all);
                
                if (offer.applicable_items) {
                    $('#offerItems').val(offer.applicable_items.split(','));
                }
                
                $('#offerModal').modal('show');
            } else {
                this.showAlert('Offer not found', 'error');
            }
        } catch (error) {
            console.error('Edit offer error:', error);
            this.showAlert('Error loading offer data', 'error');
        }
    }

    async deleteOffer(e) {
        const offerId = $(e.target).closest('button').data('id');
        
        if (!confirm('Are you sure you want to delete this offer?')) {
            return;
        }
        
        try {
            const response = await $.ajax({
                url: 'api/offers.php',
                method: 'DELETE',
                contentType: 'application/json',
                data: JSON.stringify({ id: offerId })
            });
            
            if (response.success) {
                this.showAlert('Offer deleted successfully!', 'success');
                this.loadOffers();
            } else {
                this.showAlert('Error deleting offer: ' + response.message, 'error');
            }
        } catch (error) {
            console.error('Delete offer error:', error);
            this.showAlert('Error deleting offer', 'error');
        }
    }

    async toggleOffer(e) {
        const button = $(e.target).closest('button');
        const offerId = button.data('id');
        const isActive = button.data('active');
        
        try {
            const response = await $.ajax({
                url: 'api/offers.php',
                method: 'PUT',
                contentType: 'application/json',
                data: JSON.stringify({ 
                    id: offerId, 
                    is_active: !isActive 
                })
            });
            
            if (response.success) {
                this.showAlert(`Offer ${!isActive ? 'activated' : 'deactivated'} successfully!`, 'success');
                this.loadOffers();
            } else {
                this.showAlert('Error updating offer: ' + response.message, 'error');
            }
        } catch (error) {
            console.error('Toggle offer error:', error);
            this.showAlert('Error updating offer', 'error');
        }
    }

    // Offers Management
    async loadOffers() {
        try {
            const response = await $.get('api/offers.php');
            if (response.success) {
                this.displayOffers(response.data);
            }
        } catch (error) {
            console.error('Error loading offers:', error);
        }
    }

    displayOffers(offers) {
        const tbody = $('#offersTable');
        
        if (offers.length === 0) {
            tbody.html('<tr><td colspan="7" class="text-center text-muted">No offers found</td></tr>');
            return;
        }

        const html = offers.map(offer => `
            <tr>
                <td>${offer.name}</td>
                <td>${offer.start_date} to ${offer.end_date}</td>
                <td>${offer.start_time} - ${offer.end_time}</td>
                <td>${offer.discount_percent}%</td>
                <td>${offer.apply_to_all ? 'All Items' : 'Specific Items'}</td>
                <td>
                    <span class="badge ${offer.is_active ? 'bg-success' : 'bg-danger'}">
                        ${offer.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-outline-primary edit-offer" data-id="${offer.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-${offer.is_active ? 'warning' : 'success'} toggle-offer" 
                                data-id="${offer.id}" data-active="${offer.is_active}">
                            <i class="fas fa-${offer.is_active ? 'pause' : 'play'}"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-offer" data-id="${offer.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');

        tbody.html(html);
    }

    async saveOffer() {
        const name = $('#offerName').val().trim();
        const startDate = $('#offerStartDate').val().trim();
        const endDate = $('#offerEndDate').val().trim();
        const startTime = $('#offerStartTime').val().trim();
        const endTime = $('#offerEndTime').val().trim();
        const discountPercent = $('#offerDiscount').val().trim();
        const applyToAll = $('#offerApplyAll').is(':checked');
        const offerItems = $('#offerItems').val() || [];
        const offerId = $('#offerId').val();

        // Validation
        if (!name || !startDate || !endDate || !discountPercent) {
            this.showAlert('Please fill all required fields', 'warning');
            return;
        }

        if (parseFloat(discountPercent) <= 0 || parseFloat(discountPercent) > 100) {
            this.showAlert('Discount percentage must be between 1 and 100', 'warning');
            return;
        }

        const formData = {
            name: name,
            start_date: startDate,
            end_date: endDate,
            start_time: startTime,
            end_time: endTime,
            discount_percent: parseFloat(discountPercent),
            apply_to_all: applyToAll,
            applicable_items: applyToAll ? [] : offerItems
        };

        if (offerId) {
            formData.id = offerId;
        }

        try {
            const method = offerId ? 'PUT' : 'POST';
            const response = await $.ajax({
                url: 'api/offers.php',
                method: method,
                contentType: 'application/json',
                data: JSON.stringify(formData)
            });
            
            if (response.success) {
                this.showAlert(`Offer ${offerId ? 'updated' : 'created'} successfully!`, 'success');
                $('#offerModal').modal('hide');
                this.loadOffers();
                this.resetOfferForm();
            } else {
                let errorMessage = 'Error saving offer';
                if (response.message) {
                    errorMessage += ': ' + response.message;
                } else if (response.errors && Array.isArray(response.errors)) {
                    errorMessage += ': ' + response.errors.join(', ');
                }
                this.showAlert(errorMessage, 'error');
            }
        } catch (error) {
            console.error('Save offer error:', error);
            let errorMessage = 'Error saving offer';
            if (error.responseJSON && error.responseJSON.message) {
                errorMessage += ': ' + error.responseJSON.message;
            }
            this.showAlert(errorMessage, 'error');
        }
    }

    resetOfferForm() {
        $('#offerForm')[0].reset();
        $('#offerId').val('');
    }

    // Export Functions
    async exportData(type) {
        try {
            const params = Object.assign({ report_type: type }, this.getDateParams());
            const queryString = $.param(params);
            
            // Create download link
            const downloadUrl = `api/export.php?${queryString}`;
            window.open(downloadUrl, '_blank');
            
            this.showAlert(`Exporting ${type} data...`, 'info');
        } catch (error) {
            console.error('Export error:', error);
            this.showAlert('Export failed', 'error');
        }
    }

    // Utility Functions
    formatNumber(number) {
        return parseFloat(number).toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    showAlert(message, type) {
        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        }[type] || 'alert-info';

        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show alert-floating" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        $('body').append(alertHtml);

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            $('.alert-floating').alert('close');
        }, 5000);
    }

    // Orders Data Management
    async loadOrdersData() {
        try {
            // Load products and combos for search functionality
            await this.loadMenuData();
            
            // Initialize orders section if needed
            this.initializeOrdersSection();
            
            // Load recent orders if we're in the orders section
            await this.loadRecentOrders();
        } catch (error) {
            console.error('Error loading orders data:', error);
            this.showAlert('Failed to load orders data', 'error');
        }
    }

    async loadRecentOrders() {
        try {
            const response = await $.get('api/orders.php', { limit: 10 });
            if (response.success && response.data.length > 0) {
                this.displayRecentOrders(response.data);
            }
        } catch (error) {
            console.error('Error loading recent orders:', error);
        }
    }

    displayRecentOrders(orders) {
        // For now, we'll just log the orders
        // Later we can add a recent orders panel to the UI
        console.log('Recent orders loaded:', orders);
        
        // We could add a recent orders section to the orders page
        // This would show recent orders for reference
    }

    initializeOrdersSection() {
        // Ensure bills are initialized
        if (!this.bills || Object.keys(this.bills).length === 0) {
            this.bills = {};
            this.bills[1] = { items: [], subtotal: 0, discount: 0, total: 0 };
            this.currentBillTab = 1;
            this.billCounter = 1;
        }

        // Update the bill display
        this.updateBillDisplay();
        this.updateBillSummary();

        // Focus on search input
        $('#itemSearch').focus();
    }
}

// Initialize application when document is ready
$(document).ready(() => {
    window.thriveCafe = new ThriveCafe();
});
