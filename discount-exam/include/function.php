<?php
/**
 * Core Discount Calculation Functions
 * All the main business logic for discount calculations
 */

require_once __DIR__ . '/config.php';

// ===== CART ITEM CLASS =====
class CartItem {
    public $id;
    public $name;
    public $price;
    public $category;
    public $quantity;
    
    public function __construct($id, $name, $price, $category, $quantity = 1) {
        $this->id = $id;
        $this->name = sanitizeInput($name);
        $this->price = (float)$price;
        $this->category = sanitizeInput($category);
        $this->quantity = (int)$quantity;
        
        $this->validate();
    }
    
    public function getTotalPrice() {
        return $this->price * $this->quantity;
    }
    
    private function validate() {
        if ($this->price < MIN_ITEM_PRICE) {
            throw new Exception("Item price cannot be negative");
        }
        if ($this->quantity < MIN_ITEM_QUANTITY) {
            throw new Exception("Item quantity must be at least " . MIN_ITEM_QUANTITY);
        }
        if (!in_array($this->category, ITEM_CATEGORIES)) {
            throw new Exception("Invalid item category: {$this->category}");
        }
    }
    
    public function toArray() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'category' => $this->category,
            'quantity' => $this->quantity,
            'total_price' => $this->getTotalPrice()
        ];
    }
}

// ===== DISCOUNT RESULT CLASS =====
class DiscountResult {
    public $originalPrice;
    public $totalDiscount;
    public $finalPrice;
    public $discountBreakdown;
    public $appliedCampaigns;
    
    public function __construct($originalPrice, $totalDiscount, $finalPrice, $discountBreakdown, $appliedCampaigns) {
        $this->originalPrice = (float)$originalPrice;
        $this->totalDiscount = (float)$totalDiscount;
        $this->finalPrice = (float)$finalPrice;
        $this->discountBreakdown = $discountBreakdown;
        $this->appliedCampaigns = $appliedCampaigns;
    }
    
    public function toArray() {
        return [
            'original_price' => $this->originalPrice,
            'total_discount' => $this->totalDiscount,
            'final_price' => $this->finalPrice,
            'discount_breakdown' => $this->discountBreakdown,
            'applied_campaigns' => $this->appliedCampaigns,
            'formatted' => [
                'original_price' => formatCurrency($this->originalPrice),
                'total_discount' => formatCurrency($this->totalDiscount),
                'final_price' => formatCurrency($this->finalPrice)
            ]
        ];
    }
}

// ===== CAMPAIGN CALCULATION FUNCTIONS =====

/**
 * Calculate Fixed Amount Discount
 */
function calculateFixedAmountDiscount($items, $amount, $customerPoints = 0) {
    $totalPrice = calculateCartTotal($items);
    return min($amount, $totalPrice);
}

/**
 * Calculate Percentage Discount
 */
function calculatePercentageDiscount($items, $percentage, $customerPoints = 0) {
    $totalPrice = calculateCartTotal($items);
    return $totalPrice * ($percentage / 100);
}

/**
 * Calculate Category-based Percentage Discount
 */
function calculateCategoryDiscount($items, $targetCategory, $percentage, $customerPoints = 0) {
    $categoryTotal = 0;
    foreach ($items as $item) {
        if ($item->category === $targetCategory) {
            $categoryTotal += $item->getTotalPrice();
        }
    }
    return $categoryTotal * ($percentage / 100);
}

/**
 * Calculate Points Discount
 */
function calculatePointsDiscount($items, $customerPoints) {
    $totalPrice = calculateCartTotal($items);
    $pointsDiscount = $customerPoints; // 1 point = 1 THB
    $maxDiscount = $totalPrice * (MAX_POINTS_DISCOUNT_PERCENTAGE / 100); // Cap at 20%
    
    return min($pointsDiscount, $maxDiscount);
}

/**
 * Calculate Special Campaign Discount
 */
function calculateSpecialDiscount($items, $threshold, $discount, $customerPoints = 0) {
    $totalPrice = calculateCartTotal($items);
    $discountTimes = floor($totalPrice / $threshold);
    return $discountTimes * $discount;
}

// ===== MAIN DISCOUNT CALCULATOR =====

/**
 * Main function to calculate total discount with multiple campaigns
 */
