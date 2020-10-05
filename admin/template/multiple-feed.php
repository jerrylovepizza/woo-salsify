<h2 class="salsify-sub-title">
    Multiple Filters
</h2>

<?php

    $instance = Salsify_Plugin::instance()->feed;
    $properties = $instance->get_property();

    $compare1 = $instance->compare1;
    $compare2 = $instance->compare2;
    
    $options = Salsify_Plugin::get_options();
    
    $selected = isset($options["selected"]) ? $options["selected"] : 0;
    $count = count(isset($options["feed"][$selected]["property"]) ? $options["feed"][$selected]["property"] : []);
    $filter = isset($options["filter"]) ? $options["filter"] : [];
    // var_dump ($options["filter"]);
    // var_dump ($options["feed"]);
    // echo $count;
?>

<div class="salsify-values" style="display: none;">
    <select name="salsify[feed][condition][]" class="salsify-condition1 salsify-condition">
        <?php 
            foreach ($compare1 as $val) {
                echo "<option value='$val'>$val</option>";
            }
        ?>
    </select>

    <select name="salsify[feed][condition][]" class="salsify-condition2 salsify-condition">
        <?php 
            foreach ($compare2 as $val) {
                echo "<option value='$val'>$val</option>";
            }
        ?>
    </select>
</div>



<form action="<?php echo admin_url( 'options.php' ); ?>" id="sal_filter_form" method="post">
    <?php settings_fields( 'salsify_settings' ); ?>
    <div class="account-feed">
        <label>Feed: </label>
        <select name="salsify[selected]" class="account-selector">
            <?php
                $accounts = isset($options["account"]) ? $options["account"] : array(array("api" => "", "org" => "", "name" => ""));
                foreach ($accounts as $key => $account):
                    $name = $account["name"];
                    if ($selected == $key) {
                        echo "<option value='$key' selected='selected'>$name</option>";
                    } else {
                        echo "<option value='$key'>$name</option>";
                    }
                    
                endforeach;
            ?>
        </select>
        
        <?php
            foreach ($accounts as $key => $account):
                if ($selected != $key) {
                    $property = isset($options["feed"][$key]["property"]) ? $options["feed"][$key]["property"] : [];

                    foreach ($property as $p_i => $p) {
                        $h_condition =  $options["feed"][$key]["condition"][$p_i];
                        $h_val1 =  $options["feed"][$key]["value1"][$p_i];
                        $h_val2 =  $options["feed"][$key]["value2"][$p_i];
                        ?>
                        <input type="hidden" name="salsify[feed][<?php echo $key; ?>][property][]" value="<?php echo $p; ?>"/>
                        <input type="hidden" name="salsify[feed][<?php echo $key; ?>][condition][]" value="<?php echo $h_condition; ?>"/>
                        <input type="hidden" name="salsify[feed][<?php echo $key; ?>][value1][]" value="<?php echo $h_val1; ?>"/>
                        <input type="hidden" name="salsify[feed][<?php echo $key; ?>][value2][]" value="<?php echo $h_val2; ?>"/>
                        <?php
                    }
                    ?>
                    <input type="hidden" name="salsify[filter][<?php echo $key; ?>]" value="<?php echo $filter[$key]; ?>"/>
                    <?php
                }
            endforeach;
        ?>
    </div>
    <table class="form-table">
        <tbody>
            <?php
            

        if ($count > 0):
        for ($i = 0; $i < $count; $i++):
        ?>
        <tr>            
            <td>
                <select name="salsify[feed][<?php echo $selected; ?>][property][]" class="tb-property">
                    <option value="">Select a Property</option>
                    <?php
                        $property = $options["feed"][$selected]["property"][$i];
                        foreach ($properties as $key => $p) {

                            if ($property == $key) {
                                echo "<option selected='selected' value=\"$key\">$key</option>";
                            } else {
                                echo "<option value=\"$key\">$key</option>";
                            }
                        }
                    ?>
                </select>
            </td>             
            <td class="td-2">
                <?php 
                 $condition = $options["feed"][$selected]["condition"][$i];
                if ($properties[$property]):
                    ?>
                    <select name="salsify[feed][<?php echo $selected; ?>][condition][]" class="salsify-condition1 salsify-condition">
                        <option value="">Select</option>
                    <?php                       
                        foreach ($compare1 as $val) {
                            if ($condition == $val) {
                                echo "<option selected='selected' value=\"$val\">$val</option>";
                            } else {
                                echo "<option value=\"$val\">$val</option>";
                            }
                            
                        }
                    ?>
                </select>
                    <?php
                else:
                    ?>
                    <select name="salsify[feed][<?php echo $selected; ?>][condition][]" class="salsify-condition2 salsify-condition">
                        <option value="">Select</option>
                    <?php 
                       
                        foreach ($compare2 as $val) {
                            if ($condition == $val) {
                                echo "<option selected='selected' value=\"$val\">$val</option>";
                            } else {
                                echo "<option value=\"$val\">$val</option>";
                            }
                            
                        }
                    ?>
                </select>
                    <?php
                endif;
                ?>
                
            </td>
            <td class="<?php echo ($condition == "is between") ? "btw-2" : ""; ?>" >
                <?php 
                    $val1 =  $options["feed"][$selected]["value1"][$i];                    
                ?>
                <input type="text" name="salsify[feed][<?php echo $selected; ?>][value1][]" value="<?php echo $val1; ?>" class="condition-value"/>
                <?php 
                 $val2 = $options["feed"][$selected]["value2"][$i];                
                ?>
                <input type="text" name="salsify[feed][<?php echo $selected; ?>][value2][]" class="btween-range-value2" value="<?php echo $val2; ?>" />
               
            </td>
            <td>
                <button type="button" class="add-feed button button-primary">Add Filter</button>
                <button type="button" class="delete-feed button button-danger">Delete</button>
            </td>
        </tr>
                <?php endfor; ?>
            <?php else: ?>
                
                <tr>            
            <td>
                <select name="salsify[feed][<?php echo $selected; ?>][property][]" class="tb-property">
                    <?php
                   
                        foreach ($properties as $key => $p) {

                           
                                echo "<option value=\"$key\">$key</option>";
                           
                        }
                    ?>
                </select>
            </td>             
            <td class="td-2">
              
                    <select name="salsify[feed][<?php echo $selected; ?>][condition][]" class="salsify-condition1 salsify-condition">
                    <?php                       
                        foreach ($compare1 as $val) {
                          
                            echo "<option value=\"$val\">$val</option>";                          
                        }
                    ?>
                </select>
               
                
            </td>
            <td  >
               
                <input type="text" name="salsify[feed][<?php echo $selected; ?>][value1][]" class="condition-value" value="" />
               
                <input type="text" name="salsify[feed][<?php echo $selected; ?>][value2][]"  class="btween-range-value2" value="" />
               
            </td>
            <td>
                <button type="button" class="add-feed button button-primary">Add Filter</button>
                <button type="button" class="delete-feed button button-danger">Delete</button>
            </td>
        </tr>
            <?php
            endif;
            ?>
        </tbody>
    </table>
    <input type="hidden" name="salsify[filter][<?php echo $selected; ?>]" value="<?php echo $options["filter"][$selected]; ?>" />
    <?php submit_button(); ?>
