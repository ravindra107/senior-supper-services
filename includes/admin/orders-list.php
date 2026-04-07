<?php
/**
 * Orders Management
 */

class SSS_Orders_List {
    
    public static function render_page() {
        ?>
        <div class="wrap sss-orders-wrap">
            <h1>Order Management</h1>
            
            <?php
            // Handle status update
            if (isset($_POST['action']) && wp_verify_nonce($_POST['sss_nonce'], 'sss_orders_nonce')) {
                if ($_POST['action'] === 'update_status') {
                    self::update_order_status(
                        intval($_POST['order_id']),
                        sanitize_text_field($_POST['status'])
                    );
                    echo '<div class="notice notice-success"><p>Order status updated!</p></div>';
                }
            }
            
            // Filters
            $page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
            $per_page = 20;
            $offset = ($page - 1) * $per_page;
            
            $filters = array();
            if (!empty($_GET['email'])) {
                $filters['customer_email'] = sanitize_email($_GET['email']);
            }
            if (!empty($_GET['date_from'])) {
                $filters['date_from'] = sanitize_text_field($_GET['date_from']);
            }
            if (!empty($_GET['date_to'])) {
                $filters['date_to'] = sanitize_text_field($_GET['date_to']);
            }
            if (!empty($_GET['status'])) {
                $filters['status'] = sanitize_text_field($_GET['status']);
            }
            
            // Get orders
            $orders = self::get_orders($offset, $per_page, $filters);
            $total = self::count_orders($filters);
            $pages = ceil($total / $per_page);
            ?>
            
            <div class="sss-filters">
                <form method="GET" action="">
                    <input type="hidden" name="page" value="sss_orders" />
                    
                    <div class="filter-group">
                        <label for="email">Email:</label>
                        <input type="email" 
                               name="email" 
                               id="email" 
                               value="<?php echo isset($_GET['email']) ? esc_attr($_GET['email']) : ''; ?>" 
                               placeholder="Customer email" />
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_from">From Date:</label>
                        <input type="date" 
                               name="date_from" 
                               id="date_from" 
                               value="<?php echo isset($_GET['date_from']) ? esc_attr($_GET['date_from']) : ''; ?>" />
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_to">To Date:</label>
                        <input type="date" 
                               name="date_to" 
                               id="date_to" 
                               value="<?php echo isset($_GET['date_to']) ? esc_attr($_GET['date_to']) : ''; ?>" />
                    </div>
                    
                    <div class="filter-group">
                        <label for="status">Status:</label>
                        <select name="status" id="status">
                            <option value="">All</option>
                            <option value="pending" <?php selected(isset($_GET['status']) && $_GET['status'] === 'pending'); ?>>Pending</option>
                            <option value="confirmed" <?php selected(isset($_GET['status']) && $_GET['status'] === 'confirmed'); ?>>Confirmed</option>
                            <option value="delivered" <?php selected(isset($_GET['status']) && $_GET['status'] === 'delivered'); ?>>Delivered</option>
                            <option value="cancelled" <?php selected(isset($_GET['status']) && $_GET['status'] === 'cancelled'); ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="button button-primary">Filter</button>
                    <a href="?page=sss_orders" class="button">Reset</a>
                </form>
            </div>
            
            <?php
            if ($orders):
                ?>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Delivery Days</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo esc_html($order->id); ?></td>
                                <td><?php echo esc_html($order->customer_name); ?></td>
                                <td><?php echo esc_html($order->customer_email); ?></td>
                                <td><?php echo esc_html($order->customer_phone); ?></td>
                                <td><?php echo esc_html($order->delivery_days); ?></td>
                                <td>$<?php echo esc_html(number_format($order->total_price, 2)); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <?php wp_nonce_field('sss_orders_nonce', 'sss_nonce'); ?>
                                        <input type="hidden" name="action" value="update_status" />
                                        <input type="hidden" name="order_id" value="<?php echo esc_attr($order->id); ?>" />
                                        
                                        <select name="status" onchange="this.form.submit();">
                                            <option value="pending" <?php selected($order->status, 'pending'); ?>>Pending</option>
                                            <option value="confirmed" <?php selected($order->status, 'confirmed'); ?>>Confirmed</option>
                                            <option value="delivered" <?php selected($order->status, 'delivered'); ?>>Delivered</option>
                                            <option value="cancelled" <?php selected($order->status, 'cancelled'); ?>>Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                                <td><?php echo esc_html($order->order_date); ?></td>
                                <td>
                                    <a href="#" class="button button-small view-order-details" data-order-id="<?php echo intval($order->id); ?>">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total' => $pages,
                            'current' => $page
                        ));
                        ?>
                    </div>
                </div>
                
                <!-- Order Details Modal -->
                <div id="order-details-modal" style="display: none; background: white; border: 1px solid #ccc; padding: 20px; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9999; max-width: 600px; max-height: 90vh; overflow-y: auto;">
                    <button onclick="document.getElementById('order-details-modal').style.display='none';" style="float: right; cursor: pointer; font-size: 20px;">&times;</button>
                    <div id="order-details-content"></div>
                </div>
                <div id="order-details-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9998;" onclick="document.getElementById('order-details-modal').style.display='none';"></div>
                
            <?php else: ?>
                <p>No orders found.</p>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.view-order-details').on('click', function(e) {
                e.preventDefault();
                var order_id = $(this).data('order-id');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sss_get_order_details',
                        order_id: order_id,
                        nonce: '<?php echo wp_create_nonce('sss_order_details'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#order-details-content').html(response.data.html);
                            $('#order-details-modal').show();
                            $('#order-details-overlay').show();
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Get Orders
     */
    private static function get_orders($offset, $per_page, $filters = array()) {
        global $wpdb;
        
        $query = "SELECT * FROM " . SSS_TABLE_PREFIX . "orders WHERE 1=1";
        $params = array();
        
        if (isset($filters['customer_email'])) {
            $query .= " AND customer_email = %s";
            $params[] = $filters['customer_email'];
        }
        
        if (isset($filters['date_from'])) {
            $query .= " AND order_date >= %s";
            $params[] = $filters['date_from'];
        }
        
        if (isset($filters['date_to'])) {
            $query .= " AND order_date <= %s";
            $params[] = $filters['date_to'];
        }
        
        if (isset($filters['status'])) {
            $query .= " AND status = %s";
            $params[] = $filters['status'];
        }
        
        $query .= " ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;
        
        return $wpdb->get_results($wpdb->prepare($query, ...$params));
    }
    
    /**
     * Count Orders
     */
    private static function count_orders($filters = array()) {
        global $wpdb;
        
        $query = "SELECT COUNT(*) FROM " . SSS_TABLE_PREFIX . "orders WHERE 1=1";
        $params = array();
        
        if (isset($filters['customer_email'])) {
            $query .= " AND customer_email = %s";
            $params[] = $filters['customer_email'];
        }
        
        if (isset($filters['date_from'])) {
            $query .= " AND order_date >= %s";
            $params[] = $filters['date_from'];
        }
        
        if (isset($filters['date_to'])) {
            $query .= " AND order_date <= %s";
            $params[] = $filters['date_to'];
        }
        
        if (isset($filters['status'])) {
            $query .= " AND status = %s";
            $params[] = $filters['status'];
        }
        
        return intval($wpdb->get_var($wpdb->prepare($query, ...$params)));
    }
    
    /**
     * Update Order Status
     */
    private static function update_order_status($order_id, $status) {
        global $wpdb;
        
        $wpdb->update(
            SSS_TABLE_PREFIX . 'orders',
            array('status' => $status),
            array('id' => $order_id),
            array('%s'),
            array('%d')
        );
    }
}

