<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/include/function.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    try {
        switch ($action) {
            case 'calculate_discount':
                $result = processFormSubmission($_POST);
                jsonResponse([
                    'success' => true,
                    'result' => $result->toArray()
                ]);
                break;
            default:
                jsonResponse(['success' => false, 'message' => 'Unknown action'], 400);
        }
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
    exit;
}

include __DIR__ . '/include/title.inc.php';
// Load data
$items = loadJsonData('items.json')['items'];
$campaigns = loadJsonData('campaigns.json')['campaigns'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    
</head>
<body>

    <?php 
        include __DIR__ . '/include/nav.inc.php';
    ?>

    <div class="container">
        <!-- Main Layout -->
        <div class="main-layout">
            <!-- Products Section -->
            <div class="main-left">
                <div class="section">
                    <div class="section-header">
                        <h2>🛍️ Shop Our Products</h2>
                    </div>
                    <div class="section-body">
                        <div class="products-grid">
                            <?php foreach ($items as $item): 
                                $icon = isset($item['icon']) ? $item['icon'] : '📦';
                            ?>
                                <div class="product-card" onclick="addToCart('<?= $item['id'] ?>')">
                                    <div class="product-image">
                                        <?= $icon ?>
                                    </div>
                                    <div class="product-info">
                                        <div class="product-name"><?= htmlspecialchars($item['name']) ?></div>
                                        <div class="product-category"><?= $item['category'] ?></div>
                                        <div class="product-price"><?= number_format($item['price'], 2) ?> THB</div>
                                        <button class="add-to-cart">Add to Cart</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <!-- Available Campaigns -->
                <div class="section">
                    <div class="section-header">
                        <h2>🎯 Available Discounts</h2>
                        <p style="color: white; opacity: 0.9; font-size: 0.875rem; margin: 0.5rem 0 0 0;">Select discount campaigns</p>
                    </div>
                    <div class="section-body">
                        <div class="campaigns-grid">
                            <?php foreach ($campaigns as $campaign): 
                                $tagClass = strtolower(str_replace(' ', '', $campaign['category']));
                                if ($campaign['category'] === 'Coupon') {
                                    $tagClass = 'coupon';
                                }
                            ?>
                                <div class="campaign-card" onclick="toggleCampaign('<?= $campaign['id'] ?>')" id="campaign-<?= $campaign['id'] ?>">
                                    <div class="campaign-tag <?= $tagClass ?>"><?= $campaign['category'] ?></div>
                                    <div class="campaign-name"><?= htmlspecialchars($campaign['name']) ?></div>
                                    <div class="campaign-desc"><?= htmlspecialchars($campaign['description']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Shopping Cart -->
            <div class="main-right">
                <div class="section" style="margin-bottom: 0; box-shadow: none; background: transparent;">
                    <div class="section-header">
                        <h2>🛒 Shopping Cart</h2>
                    </div>
                    <div class="section-body">
                        <div id="empty-cart" class="text-center" style="padding: 2rem; color: #6b7280;">
                            Your cart is empty. Start shopping above!
                        </div>
                        <div id="cart-items" class="cart-items"></div>
                        <div class="points-section" style="display: none;" id="points-section">
                            <label>
                                💰 <strong>Use Points:</strong>
                                <input type="number" id="customer-points" class="points-input" value="0" min="0" placeholder="0">
                                <small style="color: #92400e;"> <br> 1 point = 1 THB (max 20% of total)</small>
                            </label>
                        </div>
                        <div class="checkout-section" style="display: none;" id="checkout-section">
                            <div class="total-breakdown" id="total-breakdown">
                                <div class="total-row subtotal">
                                    <span>Subtotal:</span>
                                    <span id="subtotal">0.00 THB</span>
                                </div>
                            </div>
                            <button class="calculate-btn" onclick="showOrderSummaryModal()">
                                Proceed to Checkout
                            </button>
                            <div id="final-result" style="display: none; margin-top: 1rem;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Order Summary Popup -->
        <div class="modal-overlay" id="order-summary-modal" style="display: none;">
            <div class="modal-content">
                <button class="close-modal-btn" onclick="closeOrderSummaryModal()">×</button>
                <div id="order-summary-content"></div>
            </div>
        </div>
    </div>

    <!-- Hidden data -->
    <script>
        const items = <?= json_encode($items) ?>;
        const campaigns = <?= json_encode($campaigns) ?>;
        let cart = [];
        let selectedCampaigns = [];
    </script>

    <script>
        // Shopping cart functionality
        function addToCart(itemId) {
            const item = items.find(i => i.id === itemId);
            if (!item) return;
            
            const existingItem = cart.find(i => i.id === itemId);
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({...item, quantity: 1});
            }
            
            updateCartDisplay();
            showMessage(`${item.name} added to cart!`, 'success');
        }
        
        function removeFromCart(itemId) {
            cart = cart.filter(item => item.id !== itemId);
            updateCartDisplay();
            showMessage('Item removed from cart', 'success');
        }
        
        function updateQuantity(itemId, newQuantity) {
            const item = cart.find(i => i.id === itemId);
            if (item && newQuantity > 0) {
                item.quantity = parseInt(newQuantity);
                updateCartDisplay();
                // Auto calculate discount when quantity changes
                if (selectedCampaigns.length > 0) {
                    calculateDiscountAuto();
                }
            }
        }
        
        function updateCartDisplay() {
            const cartItems = document.getElementById('cart-items');
            const emptyCart = document.getElementById('empty-cart');
            const pointsSection = document.getElementById('points-section');
            const checkoutSection = document.getElementById('checkout-section');
            
            if (cart.length === 0) {
                cartItems.style.display = 'none';
                emptyCart.style.display = 'block';
                pointsSection.style.display = 'none';
                checkoutSection.style.display = 'none';
                updateCartSummary();
                return;
            }
            
            emptyCart.style.display = 'none';
            cartItems.style.display = 'block';
            checkoutSection.style.display = 'block';
            
            let html = '';
            cart.forEach(item => {
                const total = item.price * item.quantity;
                html += `
                    <div class="cart-item">
                        <div class="item-info">
                            <div class="item-name">${item.name}</div>
                            <div class="item-category">${item.category}</div>
                        </div>
                        <div class="item-controls">
                            <div class="quantity-control">
                                <button class="qty-btn" onclick="updateQuantity('${item.id}', ${item.quantity - 1})">-</button>
                                <input type="number" class="qty-input" value="${item.quantity}" min="1" inputmode="numeric" pattern="[0-9]*" tabindex="-1" onchange="updateQuantity('${item.id}', this.value)">
                                <button class="qty-btn" onclick="updateQuantity('${item.id}', ${item.quantity + 1})">+</button>
                            </div>
                            <div class="item-price">${total.toFixed(2)} THB</div>
                            <button class="remove-item" onclick="removeFromCart('${item.id}')">×</button>
                        </div>
                    </div>
                `;
            });
            
            cartItems.innerHTML = html;
            updateCartSummary();
            updateSelectedCampaignsDisplay();
            updatePointsSectionDisplay();
            
            // Auto calculate discount if campaigns are selected
            if (selectedCampaigns.length > 0) {
                console.log('Cart updated, calculating discount for selected campaigns...');
                setTimeout(() => calculateDiscountAuto(), 100);
            } else {
                console.log('Cart updated, no campaigns selected');
            }
        }
        
        function updateCartSummary() {
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            const totalPrice = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            
            document.getElementById('cart-count').textContent = totalItems;
            document.getElementById('cart-total').textContent = totalPrice.toFixed(2) + ' THB';
            document.getElementById('subtotal').textContent = totalPrice.toFixed(2) + ' THB';
            
            // If no campaigns selected, reset the breakdown
            if (selectedCampaigns.length === 0) {
                const totalBreakdown = document.getElementById('total-breakdown');
                const existingDiscountRows = totalBreakdown.querySelectorAll('.live-discount');
                existingDiscountRows.forEach(row => row.remove());
                
                const existingFinalRow = totalBreakdown.querySelector('.final');
                if (existingFinalRow) {
                    existingFinalRow.remove();
                }
                
                const savingsDiv = document.getElementById('savings-summary');
                if (savingsDiv) {
                    savingsDiv.style.display = 'none';
                }
                
                // Hide Total section when no campaigns
                const totalSection = document.getElementById('total-section');
                if (totalSection) {
                    totalSection.style.display = 'none';
                }
            }
        }
        
        function updateCartSummaryWithDiscount(result) {
            console.log('Updating cart summary with discount:', result);
            
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            const originalPrice = parseFloat(result.original_price);
            const finalPrice = parseFloat(result.final_price);
            const totalDiscount = parseFloat(result.total_discount);
            
            // Update header cart summary
            document.getElementById('cart-count').textContent = totalItems;
            document.getElementById('cart-total').textContent = finalPrice.toFixed(2) + ' THB';
            
            // Update subtotal in breakdown
            document.getElementById('subtotal').textContent = originalPrice.toFixed(2) + ' THB';
            
            // Update total breakdown with live discount
            const totalBreakdown = document.getElementById('total-breakdown');
            const subtotalRow = totalBreakdown.querySelector('.subtotal');
            
            // Remove existing discount rows and final row
            const existingDiscountRows = totalBreakdown.querySelectorAll('.live-discount');
            existingDiscountRows.forEach(row => row.remove());
            
            const existingFinalRow = totalBreakdown.querySelector('.final');
            if (existingFinalRow) {
                existingFinalRow.remove();
            }
            
            // Add discount
            if (result.discount_breakdown && result.discount_breakdown.length > 0) {
                result.discount_breakdown.forEach(breakdown => {
                    const campaign = campaigns.find(c => c.type === breakdown.campaign && c.category === breakdown.category);
                    const description = campaign ? getCampaignDescription(campaign) : '';
                    breakdown.description = description;
                    const discountRow = document.createElement('div');
                    discountRow.className = 'total-row discount live-discount';
                    discountRow.innerHTML = `
                        <span>
                            <span class="discount-campaign-title">${breakdown.campaign}</span>
                            ${description ? `<span class="discount-campaign-desc">${description}</span>` : ''}
                        </span>
                        <span style="color: var(--success-color); font-weight: 600;">-${parseFloat(breakdown.discount).toFixed(2)} THB
                            <button class="remove-campaign-btn" onclick="removeCampaignFromCartByType('${breakdown.campaign}', '${breakdown.category}')">×</button>
                        </span>
                    `;
                    totalBreakdown.insertBefore(discountRow, subtotalRow.nextSibling);
                });
            }
            
            // Remove all existing savings-summary 
            const existingSavingsRows = totalBreakdown.querySelectorAll('.savings-summary');
            existingSavingsRows.forEach(row => row.remove());

            // Add final total row (ราคาหลังหักส่วนลด)
            const newFinalRow = document.createElement('div');
            newFinalRow.className = 'total-row final';
            newFinalRow.innerHTML = `
                <span>Total:</span>
                <span style="color: var(--primary-color); font-weight: 700; font-size: 1.25rem;">${finalPrice.toFixed(2)} THB</span>
            `;
            totalBreakdown.appendChild(newFinalRow);

            // Add savings summary
            if (totalDiscount > 0) {
                const savingsPercentage = ((totalDiscount / originalPrice) * 100).toFixed(1);
                const savingsRow = document.createElement('div');
                savingsRow.className = 'total-row savings-summary';
                savingsRow.style = 'background: #e6f9ef; color: var(--success-color); border-radius: 8px; margin-top: 0.5rem; font-size: 1rem; font-weight: 600; justify-content: flex-end;';
                savingsRow.innerHTML = `<span style="margin-right: 0.5rem;">You saved:</span> <span>${totalDiscount.toFixed(2)} THB (${savingsPercentage}%)</span>`;
                totalBreakdown.appendChild(savingsRow);
            }
            
            // Update Total section
            const totalSection = document.getElementById('total-section');
            const totalAmount = document.getElementById('total-amount');
            const totalSavings = document.getElementById('total-savings');
            
            if (totalSection && totalAmount) {
                totalSection.style.display = 'block';
                totalAmount.querySelector('.amount').textContent = finalPrice.toFixed(2);
                
                // Show savings if there's a discount
                if (totalDiscount > 0) {
                    const savingsPercentage = ((totalDiscount / originalPrice) * 100).toFixed(1);
                    totalSavings.style.display = 'flex';
                    totalSavings.querySelector('.savings-amount').textContent = `${totalDiscount.toFixed(2)} THB (${savingsPercentage}%)`;
                } else {
                    totalSavings.style.display = 'none';
                }
            }
            
            // Show savings summary
            if (totalDiscount > 0) {
                const savingsPercentage = ((totalDiscount / originalPrice) * 100).toFixed(1);
                const savingsDiv = document.getElementById('savings-summary');
                if (savingsDiv) {
                    savingsDiv.innerHTML = `
                        <div style="text-align: center; margin-top: 1rem; padding: 1rem; background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.1)); border-radius: 8px; border: 1px solid var(--success-color);">
                            <div style="font-size: 1.2rem; font-weight: 700; color: var(--success-color); margin-bottom: 0.5rem;">
                                💰 You saved ${totalDiscount.toFixed(2)} THB!
                            </div>
                            <div style="color: var(--text-secondary); font-size: 0.875rem;">
                                That's a ${savingsPercentage}% discount!
                            </div>
                        </div>
                    `;
                    savingsDiv.style.display = 'block';
                }
            } else {
                // Hide savings summary if no discount
                const savingsDiv = document.getElementById('savings-summary');
                if (savingsDiv) {
                    savingsDiv.style.display = 'none';
                }
            }
            
            console.log('Cart summary updated successfully');
        }
        
        function updateSelectedCampaignsDisplay() {
            const totalBreakdown = document.getElementById('total-breakdown');
            const subtotalRow = totalBreakdown.querySelector('.subtotal');
            
            // Remove existing campaign rows (but keep live-discount rows)
            const existingCampaignRows = totalBreakdown.querySelectorAll('.campaign-row');
            existingCampaignRows.forEach(row => row.remove());
            
        }
        
        function removeCampaignFromCart(campaignId) {
            const campaign = campaigns.find(c => c.id === campaignId);
            if (!campaign) return;
            // Remove from selected campaigns
            selectedCampaigns = selectedCampaigns.filter(c => c.id !== campaignId);
            // Remove selected class from campaign card
            const campaignElement = document.getElementById(`campaign-${campaignId}`);
            if (campaignElement) {
                campaignElement.classList.remove('selected');
            }
            // Update display and recalculate
            updateSelectedCampaignsDisplay();
            calculateDiscountAuto(); // เรียกทุกครั้ง ไม่ต้องเช็ค cart.length
            showMessage(`${campaign.name} removed from cart`, 'success');
        }
        
        function getCampaignDescription(campaign) {
            const params = campaign.parameters;
            switch (campaign.type) {
                case 'Fixed Amount':
                    return `-${params.amount} THB`;
                case 'Percentage Discount':
                    return `-${params.percentage}%`;
                case 'Percentage by Category':
                    return `-${params.percentage}% on ${params.targetCategory}`;
                case 'Discount by Points':
                    return 'Use points (1pt = 1THB, max 20%)';
                case 'Special Campaigns':
                    return `-${params.discount} THB for every ${params.threshold} THB`;
                default:
                    return campaign.description;
            }
        }
        
        // Campaign selection
        function toggleCampaign(campaignId) {
            console.log('Toggling campaign:', campaignId);
            const campaign = campaigns.find(c => c.id === campaignId);
            const campaignElement = document.getElementById(`campaign-${campaignId}`);
            if (!campaign) {
                console.log('Campaign not found:', campaignId);
                return;
            }
            // Check if already selected
            const existingIndex = selectedCampaigns.findIndex(c => c.id === campaignId);
            if (existingIndex >= 0) {
                // Remove campaign
                selectedCampaigns.splice(existingIndex, 1);
                campaignElement.classList.remove('selected');
                showMessage(`${campaign.name} removed`, 'success');
                console.log('Campaign removed:', campaign.name);
            } else {
                // Check for category conflict
                const conflictingCampaign = selectedCampaigns.find(c => c.category === campaign.category);
                if (conflictingCampaign) {
                    // Remove conflicting campaign
                    const conflictElement = document.getElementById(`campaign-${conflictingCampaign.id}`);
                    conflictElement.classList.remove('selected');
                    selectedCampaigns = selectedCampaigns.filter(c => c.id !== conflictingCampaign.id);
                    showMessage(`Replaced ${conflictingCampaign.name} with ${campaign.name}`, 'success');
                    console.log('Campaign replaced:', conflictingCampaign.name, 'with', campaign.name);
                }
                // Add new campaign
                selectedCampaigns.push(campaign);
                campaignElement.classList.add('selected');
                if (!conflictingCampaign) {
                    showMessage(`${campaign.name} selected`, 'success');
                    console.log('Campaign added:', campaign.name);
                }
                // ถ้าเป็น Discount by Points ให้ scroll ไปที่ input customer-points และ focus
                if (campaign.type === 'Discount by Points') {
                    setTimeout(() => {
                        const pointsInput = document.getElementById('customer-points');
                        if (pointsInput) {
                            pointsInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            pointsInput.focus();
                        }
                    }, 200);
                }
            }
            console.log('Current selected campaigns:', selectedCampaigns);
            // Update display and calculate discount automatically
            updateSelectedCampaignsDisplay();
            updatePointsSectionDisplay();
            calculateDiscountAuto(); // เรียกทุกครั้ง ไม่ต้องเช็ค cart.length
        }
        
        // Calculate discount automatically
        async function calculateDiscountAuto() {
            if (cart.length === 0) {
                console.log('Cart is empty, skipping calculation');
                return;
            }
            
            // ประกาศ customerPoints ก่อน
            const customerPoints = parseInt(document.getElementById('customer-points').value) || 0;
            
            // ถ้าเลือก Use Your Points แต่ไม่ได้กรอกคะแนนหรือกรอก 0
            if (isPointsCampaignSelected()) {
                if (customerPoints <= 0) {
                    showMessage('Please enter your points to use.', 'error');
                    return;
                }
            }
            
            console.log('Auto calculating discount...');
            console.log('Cart:', cart);
            console.log('Selected campaigns:', selectedCampaigns);
            console.log('Customer points:', customerPoints);
            
            const formData = new FormData();
            formData.append('action', 'calculate_discount');
            formData.append('customer_points', customerPoints);
            formData.append('items', JSON.stringify(cart));
            formData.append('campaigns', JSON.stringify(selectedCampaigns));
            
            console.log('FormData entries:');
            for (let [key, value] of formData.entries()) {
                console.log(key, value);
            }
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                console.log('Calculation result:', result);
                
                if (result.success) {
                    console.log('Updating cart with discount result');
                    updateCartSummaryWithDiscount(result.result);
                } else {
                    console.error('Calculation failed:', result.message);
                    // Fallback to normal cart summary if calculation fails
                    updateCartSummary();
                }
            } catch (error) {
                console.error('Auto calculation error:', error);
                // Fallback to normal cart summary if error occurs
                updateCartSummary();
            }
        }
        
        // Calculate discount (manual trigger)
        async function calculateDiscount() {
            if (cart.length === 0) {
                showMessage('Please add items to cart first', 'error');
                return;
            }
            
            // ประกาศ customerPoints ก่อน
            const customerPoints = parseInt(document.getElementById('customer-points').value) || 0;
            
            // ถ้าเลือก Use Your Points แต่ไม่ได้กรอกคะแนนหรือกรอก 0 kk
            if (isPointsCampaignSelected()) {
                if (customerPoints <= 0) {
                    showMessage('Please enter your points to use.', 'error');
                    return;
                }
            }
            
            const formData = new FormData();
            formData.append('action', 'calculate_discount');
            formData.append('customer_points', customerPoints);
            formData.append('items', JSON.stringify(cart));
            formData.append('campaigns', JSON.stringify(selectedCampaigns));
            
            console.log('FormData entries:');
            for (let [key, value] of formData.entries()) {
                console.log(key, value);
            }
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    displayResult(result.result);
                    document.getElementById('final-result').style.display = 'block';
                } else {
                    showMessage('Error calculating discount: ' + result.message, 'error');
                    const resultDiv = document.getElementById('final-result');
                    resultDiv.innerHTML = '';
                    resultDiv.style.display = 'none';
                }
            } catch (error) {
                showMessage('Error calculating discount', 'error');
                const resultDiv = document.getElementById('final-result');
                resultDiv.innerHTML = '';
                resultDiv.style.display = 'none';
                console.error(error);
            }
        }
        
        function displayResult(result) {
            const resultDiv = document.getElementById('final-result');

            // สร้าง HTML สำหรับแสดงรายการสินค้า
            let itemsHtml = '';
            if (result.items && result.items.length > 0) {
                itemsHtml = `
                    <div class="order-items" style="margin-bottom: 0.5rem;">
                        <div style="color: #e11d48; font-weight: 700; margin-bottom: 0.25rem;">Ordered Items</div>
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            ${result.items.map(item => `
                                <li style="display: flex; justify-content: space-between; align-items: center; padding: 0.15rem 0;">
                                    <span>${item.name} <span style='color: #888; font-size: 0.95em;'>(x${item.quantity})</span></span>
                                    <span style="font-weight: 600; color: var(--secondary-color);">${parseFloat(item.price * item.quantity).toFixed(2)} THB</span>
                                </li>
                            `).join('')}
                        </ul>
                    </div>
                `;
            }

            let breakdownHtml = '';
            if (result.discount_breakdown && result.discount_breakdown.length > 0) {
                breakdownHtml = result.discount_breakdown.map(breakdown => `
                    <div class="total-row discount">
                        <span>
                            <span class="discount-campaign-title">${breakdown.campaign}</span>
                            ${breakdown.description ? `<span class="discount-campaign-desc">${breakdown.description}</span>` : ''}
                        </span>
                        <span style="color: var(--success-color); font-weight: 600;">-${parseFloat(breakdown.discount).toFixed(2)} THB</span>
                    </div>
                `).join('');
            }

            const savedAmount = result.total_discount;
            const savingsPercentage = ((savedAmount / result.original_price) * 100).toFixed(1);

            resultDiv.innerHTML = `
                <div class="card" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.05)); border: 2px solid var(--primary-color);">
                    <div class="card-header" style="background: linear-gradient(135deg, var(--success-color), #059669);">
                        <h3 style="color: white; margin: 0;">✅ Order Summary</h3>
                    </div>
                    <div class="card-body">
                        <div class="total-breakdown" style="margin-bottom: 1.5rem;">
                            ${itemsHtml}
                            <div class="total-row subtotal" style="margin-top: 0.5rem;">
                                <span>Original Total:</span>
                                <span style="font-weight: 600;">${parseFloat(result.original_price).toFixed(2)} THB</span>
                            </div>
                            ${breakdownHtml}
                            <div class="total-row final">
                                <span>Total:</span>
                                <span style="color: var(--primary-color); font-weight: 700; font-size: 1.25rem;">${parseFloat(result.final_price).toFixed(2)} THB</span>
                            </div>
                        </div>
                        <div style="text-align: center; margin-top: 1.5rem; padding: 1rem; background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.1)); border-radius: 8px; border: 1px solid var(--success-color);">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--success-color); margin-bottom: 0.5rem;">
                                💰 You saved ${parseFloat(savedAmount).toFixed(2)} THB!
                            </div>
                            <div style="color: var(--text-secondary); font-size: 0.875rem;">
                                That's a ${savingsPercentage}% discount!
                            </div>
                        </div>
                        ${result.applied_campaigns && result.applied_campaigns.length > 0 ? `
                            <div style="margin-top: 1rem;">
                                <h4 style="color: var(--text-primary); margin-bottom: 0.5rem;">Applied Campaigns:</h4>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    ${result.applied_campaigns.map(campaign => `
                                        <span class="badge badge-primary">${campaign}</span>
                                    `).join('')}
                                </div>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;

            resultDiv.style.display = 'block';
            resultDiv.scrollIntoView({ behavior: 'smooth' });
            showMessage('Discount calculated successfully!', 'success');
        }
        
        function showMessage(message, type) {
            // Remove existing messages
            const existing = document.querySelectorAll('.alert');
            existing.forEach(el => el.remove());

            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;

            const container = document.querySelector('.container');
            const firstSection = container ? container.querySelector('.section') : null;
            if (container) {
                if (firstSection && firstSection.parentNode === container) {
                    container.insertBefore(alert, firstSection);
                } else {
                    container.prepend(alert);
                }
            } else {
                document.body.prepend(alert);
            }

            setTimeout(() => alert.remove(), 3000);
        }
        
        // Add event listener for customer points
        document.addEventListener('DOMContentLoaded', function() {
            const pointsInput = document.getElementById('customer-points');
            if (pointsInput) {
                pointsInput.addEventListener('input', function() {
                    if (cart.length > 0 && selectedCampaigns.length > 0) {
                        calculateDiscountAuto();
                    }
                });
            }
        });
        
        // Initialize
        updateCartDisplay();

        // removeCampaignFromCartByType
        function removeCampaignFromCartByType(type, category) {
            const campaign = selectedCampaigns.find(c => c.type === type && c.category === category);
            if (campaign) {
                removeCampaignFromCart(campaign.id);
            }
        }

        // Order Summary Modal
        function showOrderSummaryModal() {
            if (cart.length === 0) {
                showMessage('Please add items to cart first', 'error');
                return;
            }
            const customerPoints = parseInt(document.getElementById('customer-points').value) || 0;
            const formData = new FormData();
            formData.append('action', 'calculate_discount');
            formData.append('customer_points', customerPoints);
            formData.append('items', JSON.stringify(cart));
            formData.append('campaigns', JSON.stringify(selectedCampaigns));
            // เรียก API แล้วแสดงผลใน modal
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    renderOrderSummaryModal(result.result);
                    document.getElementById('order-summary-modal').style.display = 'flex';
                    document.body.style.overflow = 'hidden'; // ป้องกัน scroll background
                } else {
                    showMessage('Error calculating discount: ' + result.message, 'error');
                }
            })
            .catch(() => {
                showMessage('Error calculating discount', 'error');
            });
        }
        function closeOrderSummaryModal() {
            document.getElementById('order-summary-modal').style.display = 'none';
            document.body.style.overflow = '';
        }
        // ปิด modal เมื่อคลิก
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('order-summary-modal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) closeOrderSummaryModal();
                });
            }
        });
        // Order Summary ใน modal
        function renderOrderSummaryModal(result) {
            const modalContent = document.getElementById('order-summary-content');
            let itemsHtml = '';
            if (result.items && result.items.length > 0) {
                itemsHtml = `
                    <div class="order-items" style="margin-bottom: 0.5rem;">
                        <div style="color: #e11d48; font-weight: 700; margin-bottom: 0.25rem;">Ordered Items</div>
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            ${result.items.map(item => `
                                <li style="display: flex; justify-content: space-between; align-items: center; padding: 0.15rem 0;">
                                    <span>${item.name} <span style='color: #888; font-size: 0.95em;'>(x${item.quantity})</span></span>
                                    <span style="font-weight: 600; color: var(--secondary-color);">${parseFloat(item.price * item.quantity).toFixed(2)} THB</span>
                                </li>
                            `).join('')}
                        </ul>
                    </div>
                `;
            }
            let breakdownHtml = '';
            if (result.discount_breakdown && result.discount_breakdown.length > 0) {
                result.discount_breakdown.forEach(breakdown => {
                    const campaign = campaigns.find(c => c.type === breakdown.campaign && c.category === breakdown.category);
                    const description = campaign ? getCampaignDescription(campaign) : '';
                    breakdown.description = description;
                    breakdownHtml += `
                        <div class="total-row discount">
                            <span>
                                <span class="discount-campaign-title">${breakdown.campaign}</span>
                                ${description ? `<span class="discount-campaign-desc">${description}</span>` : ''}
                            </span>
                            <span style="color: var(--success-color); font-weight: 600;">-${parseFloat(breakdown.discount).toFixed(2)} THB</span>
                        </div>
                    `;
                });
            }
            const savedAmount = result.total_discount;
            const savingsPercentage = ((savedAmount / result.original_price) * 100).toFixed(1);
            modalContent.innerHTML = `
                <div class="card" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.05)); border: 2px solid var(--primary-color);">
                    <div class="card-header" style="background: linear-gradient(135deg, var(--success-color), #059669);">
                        <h3 style="color: white; margin: 0;">✅ Order Summary</h3>
                    </div>
                    <div class="card-body">
                        <div class="total-breakdown" style="margin-bottom: 1.5rem;">
                            ${itemsHtml}
                            <div class="total-row subtotal" style="margin-top: 0.5rem;">
                                <span>Original Total:</span>
                                <span style="font-weight: 600;">${parseFloat(result.original_price).toFixed(2)} THB</span>
                            </div>
                            ${breakdownHtml}
                            <div class="total-row final">
                                <span>Total:</span>
                                <span style="color: var(--primary-color); font-weight: 700; font-size: 1.25rem;">${parseFloat(result.final_price).toFixed(2)} THB</span>
                            </div>
                        </div>
                        <div style="text-align: center; margin-top: 1.5rem; padding: 1rem; background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.1)); border-radius: 8px; border: 1px solid var(--success-color);">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--success-color); margin-bottom: 0.5rem;">
                                💰 You saved ${parseFloat(savedAmount).toFixed(2)} THB!
                            </div>
                            <div style="color: var(--text-secondary); font-size: 0.875rem;">
                                That's a ${savingsPercentage}% discount!
                            </div>
                        </div>
                        ${result.applied_campaigns && result.applied_campaigns.length > 0 ? `
                            <div style="margin-top: 1rem;">
                                <h4 style="color: var(--text-primary); margin-bottom: 0.5rem;">Applied Campaigns:</h4>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    ${result.applied_campaigns.map(campaign => `
                                        <span class="badge badge-primary">${campaign}</span>
                                    `).join('')}
                                </div>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }
        // ฟังก์ชันเช็คว่าเลือก Use Your Points มั้ย
        function isPointsCampaignSelected() {
            return selectedCampaigns.some(c => c.id === 'points_discount');
        }
        // ฟังก์ชันซ่อน input คะแนน
        function updatePointsSectionDisplay() {
            const pointsSection = document.getElementById('points-section');
            if (isPointsCampaignSelected()) {
                pointsSection.style.display = 'block';
                setTimeout(() => {
                    const input = document.getElementById('customer-points');
                    if (input) input.focus();
                }, 100);
            } else {
                pointsSection.style.display = 'none';
                const input = document.getElementById('customer-points');
                if (input) input.value = 0;
            }
        }
    </script>
</body>
</html>