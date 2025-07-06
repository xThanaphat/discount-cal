# Discount Calculation System

A professional PHP-based discount calculation system with a modern web interface for testing various discount scenarios and campaigns.

## Features

- **Multiple Discount Types**: Fixed amount, percentage, category-based, points-based, and special campaigns
- **Campaign Categories**: Coupon, On Top, and Seasonal campaigns with priority-based application
- **Modern Web Interface**: Clean, responsive design with real-time calculation
- **Test Functions**: Pre-built test scenarios for assignment, edge cases, combinations, and stress testing
- **Manual Testing**: Interactive form for custom discount calculations
- **JSON Data Storage**: Flexible data structure using JSON files

## Project Structure

```
discount-exam/
├── css/
│   └── style.css          # Main stylesheet
├── data/
│   ├── campaigns.json     # Campaign definitions
│   ├── cart.json          # Sample cart data
│   ├── items.json         # Available items
│   └── test_function.json # Test scenarios
├── include/
│   ├── config.php         # Configuration constants
│   └── function.php       # Core business logic
├── js/
│   └── app_script.js      # Frontend JavaScript
├── index.php              # Main application file
└── README.md              # This file
```

## Installation

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd discount-exam
   ```

2. **Set up web server**:
   - Ensure PHP 7.4+ is installed
   - Configure your web server to serve from the project directory
   - For local development, you can use PHP's built-in server:
     ```bash
     php -S localhost:8000
     ```

3. **Access the application**:
   - Open your browser and navigate to `http://localhost:8000`

## Usage

### Quick Test Functions
- Use the predefined test scenarios to verify different discount calculations
- Tests are categorized into: Assignment, Edge Case, Combination, and Stress tests

### Manual Testing
1. **Add Items**: Use the item browser or manually enter item details
2. **Select Campaigns**: Choose from available discount campaigns
3. **Set Customer Points**: Enter available loyalty points (if any)
4. **Calculate**: Click "Calculate Discount" to see results

### Campaign Types

1. **Fixed Amount**: Direct THB discount (e.g., 50 THB off)
2. **Percentage Discount**: Percentage off entire order (e.g., 10% off)
3. **Percentage by Category**: Category-specific percentage discount
4. **Discount by Points**: Use loyalty points (1 point = 1 THB, max 20%)
5. **Special Campaigns**: Threshold-based discounts (e.g., 40 THB off for every 300 THB spent)

### Campaign Categories & Priority

1. **Coupon** (Highest Priority): Fixed amount and percentage discounts
2. **On Top** (Medium Priority): Category-specific and points-based discounts
3. **Seasonal** (Lowest Priority): Special threshold-based campaigns

## Data Files

### campaigns.json
Contains all available discount campaigns with their parameters and descriptions.

### items.json
Available items for testing with categories and prices.

### cart.json
Sample cart data for initial testing.

### test_function.json
Predefined test scenarios for automated testing.

## Business Logic

The system applies discounts in priority order:
1. Coupon campaigns (fixed amount, percentage)
2. On Top campaigns (category-specific, points)
3. Seasonal campaigns (threshold-based)

Only one campaign per category is allowed per calculation.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is for educational and testing purposes.

## Support

For issues or questions, please create an issue in the repository. 