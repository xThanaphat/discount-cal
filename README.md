## 🎯 Core Requirements Met

### ✅ Campaign Categories & Rules
- **Fixed Amount (Coupon)**: Direct THB discount from total cart value
- **Percentage Discount (Coupon)**: Percentage off entire cart total  
- **Percentage by Category (On Top)**: Category-specific percentage discounts
- **Discount by Points (On Top)**: Points-based discount (1 point = 1 THB, max 20%)
- **Special Campaigns (Seasonal)**: Threshold-based discounts (X THB off for every Y THB spent)

### ✅ Business Rules Implementation
- **One campaign per category**: Users must choose between Fixed Amount OR Percentage Discount
- **Priority order**: Coupon → On Top → Seasonal
- **Points cap**: Maximum 20% of total price for points-based discounts
- **Multiple campaign support**: Can apply different category campaigns simultaneously

### ✅ Technical Requirements
- **Input handling**: JSON-based data structure for items and campaigns
- **UI/UX**: Modern, responsive web interface for campaign selection and calculation

---

## 🏗️ Project Structure

```
discount-exam/
├── css/
│   └── style.css              # Modern responsive styling
├── data/
│   ├── campaigns.json         # Campaign definitions & parameters
│   ├── items.json            # Available products with categories
├── include/
│   ├── config.php            # Configuration constants
│   ├── function.php          # Core business logic & calculations
│   ├── nav.inc.php           # Navigation component
│   └── title.inc.php         # Page title & meta
├── js/
│   └── app_script.js         # Frontend JavaScript functionality
├── index.php                 # Main application entry point
└── README.md                 # This documentation
```

---

## 🚀 Features

### Core Functionality
- **Real-time calculation**: Instant discount updates as cart or campaigns change
- **Campaign management**: Visual campaign selection with category conflict handling
- **Points integration**: Dynamic points input with validation (shows only when relevant)
- **Order summary**: Modal popup with detailed breakdown and savings display
- **Responsive design**: Works seamlessly on desktop and mobile devices

### User Experience
- **Visual feedback**: Campaign cards with color-coded categories and hover effects
- **Smart validation**: Prevents invalid inputs and provides helpful error messages
- **Auto-calculation**: Discounts update automatically when cart or campaigns change
- **Clear breakdown**: Detailed discount breakdown showing applied campaigns and savings

### Technical Excellence
- **Object-oriented design**: Clean separation of concerns with CartItem and DiscountResult classes
- **Error handling**: Comprehensive validation and graceful error recovery
- **Performance optimized**: Efficient calculations and minimal DOM updates
- **Accessibility**: Keyboard navigation and screen reader friendly

---

## 📊 Campaign Examples

### Fixed Amount Campaign
```
Items: T-Shirt (350 THB) + Hat (250 THB) = 600 THB
Campaign: Fixed 50 THB Off
Result: 600 - 50 = 550 THB
```

### Percentage Discount
```
Items: T-Shirt (350 THB) + Hat (250 THB) = 600 THB  
Campaign: 10% Off Everything
Result: 600 - (600 × 0.10) = 540 THB
```

### Category-based Discount
```
Items: T-Shirt (350 THB) + Hoodie (700 THB) + Watch (850 THB) + Bag (640 THB) = 2,540 THB
Campaign: 15% Off Clothing (T-Shirt + Hoodie = 1,050 THB)
Result: 2,540 - (1,050 × 0.15) = 2,382.5 THB
```

### Points-based Discount
```
Items: T-Shirt (350 THB) + Hat (250 THB) + Belt (230 THB) = 830 THB
Points: 68 points
Max discount: 830 × 0.20 = 166 THB
Applied: min(68, 166) = 68 THB
Result: 830 - 68 = 762 THB
```

### Special Campaign
```
Items: T-Shirt (350 THB) + Hat (250 THB) + Belt (230 THB) = 830 THB
Campaign: 40 THB off for every 300 THB
Multiplier: floor(830 ÷ 300) = 2
Discount: 2 × 40 = 80 THB
Result: 830 - 80 = 750 THB
```

---

## 🛠️ Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- Web server (Apache/Nginx) or PHP built-in server
- Modern web browser

### Production Deployment
- Upload files to web server
- Ensure PHP has read/write permissions for data directory
- Configure web server to serve from project root

## 🧪 Testing

### Manual Testing
1. **Add items** to cart using the product grid
2. **Select campaigns** from available discount options
3. **Enter points** (if using points-based campaign)
4. **View real-time** discount calculations in cart summary
5. **Proceed to checkout** for detailed order summary

---

## 🔧 Technical Implementation

### Business Logic Architecture
```php
// Core calculation flow
calculateDiscount($items, $campaigns, $customerPoints)
├── validateCampaigns()           // Check category conflicts
├── sortCampaignsByPriority()     // Coupon → On Top → Seasonal
├── applyCampaigns()              // Calculate discounts in order
└── return DiscountResult         // Final price and breakdown
```

### Key Classes
- **CartItem**: Represents individual cart items with validation
- **DiscountResult**: Encapsulates calculation results and formatting
- **Campaign Types**: Fixed Amount, Percentage, Category, Points, Special

### Frontend Features
- **Real-time updates**: Automatic recalculation on cart/campaign changes
- **Visual feedback**: Color-coded campaign categories and selection states
- **Responsive design**: Mobile-first approach with touch-friendly controls
- **Accessibility**: Keyboard navigation and screen reader support

---

## 📱 User Interface

### Desktop Experience
- **Two-column layout**: Products/campaigns + Cart
- **Visual campaign cards**: Color-coded by category with hover effects
- **Real-time cart summary**: Header shows current total and item count
- **Detailed breakdown**: Step-by-step discount application display

### Mobile Experience
- **Single-column layout**: Optimized for touch interaction
- **Compact cards**: Smaller campaign cards with essential information
- **Touch-friendly controls**: Larger buttons and input areas
- **Modal summaries**: Full-screen order summary for mobile

---

## 🎨 Design Decisions

### Assumptions Made
1. **Points input visibility**: Only shown when points-based campaign is selected
2. **Campaign replacement**: Automatic replacement of conflicting campaigns with user notification
3. **Real-time calculation**: Immediate updates for better user experience
4. **Modal order summary**: Non-intrusive detailed view without page replacement

### Technology Choices
- **PHP**: Server-side business logic for reliability and performance
- **Vanilla JavaScript**: No framework dependencies for simplicity
- **CSS Grid/Flexbox**: Modern layout techniques for responsiveness
- **JSON data**: Flexible, configuration format

---

## 📈 Performance Considerations

### Optimization Strategies
- **Efficient calculations**: Single-pass campaign processing
- **Minimal DOM updates**: Targeted element updates only when needed
- **Debounced inputs**: Prevents excessive API calls during typing
- **Cached results**: Avoid redundant calculations for same inputs

### Scalability
- **Modular architecture**: Easy to add new campaign types
- **Configuration-driven**: Campaigns defined in JSON, no code changes needed
- **Extensible design**: Clean separation allows for future enhancements

---

## 🔮 Future Enhancements
### Potential Improvements
- **Database integration**: Persistent cart and user data
- **User authentication**: Personalized campaigns and points tracking
- **Advanced campaigns**: Time-based, user-specific, or combination rules
- **Analytics**: Campaign performance tracking and optimization
- **API endpoints**: RESTful API for mobile app integration

---

## 📄 License

This project is created specifically for Playtorium's take-home assignment and interview process. All rights reserved. 
