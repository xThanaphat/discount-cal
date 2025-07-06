/**
 * Interactive JavaScript for Discount Calculator
 * Updated for new data structure with items and campaigns selection
 */

// Global variables
let itemCounter = 1;
let activeCampaigns = [];
let cart = [];

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

/**
 * Initialize the application
 */
function initializeApp() {
    setupEventListeners();
    setupRealTimeValidation();
    setupKeyboardShortcuts();
    updateCartTotal();
    setupFormValidation();
    
    // Show welcome message
    setTimeout(() => {
        showNotification('Welcome! Use Quick Tests or build your own cart with items and campaigns.', 'info');
    }, 500);
    
    console.log('🚀 Discount Calculator fully initialized');
    console.log('💡 Available items:', window.availableItems?.length || 0);
    console.log('💡 Available campaigns:', window.availableCampaigns?.length || 0);
}

/**
 * Show/Hide item selector
 */
function showItemSelector() {
    const browser = document.getElementById('itemBrowser');
    if (browser.style.display === 'none') {
        browser.style.display = 'block';
        browser.classList.add('slide-down');
    } else {
        browser.style.display = 'none';
    }
}

/**
 * Show/Hide campaign selector
 */
function showCampaignSelector() {
    const browser = document.getElementById('campaignBrowser');
    if (browser.style.display === 'none') {
        browser.style.display = 'block';
        browser.classList.add('slide-down');
    } else {
        browser.style.display = 'none';
    }
}

/**
 * Add item to cart from selection
 */
function addItemToCart(itemId) {
    const item = window.availableItems.find(i => i.id === itemId);
    if (!item) return;
    
    // Check if item already exists in cart
    const existingItems = document.querySelectorAll('input[name*="[name]"]');
    for (let input of existingItems) {
        if (input.value === item.name) {
            // Increase quantity instead
            const quantityInput = input.closest('.item-row').querySelector('input[name*="[quantity]"]');
            quantityInput.value = parseInt(quantityInput.value) + 1;
            updateItemTotal(input);
            updateCartTotal();
            showNotification(`Increased ${item.name} quantity`, 'success');
            return;
        }
    }
    
    // Add new item
    const cartItems = document.getElementById('cartItems');
    const newItem = createItemRowFromData(itemCounter, item);
    
    cartItems.appendChild(newItem);
    itemCounter++;
    
    // Animate the new item
    newItem.style.opacity = '0';
    newItem.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
        newItem.style.transition = 'all 0.3s ease';
        newItem.style.opacity = '1';
        newItem.style.transform = 'translateY(0)';
    }, 10);
    
    updateCartTotal();
    showNotification(`Added ${item.name} to cart`, 'success');
}

/**
 * Create item row from data
 */
function createItemRowFromData(index, item) {
    const div = document.createElement('div');
    div.className = 'item-row';
    div.setAttribute('data-index', index);
    
    div.innerHTML = `
        <div class="row">
            <div class="col-3">
                <label class="form-label">Item Name</label>
                <input type="text" class="form-control" name="items[${index}][name]" value="${item.name}">
            </div>
            <div class="col-2">
                <label class="form-label">Price (THB)</label>
                <input type="number" class="form-control" name="items[${index}][price]" step="0.01" min="0" value="${item.price}">
            </div>
            <div class="col-2">
                <label class="form-label">Category</label>
                <select class="form-control" name="items[${index}][category]">
                    <option value="Clothing" ${item.category === 'Clothing' ? 'selected' : ''}>Clothing</option>
                    <option value="Accessories" ${item.category === 'Accessories' ? 'selected' : ''}>Accessories</option>
                    <option value="Electronics" ${item.category === 'Electronics' ? 'selected' : ''}>Electronics</option>
                </select>
            </div>
            <div class="col-2">
                <label class="form-label">Quantity</label>
                <input type="number" class="form-control" name="items[${index}][quantity]" min="1" value="${item.quantity || 1}">
            </div>
            <div class="col-2">
                <label class="form-label">Total</label>
                <input type="text" class="form-control item-total" readonly value="${formatCurrency(item.price * (item.quantity || 1))}">
            </div>
            <div class="col-auto">
                <label class="form-label">&nbsp;</label><br>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(this)">×</button>
            </div>
        </div>
    `;
    
    return div;
}

/**
 * Apply campaign
 */
function applyCampaign(campaignId) {
    const campaign = window.availableCampaigns.find(c => c.id === campaignId);
    if (!campaign) return;
    
    // Check for category conflicts
    const existingCampaign = activeCampaigns.find(c => c.category === campaign.category);
    if (existingCampaign) {
        if (confirm(`Replace existing ${campaign.category} campaign "${existingCampaign.name}" with "${campaign.name}"?`)) {
            // Remove existing campaign
            activeCampaigns = activeCampaigns.filter(c => c.id !== existingCampaign.id);
        } else {
            return;
        }
    }
    
    // Add new campaign
    activeCampaigns.push(campaign);
    updateCampaignDisplay();
    showNotification(`Applied campaign: ${campaign.name}`, 'success');
}

