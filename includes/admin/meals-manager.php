<?php
/**
 * Meals Management
 */

class SSS_Meals_Manager {
    
    public static function render_page() {
        ?>
        <div class="wrap sss-meals-wrap">
            <h1>Meals Management</h1>
            
            <?php
            // Handle actions
            if (isset($_POST['action']) && wp_verify_nonce($_POST['sss_nonce'], 'sss_meals_nonce')) {
                if ($_POST['action'] === 'add_meal') {
                    self::add_meal();
                } elseif ($_POST['action'] === 'edit_meal') {
                    self::edit_meal();
                } elseif ($_POST['action'] === 'delete_meal') {
                    self::delete_meal();
                }
            }
            
            // Display messages
            if (isset($_GET['message'])) {
                if ($_GET['message'] === 'added') {
                    echo '<div class="notice notice-success"><p>Meal added successfully!</p></div>';
                } elseif ($_GET['message'] === 'updated') {
                    echo '<div class="notice notice-success"><p>Meal updated successfully!</p></div>';
                } elseif ($_GET['message'] === 'deleted') {
                    echo '<div class="notice notice-success"><p>Meal deleted successfully!</p></div>';
                }
            }
            ?>
            
            <div class="sss-meals-container">
                <div class="sss-meals-form">
                    <h2><?php echo isset($_GET['edit']) ? 'Edit Meal' : 'Add New Meal'; ?></h2>
                    
                    <?php
                    $meal = null;
                    if (isset($_GET['edit'])) {
                        $meal = self::get_meal(intval($_GET['edit']));
                    }
                    ?>
                    
                    <form method="POST" action="">
                        <?php wp_nonce_field('sss_meals_nonce', 'sss_nonce'); ?>
                        
                        <input type="hidden" name="action" value="<?php echo isset($_GET['edit']) ? 'edit_meal' : 'add_meal'; ?>" />
                        
                        <?php if ($meal): ?>
                            <input type="hidden" name="meal_id" value="<?php echo esc_attr($meal->id); ?>" />
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="day_of_week">Day of Week</label>
                            <select name="day_of_week" id="day_of_week" required>
                                <option value="">Select Day</option>
                                <option value="Monday" <?php selected($meal?->day_of_week, 'Monday'); ?>>Monday</option>
                                <option value="Tuesday" <?php selected($meal?->day_of_week, 'Tuesday'); ?>>Tuesday</option>
                                <option value="Wednesday" <?php selected($meal?->day_of_week, 'Wednesday'); ?>>Wednesday</option>
                                <option value="Thursday" <?php selected($meal?->day_of_week, 'Thursday'); ?>>Thursday</option>
                                <option value="Friday" <?php selected($meal?->day_of_week, 'Friday'); ?>>Friday</option>
                                <option value="Saturday" <?php selected($meal?->day_of_week, 'Saturday'); ?>>Saturday</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="meal_name">Meal Name</label>
                            <input type="text" 
                                   name="meal_name" 
                                   id="meal_name" 
                                   value="<?php echo esc_attr($meal?->meal_name ?? ''); ?>" 
                                   required />
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" rows="4"><?php echo esc_textarea($meal?->description ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Price</label>
                            <input type="number" 
                                   name="price" 
                                   id="price" 
                                   value="<?php echo esc_attr($meal?->price ?? '15.50'); ?>" 
                                   step="0.01" 
                                   min="0" 
                                   required />
                        </div>
                        
                        <div class="form-group">
                            <label for="status">
                                <input type="checkbox" 
                                       name="status" 
                                       id="status" 
                                       value="1" 
                                       <?php checked($meal?->status, 1); ?> />
                                Active
                            </label>
                        </div>
                        
                        <button type="submit" class="button button-primary">
                            <?php echo isset($_GET['edit']) ? 'Update Meal' : 'Add Meal'; ?>
                        </button>
                        <?php if (isset($_GET['edit'])): ?>
                            <a href="?page=sss_meals" class="button">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="sss-meals-list">
                    <h2>Meals by Day</h2>
                    
                    <?php
                    $days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
                    foreach ($days as $day) {
                        $meals = self::get_meals_by_day($day);
                        $enabled = SSS_Database_Schema::get_setting(strtolower($day) . '_enabled', '1');
                        
                        if (!$enabled) {
                            continue;
                        }
                        ?>
                        <div class="sss-day-section">
                            <h3><?php echo esc_html($day); ?> Delivery</h3>
                            
                            <?php if ($meals): ?>
                                <table class="wp-list-table widefat">
                                    <thead>
                                        <tr>
                                            <th>Meal Name</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($meals as $meal): ?>
                                            <tr>
                                                <td><?php echo esc_html($meal->meal_name); ?></td>
                                                <td>$<?php echo esc_html(number_format($meal->price, 2)); ?></td>
                                                <td><?php echo $meal->status ? 'Active' : 'Inactive'; ?></td>
                                                <td>
                                                    <a href="?page=sss_meals&edit=<?php echo intval($meal->id); ?>" class="button button-small">Edit</a>
                                                    <form method="POST" style="display:inline;">
                                                        <?php wp_nonce_field('sss_meals_nonce', 'sss_nonce'); ?>
                                                        <input type="hidden" name="action" value="delete_meal" />
                                                        <input type="hidden" name="meal_id" value="<?php echo esc_attr($meal->id); ?>" />
                                                        <button type="submit" class="button button-small button-link-delete" onclick="return confirm('Are you sure?');">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p>No meals added for <?php echo esc_html($day); ?> yet.</p>
                            <?php endif; ?>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get Meal by ID
     */
    private static function get_meal($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . SSS_TABLE_PREFIX . "meals WHERE id = %d",
            $id
        ));
    }
    
