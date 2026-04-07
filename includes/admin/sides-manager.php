<?php
/**
 * Sides Management
 */

class SSS_Sides_Manager {
    
    public static function render_page() {
        ?>
        <div class="wrap sss-sides-wrap">
            <h1>Sides & Add-ons Management</h1>
            
            <?php
            // Handle actions
            if (isset($_POST['action']) && wp_verify_nonce($_POST['sss_nonce'], 'sss_sides_nonce')) {
                if ($_POST['action'] === 'add_side') {
                    self::add_side();
                } elseif ($_POST['action'] === 'edit_side') {
                    self::edit_side();
                } elseif ($_POST['action'] === 'delete_side') {
                    self::delete_side();
                }
            }
            
            // Display messages
            if (isset($_GET['message'])) {
                if ($_GET['message'] === 'added') {
                    echo '<div class="notice notice-success"><p>Side added successfully!</p></div>';
                } elseif ($_GET['message'] === 'updated') {
                    echo '<div class="notice notice-success"><p>Side updated successfully!</p></div>';
                } elseif ($_GET['message'] === 'deleted') {
                    echo '<div class="notice notice-success"><p>Side deleted successfully!</p></div>';
                }
            }
            ?>
            
            <div class="sss-sides-container">
                <div class="sss-sides-form">
                    <h2><?php echo isset($_GET['edit']) ? 'Edit Side' : 'Add New Side'; ?></h2>
                    
                    <?php
                    $side = null;
                    if (isset($_GET['edit'])) {
                        $side = self::get_side(intval($_GET['edit']));
                    }
                    ?>
                    
                    <form method="POST" action="">
                        <?php wp_nonce_field('sss_sides_nonce', 'sss_nonce'); ?>
                        
                        <input type="hidden" name="action" value="<?php echo isset($_GET['edit']) ? 'edit_side' : 'add_side'; ?>" />
                        
                        <?php if ($side): ?>
                            <input type="hidden" name="side_id" value="<?php echo esc_attr($side->id); ?>" />
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="side_name">Side Name</label>
                            <input type="text" 
                                   name="side_name" 
                                   id="side_name" 
                                   value="<?php echo esc_attr($side?->side_name ?? ''); ?>" 
                                   placeholder="e.g., Loaf/Bread, Soup, Overnight Oats"
                                   required />
                            <p class="description">Available sides: Loaf/Bread, Soup, Overnight Oats, Veggies Tray, Fruit Bowl, Side Salad, Dessert</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="field_type">Field Type</label>
                            <select name="field_type" id="field_type" required>
                                <option value="">Select Type</option>
                                <option value="checkbox" <?php selected($side?->field_type, 'checkbox'); ?>>Checkbox</option>
                                <option value="radio" <?php selected($side?->field_type, 'radio'); ?>>Radio</option>
                                <option value="dropdown" <?php selected($side?->field_type, 'dropdown'); ?>>Dropdown</option>
                            </select>
                            <p class="description">
                                <strong>Checkbox:</strong> Multiple selections<br>
                                <strong>Radio:</strong> Single selection (Yes/No)<br>
                                <strong>Dropdown:</strong> Single selection from list
                            </p>
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Price</label>
                            <input type="number" 
                                   name="price" 
                                   id="price" 
                                   value="<?php echo esc_attr($side?->price ?? '0.00'); ?>" 
                                   step="0.01" 
                                   min="0" 
                                   required />
                        </div>
                        
                        <div class="form-group">
                            <label for="has_instructions">
                                <input type="checkbox" 
                                       name="has_instructions" 
                                       id="has_instructions" 
                                       value="1" 
                                       <?php checked($side?->has_instructions, 1); ?> />
                                Enable Special Instructions Field
                            </label>
                            <p class="description">When enabled, customers can add special instructions for this side</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">
                                <input type="checkbox" 
                                       name="status" 
                                       id="status" 
                                       value="1" 
                                       <?php checked($side?->status, 1); ?> />
                                Active
                            </label>
                        </div>
                        
                        <button type="submit" class="button button-primary">
                            <?php echo isset($_GET['edit']) ? 'Update Side' : 'Add Side'; ?>
                        </button>
                        <?php if (isset($_GET['edit'])): ?>
                            <a href="?page=sss_sides" class="button">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="sss-sides-list">
                    <h2>All Sides & Add-ons</h2>
                    
                    <?php
                    $sides = self::get_all_sides();
                    
                    if ($sides):
                        ?>
                        <table class="wp-list-table widefat striped">
                            <thead>
                                <tr>
                                    <th>Side Name</th>
                                    <th>Field Type</th>
                                    <th>Price</th>
                                    <th>Instructions Field</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sides as $side): ?>
                                    <tr>
                                        <td><?php echo esc_html($side->side_name); ?></td>
                                        <td><?php echo esc_html(ucfirst($side->field_type)); ?></td>
                                        <td>$<?php echo esc_html(number_format($side->price, 2)); ?></td>
                                        <td><?php echo $side->has_instructions ? 'Yes' : 'No'; ?></td>
                                        <td><?php echo $side->status ? 'Active' : 'Inactive'; ?></td>
                                        <td>
                                            <a href="?page=sss_sides&edit=<?php echo intval($side->id); ?>" class="button button-small">Edit</a>
                                            <form method="POST" style="display:inline;">
                                                <?php wp_nonce_field('sss_sides_nonce', 'sss_nonce'); ?>
                                                <input type="hidden" name="action" value="delete_side" />
                                                <input type="hidden" name="side_id" value="<?php echo esc_attr($side->id); ?>" />
                                                <button type="submit" class="button button-small button-link-delete" onclick="return confirm('Are you sure?');">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No sides added yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get Side by ID
     */
    private static function get_side($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . SSS_TABLE_PREFIX . "sides WHERE id = %d",
            $id
        ));
    }
    
    /**
     * Get All Sides
     */
    private static function get_all_sides() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM " . SSS_TABLE_PREFIX . "sides WHERE status = 1 ORDER BY side_name ASC"
        );
    }
    
    /**
     * Add Side
     */
    private static function add_side() {
        global $wpdb;
        
        $wpdb->insert(
            SSS_TABLE_PREFIX . 'sides',
            array(
                'side_name' => sanitize_text_field($_POST['side_name']),
                'field_type' => sanitize_text_field($_POST['field_type']),
                'price' => floatval($_POST['price']),
                'has_instructions' => isset($_POST['has_instructions']) ? 1 : 0,
                'status' => isset($_POST['status']) ? 1 : 0
            ),
            array('%s', '%s', '%f', '%d', '%d')
        );
        
        wp_redirect(add_query_arg('message', 'added', admin_url('admin.php?page=sss_sides')));
        exit;
    }
    
    /**
     * Edit Side
     */
    private static function edit_side() {
        global $wpdb;
        
        $wpdb->update(
            SSS_TABLE_PREFIX . 'sides',
            array(
                'side_name' => sanitize_text_field($_POST['side_name']),
                'field_type' => sanitize_text_field($_POST['field_type']),
                'price' => floatval($_POST['price']),
                'has_instructions' => isset($_POST['has_instructions']) ? 1 : 0,
                'status' => isset($_POST['status']) ? 1 : 0
            ),
            array('id' => intval($_POST['side_id'])),
            array('%s', '%s', '%f', '%d', '%d'),
            array('%d')
        );
        
        wp_redirect(add_query_arg('message', 'updated', admin_url('admin.php?page=sss_sides')));
        exit;
    }
    
    /**
     * Delete Side
     */
    private static function delete_side() {
        global $wpdb;
        
        $wpdb->delete(
            SSS_TABLE_PREFIX . 'sides',
            array('id' => intval($_POST['side_id'])),
            array('%d')
        );
        
        wp_redirect(add_query_arg('message', 'deleted', admin_url('admin.php?page=sss_sides')));
        exit;
    }
}
