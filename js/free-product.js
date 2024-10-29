jQuery(document).ready(function($) {
    var variations_picker = { 
        init : function() { 
            this.attach(); 
            $(document).on('wc_update_cart', $.proxy(this.update, this)); 
            $(document).on('updated_cart_totals', $.proxy(this.update, this));
        },
        attach : function() { 
            this.variation_modal = $('#wc-freeproduct');
            this.form = $('form.cart', this.variation_modal);
            this.form_submit_button = $('button.single_add_to_cart_button', this.form);

            this.form_submit_button.click($.proxy(this.submit, this)); 
            $('#freeproduct_modal').modal('toggle');
        },
        replace_form : function(html_str) { 
            html = $.parseHTML(html_str); 
            new_modal = $('#wc-freeproduct', html);
           
            this.variation_modal.replaceWith(new_modal); 

            this.attach(); 
            this.form.wc_variation_form().find('.variations select:eq(0)').change();

        },
        submit : function(e) { 
            console.log('submit action');
            e.preventDefault(); 

            $.ajax({
                context:    this, 
                type:       this.form.attr('method'), 
                url:        this.form.attr('action'), 
                data:       this.form.serialize(), 
                dataType:   'html',
                success:    function(response) { 
                    $('button.freeproduct_cancel', this.variation_modal).click(); 
                    $(document).trigger('wc_update_cart');
                }, 
                complete:   function() { 
                } 
            });
        }, 
        update: function(e) { 
            $.ajax({ 
                context:    this, 
                type:       'get', 
                dataType:   'html', 
                success:    function(response) { 
                    this.replace_form(response);
                }, 
                complete:   function() { 
                }
            });
        },
    };

    variations_picker.init(); 
});
