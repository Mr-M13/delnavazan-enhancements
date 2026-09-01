<?php

defined( 'ABSPATH' ) || exit;

function dlnv_enhancements_password_reset_email( array $defaults, string $key, string $user_login, WP_User $user_data ): array {
	$reset_url = network_site_url(
		'wp-login.php?action=rp&key=' . rawurlencode( $key ) . '&login=' . rawurlencode( $user_login ),
		'login'
	);

	$defaults['subject'] = 'بازیابی رمز عبور | دلنوازان';
	$defaults['headers'] = array( 'Content-Type: text/html; charset=UTF-8' );
	$defaults['message'] = '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>'
		. '<body style="margin:0;padding:0;background:#f5f5f5;font-family:Tahoma,Arial,sans-serif;direction:rtl;text-align:right">'
		. '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f5f5f5;padding:30px 15px"><tr><td align="center">'
		. '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;background:#fff;border-radius:10px;overflow:hidden">'
		. '<tr><td style="padding:30px 35px 15px;text-align:center"><div style="font-size:30px;font-weight:bold;color:#333">دلنوازان</div><div style="font-size:13px;color:#888;margin-top:6px">آموزش آنلاین موسیقی ایرانی</div></td></tr>'
		. '<tr><td style="padding:20px 40px 35px;color:#333;font-size:16px;line-height:2"><p style="margin-top:0">سلام،</p><p>درخواستی برای تغییر رمز عبور حساب شما در دلنوازان دریافت کرده‌ایم.</p><p>برای انتخاب رمز عبور جدید، روی دکمه زیر کلیک کنید:</p>'
		. '<div style="text-align:center;margin:30px 0"><a href="' . esc_url( $reset_url ) . '" style="background:#333;color:#fff;text-decoration:none;padding:13px 30px;border-radius:6px;display:inline-block;font-size:16px;font-weight:bold">تغییر رمز عبور</a></div>'
		. '<p style="font-size:14px;color:#666">اگر شما درخواست تغییر رمز عبور را ثبت نکرده‌اید، نیازی به انجام کاری نیست و می‌توانید این ایمیل را نادیده بگیرید.</p><p style="margin-bottom:0">با احترام<br><strong>دلنوازان</strong></p></td></tr>'
		. '<tr><td style="border-top:1px solid #eee;padding:20px 30px;text-align:center;font-size:12px;color:#999;direction:ltr">https://delnavazan.com/</td></tr>'
		. '</table></td></tr></table></body></html>';

	return $defaults;
}
add_filter( 'retrieve_password_notification_email', 'dlnv_enhancements_password_reset_email', 11, 4 );


