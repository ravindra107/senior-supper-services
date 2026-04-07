<?php
/**
 * Settings Management
 */

class SSS_Settings {
    
    public static function render_page() {
        ?>
        <div class="wrap sss-settings-wrap">
            <h1>Senior Supper Services - Settings</h1>
            
            <?php
            // Handle form submission
            if (isset($_POST['sss_settings_submit']) && wp_verify_nonce($_POST['sss_settings_nonce'], 'sss_settings')) {
                self::save_settings();
                echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
            }
            ?>
            
            <form method="POST" action="">
                <?php wp_nonce_field('sss_settings', 'sss_settings_nonce'); ?>
                
                <div class="sss-settings-section">
                    <h2>Delivery Days Configuration</h2>
                    <p class="description">Enable or disable delivery days for your customers</p>
                    
                    <table class="sss-settings-table">
                        <tr>
                            <th>Day</th>
                            <th>Enable</th>
                        </tr>
                        <?php
                        $days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
                        foreach ($days as $day) {
                            $key = strtolower($day) . '_enabled';
                            $enabled = SSS_Database_Schema::get_setting($key, '1');
                            ?>
                            <tr>
                                <td><?php echo esc_html($day); ?></td>
                                <td>
                                    <input type="checkbox" 
                                           name="sss_settings[<?php echo esc_attr($key); ?>]" 
                                           value="1" 
                                           <?php checked($enabled, '1'); ?> />
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </table>
                </div>
                
                <div class="sss-settings-section">
                    <h2>Pricing Configuration</h2>
                    <p class="description">Set your meal pricing</p>
                    
                    <table class="sss-settings-table">
                        <tr>
                            <th>Item</th>
                            <th>Price</th>
                        </tr>
                        <tr>
                            <td><label for="first_meal_price">First Meal Price</label></td>
                            <td>
                                <input type="number" 
                                       id="first_meal_price"
                                       name="sss_settings[first_meal_price]" 
                                       value="<?php echo esc_attr(SSS_Database_Schema::get_setting('first_meal_price', '15.50')); ?>" 
                                       step="0.01" 
                                       min="0" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="other_meals_price">Other Meals Price</label></td>
                            <td>
                                <input type="number" 
                                       id="other_meals_price"
                                       name="sss_settings[other_meals_price]" 
                                       value="<?php echo esc_attr(SSS_Database_Schema::get_setting('other_meals_price', '13.50')); ?>" 
                                       step="0.01" 
                                       min="0" />
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="sss-settings-section">
                    <h2>Field Type Configuration</h2>
                    <p class="description">Available field types for sides and add-ons</p>
                    
                    <ul>
                        <li><strong>Checkbox:</strong> Allow customers to select multiple options</li>
                        <li><strong>Radio:</strong> Allow customers to select only one option</li>
                        <li><strong>Dropdown:</strong> Single selection from a dropdown list</li>
                        <li><strong>Text Field:</strong> For single-line input like special instructions</li>
                    </ul>
                </div>
                
                <div class="sss-button-group">
                    <input type="submit" 
                           name="sss_settings_submit" 
                           class="button button-primary" 
                           value="Save Settings" />
                </div>
            </form>
        </div>
        <?php
    }
    
    /**
     * Save Settings
     */
    private static function save_settings() {
        if (!isset($_POST['sss_settings'])) {
            return;
        }
        
        $settings = array_map('sanitize_text_field', $_POST['sss_settings']);
        
        foreach ($settings as $key => $value) {
            // Handle checkboxes
            if (strpos($key, '_enabled') !== false) {
                $value = isset($_POST['sss_settings'][$key]) ? '1' : '0';
            }
            
            SSS_Database_Schema::update_setting($key, $value);
        }
    }
}
