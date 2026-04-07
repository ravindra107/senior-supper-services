# Senior Supper Services - WordPress Plugin

A complete meal delivery order management system for Senior Supper Services.

## Features

✅ **Complete Admin Dashboard**
- Dashboard with statistics and recent orders
- Settings management for delivery days and pricing
- Meals management by day
- Sides & add-ons management
- Combo meals management
- Order management with filtering and search

✅ **Advanced Pricing System**
- First meal price: $15.50
- Other meals price: $13.50
- Email-based pricing verification
- Dynamic sides and dessert pricing
- Combo meal pricing

✅ **Flexible Form Fields**
- Checkbox fields for multiple selections
- Radio buttons for single selections
- Dropdown selectors
- Text areas for special instructions
- Conditional display of special instructions

✅ **Complete Order Management**
- Order storage with customer information
- Order items tracking
- Order status management (Pending, Confirmed, Delivered, Cancelled)
- Date range filtering
- Email-based filtering
- Export to CSV and JSON

✅ **Customer-Facing Features**
- Responsive order form
- Dynamic pricing calculation
- Real-time form validation
- Special instructions for each side/add-on
- Order summary display
- Delivery days selection

## Installation

### 1. Upload Plugin Files

1. Download the plugin files
2. Extract to a folder named `senior-supper-services`
3. Upload to `/wp-content/plugins/`

### 2. Activate Plugin

1. Go to WordPress Admin Panel
2. Navigate to Plugins
3. Find "Senior Supper Services"
4. Click "Activate"

### 3. Initial Setup

1. Go to **Supper Services** → **Settings**
2. Enable/Disable delivery days as needed
3. Set meal pricing (First meal and Other meals)
4. Go to **Supper Services** → **Meals**
5. Add meals for each delivery day
6. Go to **Supper Services** → **Sides**
7. Add sides and add-ons
8. Go to **Supper Services** → **Combos** (optional)
9. Add combo meals if needed

## Usage

### Admin Panel

#### Dashboard
- View overall statistics (total orders, revenue, pending/delivered)
- See recent orders at a glance
- Quick access to all management pages

#### Settings
- Enable/Disable each day (Monday-Saturday)
- Set meal pricing
- Field type information

#### Meals Management
- Add meals for specific days
- Edit meal details (name, description, price)
- View meals organized by delivery day
- Delete meals

#### Sides Management
- Add sides and add-ons (Loaf/Bread, Soup, Overnight Oats, etc.)
- Select field type (Checkbox, Radio, Dropdown)
- Enable/Disable special instructions field
- Set pricing for each side
- Edit or delete sides

#### Combos Management
- Create combo meals (2-way, 3-way, etc.)
- Set delivery day and delivery-on day
- Add items to combos
- Set combo pricing
- Manage items within each combo

#### Orders Management
- View all submitted orders
- Filter by email, date range, or status
- Update order status
- View detailed order information including items and special instructions
- Search orders by customer name, email, or phone

### Frontend Form

Add this shortcode to display the order form:

```
[sss_order_form]
```

The form includes:
- Personal information fields (Name, Email, Phone, Address)
- Delivery day sections with meal selection
- Sides and add-ons checkboxes with special instructions
- Combo meal options (for Friday with Wednesday delivery notification)
- Special instructions text area per day
- Real-time pricing summary
- Order submission

## Database Tables

The plugin creates these tables:

- `wp_sss_meals` - Meals by day
- `wp_sss_sides` - Sides and add-ons
- `wp_sss_combos` - Combo meals
- `wp_sss_combo_items` - Items in combos
- `wp_sss_settings` - Plugin settings
- `wp_sss_orders` - Customer orders
- `wp_sss_order_items` - Order line items

## Pricing Logic

### First Meal Discount
- First meal ordered by a customer: **$15.50**
- System checks customer email to determine if it's their first order
- Only the first meal in an order gets the discount

### Other Meals
- Second meal onwards: **$13.50**
- Applied to all additional meals from the same customer

### Sides Pricing
- Each side has its own price
- Prices are additive to meal prices
- Special instructions don't affect pricing

### Dessert
- Treated as a side with pricing
- Special instructions field can be enabled

### Combos
- Fixed combo pricing
- Multiplied by quantity selected
- Can include sides and desserts

## Pricing Configuration

To change prices:

1. Go to **Supper Services** → **Settings**
2. Update "First Meal Price" and "Other Meals Price"
3. Click "Save Settings"

Individual side prices are set when creating/editing sides.

## Features Explained

### Field Types

**Checkbox**
- Customers can select multiple options
- Each selection adds to the order
- Special instructions can be added per selection

**Radio**
- Customers select only one option
- Typically used for Yes/No selections (like Dessert)
- Only one instruction field per radio group

**Dropdown**
- Single selection from a list
- Cleaner UI for long lists
- Special instructions can be added

### Special Instructions

Each side/add-on can have an optional special instructions field:
- Appears when the side is selected
- Customer can enter custom preferences
- Stored with the order item

### Combo Meals

Combos allow customers to select multiple items:
- Set number of items to select (2-way, 3-way, etc.)
- Each combo has its own price
- Can be added to any day (not just Friday)
- Includes notification about delivery day differences

## Order Status

Orders can have these statuses:
- **Pending** - Just submitted, needs confirmation
- **Confirmed** - Confirmed by admin
- **Delivered** - Delivered to customer
- **Cancelled** - Order was cancelled

Status can be changed from the Orders page.

## Email Notifications

When an order is submitted:
- Admin receives an email notification
- Email includes order ID and customer information
- Link to view order details in admin panel

## Export Orders

You can export orders programmatically:

```php
// Export to CSV
$csv = SSS_Orders_API::export_to_csv('2024-01-01', '2024-01-31', 'delivered');

// Export to JSON
$json = SSS_Orders_API::export_to_json('2024-01-01', '2024-01-31', 'delivered');
```

## API Functions

### Pricing
```php
// Get meal price
$price = SSS_Pricing_Engine::get_meal_price($email, $meal_position);

// Check if new customer
$is_new = SSS_Pricing_Engine::is_new_customer($email);

// Calculate order total
$total = SSS_Pricing_Engine::calculate_total($order_data);
```

### Orders
```php
// Get order
$order = SSS_Orders_API::get_order($order_id);

// Get order items
$items = SSS_Orders_API::get_order_items($order_id);

// Get pending orders
$pending = SSS_Orders_API::get_pending_orders();

// Search orders
$results = SSS_Orders_API::search_orders('john@example.com');
```

## Troubleshooting

### Orders not appearing
- Check if delivery days are enabled in Settings
- Ensure meals are added for the selected days
- Verify customer email is being submitted

### Pricing not calculating correctly
- Check customer email format
- Verify meal prices in Meals section
- Check sides pricing in Sides section
- Ensure quantities are set correctly

### Form not displaying
- Verify shortcode is correct: `[sss_order_form]`
- Check page/post content
- Clear WordPress cache if using caching plugin
- Check browser console for JavaScript errors

### Database errors
- Ensure database user has CREATE, INSERT, UPDATE, DELETE permissions
- Check that table prefix matches WordPress installation
- Verify database connection

## Support

For issues or feature requests, please contact the development team.

## Version

Current Version: 1.0.0

## License

GPL v2 or later

## Credits

Developed for Senior Supper Services
Website: https://seniorsupperservices.net/

---

**Last Updated:** 2024
