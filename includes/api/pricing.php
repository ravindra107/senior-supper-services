<?php
/**
 * Pricing Engine
 */

class SSS_Pricing_Engine {
    
    /**
     * Calculate Total Price
     */
    public static function calculate_total($data) {
        global $wpdb;
        
        $total = 0;
        $email = $data['email'];
        $day = $data['day'];
        
        // Check if this is first meal for customer
        $is_first_meal = !$wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . SSS_TABLE_PREFIX . "orders WHERE customer_email = %s",
            $email
        ));
        
        // Get meal price
        if (!empty($data['meals'])) {
            foreach ($data['meals'] as $meal_id) {
                $meal = $wpdb->get_row($wpdb->prepare(
                    "SELECT price FROM " . SSS_TABLE_PREFIX . "meals WHERE id = %d",
                    $meal_id
                ));
                
                if ($meal) {
                    $meal_price = $is_first_meal ? 
                        floatval(SSS_Database_Schema::get_setting('first_meal_price', '15.50')) :
                        floatval(SSS_Database_Schema::get_setting('other_meals_price', '13.50'));
                    
                    $total += $meal_price * $data['quantity'];
                    $is_first_meal = false; // Only first meal gets the first price
                }
            }
        }
        
        // Get sides price
        if (!empty($data['sides'])) {
            foreach ($data['sides'] as $side_id) {
                $side = $wpdb->get_row($wpdb->prepare(
                    "SELECT price FROM " . SSS_TABLE_PREFIX . "sides WHERE id = %d",
                    $side_id
                ));
                
                if ($side) {
                    $total += floatval($side->price) * $data['quantity'];
                }
            }
        }
        
        // Get dessert price
        if (!empty($data['dessert'])) {
            $dessert = $wpdb->get_row($wpdb->prepare(
                "SELECT price FROM " . SSS_TABLE_PREFIX . "sides WHERE id = %d",
                $data['dessert']
            ));
            
            if ($dessert) {
                $total += floatval($dessert->price) * $data['quantity'];
            }
        }
        
        return round($total, 2);
    }
    
    /**
     * Get First Meal Price
     */
    public static function get_first_meal_price() {
        return floatval(SSS_Database_Schema::get_setting('first_meal_price', '15.50'));
    }
    
    /**
     * Get Other Meals Price
     */
    public static function get_other_meals_price() {
        return floatval(SSS_Database_Schema::get_setting('other_meals_price', '13.50'));
    }
    
    /**
     * Check if Customer is New (No previous orders)
     */
    public static function is_new_customer($email) {
        global $wpdb;
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . SSS_TABLE_PREFIX . "orders WHERE customer_email = %s",
            $email
        ));
        
        return intval($result) === 0;
    }
    
    /**
     * Get Meal Price Based on Position
     */
    public static function get_meal_price($email, $meal_position = 1) {
        $is_first_meal = $meal_position === 1 && self::is_new_customer($email);
        
        return $is_first_meal ? 
            self::get_first_meal_price() :
            self::get_other_meals_price();
    }
    
    /**
     * Get Sides Price
     */
    public static function get_side_price($side_id) {
        global $wpdb;
        
        $price = $wpdb->get_var($wpdb->prepare(
            "SELECT price FROM " . SSS_TABLE_PREFIX . "sides WHERE id = %d",
            $side_id
        ));
        
        return $price ? floatval($price) : 0;
    }
    
    /**
     * Calculate Order Total
     */
    public static function calculate_order_total($order_id) {
        global $wpdb;
        
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . SSS_TABLE_PREFIX . "order_items WHERE order_id = %d",
            $order_id
        ));
        
        $total = 0;
        foreach ($items as $item) {
            $total += floatval($item->subtotal);
        }
        
        // Update order with total
        $wpdb->update(
            SSS_TABLE_PREFIX . 'orders',
            array('total_price' => $total),
            array('id' => $order_id),
            array('%f'),
            array('%d')
        );
        
        return $total;
    }
    
    /**
     * Validate Pricing
     */
    public static function validate_pricing($order_data) {
        $errors = array();
        
        // Validate email format
        if (!is_email($order_data['email'])) {
            $errors[] = 'Invalid email format';
        }
        
        // Validate meals selected
        if (empty($order_data['meals'])) {
            $errors[] = 'At least one meal must be selected';
        }
        
        // Validate quantity
        if (intval($order_data['quantity']) < 1) {
            $errors[] = 'Quantity must be at least 1';
        }
        
        return $errors;
    }
    
    /**
     * Format Price for Display
     */
    public static function format_price($price) {
        return '$' . number_format(floatval($price), 2);
    }
}
