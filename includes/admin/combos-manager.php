<?php
/**
 * Combos Management
 */

class SSS_Combos_Manager {
    
    public static function render_page() {
        ?>
        <div class="wrap sss-combos-wrap">
            <h1>Combo Meals Management</h1>
            
            <?php
            // Handle actions
            if (isset($_POST['action']) && wp_verify_nonce($_POST['sss_nonce'], 'sss_combos_nonce')) {
                if ($_POST['action'] === 'add_combo') {
                    self::add_combo();
                } elseif ($_POST['action'] === 'edit_combo') {
                    self::edit_combo();
                } elseif ($_POST['action'] === 'delete_combo') {
                    self::delete_combo();
                } elseif ($_POST['action'] === 'add_combo_item') {
                    self::add_combo_item();
                }
            }
            
            // Display messages
            if (isset($_GET['message'])) {
                if ($_GET['message'] === 'added') {
                    echo '<div class="notice notice-success"><p>Combo added successfully!</p></div>';
                } elseif ($_GET['message'] === 'updated') {
                    echo '<div class="notice notice-success"><p>Combo updated successfully!</p></div>';
                } elseif ($_GET['message'] === 'deleted') {
                    echo '<div class="notice notice-success"><p>Combo deleted successfully!</p></div>';
                } elseif ($_GET['message'] === 'item_added') {
                    echo '<div class="notice notice-success"><p>Combo item added successfully!</p></div>';
                }
            }
            ?>
            
            <div class="sss-combos-container">
                <div class="sss-combos-form">
                    <h2><?php echo isset($_GET['edit']) ? 'Edit Combo' : 'Add New Combo'; ?></h2>
                    
                    <?php
                    $combo = null;
                    if (isset($_GET['edit'])) {
                        $combo = self::get_combo(intval($_GET['edit']));
                    }
                    ?>
                    
                    <form method="POST" action="">
                        <?php wp_nonce_field('sss_combos_nonce', 'sss_nonce'); ?>
                        
                        <input type="hidden" name="action" value="<?php echo isset($_GET['edit']) ? 'edit_combo' : 'add_combo'; ?>" />
                        
                        <?php if ($combo): ?>
                            <input type="hidden" name="combo_id" value="<?php echo esc_attr($combo->id); ?>" />
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="combo_name">Combo Name</label>
                            <input type="text" 
                                   name="combo_name" 
                                   id="combo_name" 
                                   value="<?php echo esc_attr($combo?->combo_name ?? ''); ?>" 
                                   placeholder="e.g., 2-Way Combo, 3-Way Combo"
                                   required />
                        </div>
                        
                        <div class="form-group">
                            <label for="combo_type">Combo Type</label>
                            <input type="text" 
                                   name="combo_type" 
                                   id="combo_type" 
                                   value="<?php echo esc_attr($combo?->combo_type ?? ''); ?>" 
                                   placeholder="e.g., 2-way, 3-way"
                                   required />
                        </div>
                        
                        <div class="form-group">
                            <label for="delivery_day">Delivery Day</label>
                            <select name="delivery_day" id="delivery_day" required>
                                <option value="">Select Delivery Day</option>
                                <option value="Monday" <?php selected($combo?->delivery_day, 'Monday'); ?>>Monday</option>
                                <option value="Tuesday" <?php selected($combo?->delivery_day, 'Tuesday'); ?>>Tuesday</option>
                                <option value="Wednesday" <?php selected($combo?->delivery_day, 'Wednesday'); ?>>Wednesday</option>
                                <option value="Thursday" <?php selected($combo?->delivery_day, 'Thursday'); ?>>Thursday</option>
                                <option value="Friday" <?php selected($combo?->delivery_day, 'Friday'); ?>>Friday</option>
                                <option value="Saturday" <?php selected($combo?->delivery_day, 'Saturday'); ?>>Saturday</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="delivery_on_day">Will Be Delivered On</label>
                            <select name="delivery_on_day" id="delivery_on_day" required>
                                <option value="">Select Delivery Day</option>
                                <option value="Monday" <?php selected($combo?->delivery_on_day, 'Monday'); ?>>Monday</option>
                                <option value="Tuesday" <?php selected($combo?->delivery_on_day, 'Tuesday'); ?>>Tuesday</option>
                                <option value="Wednesday" <?php selected($combo?->delivery_on_day, 'Wednesday'); ?>>Wednesday</option>
                                <option value="Thursday" <?php selected($combo?->delivery_on_day, 'Thursday'); ?>>Thursday</option>
                                <option value="Friday" <?php selected($combo?->delivery_on_day, 'Friday'); ?>>Friday</option>
                                <option value="Saturday" <?php selected($combo?->delivery_on_day, 'Saturday'); ?>>Saturday</option>
                            </select>
                            <p class="description">Example: Friday combo will be delivered on Wednesday</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="num_items">Number of Items to Select</label>
                            <input type="number" 
                                   name="num_items_to_select" 
                                   id="num_items" 
                                   value="<?php echo esc_attr($combo?->num_items_to_select ?? '2'); ?>" 
                                   min="1" 
                                   required />
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Combo Price</label>
                            <input type="number" 
                                   name="price" 
                                   id="price" 
                                   value="<?php echo esc_attr($combo?->price ?? '0.00'); ?>" 
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
                                       <?php checked($combo?->status, 1); ?> />
                                Active
                            </label>
                        </div>
                        
                        <button type="submit" class="button button-primary">
                            <?php echo isset($_GET['edit']) ? 'Update Combo' : 'Add Combo'; ?>
                        </button>
                        <?php if (isset($_GET['edit'])): ?>
                            <a href="?page=sss_combos" class="button">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="sss-combos-list">
                    <h2>All Combos</h2>
                    
                    <?php
                    $combos = self::get_all_combos();
                    
                    if ($combos):
                        ?>
                        <table class="wp-list-table widefat striped">
                            <thead>
                                <tr>
                                    <th>Combo Name</th>
                                    <th>Type</th>
                                    <th>Ordered On</th>
                                    <th>Delivered On</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($combos as $combo): ?>
                                    <tr>
                                        <td><?php echo esc_html($combo->combo_name); ?></td>
                                        <td><?php echo esc_html($combo->combo_type); ?></td>
                                        <td><?php echo esc_html($combo->delivery_day); ?></td>
                                        <td><?php echo esc_html($combo->delivery_on_day); ?></td>
                                        <td>$<?php echo esc_html(number_format($combo->price, 2)); ?></td>
                                        <td><?php echo $combo->status ? 'Active' : 'Inactive'; ?></td>
                                        <td>
                                            <a href="?page=sss_combos&edit=<?php echo intval($combo->id); ?>&items" class="button button-small">Edit Items</a>
                                            <a href="?page=sss_combos&edit=<?php echo intval($combo->id); ?>" class="button button-small">Edit</a>
                                            <form method="POST" style="display:inline;">
                                                <?php wp_nonce_field('sss_combos_nonce', 'sss_nonce'); ?>
                                                <input type="hidden" name="action" value="delete_combo" />
                                                <input type="hidden" name="combo_id" value="<?php echo esc_attr($combo->id); ?>" />
                                                <button type="submit" class="button button-small button-link-delete" onclick="return confirm('Are you sure?');">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No combos added yet.</p>
                    <?php endif; ?>
                </div>
                
                <?php
                // Combo Items Section
                if (isset($_GET['edit']) && isset($_GET['items'])) {
                    $combo = self::get_combo(intval($_GET['edit']));
                    if ($combo):
                        ?>
                        <div class="sss-combo-items">
                            <h2>Combo Items for <?php echo esc_html($combo->combo_name); ?></h2>
                            
                            <form method="POST" action="">
                                <?php wp_nonce_field('sss_combos_nonce', 'sss_nonce'); ?>
                                
                                <input type="hidden" name="action" value="add_combo_item" />
                                <input type="hidden" name="combo_id" value="<?php echo esc_attr($combo->id); ?>" />
                                
                                <div class="form-group">
                                    <label for="item_name">Item Name</label>
                                    <input type="text" 
                                           name="item_name" 
                                           id="item_name" 
                                           placeholder="e.g., Chicken & Rice"
                                           required />
                                </div>
                                
                                <div class="form-group">
                                    <label for="item_price">Item Price</label>
                                    <input type="number" 
                                           name="item_price" 
                                           id="item_price" 
                                           step="0.01" 
                                           min="0" 
                                           required />
                                </div>
                                
                                <button type="submit" class="button button-primary">Add Item</button>
                            </form>
                            
                            <h3>Items in this Combo</h3>
                            <?php
                            $items = self::get_combo_items($combo->id);
                            if ($items):
                                ?>
                                <table class="wp-list-table widefat">
                                    <thead>
                                        <tr>
                                            <th>Item Name</th>
                                            <th>Price</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item): ?>
                                            <tr>
                                                <td><?php echo esc_html($item->item_name); ?></td>
                                                <td>$<?php echo esc_html(number_format($item->item_price, 2)); ?></td>
                                                <td>
                                                    <form method="POST" style="display:inline;">
                                                        <?php wp_nonce_field('sss_combos_nonce', 'sss_nonce'); ?>
                                                        <input type="hidden" name="action" value="delete_combo_item" />
                                                        <input type="hidden" name="item_id" value="<?php echo esc_attr($item->id); ?>" />
                                                        <button type="submit" class="button button-small button-link-delete" onclick="return confirm('Are you sure?');">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p>No items added yet.</p>
                            <?php endif; ?>
                        </div>
                        <?php
                    endif;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get Combo by ID
     */
    private static function get_combo($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . SSS_TABLE_PREFIX . "combos WHERE id = %d",
            $id
        ));
    }
    
    /**
     * Get All Combos
     */
    private static function get_all_combos() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM " . SSS_TABLE_PREFIX . "combos WHERE status = 1 ORDER BY combo_name ASC"
        );
    }
    
    /**
     * Get Combo Items
     */
    private static function get_combo_items($combo_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . SSS_TABLE_PREFIX . "combo_items WHERE combo_id = %d ORDER BY id ASC",
            $combo_id
        ));
    }
    
    /**
     * Add Combo
     */
    private static function add_combo() {
        global $wpdb;
        
        $wpdb->insert(
            SSS_TABLE_PREFIX . 'combos',
            array(
                'combo_name' => sanitize_text_field($_POST['combo_name']),
                'combo_type' => sanitize_text_field($_POST['combo_type']),
                'delivery_day' => sanitize_text_field($_POST['delivery_day']),
                'delivery_on_day' => sanitize_text_field($_POST['delivery_on_day']),
                'num_items_to_select' => intval($_POST['num_items_to_select']),
                'price' => floatval($_POST['price']),
                'status' => isset($_POST['status']) ? 1 : 0
            ),
            array('%s', '%s', '%s', '%s', '%d', '%f', '%d')
        );
        
        wp_redirect(add_query_arg('message', 'added', admin_url('admin.php?page=sss_combos')));
        exit;
    }
    
    /**
     * Edit Combo
     */
    private static function edit_combo() {
        global $wpdb;
        
        $wpdb->update(
            SSS_TABLE_PREFIX . 'combos',
            array(
                'combo_name' => sanitize_text_field($_POST['combo_name']),
                'combo_type' => sanitize_text_field($_POST['combo_type']),
                'delivery_day' => sanitize_text_field($_POST['delivery_day']),
                'delivery_on_day' => sanitize_text_field($_POST['delivery_on_day']),
                'num_items_to_select' => intval($_POST['num_items_to_select']),
                'price' => floatval($_POST['price']),
                'status' => isset($_POST['status']) ? 1 : 0
            ),
            array('id' => intval($_POST['combo_id'])),
            array('%s', '%s', '%s', '%s', '%d', '%f', '%d'),
            array('%d')
        );
        
        wp_redirect(add_query_arg('message', 'updated', admin_url('admin.php?page=sss_combos')));
        exit;
    }
    
    /**
     * Delete Combo
     */
    private static function delete_combo() {
        global $wpdb;
        
        $wpdb->delete(
            SSS_TABLE_PREFIX . 'combos',
            array('id' => intval($_POST['combo_id'])),
            array('%d')
        );
        
        wp_redirect(add_query_arg('message', 'deleted', admin_url('admin.php?page=sss_combos')));
        exit;
    }
    
    /**
     * Add Combo Item
     */
    private static function add_combo_item() {
        global $wpdb;
        
        $wpdb->insert(
            SSS_TABLE_PREFIX . 'combo_items',
            array(
                'combo_id' => intval($_POST['combo_id']),
                'item_name' => sanitize_text_field($_POST['item_name']),
                'item_price' => floatval($_POST['item_price'])
            ),
            array('%d', '%s', '%f')
        );
        
        $combo_id = intval($_POST['combo_id']);
        wp_redirect(add_query_arg(
            array('message' => 'item_added', 'edit' => $combo_id, 'items' => '1'),
            admin_url('admin.php?page=sss_combos')
        ));
        exit;
    }
}