/**
 * Remove campaign
 */
function removeCampaign(campaignId) {
    activeCampaigns = activeCampaigns.filter(c => c.id !== campaignId);
    updateCampaignDisplay();
    showNotification('Campaign removed', 'info');
}

/**
 * Update campaign display
 */
function updateCampaignDisplay() {
    const campaignList = document.getElementById('campaignList');
    
    if (activeCampaigns.length === 0) {
        campaignList.innerHTML = '<p class="text-muted">No campaigns selected</p>';
        return;
    }
    
    let html = '';
    activeCampaigns.forEach(campaign => {
        html += `
            <div class="campaign-item">
                <div>
                    <h6>${campaign.name}</h6>
                    <small>${campaign.description}</small>
                    <span class="badge badge-secondary">${campaign.category}</span>
                </div>
                <button type="button" class="campaign-remove" onclick="removeCampaign('${campaign.id}')">×</button>
            </div>
        `;
    });
    
    campaignList.innerHTML = html;
}

/**
 * Run test function
 */
async function runTestFunction(testId) {
    try {
        showCalculateSpinner();
        
        const formData = new FormData();
        formData.append('action', 'run_test_function');
        formData.append('test_id', testId);
        
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            displayTestResults(result.result);
        } else {
            showNotification('Error running test: ' + result.message, 'error');
        }
    } catch (error) {
        showNotification('Error running test: ' + error.message, 'error');
    } finally {
        hideCalculateSpinner();
    }
}

/**
 * Display test results
 */
function displayTestResults(testResult) {
    const resultsDiv = document.getElementById('test-results');
    const contentDiv = document.getElementById('test-result-content');
    
    let html = `
        <div class="test-result-item ${testResult.comparison?.passed ? 'test-passed' : 'test-failed'}">
            <h4>${testResult.test_info.name}</h4>
            <p>${testResult.test_info.description}</p>
            
            <div class="result-summary">
                <h5>Results:</h5>
                <div class="summary-item">
                    <span>Original Price:</span>
                    <span>${testResult.result.formatted.original_price}</span>
                </div>
                <div class="summary-item">
                    <span>Total Discount:</span>
                    <span class="discount-amount">-${testResult.result.formatted.total_discount}</span>
                </div>
                <div class="summary-item">
                    <span>Final Price:</span>
                    <span>${testResult.result.formatted.final_price}</span>
                </div>
            </div>
    `;
    
    if (testResult.comparison) {
        html += `
            <div class="test-status ${testResult.comparison.passed ? 'passed' : 'failed'}">
                ${testResult.comparison.passed ? 'PASSED' : 'FAILED'}
            </div>
        `;
    }
    
    html += '</div>';
    
    contentDiv.innerHTML = html;
    resultsDiv.style.display = 'block';
    
    // Scroll to results
    resultsDiv.scrollIntoView({ behavior: 'smooth' });
}

/**
 * Setup form validation
 */
function setupFormValidation() {
    const form = document.getElementById('discountForm');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Add campaigns to form data
        addCampaignsToForm();
        
        // Submit form
        calculateDiscount();
    });
}

/**
 * Add campaigns to form data
 */
function addCampaignsToForm() {
    // Remove existing campaign inputs
    const existingInputs = document.querySelectorAll('input[name^="campaigns["]');
    existingInputs.forEach(input => input.remove());
    
    // Add current campaigns
    activeCampaigns.forEach((campaign, index) => {
        const form = document.getElementById('discountForm');
        
        // Create hidden inputs for campaign data
        const enabledInput = document.createElement('input');
        enabledInput.type = 'hidden';
        enabledInput.name = `campaigns[${index}][enabled]`;
        enabledInput.value = '1';
        form.appendChild(enabledInput);
        
        const typeInput = document.createElement('input');
        typeInput.type = 'hidden';
        typeInput.name = `campaigns[${index}][type]`;
        typeInput.value = campaign.type;
        form.appendChild(typeInput);
        
        // Add parameters
        Object.keys(campaign.parameters).forEach(param => {
            const paramInput = document.createElement('input');
            paramInput.type = 'hidden';
            paramInput.name = `campaigns[${index}][${param}]`;
            paramInput.value = campaign.parameters[param];
            form.appendChild(paramInput);
        });
    });
}

/**
 * Clear cart
 */
