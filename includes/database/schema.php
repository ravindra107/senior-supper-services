<?php
/**
 * Database Schema and Setup
 */

class SSS_Database_Schema {
    
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Meals table
        $wpdb->query("CREATE TABLE IF NOT EXISTS " . SSS_TABLE_PREFIX . "meals (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            day_of_week VARCHAR(20) NOT NULL,
            meal_name VARCHAR(255) NOT NULL,
            description LONGTEXT,
            price DECIMAL(10, 2) NOT NULL,
            status TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX day_of_week (day_of_week),
            INDEX status (status)
        ) $charset_collate;");
        
        // Sides table
        $wpdb->query("CREATE TABLE IF NOT EXISTS " . SSS_TABLE_PREFIX . "sides (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            side_name VARCHAR(255) NOT NULL,
            field_type VARCHAR(50) NOT NULL,
            price DECIMAL(10, 2) NOT NULL,
            has_instructions TINYINT(1) DEFAULT 1,
            status TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX status (status)
        ) $charset_collate;");
        
        // Combos table
        $wpdb->query("CREATE TABLE IF NOT EXISTS " . SSS_TABLE_PREFIX . "combos (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            combo_name VARCHAR(255) NOT NULL,
            combo_type VARCHAR(50) NOT NULL,
            delivery_day VARCHAR(20) NOT NULL,
            delivery_on_day VARCHAR(20),
            price DECIMAL(10, 2) NOT NULL,
            num_items_to_select INT(11) DEFAULT 2,
            status TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX delivery_day (delivery_day),
            INDEX status (status)
        ) $charset_collate;");
        
        // Combo Items table
        $wpdb->query("CREATE TABLE IF NOT EXISTS " . SSS_TABLE_PREFIX . "combo_items (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            combo_id BIGINT(20) UNSIGNED NOT NULL,
            item_name VARCHAR(255) NOT NULL,
            item_price DECIMAL(10, 2) NOT NULL,
            status TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (combo_id) REFERENCES " . SSS_TABLE_PREFIX . "combos(id) ON DELETE CASCADE,
            INDEX combo_id (combo_id),
            INDEX status (status)
        ) $charset_collate;");
        
        // Settings table
        $wpdb->query("CREATE TABLE IF NOT EXISTS " . SSS_TABLE_PREFIX . "settings (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(255) UNIQUE NOT NULL,
            setting_value LONGTEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX setting_key (setting_key)
        ) $charset_collate;");
        
        // Orders table
        $wpdb->query("CREATE TABLE IF NOT EXISTS " . SSS_TABLE_PREFIX . "orders (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_name VARCHAR(255) NOT NULL,
            customer_email VARCHAR(255) NOT NULL,
            customer_phone VARCHAR(20) NOT NULL,
            customer_address LONGTEXT NOT NULL,
            order_date DATE NOT NULL,
            delivery_days LONGTEXT,
            total_price DECIMAL(10, 2) NOT NULL,
            order_notes LONGTEXT,
            status VARCHAR(50) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX customer_email (customer_email),
            INDEX order_date (order_date),
            INDEX status (status)
        ) $charset_collate;");
        
        // Order Items table
        $wpdb->query("CREATE TABLE IF NOT EXISTS " . SSS_TABLE_PREFIX . "order_items (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT(20) UNSIGNED NOT NULL,
            item_type VARCHAR(50) NOT NULL,
            item_name VARCHAR(255) NOT NULL,
            item_price DECIMAL(10, 2) NOT NULL,
            quantity INT(11) NOT NULL,
            special_instructions LONGTEXT,
            subtotal DECIMAL(10, 2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES " . SSS_TABLE_PREFIX . "orders(id) ON DELETE CASCADE,
            INDEX order_id (order_id),
            INDEX item_type (item_type)
        ) $charset_collate;");
    }
    
    public static function create_default_settings() {
        global $wpdb;
        
        $defaults = array(
            'monday_enabled' => '1',
            'tuesday_enabled' => '1',
            'wednesday_enabled' => '1',
            'thursday_enabled' => '1',
            'friday_enabled' => '1',
            'saturday_enabled' => '1',
            'sunday_enabled' => '0',
            'first_meal_price' => '15.50',
            'other_meals_price' => '13.50'
        );
        
        foreach ($defaults as $key => $value) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM " . SSS_TABLE_PREFIX . "settings WHERE setting_key = %s",
                $key
            ));
            
            if (!$existing) {
                $wpdb->insert(
                    SSS_TABLE_PREFIX . 'settings',
                    array(
                        'setting_key' => $key,
                        'setting_value' => $value
                    ),
                    array('%s', '%s')
                );
            }
        }
    }
    
    /**
     * Get Setting Value
     */
    public static function get_setting($key, $default = '') {
        global $wpdb;
        
        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM " . SSS_TABLE_PREFIX . "settings WHERE setting_key = %s",
            $key
        ));
        
        return $value !== null ? $value : $default;
    }
    
    /**
     * Update Setting Value
     */
    public static function update_setting($key, $value) {
        global $wpdb;
        
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . SSS_TABLE_PREFIX . "settings WHERE setting_key = %s",
            $key
        ));
        
        if ($existing) {
            return $wpdb->update(
                SSS_TABLE_PREFIX . 'settings',
                array('setting_value' => $value),
                array('setting_key' => $key),
                array('%s'),
                array('%s')
            );
        } else {
            return $wpdb->insert(
                SSS_TABLE_PREFIX . 'settings',
                array(
                    'setting_key' => $key,
                    'setting_value' => $value
                ),
                array('%s', '%s')
            );
        }
    }
}