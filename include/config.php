<?php
// Minimum item price and quantity
if (!defined('MIN_ITEM_PRICE')) define('MIN_ITEM_PRICE', 0);
if (!defined('MIN_ITEM_QUANTITY')) define('MIN_ITEM_QUANTITY', 1);

// Item categories
if (!defined('ITEM_CATEGORIES')) define('ITEM_CATEGORIES', [
    'Clothing', 'Accessories', 'Electronics', 'Home', 'Other'
]);

// Maximum points discount percentage (เช่น 20%)
if (!defined('MAX_POINTS_DISCOUNT_PERCENTAGE')) define('MAX_POINTS_DISCOUNT_PERCENTAGE', 20);

// Application name
if (!defined('APP_NAME')) define('APP_NAME', 'PlaytoMart');

// Campaign types (campaigns.json)
if (!defined('CAMPAIGN_TYPES')) define('CAMPAIGN_TYPES', [
    'FIXED_AMOUNT' => 'Fixed Amount',
    'PERCENTAGE_DISCOUNT' => 'Percentage Discount',
    'PERCENTAGE_BY_CATEGORY' => 'Percentage by Category',
    'DISCOUNT_BY_POINTS' => 'Discount by Points',
    'SPECIAL_CAMPAIGNS' => 'Special Campaigns'
]);

// Campaign categories (campaigns.json)
if (!defined('CAMPAIGN_CATEGORIES')) define('CAMPAIGN_CATEGORIES', [
    'COUPON' => 'Coupon',
    'ON_TOP' => 'On Top',
    'SEASONAL' => 'Seasonal'
]);

// Campaign priority (Coupon → On Top → Seasonal)
if (!defined('CAMPAIGN_PRIORITY')) define('CAMPAIGN_PRIORITY', [
    'Coupon' => 1,
    'On Top' => 2,
    'Seasonal' => 3
]);

// ฟังก์ชันช่วยเหลือ
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('formatCurrency')) {
    function formatCurrency($amount) {
        return number_format($amount, 2) . ' THB';
    }
} 