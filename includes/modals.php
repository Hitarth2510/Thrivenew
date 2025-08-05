<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-coffee"></i> Add/Edit Product
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="productForm">
                    <input type="hidden" id="productId">
                    <div class="mb-3">
                        <label for="productName" class="form-label">Product Name *</label>
                        <input type="text" class="form-control" id="productName" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="productPrice" class="form-label">Selling Price (₹) *</label>
                                <input type="number" class="form-control" id="productPrice" 
                                       step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="productCost" class="form-label">Making Cost (₹) *</label>
                                <input type="number" class="form-control" id="productCost" 
                                       step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="productStock" class="form-label">Stock Quantity</label>
                        <input type="number" class="form-control" id="productStock" 
                               min="0" value="0">
                    </div>
                    <div class="mb-3">
                        <label for="productDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="productDescription" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="productActive" checked>
                            <label class="form-check-label" for="productActive">Active</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveProduct">Save Product</button>
            </div>
        </div>
    </div>
</div>

<!-- Combo Modal -->
<div class="modal fade" id="comboModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-layer-group"></i> Add/Edit Combo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="comboForm">
                    <input type="hidden" id="comboId">
                    <div class="mb-3">
                        <label for="comboName" class="form-label">Combo Name *</label>
                        <input type="text" class="form-control" id="comboName" required>
                    </div>
                    <div class="mb-3">
                        <label for="comboProducts" class="form-label">Select Products *</label>
                        <select class="form-select" id="comboProducts" multiple required>
                            <!-- Options will be populated by JavaScript -->
                        </select>
                        <div class="form-text">Hold Ctrl/Cmd to select multiple products</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="comboPrice" class="form-label">Combo Price (₹) *</label>
                                <input type="number" class="form-control" id="comboPrice" 
                                       step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="comboCost" class="form-label">Making Cost (₹)</label>
                                <input type="number" class="form-control" id="comboCost" 
                                       step="0.01" min="0" readonly>
                                <div class="form-text">Auto-calculated from selected products</div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="comboDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="comboDescription" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="comboActive" checked>
                            <label class="form-check-label" for="comboActive">Active</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveCombo">Save Combo</button>
            </div>
        </div>
    </div>
</div>

<!-- Offer Modal -->
<div class="modal fade" id="offerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-tags"></i> Add/Edit Offer
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="offerForm">
                    <input type="hidden" id="offerId">
                    <div class="mb-3">
                        <label for="offerName" class="form-label">Offer Name *</label>
                        <input type="text" class="form-control" id="offerName" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="offerStartDate" class="form-label">Start Date *</label>
                                <input type="date" class="form-control" id="offerStartDate" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="offerEndDate" class="form-label">End Date *</label>
                                <input type="date" class="form-control" id="offerEndDate" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="offerStartTime" class="form-label">Start Time *</label>
                                <input type="time" class="form-control" id="offerStartTime" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="offerEndTime" class="form-label">End Time *</label>
                                <input type="time" class="form-control" id="offerEndTime" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="offerDiscount" class="form-label">Discount Percentage (%) *</label>
                        <input type="number" class="form-control" id="offerDiscount" 
                               step="0.01" min="0" max="100" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="offerApplicability" 
                                   id="offerApplyAll" value="all" checked>
                            <label class="form-check-label" for="offerApplyAll">
                                Apply to Entire Bill
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="offerApplicability" 
                                   id="offerApplySpecific" value="specific">
                            <label class="form-check-label" for="offerApplySpecific">
                                Apply to Specific Items
                            </label>
                        </div>
                    </div>
                    <div class="mb-3" id="offerItemsDiv" style="display: none;">
                        <label for="offerItems" class="form-label">Select Items</label>
                        <select class="form-select" id="offerItems" multiple>
                            <!-- Options will be populated by JavaScript -->
                        </select>
                        <div class="form-text">Hold Ctrl/Cmd to select multiple items</div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="offerActive" checked>
                            <label class="form-check-label" for="offerActive">Active</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveOffer">Save Offer</button>
            </div>
        </div>
    </div>
