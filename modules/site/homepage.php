<?php
defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', function () {
    if ( ! is_front_page() ) return;
    ?>
    <style id="dn-homepage-enhancements">
    body.home .nv-content-wrap.entry-content{overflow:visible!important}
    body.home #home{position:relative;isolation:isolate;width:100vw!important;max-width:none!important;margin-left:calc(50% - 50vw)!important;margin-right:calc(50% - 50vw)!important;border-radius:0!important}
    body.home #home::after{content:"";position:absolute;inset:auto -8% -110px;height:220px;background:radial-gradient(ellipse at center,rgba(255,255,255,.16),transparent 68%);pointer-events:none;z-index:0}
    body.home #home .wp-block-cover__inner-container{position:relative;z-index:1}
    body.home .entry-content>.wp-block-columns:first-of-type{gap:18px;padding:12px 18px 28px}
    body.home .entry-content>.wp-block-columns:first-of-type>.wp-block-column{border-radius:20px;transition:transform .22s ease,box-shadow .22s ease}

    body.home .entry-content>.wp-block-columns:first-of-type{align-items:stretch}
    body.home .entry-content>.wp-block-columns:first-of-type>.wp-block-column{position:relative;overflow:hidden;padding:26px 18px!important;background:linear-gradient(180deg,#fff 0%,#fbf8fc 100%);border:1px solid rgba(105,62,124,.13);box-shadow:0 10px 30px rgba(68,37,91,.08);text-align:center}
    body.home .entry-content>.wp-block-columns:first-of-type>.wp-block-column::before{content:"";position:absolute;inset:0 0 auto;height:4px;background:linear-gradient(90deg,#8c4b9f,#d8a840);opacity:.9}
    body.home .entry-content>.wp-block-columns:first-of-type figure{margin-bottom:14px!important}
    body.home .entry-content>.wp-block-columns:first-of-type figure img{max-height:62px;width:auto;object-fit:contain;filter:drop-shadow(0 7px 10px rgba(68,37,91,.12))}
    body.home .entry-content>.wp-block-columns:first-of-type h3{margin:8px 0 7px!important;color:#4d2858!important;font-weight:800}
    body.home .entry-content>.wp-block-columns:first-of-type p{margin-bottom:0!important;color:#665c69}
    body.home .payment-container{display:flex;justify-content:center;align-items:center;margin-top:12px}
    body.home .payment-btn{appearance:none;-webkit-appearance:none;min-width:190px;padding:13px 26px!important;border:1px solid rgba(255,255,255,.28)!important;border-radius:999px!important;background:linear-gradient(135deg,#7d3f8f 0%,#532660 100%)!important;color:#fff!important;font-family:Vazirmatn,Tahoma,sans-serif!important;font-size:17px!important;font-weight:800!important;line-height:1.4!important;letter-spacing:0!important;box-shadow:0 10px 25px rgba(79,35,92,.25)!important;cursor:pointer;transition:transform .2s ease,box-shadow .2s ease,filter .2s ease}
    body.home .payment-btn:hover,body.home .payment-btn:focus-visible{transform:translateY(-2px);box-shadow:0 14px 30px rgba(79,35,92,.32)!important;filter:saturate(1.08)}
    .dn-mobile-cta{display:none}
    @media(min-width:769px){body.home #home{min-height:calc(100vh - 70px)!important}body.home .entry-content>.wp-block-columns:first-of-type>.wp-block-column:hover{transform:translateY(-4px);box-shadow:0 16px 34px rgba(68,37,91,.10)}}
    @media(max-width:768px){body.home{padding-bottom:78px}body.home #home{min-height:545px!important;padding:28px 20px 52px!important;align-items:center}body.home #home .wp-block-columns{gap:8px}body.home #home h1{font-size:clamp(32px,9.2vw,44px)!important;line-height:1.42!important;letter-spacing:-.4px;margin-bottom:14px!important;text-wrap:balance}body.home #home h1+p,body.home #home p{font-size:17px!important;line-height:1.8!important}body.home #home .wp-block-buttons{margin-top:22px!important}body.home #home .wp-block-button,body.home #home .wp-block-button__link{width:100%}body.home #home .wp-block-button__link{min-height:52px;display:inline-flex;align-items:center;justify-content:center;border-radius:999px!important;font-weight:800;font-size:17px;box-shadow:0 12px 30px rgba(31,16,48,.24)}body.home .entry-content>.wp-block-columns:first-of-type{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;padding:18px 12px 28px}body.home .entry-content>.wp-block-columns:first-of-type>.wp-block-column{min-width:0;margin:0!important;padding:16px 9px!important;background:#fff;border:1px solid rgba(102,63,124,.10);box-shadow:0 8px 24px rgba(68,37,91,.08)}body.home .entry-content>.wp-block-columns:first-of-type>.wp-block-column:last-child{grid-column:1/-1}body.home .entry-content>.wp-block-columns:first-of-type h3{font-size:16px!important;line-height:1.6!important;margin-top:8px!important}body.home .entry-content>.wp-block-columns:first-of-type p{font-size:14px!important;line-height:1.7!important}body.home .entry-content h2{font-size:clamp(27px,7vw,34px)!important;line-height:1.55!important;text-wrap:balance}.dn-mobile-cta{position:fixed;display:flex;left:14px;right:76px;bottom:12px;z-index:9998;align-items:center;justify-content:center;min-height:54px;padding:12px 18px;color:#fff!important;background:linear-gradient(135deg,#7d3f8f,#562864);border:1px solid rgba(255,255,255,.25);border-radius:999px;box-shadow:0 12px 32px rgba(43,20,51,.34);font-weight:800;font-size:15px;text-decoration:none!important;direction:rtl}.dn-mobile-cta::before{content:"♪";margin-left:8px;color:#f7c65e;font-size:20px}body.home .joinchat{bottom:76px!important}}
    @media(max-width:390px){body.home .entry-content>.wp-block-columns:first-of-type{grid-template-columns:1fr}body.home .entry-content>.wp-block-columns:first-of-type>.wp-block-column:last-child{grid-column:auto}.dn-mobile-cta{right:14px}body.home .joinchat{bottom:80px!important}}

    /* Stable feature-grid targeting */
    body.home .entry-content>.wp-block-columns:has(.payment-container){display:grid!important;grid-template-columns:repeat(5,minmax(0,1fr));align-items:stretch!important;gap:16px!important;padding:20px 16px 34px!important}
    body.home .entry-content>.wp-block-columns:has(.payment-container)>.wp-block-column{position:relative;overflow:hidden;min-width:0;margin:0!important;padding:25px 16px!important;background:linear-gradient(180deg,#fff 0%,#fbf7fd 100%)!important;border:1px solid rgba(105,62,124,.14)!important;border-radius:22px!important;box-shadow:0 12px 30px rgba(68,37,91,.10)!important;text-align:center;transition:transform .22s ease,box-shadow .22s ease}
    body.home .entry-content>.wp-block-columns:has(.payment-container)>.wp-block-column::before{content:"";position:absolute;inset:0 0 auto;height:4px;background:linear-gradient(90deg,#8c4b9f,#d8a840)}
    body.home .entry-content>.wp-block-columns:has(.payment-container)>.wp-block-column:hover{transform:translateY(-4px);box-shadow:0 18px 38px rgba(68,37,91,.15)!important}
    body.home .entry-content>.wp-block-columns:has(.payment-container) figure{margin:0 0 13px!important}
    body.home .entry-content>.wp-block-columns:has(.payment-container) figure img{max-height:62px;width:auto;object-fit:contain;filter:drop-shadow(0 7px 10px rgba(68,37,91,.13))}
    body.home .entry-content>.wp-block-columns:has(.payment-container) h3{margin:8px 0!important;color:#4d2858!important;font-size:17px!important;line-height:1.55!important;font-weight:800!important}
    body.home .entry-content>.wp-block-columns:has(.payment-container) p{margin-bottom:0!important;color:#665c69!important;font-size:14px!important;line-height:1.75!important}
    body.home .entry-content>.wp-block-columns:has(.payment-container) .payment-btn{min-width:0;width:100%;padding:12px 10px!important;font-size:15px!important}

    /* Compact contact band */
    body.home section#contact{width:min(1120px,calc(100% - 32px))!important;margin:56px auto 38px!important;padding:30px 34px!important;border:1px solid rgba(255,255,255,.18)!important;border-radius:28px!important;background:linear-gradient(135deg,#5b2b68 0%,#7d3f8f 62%,#9a607f 100%)!important;box-shadow:0 18px 46px rgba(61,27,72,.20)!important;color:#fff!important;overflow:hidden}
    body.home section#contact h2{margin:0 0 22px!important;color:#fff!important;text-align:center;font-size:clamp(27px,3vw,38px)!important}
    body.home section#contact .innerblocks-wrap{gap:14px!important}
    body.home section#contact .wp-block-themeisle-blocks-advanced-column{padding:16px 18px!important;border:1px solid rgba(255,255,255,.18)!important;border-radius:18px!important;background:rgba(255,255,255,.10)!important;backdrop-filter:blur(8px);transition:transform .2s ease,background .2s ease}
    body.home section#contact .wp-block-themeisle-blocks-advanced-column:hover{transform:translateY(-2px);background:rgba(255,255,255,.16)!important}
    body.home section#contact .wp-block-themeisle-blocks-advanced-column:has(a[href*="wa.me"]){background:linear-gradient(180deg,#fff 0%,#fbf7fd 100%)!important;border-color:rgba(105,62,124,.14)!important;box-shadow:0 12px 30px rgba(68,37,91,.10)!important}
    body.home section#contact a,body.home section#contact h4{color:#fff!important;text-decoration:none!important}
    body.home section#contact h4{margin:6px 0 0!important;font-size:17px!important}
    @media(max-width:980px){body.home .entry-content>.wp-block-columns:has(.payment-container){grid-template-columns:repeat(2,minmax(0,1fr))}body.home .entry-content>.wp-block-columns:has(.payment-container)>.wp-block-column:last-child{grid-column:1/-1}}
    @media(max-width:600px){body.home .entry-content>.wp-block-columns:has(.payment-container){grid-template-columns:1fr!important;padding:16px 12px 28px!important}body.home .entry-content>.wp-block-columns:has(.payment-container)>.wp-block-column:last-child{grid-column:auto}body.home section#contact{width:calc(100% - 24px)!important;margin:40px auto 28px!important;padding:24px 16px!important;border-radius:22px!important}body.home section#contact .innerblocks-wrap{gap:10px!important}}

    /* Delnavazan topic-specific feature icons */
    body.home .entry-content>.wp-block-columns:has(.payment-container)>.wp-block-column>figure:first-child{display:flex!important;align-items:center;justify-content:center;width:78px;height:78px;margin:0 auto 15px!important;border:1px solid rgba(126,69,145,.14);border-radius:24px;background:linear-gradient(145deg,#fff 0%,#f5eefa 70%,#f9e8bd 100%);box-shadow:0 10px 24px rgba(71,37,84,.11)}
    body.home .entry-content>.wp-block-columns:has(.payment-container)>.wp-block-column>figure:first-child img{display:none!important}
    body.home .entry-content>.wp-block-columns:has(.payment-container)>.wp-block-column>figure:first-child::before{content:"";display:block;width:46px;height:46px;background-position:center;background-repeat:no-repeat;background-size:contain}
    body.home .entry-content>.wp-block-columns:has(.payment-container)>.wp-block-column:nth-child(1)>figure:first-child::before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Crect x='5' y='9' width='38' height='27' rx='5' fill='none' stroke='%236b3479' stroke-width='3'/%3E%3Cpath d='M17 18v10l10-5z' fill='%23d3a23b'/%3E%3Cpath d='M17 41h14' stroke='%236b3479' stroke-width='3' stroke-linecap='round'/%3E%3C/svg%3E")}
    body.home .entry-content>.wp-block-columns:has(.payment-container)>.wp-block-column:nth-child(2)>figure:first-child::before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Crect x='7' y='10' width='34' height='31' rx='5' fill='none' stroke='%236b3479' stroke-width='3'/%3E%3Cpath d='M7 19h34M16 6v8M32 6v8' stroke='%236b3479' stroke-width='3' stroke-linecap='round'/%3E%3Ctext x='24' y='34' text-anchor='middle' font-size='14' font-family='Arial' font-weight='700' fill='%23d3a23b'%3E12%3C/text%3E%3C/svg%3E")}
    body.home .entry-content>.wp-block-columns:has(.payment-container)>.wp-block-column:nth-child(3)>figure:first-child::before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Cpath d='M24 5l15 6v11c0 10-6 17-15 21C15 39 9 32 9 22V11z' fill='none' stroke='%236b3479' stroke-width='3' stroke-linejoin='round'/%3E%3Cpath d='M17 24l5 5 10-11' fill='none' stroke='%23d3a23b' stroke-width='3.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E")}
    body.home .entry-content>.wp-block-columns:has(.payment-container)>.wp-block-column:nth-child(4)>figure:first-child::before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Ccircle cx='21' cy='16' r='8' fill='none' stroke='%236b3479' stroke-width='3'/%3E%3Cpath d='M7 40c1-9 6-14 14-14s13 5 14 14' fill='none' stroke='%236b3479' stroke-width='3' stroke-linecap='round'/%3E%3Ccircle cx='36' cy='28' r='8' fill='%23f8e6b8' stroke='%23d3a23b' stroke-width='2'/%3E%3Cpath d='M32 28l3 3 5-6' fill='none' stroke='%236b3479' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E")}
    body.home .entry-content>.wp-block-columns:has(.payment-container)>.wp-block-column:nth-child(5)>figure:first-child::before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Cpath d='M7 15l17-8 17 8-17 8zM7 15v20l17 8 17-8V15M24 23v20' fill='none' stroke='%236b3479' stroke-width='3' stroke-linejoin='round'/%3E%3Cpath d='M31 24v9a4 4 0 1 1-3-3' fill='none' stroke='%23d3a23b' stroke-width='3' stroke-linecap='round'/%3E%3C/svg%3E")}

    /* Stable classes added by the homepage module */
    body.home .dn-feature-grid{display:grid!important;grid-template-columns:repeat(5,minmax(0,1fr));align-items:stretch!important;gap:16px!important;padding:20px 16px 34px!important}
    body.home .dn-feature-grid>.wp-block-column{position:relative!important;overflow:hidden!important;min-width:0!important;margin:0!important;padding:25px 16px!important;background:linear-gradient(180deg,#fff 0%,#fbf7fd 100%)!important;border:1px solid rgba(105,62,124,.14)!important;border-radius:22px!important;box-shadow:0 12px 30px rgba(68,37,91,.10)!important;text-align:center!important}
    body.home .dn-feature-grid>.wp-block-column::before{content:"";position:absolute;inset:0 0 auto;height:4px;background:linear-gradient(90deg,#8c4b9f,#d8a840)}
    body.home .dn-feature-grid>.wp-block-column figure:first-child{display:flex!important;align-items:center!important;justify-content:center!important;width:78px!important;height:78px!important;margin:0 auto 15px!important;border:1px solid rgba(126,69,145,.14)!important;border-radius:24px!important;background:linear-gradient(145deg,#fff 0%,#f5eefa 70%,#f9e8bd 100%)!important;box-shadow:0 10px 24px rgba(71,37,84,.11)!important}
    body.home .dn-feature-grid>.wp-block-column figure:first-child img{display:none!important}
    body.home .dn-feature-grid>.wp-block-column figure:first-child::before{content:"";display:block;width:46px;height:46px;background-position:center;background-repeat:no-repeat;background-size:contain}
    body.home .dn-feature-grid>.wp-block-column:nth-child(1) figure:first-child::before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Crect x='5' y='9' width='38' height='27' rx='5' fill='none' stroke='%236b3479' stroke-width='3'/%3E%3Cpath d='M17 18v10l10-5z' fill='%23d3a23b'/%3E%3Cpath d='M17 41h14' stroke='%236b3479' stroke-width='3' stroke-linecap='round'/%3E%3C/svg%3E")}
    body.home .dn-feature-grid>.wp-block-column:nth-child(2) figure:first-child::before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Crect x='7' y='10' width='34' height='31' rx='5' fill='none' stroke='%236b3479' stroke-width='3'/%3E%3Cpath d='M7 19h34M16 6v8M32 6v8' stroke='%236b3479' stroke-width='3' stroke-linecap='round'/%3E%3Ctext x='24' y='34' text-anchor='middle' font-size='14' font-family='Arial' font-weight='700' fill='%23d3a23b'%3E12%3C/text%3E%3C/svg%3E")}
    body.home .dn-feature-grid>.wp-block-column:nth-child(3) figure:first-child::before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Cpath d='M24 5l15 6v11c0 10-6 17-15 21C15 39 9 32 9 22V11z' fill='none' stroke='%236b3479' stroke-width='3' stroke-linejoin='round'/%3E%3Cpath d='M17 24l5 5 10-11' fill='none' stroke='%23d3a23b' stroke-width='3.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E")}
    body.home .dn-feature-grid>.wp-block-column:nth-child(4) figure:first-child::before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Ccircle cx='21' cy='16' r='8' fill='none' stroke='%236b3479' stroke-width='3'/%3E%3Cpath d='M7 40c1-9 6-14 14-14s13 5 14 14' fill='none' stroke='%236b3479' stroke-width='3' stroke-linecap='round'/%3E%3Ccircle cx='36' cy='28' r='8' fill='%23f8e6b8' stroke='%23d3a23b' stroke-width='2'/%3E%3Cpath d='M32 28l3 3 5-6' fill='none' stroke='%236b3479' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E")}
    body.home .dn-feature-grid>.wp-block-column:nth-child(5) figure:first-child::before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Cpath d='M7 15l17-8 17 8-17 8zM7 15v20l17 8 17-8V15M24 23v20' fill='none' stroke='%236b3479' stroke-width='3' stroke-linejoin='round'/%3E%3Cpath d='M31 24v9a4 4 0 1 1-3-3' fill='none' stroke='%23d3a23b' stroke-width='3' stroke-linecap='round'/%3E%3C/svg%3E")}
    body.home #home .dn-hero-enrol{display:inline-flex!important;align-items:center!important;justify-content:center!important;min-height:54px!important;padding:13px 30px!important;border:1px solid rgba(255,255,255,.34)!important;border-radius:999px!important;background:linear-gradient(135deg,#8a4a9c 0%,#5a2967 100%)!important;color:#fff!important;font-family:Vazirmatn,Tahoma,sans-serif!important;font-size:17px!important;font-weight:800!important;text-decoration:none!important;box-shadow:0 13px 32px rgba(42,18,50,.30)!important;transition:transform .2s ease,box-shadow .2s ease!important}
    body.home #home .dn-hero-enrol:hover,body.home #home .dn-hero-enrol:focus-visible{transform:translateY(-2px);box-shadow:0 17px 38px rgba(42,18,50,.38)!important}
    @media(max-width:980px){body.home .dn-feature-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important}body.home .dn-feature-grid>.wp-block-column:last-child{grid-column:1/-1}}
    @media(max-width:600px){body.home .dn-feature-grid{grid-template-columns:1fr!important;padding:16px 12px 28px!important}body.home .dn-feature-grid>.wp-block-column:last-child{grid-column:auto}body.home #home .dn-hero-enrol{width:100%!important}}

    /* Contact cards matching the homepage feature system */
    body.home section#contact.dn-contact-section{width:min(1120px,calc(100% - 32px))!important;margin:56px auto 38px!important;padding:8px 0 28px!important;border:0!important;border-radius:0!important;background:transparent!important;box-shadow:none!important;color:#3f3443!important;overflow:visible!important}
    body.home section#contact.dn-contact-section h2{margin:0 0 24px!important;color:#542860!important;text-align:center!important;font-size:clamp(28px,3.4vw,40px)!important}
    body.home .dn-contact-grid{display:grid!important;grid-template-columns:repeat(3,minmax(0,1fr))!important;gap:16px!important;align-items:stretch!important}
    body.home section#contact .dn-contact-card{position:relative!important;display:flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;min-height:190px!important;padding:24px 18px!important;border:1px solid rgba(105,62,124,.14)!important;border-radius:22px!important;background:linear-gradient(180deg,#fff 0%,#fbf7fd 100%)!important;box-shadow:0 12px 30px rgba(68,37,91,.10)!important;text-align:center!important;overflow:hidden!important;transition:transform .22s ease,box-shadow .22s ease!important}
    body.home section#contact .dn-contact-card:hover{transform:translateY(-4px)!important;box-shadow:0 18px 38px rgba(68,37,91,.15)!important;background:linear-gradient(180deg,#fff 0%,#fbf7fd 100%)!important}
    body.home section#contact .dn-contact-card:after{content:"";position:absolute;inset:0 0 auto;height:4px;background:linear-gradient(90deg,#8c4b9f,#d8a840)}
    body.home section#contact .dn-contact-card:before{content:"";display:block;width:72px;height:72px;margin:0 auto 15px;border:1px solid rgba(126,69,145,.14);border-radius:22px;background-color:#f6eff9;background-position:center;background-repeat:no-repeat;background-size:42px 42px;box-shadow:0 9px 22px rgba(71,37,84,.10)}
    body.home section#contact .dn-contact-card h4{margin:5px 0 0!important;color:#542860!important;font-size:18px!important;font-weight:800!important}
    body.home section#contact .dn-contact-card h4 a{color:#542860!important;text-decoration:none!important}
    body.home section#contact .dn-contact-card .dn-original-contact-icon{display:none!important}
    body.home section#contact .dn-contact-email:before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Crect x='5' y='10' width='38' height='28' rx='5' fill='none' stroke='%236b3479' stroke-width='3'/%3E%3Cpath d='M7 13l17 14 17-14' fill='none' stroke='%23d3a23b' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E")}
    body.home section#contact .dn-contact-instagram:before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Crect x='7' y='7' width='34' height='34' rx='10' fill='none' stroke='%236b3479' stroke-width='3'/%3E%3Ccircle cx='24' cy='24' r='8' fill='none' stroke='%23d3a23b' stroke-width='3'/%3E%3Ccircle cx='34' cy='14' r='2.5' fill='%236b3479'/%3E%3C/svg%3E")}
    body.home section#contact .dn-contact-whatsapp{border-color:rgba(105,62,124,.14)!important;background:linear-gradient(180deg,#fff 0%,#fbf7fd 100%)!important}
    body.home section#contact .dn-contact-whatsapp:after{background:linear-gradient(90deg,#8c4b9f,#d8a840)!important}
    body.home section#contact .dn-contact-whatsapp:before{border-color:rgba(126,69,145,.14);background-color:#f6eff9;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Cpath d='M39 23a15 15 0 0 1-22 13L9 39l3-8A15 15 0 1 1 39 23z' fill='none' stroke='%236b3479' stroke-width='3' stroke-linejoin='round'/%3E%3Cpath d='M18 15c1 7 5 11 12 14l3-4' fill='none' stroke='%23d3a23b' stroke-width='3' stroke-linecap='round'/%3E%3C/svg%3E")}
    body.home section#contact .dn-contact-whatsapp h4 a{color:#542860!important}
    @media(max-width:760px){body.home .dn-contact-grid{grid-template-columns:1fr!important;gap:12px!important}body.home section#contact.dn-contact-section{width:calc(100% - 24px)!important;margin:42px auto 28px!important;padding-bottom:14px!important}body.home section#contact .dn-contact-card{min-height:160px!important}}
body.home section#contact.dn-contact-section .dn-contact-grid .dn-contact-card.dn-contact-whatsapp{background:linear-gradient(180deg,#fff 0%,#fbf7fd 100%)!important;border-color:rgba(105,62,124,.14)!important;box-shadow:0 12px 30px rgba(68,37,91,.10)!important}
    body.home section#contact .dn-contact-whatsapp:before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Cpath d='M24 6a17 17 0 0 0-14.7 25.6L7 41l9.7-2.5A17 17 0 1 0 24 6z' fill='none' stroke='%236b3479' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'/%3E%3Cpath d='M17 14c1 8 7 15 15 17l3-5-6-3-2 3c-3-1-6-4-7-7l3-2-3-6z' fill='none' stroke='%23d3a23b' stroke-width='2.7' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E")!important;background-size:44px 44px!important}
    </style>
    <?php
}, 40 );

add_action( 'wp_footer', function () {
    if ( ! is_front_page() ) return;
    echo '<a class="dn-mobile-cta" href="' . esc_url( home_url( '/enrol/' ) ) . '" aria-label="رزرو جلسه معارفه رایگان">رزرو جلسه معارفه رایگان</a>';
}, 40 );


add_action( 'wp_footer', function () {
    if ( ! is_front_page() ) return;
    ?>
    <script id="dn-homepage-stable-targets">
    (function () {
        var payment = document.querySelector('.payment-container');
        var grid = payment ? payment.closest('.wp-block-columns') : null;
        if (grid) grid.classList.add('dn-feature-grid');

        var heroButton = document.querySelector('#home a.wp-block-button__link[href*="/enrol/"]');
        if (heroButton) {
            heroButton.classList.add('dn-hero-enrol');
            heroButton.textContent = 'رزرو جلسه معارفه رایگان';
        }
    }());
    </script>
    <?php
}, 41 );


add_action( 'wp_footer', function () {
    if ( ! is_front_page() ) return;
    ?>
    <script id="dn-contact-stable-targets">
    (function () {
        var section = document.querySelector('section#contact');
        if (!section) return;
        section.classList.add('dn-contact-section');

        [
            ['a[href^="mailto:"]', 'dn-contact-email'],
            ['a[href*="instagram.com"]', 'dn-contact-instagram'],
            ['a[href*="wa.me"]', 'dn-contact-whatsapp']
        ].forEach(function (item) {
            var link = section.querySelector(item[0]);
            var card = link ? link.closest('.wp-block-themeisle-blocks-advanced-column') : null;
            if (!card) return;
            card.classList.add('dn-contact-card', item[1]);
            var grid = card.closest('.innerblocks-wrap');
            if (grid) grid.classList.add('dn-contact-grid');
            var links = card.querySelectorAll(item[0]);
            if (links.length) links[0].classList.add('dn-original-contact-icon');
        });
    }());
    </script>
    <?php
}, 42 );


add_action( 'wp_head', function () {
    if ( ! is_front_page() ) return;
    ?>
    <style id="dn-homepage-consistency-v1">
    body.home main{overflow:hidden}
    body.home main p{color:#493d4d;line-height:2}
    body.home .dn-home-section-title{position:relative;color:#5b2c69!important;font-weight:800!important;line-height:1.5!important;text-align:center!important;margin:0!important;padding:28px 24px 20px!important;background:linear-gradient(180deg,#fff 0%,#fcf8fd 100%)!important;border:1px solid rgba(105,62,124,.13)!important;border-bottom:0!important;border-radius:26px 26px 0 0!important;box-shadow:0 14px 38px rgba(68,37,91,.08)!important}
    body.home .dn-home-section-title:after{content:"";display:block;width:54px;height:3px;margin:12px auto 0;border-radius:4px;background:linear-gradient(90deg,#6b3479,#d3a23b)}
    body.home .dn-home-copy{max-width:none!important;margin:0!important;padding:0 34px 18px!important;background:#fff!important;border-left:1px solid rgba(105,62,124,.13)!important;border-right:1px solid rgba(105,62,124,.13)!important;font-size:16.5px!important}
    body.home .dn-home-copy.dn-home-copy-last{padding-bottom:32px!important;border-bottom:1px solid rgba(105,62,124,.13)!important;border-radius:0 0 26px 26px!important;box-shadow:0 18px 40px rgba(68,37,91,.08)!important;margin-bottom:58px!important}
    body.home .dn-home-courses-title{margin-top:0!important}
    body.home .dn-home-course-copy{text-align:center!important}
    body.home .dn-home-seo-heading{color:#6b3479!important;text-align:center!important;font-size:clamp(20px,2.4vw,28px)!important;margin:46px auto 22px!important;max-width:900px!important;line-height:1.7!important}
    body.home .dn-faq-title{color:#5b2c69!important;text-align:center!important;font-weight:800!important;margin:54px auto 24px!important}
    body.home .dn-faq-title:after{content:"";display:block;width:54px;height:3px;margin:12px auto 0;border-radius:4px;background:#d3a23b}
    body.home .dn-faq-item{display:block!important;max-width:980px!important;margin:0 auto 13px!important;padding:0!important;background:#fff!important;border:1px solid rgba(105,62,124,.14)!important;border-radius:17px!important;box-shadow:0 8px 24px rgba(68,37,91,.07)!important;overflow:hidden!important;transition:border-color .2s ease,box-shadow .2s ease,transform .2s ease}
    body.home .dn-faq-item:hover{border-color:rgba(105,62,124,.32)!important;box-shadow:0 12px 30px rgba(68,37,91,.11)!important;transform:translateY(-1px)}
    body.home .dn-faq-item[open]{border-color:rgba(211,162,59,.55)!important;background:linear-gradient(180deg,#fff,#fcf8fd)!important}
    body.home .dn-faq-item summary{cursor:pointer!important;color:#542860!important;font-weight:750!important;font-size:16px!important;line-height:1.8!important;padding:17px 22px!important;list-style:none!important}
    body.home .dn-faq-item summary::-webkit-details-marker{display:none}
    body.home .dn-faq-item summary:before{content:"+";display:inline-grid;place-items:center;float:left;width:26px;height:26px;margin:1px 12px 0 0;border-radius:50%;background:#f3eaf6;color:#6b3479;font-size:20px;line-height:1}
    body.home .dn-faq-item[open] summary:before{content:"−";background:#6b3479;color:#fff}
    body.home .dn-faq-item>div,body.home .dn-faq-item>p{padding:0 22px 18px!important;margin:0!important;line-height:2!important}
    body.home .dn-home-manifesto{max-width:980px!important;margin:58px auto!important;padding:38px 34px!important;border-radius:26px!important;background:linear-gradient(135deg,#51285f 0%,#744081 100%)!important;box-shadow:0 20px 44px rgba(68,37,91,.18)!important;text-align:center!important;position:relative!important;overflow:hidden!important}
    body.home .dn-home-manifesto:before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 12% 20%,rgba(211,162,59,.22),transparent 34%);pointer-events:none}
    body.home .dn-home-manifesto,body.home .dn-home-manifesto *{color:#fff!important}
    body.home .dn-home-manifesto strong{position:relative;font-size:clamp(20px,2.5vw,29px)!important;line-height:1.8!important}
    body.home .dn-home-manifesto p{position:relative;margin:12px auto 0!important;max-width:790px!important;color:#f7edf9!important}
    body.home .dn-article-card{border:1px solid rgba(105,62,124,.12)!important;border-radius:20px!important;overflow:hidden!important;box-shadow:0 10px 28px rgba(68,37,91,.08)!important;background:#fff!important;transition:transform .2s ease,box-shadow .2s ease!important}
    body.home .dn-article-card:hover{transform:translateY(-3px);box-shadow:0 16px 36px rgba(68,37,91,.13)!important}
    body.home .dn-article-card h4 a{color:#5b2c69!important;line-height:1.65!important}
    @media(max-width:760px){body.home .dn-home-section-title{padding:23px 17px 17px!important;border-radius:20px 20px 0 0!important;font-size:23px!important}body.home .dn-home-copy{padding:0 19px 16px!important;font-size:15.5px!important;text-align:right!important}body.home .dn-home-copy.dn-home-copy-last{padding-bottom:24px!important;border-radius:0 0 20px 20px!important;margin-bottom:38px!important}body.home .dn-home-seo-heading{font-size:20px!important;margin:34px 15px 20px!important}body.home .dn-faq-title{font-size:24px!important;margin:38px auto 20px!important}body.home .dn-faq-item{margin:0 12px 11px!important;border-radius:15px!important}body.home .dn-faq-item summary{padding:15px 16px!important;font-size:15px!important}body.home .dn-faq-item summary:before{margin-right:8px!important}body.home .dn-home-manifesto{width:calc(100% - 24px)!important;margin:40px auto!important;padding:30px 20px!important;border-radius:21px!important}body.home .dn-home-manifesto strong{font-size:20px!important}}
    </style>
    <?php
}, 43 );

add_action( 'wp_footer', function () {
    if ( ! is_front_page() ) return;
    ?>
    <script id="dn-homepage-consistency-targets-v1">
    (function () {
      function text(el){return (el && el.textContent || '').replace(/\s+/g,' ').trim();}
      var headings = Array.prototype.slice.call(document.querySelectorAll('main h2,main h3'));
      var explainer = headings.find(function(el){return text(el)==='نحوه برگزاری کلاس‌ها';});
      if(explainer){explainer.classList.add('dn-home-section-title');var n=explainer.nextElementSibling,last=null;while(n && !/^H[1-6]$/.test(n.tagName)){if(n.tagName==='P'){n.classList.add('dn-home-copy');last=n;}n=n.nextElementSibling;}if(last)last.classList.add('dn-home-copy-last');}
      var courses = headings.find(function(el){return text(el)==='دوره های موجود';});
      if(courses){courses.classList.add('dn-home-section-title','dn-home-courses-title');var cp=courses.nextElementSibling;if(cp&&cp.tagName==='P')cp.classList.add('dn-home-copy','dn-home-copy-last','dn-home-course-copy');}
      var seoH=headings.find(function(el){return text(el)==='کلاس‌های آنلاین موسیقی ایرانی برای ایرانیان خارج از کشور';});if(seoH)seoH.classList.add('dn-home-seo-heading');
      var faq=headings.find(function(el){return text(el)==='پاسخ به پرسشهای متداول';});if(faq){faq.classList.add('dn-faq-title');}
      Array.prototype.forEach.call(document.querySelectorAll('main details'),function(d){if(faq&&(faq.compareDocumentPosition(d)&Node.DOCUMENT_POSITION_FOLLOWING))d.classList.add('dn-faq-item');});
      var manifestoStrong=Array.prototype.find.call(document.querySelectorAll('main strong'),function(el){return text(el)==='در این راه، دلنوازان هم‌سفر شماست';});if(manifestoStrong){var block=manifestoStrong.closest('.wp-block-group,.wp-block-columns,.wp-block-column,figure')||manifestoStrong.parentElement;if(block)block.classList.add('dn-home-manifesto');}
      Array.prototype.forEach.call(document.querySelectorAll('main h4 a'),function(a){var card=a.closest('article,.wp-block-post,.wp-block-themeisle-blocks-posts-grid-post-blog,.wp-block-themeisle-blocks-posts-grid-post');if(card)card.classList.add('dn-article-card');});
    }());
    </script>
    <?php
}, 44 );


add_action( 'wp_footer', function () {
    if ( ! is_front_page() ) return;
    ?>
    <script id="dn-homepage-contact-order-v1">
    (function(){
      var contact=document.querySelector('section#contact');
      if(contact && contact.parentElement) contact.parentElement.appendChild(contact);
    }());
    </script>
    <?php
}, 45 );


add_filter( 'render_block', function ( $content, $block ) {
    if ( is_admin() || ! is_front_page() || empty( $content ) ) return $content;
    $content = str_replace(
        array( 'دوره های موجود', 'پاسخ به پرسشهای متداول', 'هفته ای', 'می توانید' ),
        array( 'دوره‌های آموزشی', 'پاسخ به پرسش‌های متداول', 'هفته‌ای', 'می‌توانید' ),
        $content
    );
    if ( strpos( wp_strip_all_tags( $content ), 'نحوه برگزاری کلاس‌ها' ) !== false ) {
        $content = preg_replace( '/<h3([^>]*)>/', '<h2$1>', $content, 1 );
        $content = preg_replace( '/<\/h3>/', '</h2>', $content, 1 );
    }
    $content = preg_replace( '/(<summary[^>]*>\s*(?:<[^>]+>\s*)*)(?:سوال|سؤال)\s*:\s*/u', '$1', $content );
    return $content;
}, 20, 2 );

add_action( 'wp_head', function () {
    if ( ! is_front_page() ) return;
    ?>
    <style id="dn-homepage-final-refinements-v1">
    body.home .dn-latest-articles{max-width:1120px;margin:62px auto 24px;text-align:center;padding:0 20px}
    body.home .dn-latest-articles h2{color:#5b2c69!important;font-weight:850!important;font-size:clamp(27px,3vw,38px)!important;margin:0!important;line-height:1.5!important}
    body.home .dn-latest-articles h2:after{content:"";display:block;width:58px;height:3px;margin:13px auto 15px;border-radius:5px;background:linear-gradient(90deg,#6b3479,#d3a23b)}
    body.home .dn-latest-articles p{max-width:720px;margin:0 auto!important;color:#665a69!important;font-size:16.5px!important}
    body.home .o-featured-post,body.home .o-posts-grid-post-blog{border:1px solid rgba(105,62,124,.13)!important;border-radius:21px!important;overflow:hidden!important;background:#fff!important;box-shadow:0 10px 28px rgba(68,37,91,.08)!important;transition:transform .2s ease,box-shadow .2s ease!important}
    body.home .o-featured-post:hover,body.home .o-posts-grid-post-blog:hover{transform:translateY(-3px);box-shadow:0 16px 38px rgba(68,37,91,.13)!important}
    body.home .o-posts-grid-post-title a{color:#5b2c69!important;line-height:1.65!important}
    body.home .o-posts-grid-post-blog:nth-child(n+6){display:none!important}
    body.home .dn-home-cta{max-width:980px;margin:48px auto 42px;padding:34px 30px;border:1px solid rgba(105,62,124,.16);border-radius:25px;background:linear-gradient(135deg,#fbf7fd 0%,#fff 70%);box-shadow:0 16px 38px rgba(68,37,91,.10);text-align:center;position:relative;overflow:hidden}
    body.home .dn-home-cta:before{content:"";position:absolute;inset:auto -60px -90px auto;width:210px;height:210px;border-radius:50%;background:rgba(211,162,59,.10)}
    body.home .dn-home-cta h2{position:relative;color:#582b66!important;font-size:clamp(24px,2.7vw,33px)!important;margin:0 0 10px!important;line-height:1.55!important}
    body.home .dn-home-cta p{position:relative;max-width:730px;margin:0 auto 21px!important;color:#5b505e!important;font-size:16.5px!important}
    body.home .dn-home-cta a,body.home .dn-all-articles{position:relative;display:inline-flex!important;align-items:center;justify-content:center;min-height:48px;padding:11px 27px!important;border-radius:999px!important;background:linear-gradient(135deg,#6b3479,#542860)!important;color:#fff!important;text-decoration:none!important;font-weight:800!important;box-shadow:0 9px 22px rgba(84,40,96,.24)!important;transition:transform .2s ease,box-shadow .2s ease!important}
    body.home .dn-home-cta a:hover,body.home .dn-all-articles:hover{transform:translateY(-2px);box-shadow:0 13px 28px rgba(84,40,96,.30)!important}
    body.home .dn-articles-footer{text-align:center;margin:28px auto 54px}
    body.home .dn-all-articles:after{content:" ←";margin-right:7px;color:#e5c777}
    @media(max-width:760px){body.home .dn-latest-articles{margin:42px auto 20px;padding:0 16px}body.home .dn-latest-articles h2{font-size:27px!important}body.home .dn-latest-articles p{font-size:15.5px!important}body.home .dn-home-cta{width:calc(100% - 24px);margin:35px auto 32px;padding:28px 18px;border-radius:21px}body.home .dn-home-cta h2{font-size:23px!important}body.home .dn-home-cta p{font-size:15.5px!important}body.home .o-featured-post,body.home .o-posts-grid-post-blog{border-radius:17px!important}body.home .dn-articles-footer{margin:23px auto 42px}}
    </style>
    <?php
}, 46 );

add_action( 'wp_footer', function () {
    if ( ! is_front_page() ) return;
    ?>
    <script id="dn-homepage-final-refinements-targets-v1">
    (function(){
      function clean(el){return(el&&el.textContent||'').replace(/\s+/g,' ').trim();}
      Array.prototype.forEach.call(document.querySelectorAll('main h2,main h3'),function(h){
        if(clean(h)==='دوره‌های آموزشی'||clean(h)==='دوره های موجود')h.classList.add('dn-home-section-title','dn-home-courses-title');
        if(clean(h)==='پاسخ به پرسش‌های متداول'||clean(h)==='پاسخ به پرسشهای متداول')h.classList.add('dn-faq-title');
        if(clean(h)==='نحوه برگزاری کلاس‌ها')h.classList.add('dn-home-section-title');
      });
      Array.prototype.forEach.call(document.querySelectorAll('main details summary strong,main details summary'),function(s){s.innerHTML=s.innerHTML.replace(/^\s*(سوال|سؤال)\s*:\s*/,'');});
      var featured=document.querySelector('main .o-featured-container');
      var grid=document.querySelector('main .o-posts-grid-columns-3');
      if(featured && !document.querySelector('.dn-latest-articles')){
        var cta=document.createElement('section');cta.className='dn-home-cta';cta.innerHTML='<h2>آماده‌اید مسیر یادگیری موسیقی را آغاز کنید؟</h2><p>جلسه معارفه فرصتی رایگان برای آشنایی با استاد، بررسی سطح شما و انتخاب مسیر آموزشی مناسب است.</p><a href="/enrol/">رزرو جلسه معارفه رایگان</a>';
        featured.parentElement.insertBefore(cta,featured);
        var intro=document.createElement('section');intro.className='dn-latest-articles';intro.innerHTML='<h2>تازه‌ترین مقالات دلنوازان</h2><p>خواندنی‌هایی درباره موسیقی ایرانی، سازها، آموزش و پیوند موسیقی با فرهنگ و هویت ایرانی.</p>';
        featured.parentElement.insertBefore(intro,featured);
      }
      if(grid && !document.querySelector('.dn-articles-footer')){
        var footer=document.createElement('div');footer.className='dn-articles-footer';footer.innerHTML='<a class="dn-all-articles" href="/articles/">مشاهده همه مقالات</a>';
        grid.parentElement.insertBefore(footer,grid.nextSibling);
      }
      var contact=document.querySelector('section#contact');if(contact&&contact.parentElement)contact.parentElement.appendChild(contact);
    }());
    </script>
    <?php
}, 47 );


add_action( 'wp_head', function () {
    if ( ! is_front_page() ) return;
    ?>
    <style id="dn-homepage-unified-articles-v1">
    body.home .o-featured-container.dn-featured-moved{display:none!important}
    body.home .o-posts-grid-columns-3>.dn-unified-article-card{display:block!important;min-width:0!important;margin:0!important;border:1px solid rgba(105,62,124,.13)!important;border-radius:21px!important;overflow:hidden!important;background:#fff!important;box-shadow:0 10px 28px rgba(68,37,91,.08)!important;transition:transform .2s ease,box-shadow .2s ease!important}
    body.home .o-posts-grid-columns-3>.dn-unified-article-card:hover{transform:translateY(-3px);box-shadow:0 16px 38px rgba(68,37,91,.13)!important}
    body.home .o-posts-grid-columns-3>.dn-unified-article-card:nth-child(-n+6){display:block!important}
    body.home .o-posts-grid-columns-3>.dn-unified-article-card:nth-child(n+7),body.home .o-posts-grid-columns-3>.o-posts-grid-post-blog:nth-child(n+7){display:none!important}
    body.home .o-posts-grid-columns-3>.dn-unified-article-card .o-posts-grid-post-image,body.home .o-posts-grid-columns-3>.dn-unified-article-card .o-posts-grid-post-image img{width:100%!important}
    body.home .o-posts-grid-columns-3>.dn-unified-article-card .o-posts-grid-post-image img{height:220px!important;object-fit:cover!important}
    body.home .o-posts-grid-columns-3>.dn-unified-article-card .o-posts-grid-post-body{padding:21px!important}
    @media(max-width:760px){body.home .o-posts-grid-columns-3>.dn-unified-article-card{border-radius:17px!important}body.home .o-posts-grid-columns-3>.dn-unified-article-card .o-posts-grid-post-image img{height:210px!important}}
    </style>
    <?php
}, 48 );

add_action( 'wp_footer', function () {
    if ( ! is_front_page() ) return;
    ?>
    <script id="dn-homepage-unified-articles-targets-v1">
    (function(){
      var container=document.querySelector('main .o-featured-container');
      var featured=container&&container.querySelector('.o-featured-post');
      var grid=document.querySelector('main .o-posts-grid-columns-3');
      if(featured&&grid){
        featured.classList.add('dn-unified-article-card');
        grid.insertBefore(featured,grid.firstElementChild);
        container.classList.add('dn-featured-moved');
      }
      if(grid){Array.prototype.forEach.call(grid.children,function(card){card.classList.add('dn-unified-article-card');});}
    }());
    </script>
    <?php
}, 49 );


add_action( 'wp_head', function () {
    if ( ! is_front_page() ) return;
    ?>
    <style id="dn-homepage-faq-manifesto-fix-v1">
    body.home .dn-home-manifesto{max-width:900px!important;margin:46px auto 34px!important;padding:27px 30px!important;border-radius:18px!important;background:#fbf8fc!important;border:1px solid rgba(105,62,124,.12)!important;border-right:4px solid #d3a23b!important;box-shadow:none!important;text-align:center!important;overflow:visible!important}
    body.home .dn-home-manifesto:before{display:none!important}
    body.home .dn-home-manifesto,body.home .dn-home-manifesto *{color:#5b4d60!important}
    body.home .dn-home-manifesto>strong,body.home .dn-home-manifesto strong{font-size:18px!important;font-weight:700!important;line-height:1.8!important;color:#62346d!important}
    body.home .dn-home-manifesto p{margin:8px auto 0!important;color:#6c6170!important;font-size:15.5px!important;line-height:1.9!important}
    body.home .dn-home-manifesto p strong{font-size:15.5px!important;font-weight:500!important;color:#6c6170!important}
    @media(max-width:760px){body.home .dn-home-manifesto{width:calc(100% - 24px)!important;margin:34px auto 27px!important;padding:22px 18px!important;border-radius:16px!important}body.home .dn-home-manifesto>strong,body.home .dn-home-manifesto strong{font-size:17px!important}body.home .dn-home-manifesto p strong{font-size:14.5px!important}}
    </style>
    <?php
}, 50 );

add_action( 'wp_footer', function () {
    if ( ! is_front_page() ) return;
    ?>
    <script id="dn-homepage-faq-restore-v1">
    (function(){
      function clean(el){return(el&&el.textContent||'').replace(/\s+/g,' ').trim();}
      var headings=Array.prototype.slice.call(document.querySelectorAll('main h2'));
      var faq=headings.find(function(h){return clean(h)==='پاسخ به پرسش‌های متداول'||clean(h)==='پاسخ به پرسشهای متداول';});
      if(faq){
        faq.classList.add('dn-faq-title');
        Array.prototype.forEach.call(document.querySelectorAll('main details'),function(d){
          if(faq.compareDocumentPosition(d)&Node.DOCUMENT_POSITION_FOLLOWING)d.classList.add('dn-faq-item');
        });
      }
    }());
    </script>
    <?php
}, 51 );


add_action( 'wp_head', function () {
    if ( ! is_front_page() ) return;
    ?>
    <style id="dn-homepage-text-only-articles-v1">
    body.home .o-posts-grid-columns-3>.dn-unified-article-card .o-posts-grid-post-image,body.home .o-posts-grid-columns-3>.dn-unified-article-card figure,body.home .o-posts-grid-columns-3>.dn-unified-article-card img{display:none!important}
    body.home .o-posts-grid-columns-3>.dn-unified-article-card{display:flex!important;flex-direction:column!important;min-height:255px!important}
    body.home .o-posts-grid-columns-3>.dn-unified-article-card .o-posts-grid-post-body{display:flex!important;flex:1!important;flex-direction:column!important;padding:25px 23px!important}
    body.home .o-posts-grid-columns-3>.dn-unified-article-card .o-posts-grid-post-title{font-size:20px!important;margin:0 0 13px!important}
    body.home .o-posts-grid-columns-3>.dn-unified-article-card .o-posts-grid-post-description,body.home .o-posts-grid-columns-3>.dn-unified-article-card .o-posts-grid-post-excerpt{display:-webkit-box!important;-webkit-line-clamp:4!important;-webkit-box-orient:vertical!important;overflow:hidden!important;color:#625766!important;font-size:15px!important;line-height:1.95!important}
    @media(max-width:760px){body.home .o-posts-grid-columns-3>.dn-unified-article-card{min-height:0!important}body.home .o-posts-grid-columns-3>.dn-unified-article-card .o-posts-grid-post-body{padding:21px 18px!important}body.home .o-posts-grid-columns-3>.dn-unified-article-card .o-posts-grid-post-title{font-size:18.5px!important}}
    </style>
    <?php
}, 52 );


add_action( 'wp_head', function () {
    if ( ! is_front_page() ) return;
    ?>
    <style id="dn-mobile-action-pair-v1">
    .dn-mobile-whatsapp{display:none}
    @media(max-width:760px){
      body.home .dn-mobile-cta{right:78px!important;left:14px!important;bottom:12px!important;width:auto!important;min-height:54px!important;z-index:9998!important;margin:0!important}
      body.home .dn-mobile-whatsapp{position:fixed;display:flex;align-items:center;justify-content:center;right:14px;bottom:12px;width:54px;height:54px;border:2px solid rgba(225,189,104,.8);border-radius:17px;background:linear-gradient(135deg,#6b3479,#542860);color:#fff!important;box-shadow:0 11px 28px rgba(52,27,65,.28);text-decoration:none!important;z-index:9999;transition:transform .2s ease,box-shadow .2s ease}
      body.home .dn-mobile-whatsapp:hover,body.home .dn-mobile-whatsapp:focus{color:#fff!important;transform:translateY(-2px);box-shadow:0 14px 32px rgba(52,27,65,.34)}
      body.home .dn-mobile-whatsapp svg{display:block;width:29px;height:29px;fill:none;stroke:currentColor;stroke-width:2.15;stroke-linecap:round;stroke-linejoin:round}
      body.home .joinchat,body.home .joinchat--right,body.home .wa__btn_popup,body.home .nta-wa-gdpr,body.home #nta-wa-gdpr,body.home .nta_wa_button,body.home .nta-whatsapp-popup{display:none!important}
      body.home{padding-bottom:78px!important}
    }
    </style>
    <?php
}, 200 );

add_action( 'wp_footer', function () {
    if ( ! is_front_page() ) return;
    ?>
    <a class="dn-mobile-whatsapp" href="https://wa.me/61413413004" target="_blank" rel="noopener noreferrer" aria-label="تماس با دلنوازان در واتساپ">
      <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16 4.5a11.3 11.3 0 0 0-9.8 17L4.7 27l5.7-1.5A11.3 11.3 0 1 0 16 4.5Z"/><path d="M11.1 10.4c.7 5.2 4.5 9 9.7 10.1l2-3.1-3.9-2-1.3 1.7c-1.9-.8-3.7-2.6-4.5-4.5l1.7-1.2-2-4Z"/></svg>
    </a>
    <?php
}, 200 );


/* Keep Google Calendar verification information in the homepage footer. */
add_filter( 'the_content', function ( $content ) {
    if ( is_admin() || ! is_front_page() || ! in_the_loop() || ! is_main_query() ) return $content;
    return preg_replace( '/<div class="wp-block-group dn-google-calendar-notice">.*?<\/div>\s*<\/div>/s', '', $content, 1 );
}, 30 );

add_action( 'wp_head', function () {
    if ( ! is_front_page() ) return;
    ?>
    <style id="dn-google-calendar-footer-style">
    .dn-google-calendar-footer{max-width:760px;margin:22px auto 0;padding:18px 20px;border-top:1px solid rgba(255,255,255,.16);color:rgba(255,255,255,.72);font-family:Vazirmatn,Tahoma,sans-serif;text-align:center;direction:ltr;font-size:12px;line-height:1.75}
    .dn-google-calendar-footer h2{margin:0 0 7px;color:#fff;font-size:14px;font-weight:700}.dn-google-calendar-footer p{margin:6px 0}.dn-google-calendar-footer a{color:#e5c777;text-decoration:none}.dn-google-calendar-footer a:hover{text-decoration:underline}
    @media(max-width:760px){.dn-google-calendar-footer{margin-top:16px;padding:15px 16px;font-size:11.5px}}
    </style>
    <?php
}, 201 );

add_action( 'wp_footer', function () {
    if ( ! is_front_page() ) return;
    ?>
    <section class="dn-google-calendar-footer" aria-label="Google Calendar information">
      <h2>Delnavazan (دلنوازان)</h2>
      <p>Delnavazan is an online music academy and lesson-booking platform. Teachers and employees may optionally connect their own Google Calendar to their Delnavazan profile through the booking system. Google Calendar access is used only to check availability, prevent booking conflicts, and create, update or remove lesson events and associated Google Meet links.</p>
      <p><strong>Students do not connect their Google Calendar, and Delnavazan does not access students’ Google Calendar data.</strong></p>
      <p><a href="https://delnavazan.com/privacy-policy/">Privacy Policy</a> · <a href="https://delnavazan.com/terms/">Terms of Service</a></p>
    </section>
    <script id="dn-google-calendar-footer-move">(function(){var n=document.querySelector('.dn-google-calendar-footer'),f=document.querySelector('footer.site-footer,footer');if(n&&f)f.appendChild(n);}());</script>
    <?php
}, 202 );


add_filter( 'render_block', function ( $content, $block ) {
    if ( is_admin() || ! is_front_page() ) return $content;
    if ( ( $block['blockName'] ?? '' ) === 'core/group' && ( $block['attrs']['className'] ?? '' ) === 'dn-google-calendar-notice' ) return '';
    return $content;
}, 30, 2 );


add_action( 'wp_head', function () {
    if ( ! is_front_page() ) return;
    ?>
    <style id="dn-google-calendar-footer-contrast">
    footer .dn-google-calendar-footer{margin:22px auto 0;padding:18px 20px;background:#fbf8fc;border:1px solid rgba(105,62,124,.14);border-radius:16px;color:#5c5361!important;box-shadow:0 8px 22px rgba(68,37,91,.06)}
    footer .dn-google-calendar-footer h2{color:#5b2c69!important}footer .dn-google-calendar-footer p,footer .dn-google-calendar-footer strong{color:#5c5361!important}footer .dn-google-calendar-footer a{color:#7a3d8d!important;font-weight:700}
    </style>
    <?php
}, 203 );



/*
 * Site-wide Google Calendar information, placed inside the official footer.
 * The existing legal links remain in the footer navigation.
 */
add_action( 'wp_head', function () {
    ?>
    <style id="dn-site-footer-verification-style">
    body.home footer .dn-google-calendar-footer{display:none!important}
    footer .dn-site-footer-verification{
        max-width:780px;
        margin:18px auto 0;
        padding:16px 20px 0;
        border-top:1px solid rgba(255,255,255,.16);
        color:rgba(255,255,255,.72)!important;
        font-family:Vazirmatn,Tahoma,sans-serif;
        text-align:center;
        direction:ltr;
        font-size:11.5px;
        line-height:1.75
    }
    footer .dn-site-footer-verification strong{color:rgba(255,255,255,.93)!important;font-weight:700}
    footer .dn-site-footer-verification p{margin:5px 0}
    @media(max-width:760px){
        footer .dn-site-footer-verification{margin-top:14px;padding:14px 16px 0;font-size:11px}
    }
    </style>
    <?php
}, 204 );

add_action( 'wp_footer', function () {
    ?>
    <section class="dn-site-footer-verification" aria-label="Google Calendar information">
        <p>Delnavazan is an online music academy and lesson-booking platform. Teachers and employees may optionally connect their own Google Calendar through the booking system to check availability, prevent conflicts, and create, update or remove lesson events and associated Google Meet links.</p>
        <p><strong>Students do not connect their Google Calendar, and Delnavazan does not access students’ Google Calendar data.</strong></p>
    </section>
    <script id="dn-site-footer-verification-move">(function(){var n=document.querySelector('.dn-site-footer-verification'),f=document.querySelector('footer.site-footer,footer');if(n&&f)f.appendChild(n);}());</script>
    <?php
}, 205 );


