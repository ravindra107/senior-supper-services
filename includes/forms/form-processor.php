<?php
/**
 * Form Processing
 */

class SSS_Form_Processor {
    
    public static function process_submission($data) {
        global $wpdb;
        
        // Validate required fields
        $name = isset($data['name']) ? sanitize_text_field($data['name']) : '';
        $email = isset($data['email']) ? sanitize_email($data['email']) : '';
        $phone = isset($data['phone']) ? sanitize_text_field($data['phone']) : '';
        $address = isset($data['address']) ? sanitize_textarea_field($data['address']) : '';
        
        if (empty($name) || empty($email) || empty($phone) || empty($address)) {
            return new WP_Error('missing_required', 'Please fill in all required fields');
        }
        
        // Determine delivery days from submitted data
        $days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
        $delivery_days = array();
        $order_items = array();
        $total_price = 0;
        
        foreach ($days as $day) {
            $day_key = strtolower($day);
            
            if (empty($data[$day_key]) || !isset($data[$day_key]['meal_1']) || empty($data[$day_key]['meal_1'])) {
                continue;
            }
            
            $delivery_days[] = $day;
            
            // Process meals
            $meal_1_id = intval($data[$day_key]['meal_1']);
            $meal_2_id = isset($data[$day_key]['meal_2']) ? intval($data[$day_key]['meal_2']) : 0;
            $quantity = isset($data[$day_key]['quantity']) ? intval($data[$day_key]['quantity']) : 1;
            
            // Get meal prices
            $meal_1 = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . SSS_TABLE_PREFIX . "meals WHERE id = %d",
                $meal_1_id
            ));
            
            if (!$meal_1) {
                return new WP_Error('invalid_meal', 'Invalid meal selected');
            }
            
            // Check if this is first meal for customer on this day
            $is_first_meal = !$wpdb->get_var($wpdb->prepare(
                "SELECT id FROM " . SSS_TABLE_PREFIX . "orders WHERE customer_email = %s AND order_date = %s",
                $email,
                date('Y-m-d')
            ));
            
            $meal_1_price = $is_first_meal ? 
                floatval(SSS_Database_Schema::get_setting('first_meal_price', '15.50')) :
                floatval(SSS_Database_Schema::get_setting('other_meals_price', '13.50'));
            
            // Add meal 1
            $meal_1_subtotal = $meal_1_price * $quantity;
            $order_items[] = array(
                'item_type' => 'meal',
                'item_name' => $meal_1->meal_name . ' (Meal 1)',
                'item_price' => $meal_1_price,
                'quantity' => $quantity,
                'subtotal' => $meal_1_subtotal,
                'special_instructions' => ''
            );
            $total_price += $meal_1_subtotal;
            
            // Add meal 2 if selected
            if ($meal_2_id > 0) {
                $meal_2 = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM " . SSS_TABLE_PREFIX . "meals WHERE id = %d",
                    $meal_2_id
                ));
                
                if ($meal_2) {
                    $meal_2_price = floatval(SSS_Database_Schema::get_setting('other_meals_price', '13.50'));
                    $meal_2_subtotal = $meal_2_price * $quantity;
                    
                    $order_items[] = array(
                        'item_type' => 'meal',
                        'item_name' => $meal_2->meal_name . ' (Meal 2)',
                        'item_price' => $meal_2_price,
                        'quantity' => $quantity,
                        'subtotal' => $meal_2_subtotal,
                        'special_instructions' => ''
                    );
                    $total_price += $meal_2_subtotal;
                }
            }
            
            // Process sides
            if (isset($data[$day_key]['sides']) && is_array($data[$day_key]['sides'])) {
                foreach ($data[$day_key]['sides'] as $side_id => $side_value) {
                    if (empty($side_value)) {
                        continue;
                    }
                    
                    $side_id = intval($side_id);
                    $side = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM " . SSS_TABLE_PREFIX . "sides WHERE id = %d",
                        $side_id
                    ));
                    
                    if (!$side || ($side->field_type !== 'checkbox' && $side_value === 'no')) {
                        continue;
                    }
                    
                    $side_subtotal = floatval($side->price) * $quantity;
                    $special_instructions = '';
                    
                    if ($side->has_instructions && isset($data[$day_key]['instructions'][$side_id])) {
                        $special_instructions = sanitize_textarea_field($data[$day_key]['instructions'][$side_id]);
                    }
                    
                    $order_items[] = array(
                        'item_type' => 'side',
                        'item_name' => $side->side_name,
                        'item_price' => floatval($side->price),
                        'quantity' => $quantity,
                        'subtotal' => $side_subtotal,
                        'special_instructions' => $special_instructions
                    );
                    $total_price += $side_subtotal;
                }
            }
            
            // Add special instructions for day
            $day_instructions = '';
            if (isset($data[$day_key]['special_instructions'])) {
                $day_instructions = sanitize_textarea_field($data[$day_key]['special_instructions']);
                if (!empty($day_instructions)) {
                    $order_items[] = array(
                        'item_type' => 'special_instruction',
                        'item_name' => $day . ' Delivery Special Instructions',
                        'item_price' => 0,
                        'quantity' => 1,
                        'subtotal' => 0,
                        'special_instructions' => $day_instructions
                    );
                }
            }
        }
        
        if (empty($delivery_days)) {
            return new WP_Error('no_meals', 'Please select at least one meal');
        }
        
        // Create order
        $order_result = $wpdb->insert(
            SSS_TABLE_PREFIX . 'orders',
            array(
                'customer_name' => $name,
                'customer_email' => $email,
                'customer_phone' => $phone,
                'customer_address' => $address,
                'order_date' => date('Y-m-d'),
                'delivery_days' => implode(', ', $delivery_days),
                'total_price' => $total_price,
                'status' => 'pending'
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s')
        );
        
        if (!$order_result) {
            return new WP_Error('order_creation', 'Failed to create order');
        }
        
        $order_id = $wpdb->insert_id;
        
        // Insert order items
        foreach ($order_items as $item) {
            $wpdb->insert(
                SSS_TABLE_PREFIX . 'order_items',
                array(
                    'order_id' => $order_id,
                    'item_type' => $item['item_type'],
                    'item_name' => $item['item_name'],
                    'item_price' => $item['item_price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                    'special_instructions' => $item['special_instructions']
                ),
                array('%d', '%s', '%s', '%f', '%d', '%f', '%s')
            );
        }
        
        // Send confirmation email to admin
        do_action('sss_order_submitted', $order_id, $name, $email);
        
        return $order_id;
    }
}

/**
 * Send order notification email to admin
 */
add_action('sss_order_submitted', function($order_id, $customer_name, $customer_email) {
    $to = get_option('admin_email');
    $subject = "New Order #$order_id from Senior Supper Services";
    $message = "New order received!\n\n";
    $message .= "Order ID: $order_id\n";
    $message .= "Customer: $customer_name\n";
    $message .= "Email: $customer_email\n\n";
    $message .= "Please log in to the admin panel to view details: " . admin_url('admin.php?page=sss_orders');
    
    wp_mail($to, $subject, $message);
}, 10, 3);