function calculateDiscount($items, $campaigns, $customerPoints = 0) {
    // Validate inputs
    if (empty($items)) {
        throw new Exception("Cart cannot be empty");
    }
    
    if (!validateCampaigns($campaigns)) {
        throw new Exception("Invalid campaigns: Only one campaign per category is allowed");
    }
    
    // Sort campaigns by priority (Coupon → On Top → Seasonal)
    $sortedCampaigns = sortCampaignsByPriority($campaigns);
    
    $discountBreakdown = [];
    $runningPrice = calculateCartTotal($items);
    $totalDiscount = 0;
    $remainingPoints = $customerPoints;
    $appliedCampaigns = [];
    
    // Apply discounts in order
    foreach ($sortedCampaigns as $campaign) {
        $discount = 0;
        
        switch ($campaign['type']) {
            case CAMPAIGN_TYPES['FIXED_AMOUNT']:
                $discount = calculateFixedAmountDiscount($items, $campaign['parameters']['amount'], $remainingPoints);
                break;
                
            case CAMPAIGN_TYPES['PERCENTAGE_DISCOUNT']:
                $discount = calculatePercentageDiscount($items, $campaign['parameters']['percentage'], $remainingPoints);
                break;
                
            case CAMPAIGN_TYPES['PERCENTAGE_BY_CATEGORY']:
                $discount = calculateCategoryDiscount($items, $campaign['parameters']['targetCategory'], $campaign['parameters']['percentage'], $remainingPoints);
                break;
                
            case CAMPAIGN_TYPES['DISCOUNT_BY_POINTS']:
                $discount = calculatePointsDiscount($items, $remainingPoints);
                break;
                
            case CAMPAIGN_TYPES['SPECIAL_CAMPAIGNS']:
                $discount = calculateSpecialDiscount($items, $campaign['parameters']['threshold'], $campaign['parameters']['discount'], $remainingPoints);
                break;
        }
        
        if ($discount > 0) {
            $discountBreakdown[] = [
                'campaign' => $campaign['type'],
                'category' => $campaign['category'],
                'discount' => $discount,
                'applied_to' => $runningPrice
            ];
            
            $totalDiscount += $discount;
            $runningPrice = max(0, $runningPrice - $discount);
            $appliedCampaigns[] = $campaign['type'];
            
            // Update remaining points for point-based campaigns
            if ($campaign['type'] === CAMPAIGN_TYPES['DISCOUNT_BY_POINTS']) {
                $remainingPoints = max(0, $remainingPoints - $discount);
            }
        }
    }
    
    $originalPrice = calculateCartTotal($items);
    $finalPrice = max(0, $originalPrice - $totalDiscount);
    
    return new DiscountResult(
        $originalPrice,
        $totalDiscount,
        $finalPrice,
        $discountBreakdown,
        $appliedCampaigns
    );
}

// ===== HELPER FUNCTIONS =====

/**
 * Calculate total cart value
 */
function calculateCartTotal($items) {
    $total = 0;
    foreach ($items as $item) {
        $total += $item->getTotalPrice();
    }
    return $total;
}

/**
 * Validate campaigns (only one per category)
 */
function validateCampaigns($campaigns) {
    $categoryCounts = [];
    
    foreach ($campaigns as $campaign) {
        $category = $campaign['category'];
        $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;
    }
    
    // Check if more than one campaign per category
    foreach ($categoryCounts as $count) {
        if ($count > 1) {
            return false;
        }
    }
    
    return true;
}

/**
 * Sort campaigns by priority (Coupon → On Top → Seasonal)
 */
function sortCampaignsByPriority($campaigns) {
    usort($campaigns, function($a, $b) {
        return CAMPAIGN_PRIORITY[$a['category']] <=> CAMPAIGN_PRIORITY[$b['category']];
    });
    
    return $campaigns;
}

/**
 * Create cart items from array data
 */
function createCartFromArray($itemsData) {
    $items = [];
    foreach ($itemsData as $itemData) {
        $items[] = new CartItem(
            $itemData['id'],
            $itemData['name'],
            $itemData['price'],
            $itemData['category'],
            $itemData['quantity'] ?? 1
        );
    }
    return $items;
}

/**
 * Create campaign from form data
 */
function createCampaignFromData($type, $parameters, $category = null) {
    // Determine category based on type if not provided
    if (!$category) {
        switch ($type) {
            case CAMPAIGN_TYPES['FIXED_AMOUNT']:
            case CAMPAIGN_TYPES['PERCENTAGE_DISCOUNT']:
                $category = CAMPAIGN_CATEGORIES['COUPON'];
                break;
            case CAMPAIGN_TYPES['PERCENTAGE_BY_CATEGORY']:
            case CAMPAIGN_TYPES['DISCOUNT_BY_POINTS']:
                $category = CAMPAIGN_CATEGORIES['ON_TOP'];
                break;
            case CAMPAIGN_TYPES['SPECIAL_CAMPAIGNS']:
                $category = CAMPAIGN_CATEGORIES['SEASONAL'];
                break;
        }
    }
    
    return [
        'type' => $type,
        'category' => $category,
        'parameters' => $parameters
    ];
}

/**
 * Get campaign description
 */
