<?php
/**
 * Form Rendering
 */

class SSS_Form_Renderer {
    
    public static function render_form() {
        $days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
        $enabled_days = array();
        
        foreach ($days as $day) {
            $key = strtolower($day) . '_enabled';
            if (SSS_Database_Schema::get_setting($key, '1') === '1') {
                $enabled_days[] = $day;
            }
        }
        
        ?>
        <div class="sss-order-form-wrapper">
            <form id="sss-order-form" class="sss-order-form" method="POST">
                
                <div class="form-section personal-info">
                    <h2>Personal Information</h2>
                    
                    <div class="form-group">
                        <label for="sss_name">Name *</label>
                        <input type="text" id="sss_name" name="name" required />
                    </div>
                    
                    <div class="form-group">
                        <label for="sss_email">Email *</label>
                        <input type="email" id="sss_email" name="email" required />
                    </div>
                    
                    <div class="form-group">
                        <label for="sss_phone">Phone *</label>
                        <input type="tel" id="sss_phone" name="phone" required />
                    </div>
                    
                    <div class="form-group">
                        <label for="sss_address">Address *</label>
                        <textarea id="sss_address" name="address" rows="3" required></textarea>
                    </div>
                </div>
                
                <?php
                // Render form for each enabled day
                foreach ($enabled_days as $day) {
                    self::render_day_section($day);
                }
                ?>
                
                <div class="form-section pricing-summary">
                    <h2>Order Summary</h2>
                    <div id="sss-pricing-summary">
                        <p>Price will be calculated based on your selections...</p>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" id="sss-submit-btn" class="button button-primary button-large">Submit Order</button>
                    <button type="reset" class="button button-secondary">Clear Form</button>
                </div>
                
                <?php wp_nonce_field('sss_form_nonce', 'sss_nonce'); ?>
            </form>
            
            <div id="sss-success-message" style="display: none;" class="notice notice-success">
                <p>Your order has been submitted successfully! We will contact you soon to confirm.</p>
            </div>
            
            <div id="sss-error-message" style="display: none;" class="notice notice-error">
                <p id="sss-error-text"></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Day Section
     */
    private static function render_day_section($day) {
        global $wpdb;
        
        $meals = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . SSS_TABLE_PREFIX . "meals WHERE day_of_week = %s AND status = 1 ORDER BY created_at ASC",
            $day
        ));
        
        $sides = $wpdb->get_results(
            "SELECT * FROM " . SSS_TABLE_PREFIX . "sides WHERE status = 1 ORDER BY side_name ASC"
        );
        
