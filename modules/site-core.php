<?php

defined( 'ABSPATH' ) || exit;



add_filter( 'nav_menu_link_attributes', function ( $atts, $item ) {
    if ( empty( $atts['href'] ) ) return $atts;
    $url = untrailingslashit( $atts['href'] );
    $classes = isset( $atts['class'] ) ? explode( ' ', $atts['class'] ) : array();
    if ( substr( $url, -6 ) === '/enrol' ) $classes[] = 'dn-header-enrol';
    if ( substr( $url, -8 ) === '/honarjo' || substr( $url, -6 ) === '/ostad' ) $classes[] = 'dn-header-portal';
    if ( $classes ) $atts['class'] = implode( ' ', array_unique( $classes ) );
    return $atts;
}, 20, 2 );

add_action( 'wp_head', function () {
    ?>
    <style id="dn-global-header-refinement-v1">
    .header .nav-menu-primary .menu-item>.wrap>a{position:relative;transition:color .2s ease,background .2s ease,transform .2s ease!important}
    .header .nav-menu-primary a.dn-header-enrol{display:inline-flex!important;align-items:center!important;justify-content:center!important;min-height:42px!important;padding:8px 21px!important;border-radius:999px!important;background:linear-gradient(135deg,#6b3479,#542860)!important;color:#fff!important;font-weight:800!important;box-shadow:0 8px 19px rgba(84,40,96,.22)!important}
    .header .nav-menu-primary a.dn-header-enrol:hover,.header .nav-menu-primary a.dn-header-enrol:focus{color:#fff!important;transform:translateY(-1px);box-shadow:0 11px 24px rgba(84,40,96,.29)!important}
    .header .nav-menu-primary a.dn-header-portal{font-size:14px!important;font-weight:550!important;color:#756b78!important}
    .header .nav-menu-primary a.dn-header-portal:hover,.header .nav-menu-primary a.dn-header-portal:focus{color:#5b2c69!important}
    .header .nav-menu-primary .current-menu-item>.wrap>a:not(.dn-header-enrol),.header .nav-menu-primary .current_page_item>.wrap>a:not(.dn-header-enrol){color:#5b2c69!important;font-weight:750!important}
    .header .nav-menu-primary .current-menu-item>.wrap>a:not(.dn-header-enrol):after,.header .nav-menu-primary .current_page_item>.wrap>a:not(.dn-header-enrol):after{content:"";position:absolute;right:18%;left:18%;bottom:4px;height:2px;border-radius:3px;background:#d3a23b}
    @media(max-width:960px){
      .site-logo>.brand{font-size:0!important;line-height:0!important}
      .site-logo>.brand img{display:block!important;width:auto!important;max-width:145px!important;max-height:58px!important}
      .header .nav-menu-primary a.dn-header-enrol{width:calc(100% - 24px)!important;margin:8px 12px!important;min-height:46px!important}
      .header .nav-menu-primary a.dn-header-portal{font-size:14px!important;color:#776d7a!important}
      .header .nav-menu-primary .current-menu-item>.wrap>a:not(.dn-header-enrol):after,.header .nav-menu-primary .current_page_item>.wrap>a:not(.dn-header-enrol):after{right:12px;left:auto;width:36px;bottom:5px}
    }
    </style>
    <?php
}, 60 );


add_filter( 'wp_nav_menu_objects', function ( $items, $args ) {
    $wanted = array( 'enrol' => 1, 'articles' => 2, 'contact' => 3, 'honarjo' => 4, 'ostad' => 5 );
    $found = 0;
    foreach ( $items as $item ) {
        $url = isset( $item->url ) ? $item->url : '';
        foreach ( $wanted as $key => $rank ) {
            if ( ( $key === 'contact' && strpos( $url, '#contact' ) !== false ) || ( $key !== 'contact' && strpos( $url, '/' . $key . '/' ) !== false ) ) { $found++; break; }
        }
    }
    if ( $found < 5 ) return $items;
    usort( $items, function ( $a, $b ) use ( $wanted ) {
        $rank = function ( $item ) use ( $wanted ) {
            $url = isset( $item->url ) ? $item->url : '';
            foreach ( $wanted as $key => $value ) {
                if ( ( $key === 'contact' && strpos( $url, '#contact' ) !== false ) || ( $key !== 'contact' && strpos( $url, '/' . $key . '/' ) !== false ) ) return $value;
            }
            return 99;
        };
        return $rank( $a ) <=> $rank( $b );
    } );
    return $items;
}, 40, 2 );

add_action( 'wp_head', function () {
    ?>
    <style id="dn-global-header-uniform-v2">
    .header .nav-menu-primary .menu-item>.wrap>a,.header .nav-menu-primary a.dn-header-enrol,.header .nav-menu-primary a.dn-header-portal{display:flex!important;align-items:center!important;justify-content:center!important;min-height:42px!important;margin:0!important;padding:8px 14px!important;border:0!important;border-radius:0!important;background:transparent!important;box-shadow:none!important;color:#5f5362!important;font-size:15px!important;font-weight:650!important;line-height:1.4!important;transform:none!important}
    .header .nav-menu-primary .menu-item>.wrap>a:hover,.header .nav-menu-primary .menu-item>.wrap>a:focus{background:transparent!important;color:#5b2c69!important;box-shadow:none!important;transform:none!important}
    .header .nav-menu-primary .current-menu-item>.wrap>a,.header .nav-menu-primary .current_page_item>.wrap>a{color:#5b2c69!important;font-weight:750!important}
    @media(max-width:960px){.header .nav-menu-primary .menu-item>.wrap>a,.header .nav-menu-primary a.dn-header-enrol,.header .nav-menu-primary a.dn-header-portal{width:100%!important;min-height:46px!important;margin:0!important;padding:10px 18px!important;justify-content:flex-start!important;font-size:15px!important}}
    </style>
    <?php
}, 61 );


/** Performance: prioritise the homepage LCP image and defer below-fold imagery. */
add_action( 'wp_head', function () {
    if ( is_front_page() ) {
        echo '<link rel="preload" as="image" href="' . esc_url( get_template_directory_uri() . '/assets/img/starter-content/neve-hero-2.png' ) . '" fetchpriority="high">';
    }
}, 2 );

add_filter( 'the_content', function ( $content ) {
    if ( is_admin() || ! in_the_loop() || ! is_main_query() || strpos( $content, '<img' ) === false ) return $content;
    $image_number = 0;
    return preg_replace_callback( '/<img\b[^>]*>/i', function ( $match ) use ( &$image_number ) {
        $image_number++;
        $tag = $match[0];
        $is_priority = is_front_page() && ( strpos( $tag, 'wp-block-cover__image-background' ) !== false || strpos( $tag, '/Logo.png' ) !== false );
        if ( strpos( $tag, 'decoding=' ) === false ) $tag = preg_replace( '/<img\b/i', '<img decoding="async"', $tag, 1 );
        if ( strpos( $tag, 'loading=' ) === false ) $tag = preg_replace( '/<img\b/i', '<img loading="' . ( $is_priority ? 'eager' : 'lazy' ) . '"', $tag, 1 );
        if ( $is_priority && strpos( $tag, 'fetchpriority=' ) === false ) $tag = preg_replace( '/<img\b/i', '<img fetchpriority="high"', $tag, 1 );
        if ( strpos( $tag, 'neve-hero-2.png' ) !== false ) {
            if ( strpos( $tag, ' width=' ) === false ) $tag = preg_replace( '/<img\b/i', '<img width="1640"', $tag, 1 );
            if ( strpos( $tag, ' height=' ) === false ) $tag = preg_replace( '/<img\b/i', '<img height="922"', $tag, 1 );
        }
        if ( strpos( $tag, '/Logo.png' ) !== false ) {
            if ( strpos( $tag, ' width=' ) === false ) $tag = preg_replace( '/<img\b/i', '<img width="643"', $tag, 1 );
            if ( strpos( $tag, ' height=' ) === false ) $tag = preg_replace( '/<img\b/i', '<img height="643"', $tag, 1 );
        }
        return $tag;
    }, $content );
}, 50 );

add_filter( 'wp_get_attachment_image_attributes', function ( $attr ) {
    if ( is_admin() ) return $attr;
    if ( empty( $attr['loading'] ) ) $attr['loading'] = 'lazy';
    if ( empty( $attr['decoding'] ) ) $attr['decoding'] = 'async';
    return $attr;
}, 20 );

/** Performance: ensure hosted Google-font requests use swap rendering. */
add_filter( 'style_loader_src', function ( $src ) {
    if ( strpos( $src, 'fonts.googleapis.com' ) !== false && strpos( $src, 'display=swap' ) === false ) {
        $src = add_query_arg( 'display', 'swap', $src );
    }
    return $src;
}, 20 );

/** Performance: prevent admin/booking assets from loading where no related interface exists. */
function dn_page_needs_booking_assets() {
    return is_page( array( 'enrol', 'honarjo', 'ostad', 'how' ) );
}
add_filter( 'script_loader_tag', function ( $tag, $handle, $src ) {
    if ( is_admin() ) return $tag;
    if ( strpos( $src, '/vibe-ai/' ) !== false ) return '';
    if ( strpos( $src, '/ameliabooking/' ) !== false && ! dn_page_needs_booking_assets() ) return '';
    return $tag;
}, 50, 3 );
add_filter( 'style_loader_tag', function ( $html, $handle, $href, $media ) {
    if ( is_admin() ) return $html;
    if ( strpos( $href, '/vibe-ai/' ) !== false ) return '';
    if ( strpos( $href, '/ameliabooking/' ) !== false && ! dn_page_needs_booking_assets() ) return '';
    return $html;
}, 50, 4 );

add_action( 'wp_head', function () {
    if ( ! is_front_page() ) return;
    ?>
    <style id="dn-homepage-performance-stability-v1">
    body.home .o-posts-grid-post-blog{display:flex!important;flex-direction:column!important;min-height:255px!important;border:1px solid rgba(105,62,124,.13)!important;border-radius:21px!important;overflow:hidden!important;background:#fff!important;box-shadow:0 10px 28px rgba(68,37,91,.08)!important}
    body.home .o-posts-grid-post-blog .o-posts-grid-post-image,body.home .o-posts-grid-post-blog figure,body.home .o-posts-grid-post-blog img{display:none!important}
    body.home .o-posts-grid-post-blog .o-posts-grid-post-body{display:flex!important;flex:1!important;flex-direction:column!important;padding:25px 23px!important}
    body.home .o-posts-grid-post-blog .o-posts-grid-post-title{font-size:20px!important;margin:0 0 13px!important}
    body.home .o-posts-grid-post-blog .o-posts-grid-post-description,body.home .o-posts-grid-post-blog .o-posts-grid-post-excerpt{display:-webkit-box!important;-webkit-line-clamp:4!important;-webkit-box-orient:vertical!important;overflow:hidden!important;color:#625766!important;font-size:15px!important;line-height:1.95!important}
    @media(max-width:760px){body.home .o-posts-grid-post-blog{min-height:0!important;border-radius:17px!important;transition:none!important}body.home .o-posts-grid-post-blog .o-posts-grid-post-body{padding:21px 18px!important}}
    </style>
    <?php
}, 70 );


add_filter( 'the_content', function ( $content ) {
    if ( is_admin() || ! is_front_page() || ! in_the_loop() || ! is_main_query() ) return $content;
    $sizes = array(
        'neve-marketing-icon-1.png'   => array( 72, 72 ),
        'neve-marketing-icon-2.png'   => array( 73, 72 ),
        'neve-marketing-icon-3.1.png' => array( 73, 72 ),
        'neve-marketing-icon-4.1.png' => array( 73, 72 ),
        'visa2.png'                   => array( 128, 128 ),
        'mastercard2.png'             => array( 128, 128 ),
        'american_express2.png'       => array( 128, 128 ),
        'apple_pay2.png'              => array( 128, 128 ),
        'google_pay2.png'             => array( 128, 128 ),
        'paypal2.png'                 => array( 128, 128 ),
    );
    return preg_replace_callback( '/<img\b[^>]*>/i', function ( $match ) use ( $sizes ) {
        $tag = $match[0];
        foreach ( $sizes as $file => $dimensions ) {
            if ( strpos( $tag, $file ) === false ) continue;
            if ( strpos( $tag, ' width=' ) === false ) $tag = preg_replace( '/<img\b/i', '<img width="' . $dimensions[0] . '"', $tag, 1 );
            if ( strpos( $tag, ' height=' ) === false ) $tag = preg_replace( '/<img\b/i', '<img height="' . $dimensions[1] . '"', $tag, 1 );
            break;
        }
        return $tag;
    }, $content );
}, 51 );

add_action( 'wp_head', function () {
    if ( ! is_front_page() ) return;
    ?>
    <style id="dn-mobile-cls-stability-v1">
    @media(max-width:767px){
      body.home #home{min-height:655px!important}
      body.home #home .wp-block-cover__inner-container{display:flex!important;align-items:center!important;width:100%!important;min-height:655px!important}
      body.home #home .wp-block-columns{display:grid!important;grid-template-columns:1fr!important;align-content:center!important;width:100%!important;min-height:585px!important;margin:0!important}
      body.home #home .wp-block-column:first-child{display:flex!important;align-items:center!important;justify-content:center!important;min-height:155px!important}
      body.home #home .wp-block-column:first-child .wp-block-image{display:flex!important;align-items:center!important;justify-content:center!important;min-height:150px!important;margin:0!important}
      body.home #home .wp-block-column:last-child{display:flex!important;flex-direction:column!important;justify-content:center!important;min-height:355px!important;padding-top:12px!important;padding-bottom:12px!important}
      body.home #home h1{min-height:126px!important;margin-bottom:10px!important}
      body.home #home .wp-block-buttons{min-height:48px!important}
    }
    </style>
    <?php
}, 71 );


add_action( 'wp_head', function () {
    if ( ! is_front_page() ) return;
    ?>
    <style id="dn-mobile-floating-controls-fix-v1">
    @media(max-width:760px){
      body.home .dn-mobile-cta{position:fixed!important;right:14px!important;left:14px!important;bottom:12px!important;width:auto!important;max-width:none!important;min-height:52px!important;z-index:9998!important;justify-content:center!important}
      body.home .joinchat,body.home .joinchat--right{right:14px!important;left:auto!important;bottom:82px!important;z-index:9997!important}
      body.home #scroll-to-top,body.home .scroll-to-top,body.home .nv-scroll-to-top,body.home button[aria-label="Scroll To Top"]{display:none!important}
      body.home{padding-bottom:76px!important}
    }
    </style>
    <?php
}, 72 );

