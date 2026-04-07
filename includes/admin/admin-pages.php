<?php
/**
 * Admin Pages
 */

class SSS_Admin_Pages {
    
    public static function render_dashboard() {
        global $wpdb;
        
        // Get statistics
        $total_orders = intval($wpdb->get_var("SELECT COUNT(*) FROM " . SSS_TABLE_PREFIX . "orders"));
        $total_revenue = floatval($wpdb->get_var("SELECT SUM(total_price) FROM " . SSS_TABLE_PREFIX . "orders"));
        $pending_orders = intval($wpdb->get_var("SELECT COUNT(*) FROM " . SSS_TABLE_PREFIX . "orders WHERE status = 'pending'"));
        $delivered_orders = intval($wpdb->get_var("SELECT COUNT(*) FROM " . SSS_TABLE_PREFIX . "orders WHERE status = 'delivered'"));
        
        // Recent orders
        $recent_orders = $wpdb->get_results("SELECT * FROM " . SSS_TABLE_PREFIX . "orders ORDER BY created_at DESC LIMIT 5");
        
        ?>
        <div class="wrap sss-dashboard-wrap">
            <h1>Senior Supper Services - Dashboard</h1>
            
            <div class="sss-stats-container">
                <div class="stat-box">
                    <h3>Total Orders</h3>
                    <p class="stat-number"><?php echo intval($total_orders); ?></p>
                </div>
                
                <div class="stat-box">
                    <h3>Total Revenue</h3>
                    <p class="stat-number">$<?php echo number_format($total_revenue, 2); ?></p>
                </div>
                
                <div class="stat-box">
                    <h3>Pending Orders</h3>
                    <p class="stat-number"><?php echo intval($pending_orders); ?></p>
                </div>
                
                <div class="stat-box">
                    <h3>Delivered Orders</h3>
                    <p class="stat-number"><?php echo intval($delivered_orders); ?></p>
                </div>
            </div>
            
            <div class="sss-dashboard-content">
                <div class="sss-recent-orders">
                    <h2>Recent Orders</h2>
                    
                    <?php if ($recent_orders): ?>
                        <table class="wp-list-table widefat striped">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td><?php echo esc_html($order->id); ?></td>
                                        <td><?php echo esc_html($order->customer_name); ?></td>
                                        <td><?php echo esc_html($order->customer_email); ?></td>
                                        <td>$<?php echo esc_html(number_format($order->total_price, 2)); ?></td>
                                        <td><?php echo esc_html(ucfirst($order->status)); ?></td>
                                        <td><?php echo esc_html($order->created_at); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No orders yet.</p>
                    <?php endif; ?>
                </div>
                
                <div class="sss-quick-actions">
                    <h2>Quick Actions</h2>
                    <ul>
                        <li><a href="admin.php?page=sss_settings" class="button button-primary">Settings</a></li>
                        <li><a href="admin.php?page=sss_meals" class="button button-primary">Manage Meals</a></li>
                        <li><a href="admin.php?page=sss_sides" class="button button-primary">Manage Sides</a></li>
                        <li><a href="admin.php?page=sss_combos" class="button button-primary">Manage Combos</a></li>
                        <li><a href="admin.php?page=sss_orders" class="button button-primary">View Orders</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
}