</form>

<script type="text/javascript">
    window.s_properties = <?php echo json_encode($properties); ?>;
    window.sal_selected = <?php echo $selected; ?>;

    jQuery(document).ready(function(e) {

        

        function add_condition() {
            jQuery(".tb-property").off("change").on("change", function(e) {
            var txt = jQuery(this).val();
            if (!window.s_properties[txt]) {
                jQuery(this).parent().next().html(jQuery(".salsify-values .salsify-condition2").clone());
            } else {
                jQuery(this).parent().next().html(jQuery(".salsify-values .salsify-condition1").clone());
            }

            add_value();
            
            });
        }

        function add_value() {
            jQuery(".salsify-condition1").off("change").on("change", function(e) {
                var txt = jQuery(this).val();
                if (txt == "is between") {
                    jQuery(this).parent().next().addClass('btw-2')
                } else {
                    jQuery(this).parent().next().removeClass('btw-2');
                }
            });

            // jQuery(".salsify-condition2").off("change").on("change", function(e) {
            //     var txt = jQuery(this).val();
            //     if (txt == "is between") {
            //         jQuery(this).parent().next().append('<input type="text" name="salsify[feed][value2][]" />');
            //     } else {
            //         jQuery(this).parent().next().find("[name='salsify[feed][value2][]']").remove();
            //     }
            // });
        }

        jQuery(".form-table").off("click").on("click", ".add-feed", function(e) {
            
            var template = wp.template( 'feed' );
            jQuery(".form-table tbody").append(template({id: window.sal_selected}));            

            add_condition();
            add_value();
        });

        jQuery(".form-table").on("click", ".delete-feed", function(e) {
            jQuery(this).parent().parent().remove();
        });
        add_value();

        function progress_active(option) {
            if (option) {
                jQuery("#salsify-feed").prop('disabled', true);
                jQuery("#salsify-progress").show();
            } else {
                jQuery("#salsify-feed").prop('disabled', false);
                jQuery("#salsify-progress").hide();
            }
        }
        function ajax_load(page) {
            progress_active(true);
            
            jQuery.ajax({
            url: "<?php echo  admin_url( 'admin-ajax.php' );?>",
            type: "POST",          
            data: {
                action: "salsify_product",
                page: page,                
            },
            success: function( data ) {
                
                data = JSON.parse(data);
                console.log(data);
                var page_count = Math.ceil(data.total_entries / 10);
                var current_page = parseInt(data.current_page);
                
                if (page_count > current_page) {
                    current_page += 1;

                    var progress = current_page / page_count * 100;
                    
                    if (progress < 1) {
                        progress = 1;
                    }

                    jQuery("#salsify-progress .s-bar").css('width', progress + "%");

                    setTimeout(function() {
                        ajax_load(current_page);
                    }, 100);
                } else {
                    progress_active(false);
                }

                
                
            },
            error: function(jqXHR, exception) {
                progress_active(false);
                // jQuery('body').removeClass('mm-ajax-loading');
            }
            });
        }

        jQuery("#salsify-feed").off("click").on("click", function(e) {
            ajax_load(1);
        });
    });
</script>

<script type="text/template" id="tmpl-feed">
    <tr>            
        <td>
            <select name="salsify[feed][{{data.id}}][property][]" class="tb-property">
                <option value="">Select a Property</option>
                <?php 
                    foreach ($properties as $key => $p) {
                        echo "<option value=\"$key\">$key</option>";
                    }
                ?>
            </select>
        </td>             
        <td class="td-2">
            <select name="salsify[feed][{{data.id}}][condition][]" class="salsify-condition1 salsify-condition">
                <option value="">Select</option>
                <?php 
                    foreach ($compare1 as $val) {
                        echo "<option value=\"$val\">$val</option>";
                    }
                ?>
            </select>
        </td>
        <td>
            <input type="text" name="salsify[feed][{{data.id}}][value1][]" class="condition-value" />    
            <input type="text" name="salsify[feed][{{data.id}}][value2][]" class="btween-range-value2" />            
        </td>
        <td>
            <button type="button" class="add-feed button button-primary">Add Filter</button>
            <button type="button" class="delete-feed button button-danger">Delete</button>
        </td>
    </tr>
</script>