function getCampaignDescription($campaign) {
    $type = $campaign['type'];
    $params = $campaign['parameters'];
    
    switch ($type) {
        case CAMPAIGN_TYPES['FIXED_AMOUNT']:
            return "Fixed discount of " . formatCurrency($params['amount']);
            
        case CAMPAIGN_TYPES['PERCENTAGE_DISCOUNT']:
            return "{$params['percentage']}% off entire order";
            
        case CAMPAIGN_TYPES['PERCENTAGE_BY_CATEGORY']:
            return "{$params['percentage']}% off {$params['targetCategory']} items";
            
        case CAMPAIGN_TYPES['DISCOUNT_BY_POINTS']:
            return "Use loyalty points (1 point = 1 THB, max 20%)";
            
        case CAMPAIGN_TYPES['SPECIAL_CAMPAIGNS']:
            return formatCurrency($params['discount']) . " off for every " . formatCurrency($params['threshold']) . " spent";
            
        default:
            return "Unknown campaign";
    }
}

/**
 * Load sample data from JSON files
 */
function loadSampleItems($setName = null) {
    $data = loadJsonData('sample_items.json');
    
    if ($setName) {
        if (!isset($data['sample_sets'][$setName])) {
            throw new Exception("Sample set not found: {$setName}");
        }
        return $data['sample_sets'][$setName];
    }
    
    return $data['sample_sets'];
}

/**
 * Load sample campaigns from JSON
 */
function loadSampleCampaigns($preset = null) {
    $data = loadJsonData('sample_campaigns.json');
    
    if ($preset) {
        if (!isset($data['campaign_presets'][$preset])) {
            throw new Exception("Campaign preset not found: {$preset}");
        }
        return $data['campaign_presets'][$preset];
    }
    
    return $data['campaign_presets'];
}

/**
 * Load test scenarios from JSON
 */
function loadTestScenarios($scenarioName = null) {
    $data = loadJsonData('test_scenarios.json');
    
    if ($scenarioName) {
        if (!isset($data['test_scenarios'][$scenarioName])) {
            throw new Exception("Test scenario not found: {$scenarioName}");
        }
        return $data['test_scenarios'][$scenarioName];
    }
    
    return $data['test_scenarios'];
}

/**
 * Process form submission and calculate discount
 */
function processFormSubmission($postData) {
    try {
        // Create cart items
        $cartItems = [];
        if (isset($postData['items']) && is_array($postData['items'])) {
            foreach ($postData['items'] as $index => $item) {
                if (!empty($item['name']) && !empty($item['price'])) {
                    $cartItems[] = new CartItem(
                        'item_' . ($index + 1),
                        $item['name'],
                        (float)$item['price'],
                        $item['category'] ?? ITEM_CATEGORIES['CLOTHING'],
                        (int)($item['quantity'] ?? 1)
                    );
                }
            }
        }
        
        if (empty($cartItems)) {
            throw new Exception('Please add at least one item to the cart');
        }
        
        // Create campaigns
        $campaigns = [];
        if (isset($postData['campaigns']) && is_array($postData['campaigns'])) {
            foreach ($postData['campaigns'] as $campaignData) {
                if (isset($campaignData['enabled']) && $campaignData['enabled'] === '1') {
                    $campaign = createCampaignFromFormData($campaignData);
                    if ($campaign) {
                        $campaigns[] = $campaign;
                    }
                }
            }
        }
        
        // Calculate discount
        $customerPoints = (int)($postData['customer_points'] ?? 0);
        return calculateDiscount($cartItems, $campaigns, $customerPoints);
        
    } catch (Exception $e) {
        throw new Exception("Error processing form: " . $e->getMessage());
    }
}

/**
 * Create campaign from form data
 */
function createCampaignFromFormData($data) {
    $type = $data['type'] ?? '';
    $category = $data['category'] ?? null;
    
    switch ($type) {
        case CAMPAIGN_TYPES['FIXED_AMOUNT']:
            return createCampaignFromData($type, [
                'amount' => (float)($data['amount'] ?? 0)
            ], $category);
            
        case CAMPAIGN_TYPES['PERCENTAGE_DISCOUNT']:
            return createCampaignFromData($type, [
                'percentage' => (float)($data['percentage'] ?? 0)
            ], $category);
            
        case CAMPAIGN_TYPES['PERCENTAGE_BY_CATEGORY']:
            return createCampaignFromData($type, [
                'targetCategory' => $data['targetCategory'] ?? $data['category'] ?? ITEM_CATEGORIES[0],
                'percentage' => (float)($data['percentage'] ?? 0)
            ], $category);
            
        case CAMPAIGN_TYPES['DISCOUNT_BY_POINTS']:
            return createCampaignFromData($type, [], $category);
            
        case CAMPAIGN_TYPES['SPECIAL_CAMPAIGNS']:
            return createCampaignFromData($type, [
                'threshold' => (float)($data['threshold'] ?? 0),
                'discount' => (float)($data['discount'] ?? 0)
            ], $category);
    }
    
    return null;
}

