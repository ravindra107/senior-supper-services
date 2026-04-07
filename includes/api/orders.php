<?php
/**
 * Orders API
 */

class SSS_Orders_API {
    
    /**
     * Get Order by ID
     */
    public static function get_order($order_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . SSS_TABLE_PREFIX . "orders WHERE id = %d",
            $order_id
        ));
    }
    
    /**
     * Get Order Items
     */
    public static function get_order_items($order_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . SSS_TABLE_PREFIX . "order_items WHERE order_id = %d ORDER BY id ASC",
            $order_id
        ));
    }
    
    /**
     * Get Orders by Email
     */
    public static function get_orders_by_email($email, $limit = 10) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . SSS_TABLE_PREFIX . "orders WHERE customer_email = %s ORDER BY created_at DESC LIMIT %d",
            $email,
            $limit
        ));
    }
    
    /**
     * Get Orders by Status
     */
    public static function get_orders_by_status($status, $limit = 20) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . SSS_TABLE_PREFIX . "orders WHERE status = %s ORDER BY created_at DESC LIMIT %d",
            $status,
            $limit
        ));
    }
    
    /**
     * Get Orders by Date Range
     */
    public static function get_orders_by_date_range($start_date, $end_date) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . SSS_TABLE_PREFIX . "orders WHERE order_date BETWEEN %s AND %s ORDER BY created_at DESC",
            $start_date,
            $end_date
        ));
    }
    
    /**
     * Get Pending Orders
     */
    public static function get_pending_orders() {
        return self::get_orders_by_status('pending', 100);
    }
    
    /**
     * Update Order Status
     */
    public static function update_order_status($order_id, $status) {
        global $wpdb;
        
        return $wpdb->update(
            SSS_TABLE_PREFIX . 'orders',
            array('status' => $status),
            array('id' => $order_id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Delete Order
     */
    public static function delete_order($order_id) {
        global $wpdb;
        
        // Delete order items first
        $wpdb->delete(
            SSS_TABLE_PREFIX . 'order_items',
            array('order_id' => $order_id),
            array('%d')
        );
        
        // Delete order
        return $wpdb->delete(
            SSS_TABLE_PREFIX . 'orders',
            array('id' => $order_id),
            array('%d')
        );
    }
    
    /**
     * Get Orders Statistics
     */
    public static function get_statistics() {
        global $wpdb;
        
        return array(
            'total_orders' => intval($wpdb->get_var("SELECT COUNT(*) FROM " . SSS_TABLE_PREFIX . "orders")),
            'total_revenue' => floatval($wpdb->get_var("SELECT SUM(total_price) FROM " . SSS_TABLE_PREFIX . "orders")),
            'pending_orders' => intval($wpdb->get_var("SELECT COUNT(*) FROM " . SSS_TABLE_PREFIX . "orders WHERE status = 'pending'")),
            'confirmed_orders' => intval($wpdb->get_var("SELECT COUNT(*) FROM " . SSS_TABLE_PREFIX . "orders WHERE status = 'confirmed'")),
            'delivered_orders' => intval($wpdb->get_var("SELECT COUNT(*) FROM " . SSS_TABLE_PREFIX . "orders WHERE status = 'delivered'"))
        );
    }
    
    /**
     * Export Orders to CSV
     */
    public static function export_to_csv($start_date = null, $end_date = null, $status = null) {
        global $wpdb;
        
        $query = "SELECT * FROM " . SSS_TABLE_PREFIX . "orders WHERE 1=1";
        $params = array();
        
        if ($start_date) {
            $query .= " AND order_date >= %s";
            $params[] = $start_date;
        }
        
        if ($end_date) {
            $query .= " AND order_date <= %s";
            $params[] = $end_date;
        }
        
        if ($status) {
            $query .= " AND status = %s";
            $params[] = $status;
        }
        
        $orders = $wpdb->get_results(
            $wpdb->prepare($query, ...$params)
        );
        
        // Create CSV
        $csv = "Order ID,Customer Name,Email,Phone,Address,Delivery Days,Total Price,Status,Order Date\n";
        
        foreach ($orders as $order) {
            $csv .= sprintf(
                '"%d","%s","%s","%s","%s","%s","%.2f","%s","%s"' . "\n",
                $order->id,
                addslashes($order->customer_name),
                $order->customer_email,
                $order->customer_phone,
                addslashes($order->customer_address),
                $order->delivery_days,
                $order->total_price,
                $order->status,
                $order->order_date
            );
        }
        
        return $csv;
    }
    
    /**
     * Export Orders to JSON
     */
    public static function export_to_json($start_date = null, $end_date = null, $status = null) {
        global $wpdb;
        
        $query = "SELECT * FROM " . SSS_TABLE_PREFIX . "orders WHERE 1=1";
        $params = array();
        
        if ($start_date) {
            $query .= " AND order_date >= %s";
            $params[] = $start_date;
        }
        
        if ($end_date) {
            $query .= " AND order_date <= %s";
            $params[] = $end_date;
        }
        
        if ($status) {
            $query .= " AND status = %s";
            $params[] = $status;
        }
        
        $orders = $wpdb->get_results(
            $wpdb->prepare($query, ...$params)
        );
        
        // Add items to each order
        foreach ($orders as $order) {
            $order->items = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM " . SSS_TABLE_PREFIX . "order_items WHERE order_id = %d",
                $order->id
            ));
        }
        
        return json_encode($orders, JSON_PRETTY_PRINT);
    }
    
    /**
     * Search Orders
     */
    public static function search_orders($keyword, $limit = 20) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . SSS_TABLE_PREFIX . "orders 
             WHERE customer_name LIKE %s 
             OR customer_email LIKE %s 
             OR customer_phone LIKE %s 
             ORDER BY created_at DESC 
             LIMIT %d",
            '%' . $wpdb->esc_like($keyword) . '%',
            '%' . $wpdb->esc_like($keyword) . '%',
            '%' . $wpdb->esc_like($keyword) . '%',
            $limit
        ));
    }
}
