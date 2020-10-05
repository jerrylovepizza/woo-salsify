jQuery(document).ready(function(e) {
    jQuery(".account-selector").off("change").on("change", function(e) {
        window.sal_selected = parseInt(jQuery(this).val());
        jQuery(this).parents('form').find("#submit").trigger('click');
            
    });

    jQuery(".btn-add-cronjob").off("click").on("click", function(e) {
        jQuery(this).parents("form").append("<input type='hidden' name='salsify[cronjob]' value='1'/>");
    });

    jQuery(".mat-field-salsify .mfs-item").draggable({
      cancel: "a.ui-icon",
      revert: "invalid",
      containment: "document",
      helper: "clone",
      cursor: "move",
       start: function(event, ui) { jQuery(this).css("z-index", 100); }
    });

    jQuery(".mat-field-products .mfp-sal-field").draggable({
            cancel: "a.ui-icon",
            revert: "invalid",
            containment: "document",
            helper: "clone",
            cursor: "move"
        });

    jQuery(".mat-field-products").off("click").on("click", ".close", function(e) {
        var key = jQuery(this).parent().attr('data-key');
        jQuery(".mat-field-salsify [data-key=\"" + key + "\"]").show();
        jQuery(this).parent().remove();
    });

    jQuery(".mat-field-products .mfp-item").droppable({
      accept: ".mat-field-salsify .mfs-item, .mat-field-products .mfp-sal-field",
      activeClass: "ui-state-highlight",
      drop: function(event, ui) {
        //deleteImage(ui.draggable);

        var title = jQuery(ui.draggable).attr('data-id');
        var name = jQuery(event.target).attr('data-key');
        var key = jQuery(ui.draggable).attr('data-key');

        if (jQuery(ui.draggable).hasClass("mfp-sal-field")) {
            jQuery(".mat-field-products").find('[data-key="' + key + '"]').remove();
        } else {
            jQuery(ui.draggable).hide();
        }

        jQuery(event.target).append('<div class="mfp-sal-field" data-id="' + title + '" data-key="' + key +'"><span class="close"></span><span>' + title + '</span><input type="hidden" name="' + name + '" value="' + title + '"</div>');

        jQuery(".mat-field-products .mfp-sal-field").draggable({
            cancel: "a.ui-icon",
            revert: "invalid",
            containment: "document",
            helper: "clone",
            cursor: "move"
        });
      }
    });

    
});