function clearCart() {
    const cartItems = document.getElementById('cartItems');
    const itemRows = cartItems.querySelectorAll('.item-row');
    
    // Animate removal
    itemRows.forEach((row, index) => {
        setTimeout(() => {
            row.style.transition = 'all 0.3s ease';
            row.style.opacity = '0';
            row.style.transform = 'translateX(-100%)';
            setTimeout(() => row.remove(), 300);
        }, index * 100);
    });
    
    // Reset counter
    itemCounter = 1;
    
    // Clear campaigns
    activeCampaigns = [];
    updateCampaignDisplay();
    
    // Update totals
    setTimeout(() => {
        updateCartTotal();
        showNotification('Cart cleared', 'info');
    }, itemRows.length * 100 + 300);
}

/**
 * Add new item row
 */
function addNewItem() {
    const cartItems = document.getElementById('cartItems');
    const newItem = createItemRow(itemCounter);
    
    cartItems.appendChild(newItem);
    itemCounter++;
    
    // Animate the new item
    newItem.style.opacity = '0';
    newItem.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
        newItem.style.transition = 'all 0.3s ease';
        newItem.style.opacity = '1';
        newItem.style.transform = 'translateY(0)';
    }, 10);
    
    updateCartTotal();
}

/**
 * Create item row
 */
function createItemRow(index) {
    const div = document.createElement('div');
    div.className = 'item-row';
    div.setAttribute('data-index', index);
    
    div.innerHTML = `
        <div class="row">
            <div class="col-3">
                <label class="form-label">Item Name</label>
                <input type="text" class="form-control" name="items[${index}][name]" placeholder="e.g., T-Shirt">
            </div>
            <div class="col-2">
                <label class="form-label">Price (THB)</label>
                <input type="number" class="form-control" name="items[${index}][price]" step="0.01" min="0" placeholder="0.00">
            </div>
            <div class="col-2">
                <label class="form-label">Category</label>
                <select class="form-control" name="items[${index}][category]">
                    <option value="Clothing">Clothing</option>
                    <option value="Accessories">Accessories</option>
                    <option value="Electronics">Electronics</option>
                </select>
            </div>
            <div class="col-2">
                <label class="form-label">Quantity</label>
                <input type="number" class="form-control" name="items[${index}][quantity]" min="1" value="1">
            </div>
            <div class="col-2">
                <label class="form-label">Total</label>
                <input type="text" class="form-control item-total" readonly placeholder="0.00">
            </div>
            <div class="col-auto">
                <label class="form-label">&nbsp;</label><br>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(this)">×</button>
            </div>
        </div>
    `;
    
    return div;
}

/**
 * Remove item
 */
function removeItem(button) {
    const itemRow = button.closest('.item-row');
    
    // Animate removal
    itemRow.style.transition = 'all 0.3s ease';
    itemRow.style.opacity = '0';
    itemRow.style.transform = 'translateX(-100%)';
    
    setTimeout(() => {
        itemRow.remove();
        updateCartTotal();
    }, 300);
}

/**
 * Update item total
 */
function updateItemTotal(input) {
    const row = input.closest('.item-row');
    const priceInput = row.querySelector('input[name*="[price]"]');
    const quantityInput = row.querySelector('input[name*="[quantity]"]');
    const totalInput = row.querySelector('.item-total');
    
    const price = parseFloat(priceInput.value) || 0;
    const quantity = parseInt(quantityInput.value) || 0;
    const total = price * quantity;
    
    totalInput.value = formatCurrency(total);
}

/**
 * Update cart total
 */
function updateCartTotal() {
    const itemRows = document.querySelectorAll('.item-row');
    let total = 0;
    
    itemRows.forEach(row => {
        const priceInput = row.querySelector('input[name*="[price]"]');
        const quantityInput = row.querySelector('input[name*="[quantity]"]');
        
        const price = parseFloat(priceInput.value) || 0;
        const quantity = parseInt(quantityInput.value) || 0;
        total += price * quantity;
    });
    
    const cartTotalElement = document.getElementById('cartTotal');
    if (cartTotalElement) {
        cartTotalElement.textContent = formatCurrency(total);
    }
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Real-time total calculation
    document.addEventListener('input', function(e) {
        if (e.target.matches('input[name*="[price]"], input[name*="[quantity]"]')) {
            updateItemTotal(e.target);
            updateCartTotal();
        }
    });
    
    // Form submission
    const form = document.getElementById('discountForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            calculateDiscount();
        });
    }
}

/**
 * Show calculate spinner
 */
function showCalculateSpinner() {
    const calculateText = document.getElementById('calculate-text');
    const calculateSpinner = document.getElementById('calculate-spinner');
    
    if (calculateText) calculateText.style.display = 'none';
    if (calculateSpinner) calculateSpinner.style.display = 'inline-block';
}

/**
 * Hide calculate spinner
 */
