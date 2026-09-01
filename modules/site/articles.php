<?php
defined( 'ABSPATH' ) || exit;

function dn_article_banner( $content ) {
    if ( is_admin() || ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) return $content;
    $url = home_url( '/' );
    $image = home_url( '/wp-content/uploads/2025/10/Logo2.png' );
    $banner = '<aside class="dn-article-promo" aria-label="معرفی کلاس‌های دلنوازان"><a class="dn-article-promo__image" href="' . esc_url( $url ) . '"><img src="' . esc_url( $image ) . '" width="500" height="600" loading="lazy" decoding="async" alt="کلاس‌های آنلاین موسیقی ایرانی دلنوازان"></a><div class="dn-article-promo__copy" dir="rtl"><span>معرفی دلنوازان</span><strong>یادگیری موسیقی ایرانی، هر کجای جهان که هستید</strong><p>کلاس‌های خصوصی و زنده ساز و آواز ایرانی با برنامه‌ای متناسب با زمان و سطح هنرجو.</p><a class="dn-article-promo__button" href="' . esc_url( $url ) . '">مشاهده کلاس‌ها</a></div></aside>';
    return $banner . $content;
}
add_filter( 'the_content', 'dn_article_banner', 8 );

add_action( 'wp_head', function () {
    if ( ! is_singular( 'post' ) ) return;
    ?>
    <style id="dn-article-enhancements">
    body.single-post .nv-post-cover{position:relative!important;overflow:hidden!important;border:0!important;background:radial-gradient(circle at 12% 20%,rgba(220,177,105,.22),transparent 28%),radial-gradient(circle at 86% 82%,rgba(255,255,255,.10),transparent 30%),linear-gradient(135deg,#241811 0%,#58391f 55%,#8b6737 100%)!important;box-shadow:inset 0 -1px 0 rgba(255,255,255,.12)!important}body.single-post .nv-post-cover::after{content:""!important;position:absolute;inset:0;pointer-events:none;opacity:.2;background-image:repeating-linear-gradient(115deg,transparent 0 34px,rgba(255,255,255,.08) 35px,transparent 36px 70px)}body.single-post .nv-post-cover>.container{position:relative;z-index:1;padding-top:clamp(44px,6vw,78px);padding-bottom:clamp(44px,6vw,78px)}body.single-post .nv-post-cover .nv-title-meta-wrap{max-width:1080px;margin-inline:auto;text-align:right}body.single-post .nv-post-cover .nv-title-meta-wrap::before{content:"مقالات دلنوازان";display:inline-flex;margin-bottom:13px;padding:5px 13px;border:1px solid rgba(255,255,255,.28);border-radius:999px;background:rgba(255,255,255,.08);color:#efd6a4;font-size:.78rem;font-weight:700}body.single-post .nv-post-cover .nv-title-meta-wrap::after{content:"";display:block;width:76px;height:3px;margin-top:20px;margin-right:0;border-radius:99px;background:#d9ad68}body.single-post .nv-post-cover .title.entry-title{max-width:1000px;margin:0;color:#fff!important;font-size:clamp(2rem,4vw,3.45rem)!important;font-weight:800!important;line-height:1.4!important;letter-spacing:-.025em;text-wrap:balance;text-shadow:0 3px 18px rgba(0,0,0,.28)}
    .dn-article-promo{display:grid;grid-template-columns:minmax(150px,31%) 1fr;gap:clamp(18px,3vw,34px);align-items:stretch;margin:12px 0 38px;overflow:hidden;border:1px solid rgba(142,105,49,.22);border-radius:18px;background:linear-gradient(135deg,#fffaf0,#f6efe2);box-shadow:0 12px 35px rgba(58,39,16,.08)}.dn-article-promo__image{display:block;min-height:210px;background:#fff}.dn-article-promo__image img{display:block;width:100%;height:100%;max-height:255px;object-fit:contain;object-position:center}.dn-article-promo__copy{display:flex;flex-direction:column;justify-content:center;align-items:flex-start;padding:24px 28px 24px 12px;color:#2e251c}.dn-article-promo__copy>span{margin-bottom:8px;color:#8a642d;font-size:.78rem;font-weight:700}.dn-article-promo__copy strong{font-size:clamp(1.18rem,2vw,1.55rem);line-height:1.55}.dn-article-promo__copy p{margin:9px 0 16px;color:#5e5143;font-size:.96rem;line-height:1.85}.dn-article-promo__button{display:inline-flex;align-items:center;min-height:42px;padding:8px 20px;border-radius:999px;background:#8a642d;color:#fff!important;font-weight:700;text-decoration:none!important}.dn-article-promo__button:hover,.dn-article-promo__button:focus{background:#6d4c21;transform:translateY(-1px)}
    @media(max-width:680px){body.single-post .nv-post-cover>.container{padding-top:34px;padding-bottom:36px}body.single-post .nv-post-cover .title.entry-title{font-size:clamp(1.72rem,8vw,2.3rem)!important;line-height:1.48!important}.dn-article-promo{grid-template-columns:1fr;gap:0;margin:8px 0 30px;border-radius:15px}.dn-article-promo__image{min-height:0;padding:14px}.dn-article-promo__image img{width:auto;height:210px;max-width:100%;margin:0 auto}.dn-article-promo__copy{padding:20px}}
    </style>
    <?php
}, 30 );



add_action( 'wp_head', function () {
    if ( ! is_page( 'articles' ) ) return;
    ?>
    <style id="dn-article-archive-cards-v1">
    body.page-slug-articles .apbGridPosts,body.page-id-0 .apbGridPosts{display:none!important}
    body.page .dn-articles-archive-intro{max-width:900px;margin:52px auto 35px;padding:0 20px;text-align:center}
    body.page .dn-articles-archive-intro h1{color:#582b66!important;font-size:clamp(31px,4vw,48px)!important;font-weight:850!important;line-height:1.45!important;margin:0!important}
    body.page .dn-articles-archive-intro h1:after{content:"";display:block;width:62px;height:3px;margin:15px auto;border-radius:5px;background:linear-gradient(90deg,#6b3479,#d3a23b)}
    body.page .dn-articles-archive-intro p{max-width:720px;margin:0 auto!important;color:#665a69!important;font-size:16.5px!important;line-height:2!important}
    body.page .wp-block-post-template{max-width:1120px!important;margin:0 auto 60px!important;padding:0 20px!important;display:grid!important;grid-template-columns:repeat(3,minmax(0,1fr))!important;gap:22px!important;list-style:none!important}
    body.page .wp-block-post-template>li{display:flex!important;flex-direction:column!important;min-width:0!important;margin:0!important;padding:25px 23px 23px!important;border:1px solid rgba(105,62,124,.13)!important;border-radius:20px!important;background:linear-gradient(180deg,#fff 0%,#fcf9fd 100%)!important;box-shadow:0 10px 28px rgba(68,37,91,.08)!important;transition:transform .2s ease,box-shadow .2s ease!important}
    body.page .wp-block-post-template>li:hover{transform:translateY(-3px);box-shadow:0 16px 38px rgba(68,37,91,.13)!important}
    body.page .wp-block-post-template>li figure,body.page .wp-block-post-template>li .wp-block-post-featured-image{display:none!important}
    body.page .wp-block-post-template .wp-block-post-title{font-size:20px!important;font-weight:800!important;line-height:1.7!important;margin:0 0 13px!important;text-align:right!important}
    body.page .wp-block-post-template .wp-block-post-title a{color:#5b2c69!important;text-decoration:none!important}
    body.page .wp-block-post-template .wp-block-post-excerpt{display:flex!important;flex:1!important;flex-direction:column!important;margin:0!important}
    body.page .wp-block-post-template .wp-block-post-excerpt__excerpt{color:#625766!important;font-size:15px!important;line-height:1.95!important;display:-webkit-box!important;-webkit-line-clamp:5!important;-webkit-box-orient:vertical!important;overflow:hidden!important;margin:0 0 18px!important}
    body.page .wp-block-post-template .wp-block-post-excerpt__more-text{margin-top:auto!important;margin-bottom:0!important}
    body.page .wp-block-post-template .wp-block-post-excerpt__more-link{display:inline-flex!important;align-items:center!important;color:#6b3479!important;font-weight:750!important;text-decoration:none!important}
    body.page .wp-block-post-template .wp-block-post-excerpt__more-link:after{content:" ←";margin-right:6px;color:#d3a23b}
    @media(max-width:900px){body.page .wp-block-post-template{grid-template-columns:repeat(2,minmax(0,1fr))!important}}
    @media(max-width:620px){body.page .dn-articles-archive-intro{margin:38px auto 27px;padding:0 17px}body.page .dn-articles-archive-intro h1{font-size:31px!important}body.page .dn-articles-archive-intro p{font-size:15.5px!important}body.page .wp-block-post-template{grid-template-columns:1fr!important;padding:0 12px!important;gap:14px!important}body.page .wp-block-post-template>li{padding:21px 18px!important;border-radius:17px!important}body.page .wp-block-post-template .wp-block-post-title{font-size:18.5px!important}}
    </style>
    <?php
}, 31 );

add_action( 'wp_footer', function () {
    if ( ! is_page( 'articles' ) ) return;
    ?>
    <script id="dn-article-archive-targets-v1">
    (function(){
      document.body.classList.add('page-slug-articles');
      var main=document.querySelector('main');
      var grid=main&&main.querySelector('.wp-block-post-template');
      if(main&&grid&&!main.querySelector('.dn-articles-archive-intro')){
        var intro=document.createElement('section');intro.className='dn-articles-archive-intro';intro.innerHTML='<h1>مقالات دلنوازان</h1><p>مجموعه‌ای از مقاله‌های تحلیلی و آموزشی درباره موسیقی ایرانی، سازها، تاریخ، فرهنگ و مسیر یادگیری.</p>';
        grid.parentElement.insertBefore(intro,grid);
      }
    }());
    </script>
    <?php
}, 31 );


add_action( 'wp_head', function () {
    if ( ! is_single() ) return;
    ?>
    <style id="dn-single-article-sidebar-v1">
    body.single-post .dn-sidebar-articles-title{color:#582b66!important;font-size:22px!important;font-weight:850!important;line-height:1.5!important;margin:0 0 16px!important;padding:0 0 12px!important;border-bottom:2px solid rgba(211,162,59,.55)!important}
    body.single-post .nv-sidebar-wrap .wp-block-themeisle-blocks-posts-grid .is-list{display:grid!important;grid-template-columns:1fr!important;gap:12px!important}
    body.single-post .nv-sidebar-wrap .o-posts-grid-post-blog{margin:0!important;padding:0!important;border:1px solid rgba(105,62,124,.13)!important;border-radius:15px!important;background:linear-gradient(180deg,#fff 0%,#fcf9fd 100%)!important;box-shadow:0 7px 20px rgba(68,37,91,.07)!important;overflow:hidden!important;transition:transform .2s ease,box-shadow .2s ease!important}
    body.single-post .nv-sidebar-wrap .o-posts-grid-post-blog:hover{transform:translateY(-2px);box-shadow:0 11px 26px rgba(68,37,91,.12)!important}
    body.single-post .nv-sidebar-wrap .o-posts-grid-post-image,body.single-post .nv-sidebar-wrap .o-posts-grid-post-blog figure,body.single-post .nv-sidebar-wrap .o-posts-grid-post-blog img{display:none!important}
    body.single-post .nv-sidebar-wrap .o-posts-grid-post-body{padding:17px 16px!important}
    body.single-post .nv-sidebar-wrap .o-posts-grid-post-title{font-size:15.5px!important;font-weight:800!important;line-height:1.75!important;margin:0 0 8px!important;text-align:right!important}
    body.single-post .nv-sidebar-wrap .o-posts-grid-post-title a{color:#5b2c69!important;text-decoration:none!important}
    body.single-post .nv-sidebar-wrap .o-posts-grid-post-description,body.single-post .nv-sidebar-wrap .o-posts-grid-post-excerpt{color:#6a5f6d!important;font-size:13.5px!important;line-height:1.85!important;display:-webkit-box!important;-webkit-line-clamp:3!important;-webkit-box-orient:vertical!important;overflow:hidden!important;margin:0!important}
    @media(max-width:960px){body.single-post .dn-sidebar-articles-title{margin-top:25px!important}body.single-post .nv-sidebar-wrap .wp-block-themeisle-blocks-posts-grid .is-list{grid-template-columns:repeat(2,minmax(0,1fr))!important}}
    @media(max-width:620px){body.single-post .nv-sidebar-wrap .wp-block-themeisle-blocks-posts-grid .is-list{grid-template-columns:1fr!important}body.single-post .nv-sidebar-wrap .o-posts-grid-post-body{padding:16px 15px!important}}
    </style>
    <?php
}, 32 );

add_action( 'wp_footer', function () {
    if ( ! is_single() ) return;
    ?>
    <script id="dn-single-article-sidebar-targets-v1">
    (function(){
      var sidebar=document.querySelector('.nv-sidebar-wrap');
      var posts=sidebar&&sidebar.querySelector('.wp-block-themeisle-blocks-posts-grid');
      if(posts&&!sidebar.querySelector('.dn-sidebar-articles-title')){
        var title=document.createElement('h2');title.className='dn-sidebar-articles-title';title.textContent='مطالب پیشنهادی';posts.parentElement.insertBefore(title,posts);
      }
    }());
    </script>
    <?php
}, 32 );


add_action( 'wp_head', function () {
    if ( ! is_single() ) return;
    ?>
    <style id="dn-single-article-header-v2">
    body.single-post .nv-post-cover{background:linear-gradient(135deg,#f5edf7 0%,#fbf8fc 55%,#fff 100%)!important;border-top:1px solid rgba(105,62,124,.08)!important;border-bottom:1px solid rgba(105,62,124,.15)!important;box-shadow:0 9px 26px rgba(68,37,91,.06)!important}
    body.single-post .nv-post-cover:before,body.single-post .nv-post-cover:after{display:none!important;background:none!important;opacity:0!important}
    body.single-post .nv-post-cover>.container{padding-top:48px!important;padding-bottom:50px!important}
    body.single-post .nv-post-cover .title.entry-title{max-width:980px!important;margin:0 auto!important;color:#582b66!important;text-shadow:none!important;font-weight:850!important;line-height:1.55!important;text-align:center!important}
    body.single-post .nv-post-cover .title.entry-title:after{content:"";display:block;width:62px;height:3px;margin:16px auto 0;border-radius:4px;background:linear-gradient(90deg,#6b3479,#d3a23b)}
    body.single-post .nv-post-cover .nv-meta-list,body.single-post .nv-post-cover .entry-meta{color:#756879!important;text-shadow:none!important;text-align:center!important}
    @media(max-width:680px){body.single-post .nv-post-cover>.container{padding-top:32px!important;padding-bottom:34px!important}body.single-post .nv-post-cover .title.entry-title{font-size:clamp(1.65rem,7.4vw,2.2rem)!important;line-height:1.55!important;padding:0 5px!important}body.single-post .nv-post-cover .title.entry-title:after{width:48px;margin-top:13px}}
    </style>
    <?php
}, 33 );


add_action( 'wp_head', function () {
    if ( ! is_single() ) return;
    ?>
    <style id="dn-single-article-header-purple-v3">
    body.single-post .nv-post-cover{background:linear-gradient(135deg,#4f255d 0%,#673574 55%,#754481 100%)!important;border-top:0!important;border-bottom:3px solid #d3a23b!important;box-shadow:0 12px 30px rgba(68,37,91,.18)!important}
    body.single-post .nv-post-cover .title.entry-title{color:#fff!important;text-shadow:none!important}
    body.single-post .nv-post-cover .title.entry-title:after{background:#e1bd68!important}
    body.single-post .nv-post-cover .nv-meta-list,body.single-post .nv-post-cover .entry-meta,body.single-post .nv-post-cover .nv-meta-list a,body.single-post .nv-post-cover .entry-meta a{color:#f1e6f3!important}
    </style>
    <?php
}, 34 );


add_action( 'wp_head', function () {
    if ( ! is_single() ) return;
    ?>
    <style id="dn-single-article-body-headings-v1">
    body.single-post article .entry-content h2,body.single-post article .entry-content h2.wp-block-heading{color:#5b2c69!important;font-size:clamp(26px,2.5vw,32px)!important;font-weight:850!important;line-height:1.65!important;margin-top:48px!important;margin-bottom:20px!important;padding-right:15px!important;border-right:4px solid #d3a23b!important}
    body.single-post article .entry-content h3,body.single-post article .entry-content h3.wp-block-heading{color:#663573!important;font-size:clamp(22px,2vw,26px)!important;font-weight:800!important;line-height:1.7!important;margin-top:40px!important;margin-bottom:17px!important}
    body.single-post article .entry-content h4,body.single-post article .entry-content h4.wp-block-heading{color:#68466f!important;font-size:20px!important;font-weight:750!important;line-height:1.7!important;margin-top:32px!important;margin-bottom:14px!important}
    body.single-post article .entry-content p{font-size:16.5px!important;line-height:2.05!important}
    @media(max-width:680px){body.single-post article .entry-content h2,body.single-post article .entry-content h2.wp-block-heading{font-size:23px!important;line-height:1.65!important;margin-top:38px!important;margin-bottom:16px!important;padding-right:11px!important;border-right-width:3px!important}body.single-post article .entry-content h3,body.single-post article .entry-content h3.wp-block-heading{font-size:20px!important;margin-top:31px!important;margin-bottom:14px!important}body.single-post article .entry-content h4,body.single-post article .entry-content h4.wp-block-heading{font-size:18px!important}body.single-post article .entry-content p{font-size:16px!important;line-height:2!important}}
    </style>
    <?php
}, 35 );


add_action( 'wp_head', function () {
    if ( ! is_single() ) return;
    ?>
    <style id="dn-single-article-headings-strong-v2">
    body.single-post .nv-single-post-wrap .entry-content h2,body.single-post article .entry-content h2,body.single-post article .entry-content h2.wp-block-heading{font-size:36px!important;font-weight:900!important;line-height:1.55!important;color:#542860!important;margin:54px 0 22px!important;padding:10px 17px 10px 0!important;border-right:5px solid #d3a23b!important;background:linear-gradient(90deg,transparent 0%,rgba(246,238,248,.75) 100%)!important;border-radius:0 10px 10px 0!important}
    body.single-post .nv-single-post-wrap .entry-content h3,body.single-post article .entry-content h3,body.single-post article .entry-content h3.wp-block-heading{font-size:28px!important;font-weight:850!important;line-height:1.6!important;color:#633270!important;margin:44px 0 19px!important}
    body.single-post .nv-single-post-wrap .entry-content h4,body.single-post article .entry-content h4,body.single-post article .entry-content h4.wp-block-heading{font-size:23px!important;font-weight:800!important;line-height:1.65!important;color:#68406f!important;margin:36px 0 16px!important}
    @media(max-width:680px){body.single-post .nv-single-post-wrap .entry-content h2,body.single-post article .entry-content h2,body.single-post article .entry-content h2.wp-block-heading{font-size:27px!important;line-height:1.6!important;margin:42px 0 18px!important;padding:8px 12px 8px 0!important;border-right-width:4px!important}body.single-post .nv-single-post-wrap .entry-content h3,body.single-post article .entry-content h3,body.single-post article .entry-content h3.wp-block-heading{font-size:23px!important;margin:34px 0 15px!important}body.single-post .nv-single-post-wrap .entry-content h4,body.single-post article .entry-content h4,body.single-post article .entry-content h4.wp-block-heading{font-size:20px!important;margin:29px 0 13px!important}}
    </style>
    <?php
}, 100 );