    /**
     * Get Meals by Day
     */
    private static function get_meals_by_day($day) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . SSS_TABLE_PREFIX . "meals WHERE day_of_week = %s ORDER BY created_at ASC",
            $day
        ));
    }
    
    /**
     * Add Meal
     */
    private static function add_meal() {
        global $wpdb;
        
        $wpdb->insert(
            SSS_TABLE_PREFIX . 'meals',
            array(
                'day_of_week' => sanitize_text_field($_POST['day_of_week']),
                'meal_name' => sanitize_text_field($_POST['meal_name']),
                'description' => sanitize_textarea_field($_POST['description']),
                'price' => floatval($_POST['price']),
                'status' => isset($_POST['status']) ? 1 : 0
            ),
            array('%s', '%s', '%s', '%f', '%d')
        );
        
        wp_redirect(add_query_arg('message', 'added', admin_url('admin.php?page=sss_meals')));
        exit;
    }
    
    /**
     * Edit Meal
     */
    private static function edit_meal() {
        global $wpdb;
        
        $wpdb->update(
            SSS_TABLE_PREFIX . 'meals',
            array(
                'day_of_week' => sanitize_text_field($_POST['day_of_week']),
                'meal_name' => sanitize_text_field($_POST['meal_name']),
                'description' => sanitize_textarea_field($_POST['description']),
                'price' => floatval($_POST['price']),
                'status' => isset($_POST['status']) ? 1 : 0
            ),
            array('id' => intval($_POST['meal_id'])),
            array('%s', '%s', '%s', '%f', '%d'),
            array('%d')
        );
        
        wp_redirect(add_query_arg('message', 'updated', admin_url('admin.php?page=sss_meals')));
        exit;
    }
    
    /**
     * Delete Meal
     */
    private static function delete_meal() {
        global $wpdb;
        
        $wpdb->delete(
            SSS_TABLE_PREFIX . 'meals',
            array('id' => intval($_POST['meal_id'])),
            array('%d')
        );
        
        wp_redirect(add_query_arg('message', 'deleted', admin_url('admin.php?page=sss_meals')));
        exit;
    }
}
