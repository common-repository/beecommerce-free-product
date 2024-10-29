<div id="freeproduct_modal" class="freeproduct_products_modal modal" role="dialog">
    <div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php printf(__("Choose free product %d of %d", 'beecommerce-free-product'), ($this->_free_products_total - $this->_free_products_left + 1), $this->_free_products_total) ?></h3>
            <button class="button close freeproduct_cancel fusion-button fusion-button-default" type="button" data-dismiss="modal" aria-label="Cancel"><span aria-hidden="true"><i class="fa fa-times" aria-hidden="true"></i></span></button>
        </div>
        <div class="modal-body">
        <?php $attachment_ids = $product->get_gallery_attachment_ids(); ?>
        <div class="row collapse">
        <div class="large-7 columns">
            <div class="product-image images">
                <div  class="product-gallery-slider ux-slider slider-nav-circle-hover slider-nav-small js-flickity" style="margin-bottom:0">
                <?php
                    //Get the Thumbnail URL
                    $src = wp_get_attachment_image_src( get_post_thumbnail_id($product->id), false, '' );
                    $src_small = wp_get_attachment_image_src( get_post_thumbnail_id($product->id),  apply_filters( 'single_product_large_thumbnail_size', 'shop_single' ));
                    $src_title = get_post(get_post_thumbnail_id())->post_title;

                ?>

                <div class="slide first">
                    <img itemprop="image" src="<?php echo $src_small[0]; ?>" alt="<?php echo $src_title; ?>" title="<?php echo $src_title; ?>" />
                </div>
                <?php

                    if ( $attachment_ids ) {

                        $loop = 0;
                        $columns = apply_filters( 'woocommerce_product_thumbnails_columns', 3 );

                        foreach ( $attachment_ids as $attachment_id ) {

                            $src = wp_get_attachment_image_src( $attachment_id, false, '' );
                            $image = wp_get_attachment_image_src( $attachment_id, apply_filters( 'single_product_large_thumbnail_size', 'shop_single' ) );
                            $image_small = wp_get_attachment_image_src( $attachment_id, apply_filters( 'single_product_large_thumbnail_size', 'shop_thumbnail' ) );
                            $image_title = esc_attr( get_the_title( $attachment_id ) );
                            ?>
                            <div class="slide">
                                <img src="<?php echo $image_small[0]; ?>" data-flickity-lazyload="<?php echo $image[0] ?>" alt="<?php echo $image_title ?>" title="<?php echo $image_title ?>" />
                            </div>
                            <?php
                        }
                    }
                ?>
                </div>
            </div>
        </div>
        <div class="large-5 columns">
        <div class="product-lightbox-inner product-info">
        <h1 itemprop="name" class="entry-title"><a href="<?php echo get_permalink($product->id); ?>"><?php echo get_the_title($product->id); ?></a></h1>
        <div class="tx-div small"></div>
        <?php do_action('woocommerce_before_add_to_cart_form'); ?>
        <form class="cart" method="post" enctype='multipart/form-data'>
            <?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

            <input type="hidden" name="product_id" value="<?php echo esc_attr( $product->id ); ?>" />

            <button type="submit" class="single_add_to_cart_button button alt"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>

            <?php
                if ( ! $product->is_sold_individually() ) {
                    woocommerce_quantity_input( array(
                        'min_value'   => apply_filters( 'woocommerce_quantity_input_min', 1, $product ),
                        'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->backorders_allowed() ? '' : $product->get_stock_quantity(), $product ),
                        'input_value' => ( isset( $_POST['quantity'] ) ? wc_stock_amount( $_POST['quantity'] ) : 1 )
                    ) );
                }
            ?>

            <?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
        </form>
        <?php 
        $cat_count = sizeof( get_the_terms( $post->ID, 'product_cat' ) );
        $tag_count = sizeof( get_the_terms( $post->ID, 'product_tag' ) );

        $attributes = $product->get_attributes();
        $producent = $product->get_attribute( 'pa_producent' );

        ?>
<!--
        <div class="product_meta">

                <?php do_action( 'woocommerce_product_meta_start' ); ?>

                <?php if ( wc_product_sku_enabled() && ( $product->get_sku() || $product->is_type( 'variable' ) ) ) : ?>

                        <span class="sku_wrapper"><?php _e( 'SKU:', 'woocommerce' ); ?> <span class="sku" itemprop="sku"><?php echo ( $sku = $product->get_sku() ) ? $sku : __( 'N/A', 'woocommerce' ); ?></span></span>

                <?php endif; ?>

                <?php echo $product->get_categories( ', ', '<span class="posted_in">' . _n( 'Category:', 'Categories:', $cat_count, 'woocommerce' ) . ' ', '</span>' ); ?>
                <?php if (!empty($producent)) { ?>
                        <span class="producent">Producent: <a href="<?php echo 'https://bardotti.pl/sklep/producent/' . sanitize_title($producent); ?>"><?php echo $producent; ?></a></span>
                <?php } ?>
                <?php echo $product->get_tags( ', ', '<span class="tagged_as">' . _n( 'Tag:', 'Tags:', $tag_count, 'woocommerce' ) . ' ', '</span>' ); ?>

                <?php do_action( 'woocommerce_product_meta_end' ); ?>

        </div>
-->
        </div>
        </div>
        </div>
        </div>
        <div class="modal-footer">
        </div>
        <?php
        do_action('woocommerce_after_add_to_cart_form');
        ?>
    </div>
    </div>
</div>
