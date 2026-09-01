<?php

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_head',
	static function (): void {
		?>
		<!-- Delnavazan Meta Pixel -->
		<script>
		(function(f,b,e,v,n,t,s){
			if (typeof f.fbq === 'function') return;
			n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};
			if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];
			t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];
			s.parentNode.insertBefore(t,s);
			n('init','888269353885959');n('track','PageView');
		})(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
		</script>
		<noscript><img height="1" width="1" style="display:none" alt="" src="https://www.facebook.com/tr?id=888269353885959&amp;ev=PageView&amp;noscript=1"></noscript>
		<?php
	},
	99
);