function hideCalculateSpinner() {
    const calculateText = document.getElementById('calculate-text');
    const calculateSpinner = document.getElementById('calculate-spinner');
    
    if (calculateText) calculateText.style.display = 'inline';
    if (calculateSpinner) calculateSpinner.style.display = 'none';
}

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());
    
    // Create new notification
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} fade-in`;
    alert.innerHTML = `
        <strong>${type.charAt(0).toUpperCase() + type.slice(1)}:</strong> ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()" style="float: right; background: none; border: none; font-size: 1.5rem; cursor: pointer;">×</button>
    `;
    
    // Insert at top of container
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(alert, container.firstChild);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alert.parentElement) {
                alert.style.transition = 'all 0.3s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);
    }
}

/**
 * Format currency
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('th-TH', {
        style: 'currency',
        currency: 'THB',
        minimumFractionDigits: 2
    }).format(amount);
}

/**
 * Validate numeric input
 */
function validateNumericInput(input, min = 0, max = null) {
    let value = parseFloat(input.value);
    
    if (isNaN(value) || value < min) {
        value = min;
    }
    
    if (max !== null && value > max) {
        value = max;
    }
    
    input.value = value;
    return value;
}

/**
 * Setup real-time validation
 */
function setupRealTimeValidation() {
    document.addEventListener('input', function(e) {
        if (e.target.matches('input[type="number"]')) {
            const min = parseFloat(e.target.min) || 0;
            const max = parseFloat(e.target.max) || null;
            
            if (e.target.value !== '') {
                validateNumericInput(e.target, min, max);
            }
        }
    });
}

/**
 * Setup keyboard shortcuts
 */
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + Enter to calculate
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            calculateDiscount();
        }
        
        // Escape to close modals
        if (e.key === 'Escape') {
            const browsers = document.querySelectorAll('#itemBrowser, #campaignBrowser');
            browsers.forEach(browser => {
                if (browser.style.display !== 'none') {
                    browser.style.display = 'none';
                }
            });
        }
    });
}

/**
 * Calculate discount
 */
async function calculateDiscount() {
    try {
        showCalculateSpinner();
        
        // Add campaigns to form
        addCampaignsToForm();
        
        const form = document.getElementById('discountForm');
        const formData = new FormData(form);
        formData.append('action', 'calculate_discount');
        
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            displayResult(result.result);
        } else {
            showNotification('Error calculating discount: ' + result.message, 'error');
        }
    } catch (error) {
        showNotification('Error calculating discount: ' + error.message, 'error');
    } finally {
        hideCalculateSpinner();
    }
}

/**
 * Display result
 */
function displayResult(result) {
    // Create result HTML
    const resultHtml = `
        <div class="card fade-in">
            <div class="card-header" style="background: linear-gradient(135deg, var(--success-color), #059669);">
                <h3 style="color: white; margin: 0;">✅ Calculation Results</h3>
            </div>
            <div class="card-body">
                <div class="result-grid">
                    <div class="result-summary">
                        <h4>Summary</h4>
                        <div class="summary-item">
                            <span>Original Price:</span>
                            <span>${result.formatted.original_price}</span>
                        </div>
                        <div class="summary-item">
                            <span>Total Discount:</span>
                            <span class="discount-amount">-${result.formatted.total_discount}</span>
                        </div>
                        <div class="summary-item">
                            <span>Final Price:</span>
                            <span>${result.formatted.final_price}</span>
                        </div>
                    </div>
                    
                    <div class="result-breakdown">
                        <h4>Discount Breakdown</h4>
                        ${result.discount_breakdown.length > 0 ? 
                            result.discount_breakdown.map(breakdown => `
                                <div class="summary-item">
                                    <span>${breakdown.campaign}</span>
                                    <span class="discount-amount">-${formatCurrency(breakdown.discount)}</span>
                                </div>
                            `).join('') : 
                            '<p class="text-muted">No discounts applied</p>'
                        }
                    </div>
                </div>
                
                ${result.applied_campaigns.length > 0 ? `
                    <div class="mt-4">
                        <h4>Applied Campaigns</h4>
                        <div class="d-flex gap-2" style="flex-wrap: wrap;">
                            ${result.applied_campaigns.map(campaign => `
                                <span class="badge badge-primary">${campaign}</span>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
            </div>
        </div>
    `;
    
    // Remove existing results
    const existingResults = document.querySelectorAll('.card.fade-in');
    existingResults.forEach(result => result.remove());
    
    // Add new result
    const container = document.querySelector('.container');
    if (container) {
        container.insertAdjacentHTML('beforeend', resultHtml);
        
        // Scroll to result
        const newResult = container.lastElementChild;
        newResult.scrollIntoView({ behavior: 'smooth' });
    }
}