<h2 class="salsify-sub-title">
    Fields
</h2>

<?php

    $instance = Salsify_Plugin::instance()->feed;
    $options = Salsify_Plugin::instance()->get_options();    
    $selected = isset($options["selected"]) ? $options["selected"] : 0;

    $properties = $instance->get_property($selected);
    $product = SOD_Product::product_fields();

    $custom_fields = SOD_Product::custom_fields();
    $custom_taxonmy = SOD_Product::taxonomy();

?>

<script type="text/javascript">
    window.sal_props = <?php echo json_encode($properties);?>
</script>
<style type="text/css">
    .sod-title {
        text-transform: capitalize;
    }

    .form-table {
        max-width: 800px;        
    }

</style>
<form action="<?php echo admin_url( 'options.php' ); ?>" method="post">
    <?php settings_fields( 'salsify_settings' ); ?>
    <?php 
    $feed_products = isset($options["product"]) ? $options["product"] : [];
    
    foreach ($feed_products as $key => $fp) {
        if ($key == $selected) {
            continue;
        }

        foreach ($fp as $key1=> $p) {
            foreach ($p as $attr) {                
                echo "<input type='hidden' name='salsify[product][$key][$key1][]' value='$attr' />";
            }
        }
    }
    ?>
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
    </div>
    <div class="mat-field-wrapper">
    
        <div class="mat-field-products">
            <?php 
            $key_list = [];
            foreach ($product as $key => $p):
                $title = str_replace("_", " ", $p);
            ?>
            <div class="mfp-item" data-id="<?php echo $key; ?>" data-key="salsify[product][<?php echo $selected; ?>][<?php echo $p; ?>][]">
                <div class="mfp-product-title">
                    <?php echo $title; ?>
                </div>
                
                <?php
                    $sal_list = isset($options["product"][$selected][$p]) ? $options["product"][$selected][$p] : [];
                    foreach ($sal_list as $pp) {
                        $key_list[$pp] = true;
                        $sal = "sal-" . str_replace(" ", "_", $pp);

                        echo "<div class='mfp-sal-field' data-id='$pp' data-key='$sal'><span class='close'></span><span>$pp</span><input type='hidden' name='salsify[product][$selected][$p][]' value='$pp' /></div>";
                    }
                ?>
            </div>
            <?php
            endforeach;

            ?>
            
            
            <div id="poststuff"><h2>Custom Fields</h2></div>
                <?php 
                
                foreach ($custom_fields as $key => $p):
                    $title = str_replace("_", " ", $p);
                ?>
                <div class="mfp-item" data-id="<?php echo $key; ?>" data-key="salsify[product][<?php echo $selected; ?>][<?php echo $key; ?>][]">
                    <div class="mfp-product-title">
                        <?php echo $title; ?>
                    </div>
                    
                    <?php
                        $sal_list = isset($options["product"][$selected][$key]) ? $options["product"][$selected][$key] : [];
                        foreach ($sal_list as $pp) {
                            $key_list[$pp] = true;
                            $sal = "sal-" . str_replace(" ", "_", $pp);

                            echo "<div class='mfp-sal-field' data-id='$pp' data-key='$sal'><span class='close'></span><span>$pp</span><input type='hidden' name='salsify[product][$selected][[$key][]' value='$pp' /></div>";
                        }
                    ?>
                </div>
                <?php
                endforeach;
                
                ?>

            <div id="poststuff"><h2>Taxonomy</h2></div>

            <?php 
                
                foreach ($custom_taxonmy as $key => $p):
                    $title = $p;
                ?>
                <div class="mfp-item" data-id="<?php echo $key; ?>" data-key="salsify[product][<?php echo $selected; ?>][<?php echo $key; ?>][]">
                    <div class="mfp-product-title">
                        <?php echo $title; ?>
                    </div>
                    
                    <?php
                        $sal_list = isset($options["product"][$selected][$key]) ? $options["product"][$selected][$key] : [];
                        foreach ($sal_list as $pp) {
                            $key_list[$pp] = true;
                            $sal = "sal-" . str_replace(" ", "_", $pp);

                            echo "<div class='mfp-sal-field' data-id='$pp' data-key='$sal'><span class='close'></span><span>$pp</span><input type='hidden' name='salsify[product][$selected][$key][]' value='$pp' /></div>";
                        }
                    ?>
                </div>
                <?php
                endforeach;
                
                ?>
        </div>
        <div class="mat-field-salsify">
            <?php
                
                foreach ($properties as $k => $pr) {
                    $key = str_replace(" ", "_", $k);
                    $style = "";
                    if (isset($key_list[$k])) {
                        $style = "style='display: none';";
                    }
                    ?>
                    <div class="mfs-item" data-key="sal-<?php echo $key;?>" data-id="<?php echo $k; ?>" <?php echo  $style; ?>>
                        <span>
                        <?php echo $k; ?>
                        </span>
                    </div>
                    <?php
                }
            ?>
        </div>
    </div>
    
    <?php submit_button(); ?>
</form>