/**
 * Run a specific test scenario
 */
function runTestScenario($scenarioName) {
    $scenario = loadTestScenarios($scenarioName);
    
    // Create cart items
    $cartItems = createCartFromArray($scenario['cart']['items']);
    
    // Create campaigns
    $campaigns = [];
    foreach ($scenario['campaigns'] as $campaignData) {
        $campaigns[] = createCampaignFromData(
            $campaignData['type'],
            $campaignData['parameters']
        );
    }
    
    // Calculate discount
    $customerPoints = $scenario['cart']['customer_points'] ?? 0;
    return calculateDiscount($cartItems, $campaigns, $customerPoints);
}

/**
 * Load selected items by IDs
 */
function loadSelectedItems($itemIds) {
    $itemsData = loadJsonData('sample_items.json');
    $selectedItems = [];
    
    foreach ($itemIds as $id) {
        foreach ($itemsData['items'] as $item) {
            if ($item['id'] === $id) {
                $selectedItems[] = $item;
                break;
            }
        }
    }
    
    return $selectedItems;
}

/**
 * Load selected campaign by ID
 */
function loadSelectedCampaign($campaignId) {
    $campaignsData = loadJsonData('sample_campaigns.json');
    
    foreach ($campaignsData['campaigns'] as $campaign) {
        if ($campaign['id'] === $campaignId) {
            return $campaign;
        }
    }
    
    throw new Exception("Campaign not found: {$campaignId}");
}

/**
 * Run a specific test function
 */
function runTestFunction($testId) {
    $testFunctions = loadJsonData('test_functions.json');
    $testFunction = null;
    
    // Find the test function
    foreach ($testFunctions['test_functions'] as $test) {
        if ($test['id'] === $testId) {
            $testFunction = $test;
            break;
        }
    }
    
    if (!$testFunction) {
        throw new Exception("Test function not found: {$testId}");
    }
    
    // Load required data
    $itemsData = loadJsonData('sample_items.json');
    $campaignsData = loadJsonData('sample_campaigns.json');
    
    // Get items for this test
    $cartItems = [];
    foreach ($testFunction['setup']['items'] as $itemId) {
        foreach ($itemsData['items'] as $item) {
            if ($item['id'] === $itemId) {
                $cartItems[] = new CartItem(
                    $item['id'],
                    $item['name'],
                    $item['price'],
                    $item['category'],
                    $item['quantity']
                );
                break;
            }
        }
    }
    
    // Get campaigns for this test
    $campaigns = [];
    foreach ($testFunction['setup']['campaigns'] as $campaignId) {
        foreach ($campaignsData['campaigns'] as $campaign) {
            if ($campaign['id'] === $campaignId) {
                $campaigns[] = createCampaignFromData(
                    $campaign['type'],
                    $campaign['parameters']
                );
                break;
            }
        }
    }
    
    // Run the calculation
    $customerPoints = $testFunction['setup']['customer_points'] ?? 0;
    $result = calculateDiscount($cartItems, $campaigns, $customerPoints);
    
    // Prepare result with test info
    $testResult = [
        'test_info' => $testFunction,
        'result' => $result->toArray(),
        'comparison' => null
    ];
    
    // Compare with expected if available
    if ($testFunction['expected']) {
        $testResult['comparison'] = compareWithExpected($result, $testFunction['expected']);
    }
    
    return $testResult;
}



// ===== JSON DATA LOADER (robust version) =====
if (!function_exists('loadJsonData')) {
    function loadJsonData($filename) {
        $pathsToTry = [];
        // 1. Absolute path (ถ้า $filename เป็น absolute)
        if (strpos($filename, '/') === 0) {
            $pathsToTry[] = $filename;
        }
        // 2. Path relative จาก working directory
        $pathsToTry[] = getcwd() . '/data/' . $filename;
        // 3. Path relative จาก __DIR__ (ตำแหน่ง function.php)
        $pathsToTry[] = __DIR__ . '/../data/' . $filename;
        // 4. Path relative จาก index.php (ถ้า run จาก root project)
        $pathsToTry[] = dirname(__DIR__) . '/data/' . $filename;

        foreach ($pathsToTry as $path) {
            $real = realpath($path);
            if ($real && file_exists($real)) {
                $json = file_get_contents($real);
                $data = json_decode($json, true);
                if ($data === null) {
                    throw new Exception("Invalid JSON in file: $real");
                }
                return $data;
            }
        }
        throw new Exception("File not found: $filename. Tried: " . implode(' | ', $pathsToTry));
    }
}