        $combos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . SSS_TABLE_PREFIX . "combos WHERE delivery_day = %s AND status = 1",
            $day
        ));
        
        ?>
        <div class="form-section day-section" data-day="<?php echo esc_attr($day); ?>">
            <h2><?php echo esc_html($day); ?> Delivery</h2>
            
            <?php if ($meals): ?>
                <div class="form-group">
                    <label for="sss_<?php echo esc_attr(strtolower($day)); ?>_meal_1">Select First Meal (Monday/Tuesday depending on day)</label>
                    <select id="sss_<?php echo esc_attr(strtolower($day)); ?>_meal_1" name="<?php echo esc_attr(strtolower($day)); ?>[meal_1]" required>
                        <option value="">Select a meal</option>
                        <?php foreach ($meals as $meal): ?>
                            <option value="<?php echo intval($meal->id); ?>">
                                <?php echo esc_html($meal->meal_name); ?> - $<?php echo esc_html(number_format($meal->price, 2)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="sss_<?php echo esc_attr(strtolower($day)); ?>_meal_2">Select Second Meal</label>
                    <select id="sss_<?php echo esc_attr(strtolower($day)); ?>_meal_2" name="<?php echo esc_attr(strtolower($day)); ?>[meal_2]">
                        <option value="">Select a meal (Optional)</option>
                        <?php foreach ($meals as $meal): ?>
                            <option value="<?php echo intval($meal->id); ?>">
                                <?php echo esc_html($meal->meal_name); ?> - $<?php echo esc_html(number_format($meal->price, 2)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="sss_<?php echo esc_attr(strtolower($day)); ?>_quantity">Quantity</label>
                    <select id="sss_<?php echo esc_attr(strtolower($day)); ?>_quantity" name="<?php echo esc_attr(strtolower($day)); ?>[quantity]" class="qty-selector">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                    </select>
                </div>
            <?php endif; ?>
            
            <!-- Sides & Add-ons -->
            <div class="form-group sides-section">
                <label>Sides & Add-ons</label>
                
                <?php foreach ($sides as $side): ?>
                    <div class="side-item" data-side-id="<?php echo intval($side->id); ?>">
                        <?php if ($side->field_type === 'checkbox'): ?>
                            <label>
                                <input type="checkbox" 
                                       name="<?php echo esc_attr(strtolower($day)); ?>[sides][<?php echo intval($side->id); ?>]" 
                                       value="<?php echo intval($side->id); ?>" 
                                       class="side-checkbox" />
                                <?php echo esc_html($side->side_name); ?> (+$<?php echo esc_html(number_format($side->price, 2)); ?>)
                            </label>
                        <?php elseif ($side->field_type === 'radio'): ?>
                            <fieldset>
                                <legend><?php echo esc_html($side->side_name); ?></legend>
                                <label>
                                    <input type="radio" 
                                           name="<?php echo esc_attr(strtolower($day)); ?>[sides][<?php echo intval($side->id); ?>]" 
                                           value="yes" />
                                    Yes (+$<?php echo esc_html(number_format($side->price, 2)); ?>)
                                </label>
                                <label>
                                    <input type="radio" 
                                           name="<?php echo esc_attr(strtolower($day)); ?>[sides][<?php echo intval($side->id); ?>]" 
                                           value="no" 
                                           checked />
                                    No
                                </label>
                            </fieldset>
                        <?php elseif ($side->field_type === 'dropdown'): ?>
                            <label for="sss_<?php echo esc_attr(strtolower($day)); ?>_side_<?php echo intval($side->id); ?>">
                                <?php echo esc_html($side->side_name); ?>
                            </label>
                            <select id="sss_<?php echo esc_attr(strtolower($day)); ?>_side_<?php echo intval($side->id); ?>" 
                                    name="<?php echo esc_attr(strtolower($day)); ?>[sides][<?php echo intval($side->id); ?>]" 
                                    class="side-dropdown">
                                <option value="">None</option>
                                <option value="yes">Yes (+$<?php echo esc_html(number_format($side->price, 2)); ?>)</option>
                            </select>
                        <?php endif; ?>
                        
                        <?php if ($side->has_instructions): ?>
                            <div class="special-instructions-field" style="display: none;">
                                <label for="sss_<?php echo esc_attr(strtolower($day)); ?>_instructions_<?php echo intval($side->id); ?>">
                                    Special Instructions for <?php echo esc_html($side->side_name); ?>
                                </label>
                                <textarea id="sss_<?php echo esc_attr(strtolower($day)); ?>_instructions_<?php echo intval($side->id); ?>"
                                          name="<?php echo esc_attr(strtolower($day)); ?>[instructions][<?php echo intval($side->id); ?>]"
                                          rows="2" placeholder="Enter any special instructions..."></textarea>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Combos for Friday if day is Friday -->
            <?php if ($day === 'Friday' && $combos): ?>
                <div class="form-group combo-section">
                    <h3>⚠️ Friday's 2-Way Combo (Will be delivered on Wednesday)</h3>
                    
                    <?php foreach ($combos as $combo): ?>
                        <div class="combo-item">
                            <h4><?php echo esc_html($combo->combo_name); ?></h4>
                            <p>Select <?php echo intval($combo->num_items_to_select); ?> items:</p>
                            
                            <div class="combo-items-list">
                                <?php
                                $combo_items = $GLOBALS['wpdb']->get_results($GLOBALS['wpdb']->prepare(
                                    "SELECT * FROM " . SSS_TABLE_PREFIX . "combo_items WHERE combo_id = %d",
                                    $combo->id
                                ));
                                
                                foreach ($combo_items as $item):
                                    ?>
                                    <label>
                                        <input type="radio" 
                                               name="friday[combo_<?php echo intval($combo->id); ?>]" 
                                               value="<?php echo intval($item->id); ?>" />
                                        <?php echo esc_html($item->item_name); ?> ($<?php echo esc_html(number_format($item->item_price, 2)); ?>)
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="sss_<?php echo esc_attr(strtolower($day)); ?>_combo_qty_<?php echo intval($combo->id); ?>">
                                    <?php echo esc_html($combo->combo_name); ?> #1 Quantity
                                </label>
                                <select id="sss_<?php echo esc_attr(strtolower($day)); ?>_combo_qty_<?php echo intval($combo->id); ?>"
                                        name="friday[combo_<?php echo intval($combo->id); ?>_qty]">
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                </select>
                            </div>
                            
                            <p class="combo-price">Price: $<?php echo esc_html(number_format($combo->price, 2)); ?> × Quantity</p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Special Instructions -->
            <div class="form-group">
                <label for="sss_<?php echo esc_attr(strtolower($day)); ?>_instructions">
                    <?php echo esc_html($day); ?> Delivery - Special Instructions
                </label>
                <textarea id="sss_<?php echo esc_attr(strtolower($day)); ?>_instructions"
                          name="<?php echo esc_attr(strtolower($day)); ?>[special_instructions]"
                          rows="3" placeholder="Any special instructions for this delivery..."></textarea>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get Meals for Day (AJAX)
     */
    public static function get_meals_for_day($day) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, meal_name, price FROM " . SSS_TABLE_PREFIX . "meals WHERE day_of_week = %s AND status = 1",
            $day
        ));
    }
}