</div>

<!-- Checkout Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-credit-card"></i> Checkout
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="checkoutForm">
                    <div class="mb-3">
                        <label for="customerName" class="form-label">Customer Name</label>
                        <input type="text" class="form-control" id="customerName">
                    </div>
                    <div class="mb-3">
                        <label for="customerMobile" class="form-label">Customer Mobile</label>
                        <input type="tel" class="form-control" id="customerMobile" 
                               pattern="[0-9]{10}" maxlength="10">
                        <div class="form-text">10-digit mobile number (optional)</div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="quickDiscount">
                            <label class="form-check-label" for="quickDiscount">
                                Apply Quick Discount (10% off)
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Active Offers</label>
                        <div id="activeOffers">
                            <!-- Offers will be populated by JavaScript -->
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="paymentMethod" class="form-label">Payment Method *</label>
                        <select class="form-select" id="paymentMethod" required>
                            <option value="">Select Payment Method</option>
                            <option value="Cash">Cash</option>
                            <option value="Card">Card</option>
                            <option value="UPI">UPI</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <hr>
                    
                    <!-- Bill Summary -->
                    <div class="row mb-2">
                        <div class="col-6">Subtotal:</div>
                        <div class="col-6 text-end" id="checkoutSubtotal">₹0.00</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6">Discount:</div>
                        <div class="col-6 text-end" id="checkoutDiscount">₹0.00</div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-6"><strong>Total Amount:</strong></div>
                        <div class="col-6 text-end"><strong id="checkoutTotal">₹0.00</strong></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="processPayment">
                    <i class="fas fa-check"></i> Process Payment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this item? This action cannot be undone.</p>
                <div id="deleteItemName" class="fw-bold"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
// Modal Event Handlers
$(document).ready(function() {
    // Product modal events
    $('#productModal').on('show.bs.modal', function() {
        loadProductsForCombo();
    });

    // Combo modal events
    $('#comboModal').on('show.bs.modal', function() {
        loadProductsForCombo();
    });

    // Combo products selection change
    $('#comboProducts').on('change', function() {
        calculateComboCost();
    });

    // Offer applicability change
    $('input[name="offerApplicability"]').on('change', function() {
        if ($(this).val() === 'specific') {
            $('#offerItemsDiv').show();
            loadItemsForOffer();
        } else {
            $('#offerItemsDiv').hide();
        }
    });

    // Load products for combo creation
    function loadProductsForCombo() {
        $.get('api/products.php')
            .done(function(response) {
                if (response.success) {
                    const options = response.data.map(product => 
                        `<option value="${product.id}" data-cost="${product.making_cost}">
                            ${product.name} (₹${product.price})
                        </option>`
                    ).join('');
                    $('#comboProducts').html(options);
                }
            });
    }

    // Calculate combo making cost
    function calculateComboCost() {
        let totalCost = 0;
        $('#comboProducts option:selected').each(function() {
            totalCost += parseFloat($(this).data('cost')) || 0;
        });
        $('#comboCost').val(totalCost.toFixed(2));
    }

    // Load items for offer creation
    function loadItemsForOffer() {
        $.when(
            $.get('api/products.php'),
            $.get('api/combos.php')
        ).done(function(productsResponse, combosResponse) {
            let options = '';
            
            if (productsResponse[0].success) {
                options += '<optgroup label="Products">';
                options += productsResponse[0].data.map(product => 
                    `<option value="product-${product.id}">${product.name}</option>`
                ).join('');
                options += '</optgroup>';
            }
            
            if (combosResponse[0].success) {
                options += '<optgroup label="Combos">';
                options += combosResponse[0].data.map(combo => 
                    `<option value="combo-${combo.id}">${combo.name}</option>`
                ).join('');
                options += '</optgroup>';
            }
            
            $('#offerItems').html(options);
        });
    }
});
</script>
