<h2 class="salsify-sub-title">
	Woo Salsify Start Page
</h2>

<?php
	$options = Salsify_Plugin::get_options();

	$product = SOD_Product::product_fields();
	$show_error = false;
  $hour = isset($options["hour"]) ? $options["hour"] : 1;


	if (!empty($product)) {				
		
	} else {
		$show_error = true;
	}
?>
<div class="salsify-wrapper">
	<form  action="<?php echo admin_url( 'options.php' ); ?>" method="post">
 		<?php settings_fields( 'salsify_settings' ); ?>
 	<?php 
    	
    ?>

            <div class="accounts-run-time">
                <label><b>Time Interval: </b></label>
                <input type="text" name="salsify[hour]" value="<?php echo $hour; ?>" />
                <span>hour</span>

                <p>
                  You should add the feed in feed pages.
                  After you add the feeds, you would matched the fields between salsify and woocommerce.
                  There are custom fields, taxonmy and woocommerce fields. 
                  If you add all the settings, You would click on Start button to pull the products from salsify. 
                </p>
            </div>
           	
           
            <p class="submit">
              <input type="submit" name="submit" id="submit" class="button button-primary" value="Start">
              <a href="<?php echo admin_url('admin.php?page=feed'); ?>" class="button-primary button">Add Feed</a>
            </p>
          
	</form>

</div>
<?php
	
?>