// AJAX: Get Order Details
add_action('wp_ajax_sss_get_order_details', function() {
    check_ajax_referer('sss_order_details', 'nonce');
    
    global $wpdb;
    $order_id = intval($_POST['order_id']);
    
    $order = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM " . SSS_TABLE_PREFIX . "orders WHERE id = %d",
        $order_id
    ));
    
    if (!$order) {
        wp_send_json_error(array('message' => 'Order not found'));
    }
    
    $items = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM " . SSS_TABLE_PREFIX . "order_items WHERE order_id = %d",
        $order_id
    ));
    
    ob_start();
    ?>
    <h2>Order #<?php echo esc_html($order->id); ?></h2>
    
    <div class="order-details">
        <h3>Customer Information</h3>
        <p><strong>Name:</strong> <?php echo esc_html($order->customer_name); ?></p>
        <p><strong>Email:</strong> <?php echo esc_html($order->customer_email); ?></p>
        <p><strong>Phone:</strong> <?php echo esc_html($order->customer_phone); ?></p>
        <p><strong>Address:</strong> <?php echo esc_html($order->customer_address); ?></p>
        
        <h3>Order Information</h3>
        <p><strong>Order Date:</strong> <?php echo esc_html($order->order_date); ?></p>
        <p><strong>Delivery Days:</strong> <?php echo esc_html($order->delivery_days); ?></p>
        <p><strong>Status:</strong> <?php echo esc_html(ucfirst($order->status)); ?></p>
        <p><strong>Total:</strong> $<?php echo esc_html(number_format($order->total_price, 2)); ?></p>
        
        <?php if ($order->order_notes): ?>
            <h3>Notes</h3>
            <p><?php echo esc_html($order->order_notes); ?></p>
        <?php endif; ?>
        
        <h3>Order Items</h3>
        <table class="wp-list-table widefat">
            <thead>
                <tr>
                    <th>Item Type</th>
                    <th>Item Name</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo esc_html(ucfirst($item->item_type)); ?></td>
                        <td><?php echo esc_html($item->item_name); ?></td>
                        <td>$<?php echo esc_html(number_format($item->item_price, 2)); ?></td>
                        <td><?php echo intval($item->quantity); ?></td>
                        <td>$<?php echo esc_html(number_format($item->subtotal, 2)); ?></td>
                    </tr>
                    <?php if ($item->special_instructions): ?>
                        <tr>
                            <td colspan="5"><em>Special Instructions: <?php echo esc_html($item->special_instructions); ?></em></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    
    $html = ob_get_clean();
    wp_send_json_success(array('html' => $html));
});
