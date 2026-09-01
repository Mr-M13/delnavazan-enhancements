<?php

defined( 'ABSPATH' ) || exit;

final class DLN_Attendance_Google {
	private const ACCOUNTS_OPTION = 'dln_attendance_google_accounts';
	private const EMPLOYEE_DIAGNOSTIC_OPTION = 'dln_attendance_employee_google_diagnostic';
	private const TOKEN_ENDPOINT  = 'https://oauth2.googleapis.com/token';
	private const API_BASE        = 'https://meet.googleapis.com/v2/';
	private const MEET_SCOPE      = 'https://www.googleapis.com/auth/meetings.space.readonly';

	public static function meet_scope(): string {
		return self::MEET_SCOPE;
	}

	public static function redirect_uri(): string {
		return admin_url( 'admin-post.php?action=dln_attendance_google_callback' );
	}

	public static function authorization_url(): string {
		$settings = DLN_Attendance_Plugin::settings();
		$state    = wp_generate_password( 32, false, false );
		set_transient( 'dln_attendance_google_state_' . get_current_user_id(), $state, 15 * MINUTE_IN_SECONDS );
		return self::build_authorization_url( $state, $settings );
	}

	private static function build_authorization_url( string $state, array $settings ): string {
		return add_query_arg(
			array(
				'client_id'     => (string) ( $settings['google_client_id'] ?? '' ),
				'redirect_uri'  => self::redirect_uri(),
				'response_type' => 'code',
				'scope'         => 'openid email profile ' . self::MEET_SCOPE,
				'access_type'   => 'offline',
				'prompt'        => 'consent select_account',
				'include_granted_scopes' => 'true',
				'state'         => $state,
			),
			'https://accounts.google.com/o/oauth2/v2/auth'
		);
	}

	public static function handle_callback(): void {
		$state    = sanitize_text_field( wp_unslash( $_GET['state'] ?? '' ) );
		$expected = current_user_can( 'manage_options' ) ? get_transient( 'dln_attendance_google_state_' . get_current_user_id() ) : false;
		$employee_key  = 'dln_attendance_employee_google_state_' . hash( 'sha256', $state );
		$employee_flow = get_transient( $employee_key );
		if ( current_user_can( 'manage_options' ) ) {
			delete_transient( 'dln_attendance_google_state_' . get_current_user_id() );
		}
		delete_transient( $employee_key );
		$is_admin_flow = $expected && hash_equals( (string) $expected, $state );
		if ( ! $is_admin_flow && ! is_array( $employee_flow ) ) {
			self::redirect_notice( 'Google authorization failed: invalid state.', 'error' );
		}
		if ( isset( $_GET['error'] ) ) {
			self::redirect_notice( 'Google authorization was not completed.', 'error' );
		}

		$code     = sanitize_text_field( wp_unslash( $_GET['code'] ?? '' ) );
		$settings = DLN_Attendance_Plugin::settings();
		$response = wp_remote_post(
			self::TOKEN_ENDPOINT,
			array(
				'timeout' => 20,
				'body'    => array(
					'code'          => $code,
					'client_id'     => (string) ( $settings['google_client_id'] ?? '' ),
					'client_secret' => (string) ( $settings['google_client_secret'] ?? '' ),
					'redirect_uri'  => self::redirect_uri(),
					'grant_type'    => 'authorization_code',
				),
			)
		);
		$token = self::json_response( $response );
		if ( is_wp_error( $token ) || empty( $token['access_token'] ) ) {
			self::redirect_notice( 'Google did not return a usable access token.', 'error' );
		}

		$profile_response = wp_remote_get(
			'https://openidconnect.googleapis.com/v1/userinfo',
			array( 'headers' => array( 'Authorization' => 'Bearer ' . $token['access_token'] ) )
		);
		$profile = self::json_response( $profile_response );
		$email   = is_wp_error( $profile ) ? '' : sanitize_email( (string) ( $profile['email'] ?? '' ) );
		if ( ! $email ) {
			self::redirect_notice( 'The connected Google account did not provide an email address.', 'error' );
		}
		$employee_id = 0;
		if ( is_array( $employee_flow ) ) {
			$employee_id = self::employee_id_by_email( $email );
			$expected_id    = absint( $employee_flow['employee_id'] ?? 0 );
			$expected_email = sanitize_email( (string) ( $employee_flow['employee_email'] ?? '' ) );
			if ( ! $employee_id || ! $expected_id || $employee_id !== $expected_id || ! $expected_email || ! hash_equals( $expected_email, $email ) ) {
				self::employee_redirect( $employee_flow, 'The Google email does not match an active Amelia teacher account.', 'error' );
			}
		}

		$accounts            = self::accounts();
		$existing            = $accounts[ $email ] ?? array();
		$accounts[ $email ]  = array(
			'email'         => $email,
			'name'          => sanitize_text_field( (string) ( $profile['name'] ?? $email ) ),
			'access_token'  => (string) $token['access_token'],
			'refresh_token' => (string) ( $token['refresh_token'] ?? ( $existing['refresh_token'] ?? '' ) ),
			'expires_at'    => time() + absint( $token['expires_in'] ?? 3600 ) - 60,
			'connected_at'  => time(),
			'source'        => 'standalone',
			'amelia_employee_id' => $employee_id,
		);
		self::save_accounts( $accounts );
		if ( is_array( $employee_flow ) ) {
			self::employee_redirect( $employee_flow, 'Google Meet attendance access connected successfully.', 'success' );
		}
		self::redirect_notice( sprintf( 'Google account %s connected.', $email ), 'success' );
	}

	public static function employee_start(): void {
		$settings = DLN_Attendance_Plugin::settings();
		if ( empty( $settings['google_client_id'] ) || empty( $settings['google_client_secret'] ) ) {
			wp_die( esc_html__( 'The attendance Google connection is not configured.', 'delnavazan-attendance' ), '', array( 'response' => 503 ) );
		}
		$grant = sanitize_text_field( wp_unslash( (string) ( $_GET['grant'] ?? '' ) ) );
		$grant_key = 'dln_attendance_employee_google_grant_' . hash( 'sha256', $grant );
		$verified = $grant ? get_transient( $grant_key ) : false;
		if ( ! is_array( $verified ) || empty( $verified['employee_id'] ) || empty( $verified['employee_email'] ) ) {
			wp_die( esc_html__( 'Please sign in to the teacher account on the Delnavazan site before connecting attendance.', 'delnavazan-attendance' ), '', array( 'response' => 403 ) );
		}
		delete_transient( $grant_key );
		$state  = wp_generate_password( 40, false, false );
		$return = wp_validate_redirect( (string) ( $verified['return_url'] ?? '' ), home_url( '/' ) );
		set_transient(
			'dln_attendance_employee_google_state_' . hash( 'sha256', $state ),
			array(
				'return_url'    => $return,
				'employee_id'   => absint( $verified['employee_id'] ),
				'employee_email'=> sanitize_email( (string) $verified['employee_email'] ),
			),
			15 * MINUTE_IN_SECONDS
		);
		wp_redirect( self::build_authorization_url( $state, $settings ), 302, 'Delnavazan Attendance' ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit;
	}

	/** Installs Amelia's supported login callback before the employee panel boots. */
	public static function employee_login_capture(): void {
		if ( is_admin() || ! is_singular() ) {
			return;
		}
		$post = get_post();
		if ( ! $post || ! has_shortcode( (string) $post->post_content, 'dln_attendance_google_connect' ) ) {
			return;
		}
		?>
		<script id="dln-attendance-amelia-login-capture">
		(function () {
			window.ameliaBooking = window.ameliaBooking || {};
			window.ameliaBooking.cabinet = window.ameliaBooking.cabinet || {};
			var cabinet = window.ameliaBooking.cabinet;
			if (cabinet.__dlnAttendanceGoogleHook) {
				return;
			}
			var previous = typeof cabinet.userLoggedIn === 'function' ? cabinet.userLoggedIn : null;
			var previousLogout = typeof cabinet.userLoggedOut === 'function' ? cabinet.userLoggedOut : null;
			cabinet.userLoggedIn = function (user) {
				if (previous) {
					try { previous.apply(this, arguments); } catch (ignore) {}
				}
				window.__dlnAttendanceVerifiedEmployee = user;
				try {
					document.dispatchEvent(new CustomEvent('dln-attendance-amelia-login', {detail: user}));
				} catch (ignore) {}
			};
			cabinet.userLoggedOut = function () {
				if (previousLogout) {
					try { previousLogout.apply(this, arguments); } catch (ignore) {}
				}
				window.__dlnAttendanceVerifiedEmployee = null;
				try {
					document.dispatchEvent(new CustomEvent('dln-attendance-amelia-login', {detail: null}));
				} catch (ignore) {}
			};
			cabinet.__dlnAttendanceGoogleHook = true;
		}());
		</script>
		<?php
	}

	public static function employee_card(): string {
		$settings = DLN_Attendance_Plugin::settings();
		if ( empty( $settings['google_client_id'] ) || empty( $settings['google_client_secret'] ) ) {
			return '';
		}
		$ajax = admin_url( 'admin-ajax.php' );
		$nonce = wp_create_nonce( 'dln_attendance_employee_connection_status' );
		$id   = 'dln-attendance-google-' . wp_generate_uuid4();
		$wordpress_login = is_user_logged_in();
		return '<div id="' . esc_attr( $id ) . '" class="dln-attendance-google-slot" dir="rtl" hidden></div>'
			. '<style>.dln-attendance-google-slot{box-sizing:border-box;font-family:"Amelia Roboto",Roboto,Arial,sans-serif;max-width:760px;margin:18px auto;padding:20px 22px;background:#fff;border:1px solid #e2e6ec;border-radius:8px;box-shadow:0 2px 8px rgba(26,45,71,.06);color:#354052}.dln-attendance-google-slot h3{font-size:17px;line-height:1.5;margin:0 0 8px;color:#354052}.dln-attendance-google-slot p{font-size:14px;line-height:1.9;margin:0 0 15px}.dln-attendance-google-slot a{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:0 18px;border-radius:6px;background:#1a84ee;color:#fff!important;font-size:14px;font-weight:500;text-decoration:none!important;transition:background .2s ease}.dln-attendance-google-slot a:hover{background:#0f6fcf}.dln-attendance-google-slot .dln-attendance-note{color:#697483;font-size:12px;margin-bottom:0}@media(max-width:600px){.dln-attendance-google-slot{margin:14px 10px;padding:17px}.dln-attendance-google-slot a{box-sizing:border-box;width:100%;text-align:center}}</style>'
			. '<script>(function(){var slot=document.getElementById(' . wp_json_encode( $id ) . ');if(!slot){return;}var ajax=' . wp_json_encode( $ajax ) . ';var nonce=' . wp_json_encode( $nonce ) . ';var wordpressLogin=' . ( $wordpress_login ? 'true' : 'false' ) . ';var employee={id:0,email:""},last="",busy=false;function token(){var value="";try{value=localStorage.getItem("vue-authenticate.vueauth_token")||"";try{var parsed=JSON.parse(value);if(typeof parsed==="string"){value=parsed;}else if(parsed&&typeof parsed==="object"){value=parsed.token||parsed.access_token||parsed.value||"";}}catch(ignore){}value=String(value).replace(/^Bearer\s+/i,"").trim();}catch(ignore){value="";}return value;}function accept(user){if(!user||user.type!=="provider"){employee={id:0,email:""};retry();return;}var id=parseInt(user.id,10)||0;var email=String(user.email||"").trim();if(!id||!email){employee={id:0,email:""};retry();return;}employee={id:id,email:email};retry();}function hook(){var cabinet=window.ameliaBooking&&window.ameliaBooking.cabinet;if(!cabinet){return;}if(!cabinet.__dlnAttendanceGoogleHook){var previous=typeof cabinet.userLoggedIn==="function"?cabinet.userLoggedIn:null;var previousLogout=typeof cabinet.userLoggedOut==="function"?cabinet.userLoggedOut:null;cabinet.userLoggedIn=function(user){if(previous){try{previous.apply(this,arguments);}catch(ignore){}}window.__dlnAttendanceVerifiedEmployee=user;try{document.dispatchEvent(new CustomEvent("dln-attendance-amelia-login",{detail:user}));}catch(ignore){}};cabinet.userLoggedOut=function(){if(previousLogout){try{previousLogout.apply(this,arguments);}catch(ignore){}}window.__dlnAttendanceVerifiedEmployee=null;try{document.dispatchEvent(new CustomEvent("dln-attendance-amelia-login",{detail:null}));}catch(ignore){}};cabinet.__dlnAttendanceGoogleHook=true;}if(window.__dlnAttendanceVerifiedEmployee){accept(window.__dlnAttendanceVerifiedEmployee);}}function clear(){slot.hidden=true;slot.innerHTML="";}function check(){var value=token();var callbackLogin=employee.id&&employee.email;if(!value&&!wordpressLogin&&!callbackLogin){last="";clear();return;}var identity=value||(callbackLogin?"amelia:"+employee.id+":"+employee.email:"wordpress");if(busy||identity===last){return;}busy=true;last=identity;var data=new FormData();data.append("action","dln_attendance_employee_connection_status");data.append("_ajax_nonce",nonce);data.append("amelia_token",value);if(callbackLogin){data.append("amelia_employee_id",String(employee.id));data.append("amelia_employee_email",employee.email);}fetch(ajax,{method:"POST",credentials:"same-origin",cache:"no-store",body:data}).then(function(response){return response.json().then(function(result){return{status:response.status,result:result};});}).then(function(packet){var result=packet.result;if(!result||!result.success||!result.data){last="";if(packet.status===401||packet.status===403){clear();}return;}if(result.data.connected){clear();return;}if(typeof result.data.connect_url!=="string"||!result.data.connect_url){last="";return;}slot.innerHTML="<h3>اتصال حضور و غیاب کلاس‌ها</h3><p>برای بررسی مدت حضور در کلاس، دسترسی فقط‌خواندنی گوگل میت را یک‌بار به حساب مدرس متصل کنید.</p><p><a href=\""+result.data.connect_url.replace(/\"/g,"&quot;")+"\">اتصال حضور و غیاب کلاس‌ها</a></p><p class=\"dln-attendance-note\">این بخش فقط پس از ورود امن مدرس در سایت دلنوازان نمایش داده می‌شود.</p>";slot.hidden=false;}).catch(function(){last="";}).finally(function(){busy=false;});}function retry(){last="";window.setTimeout(check,150);}document.addEventListener("dln-attendance-amelia-login",function(event){accept(event.detail);});hook();check();window.setInterval(function(){hook();check();},2000);window.addEventListener("focus",retry);window.addEventListener("pageshow",retry);window.addEventListener("storage",retry);document.addEventListener("visibilitychange",function(){if(!document.hidden){retry();}});})();</script>';
	}

	/** Verifies the active site teacher session before exposing an OAuth grant. */
	public static function employee_connection_status(): void {
		check_ajax_referer( 'dln_attendance_employee_connection_status' );
		$token = sanitize_text_field( wp_unslash( (string) ( $_POST['amelia_token'] ?? '' ) ) );
		$has_amelia_token = strlen( $token ) >= 20 && strlen( $token ) <= 4096;
		$callback_id = absint( $_POST['amelia_employee_id'] ?? 0 );
		$callback_email = sanitize_email( wp_unslash( (string) ( $_POST['amelia_employee_email'] ?? '' ) ) );
		$has_amelia_callback = $callback_id && $callback_email;
		if ( ( $has_amelia_token || ( $has_amelia_callback && ! current_user_can( 'manage_options' ) ) ) && self::employee_status_rate_limited() ) {
			wp_send_json_error( array( 'message' => 'rate_limited' ), 429 );
		}
		if ( $has_amelia_token ) {
			// The teacher currently signed into Amelia is authoritative for the
			// Employee Panel, even when an administrator is also signed into WordPress.
			$response = wp_remote_get(
				admin_url( 'admin-ajax.php?action=wpamelia_api&call=/users/current' ),
				array(
					'timeout'     => 5,
					'redirection' => 0,
					'headers'     => array( 'Authorization' => 'Bearer ' . $token ),
				)
			);
			$data = self::json_response( $response );
			$user = ! is_wp_error( $data ) && is_array( $data['data']['user'] ?? null ) ? $data['data']['user'] : array();
			$id   = absint( $user['id'] ?? 0 );
			$email= sanitize_email( (string) ( $user['email'] ?? '' ) );
		} elseif ( $has_amelia_callback ) {
			// Amelia emits this callback only after its employee authentication
			// succeeds. The provider table is checked below, and OAuth completion
			// still requires the Google account to match this exact provider email.
			$id = $callback_id;
			$email = $callback_email;
		} else {
			$wordpress_employee = self::wordpress_employee_identity();
			if ( ! $wordpress_employee ) {
				if ( is_user_logged_in() ) {
					self::set_employee_diagnostic( 'invalid_wordpress_teacher', 0, 'The signed-in WordPress account does not match an Amelia provider email.' );
				}
				wp_send_json_error( array( 'message' => 'invalid_session' ), 401 );
			}
			$id = absint( $wordpress_employee['id'] );
			$email = sanitize_email( (string) $wordpress_employee['email'] );
		}
		if ( ! $id || ! $email || ! self::verified_employee( $id, $email ) ) {
			if ( is_user_logged_in() ) {
				self::set_employee_diagnostic( 'invalid_teacher_session', $id, 'Amelia current-user validation did not resolve to a matching provider record.' );
			}
			wp_send_json_error( array( 'message' => 'invalid_teacher_session' ), 403 );
		}
		if ( self::employee_account_connected( $id ) ) {
			self::set_employee_diagnostic( 'already_connected', $id, 'This verified teacher already has a renewable attendance connection.' );
			wp_send_json_success( array( 'connected' => true ) );
		}
		$grant = wp_generate_password( 48, false, false );
		$return = wp_validate_redirect( wp_get_referer(), home_url( '/' ) );
		set_transient(
			'dln_attendance_employee_google_grant_' . hash( 'sha256', $grant ),
			array( 'employee_id' => $id, 'employee_email' => $email, 'return_url' => $return ),
			5 * MINUTE_IN_SECONDS
		);
		self::set_employee_diagnostic( 'grant_ready', $id, 'Verified teacher session accepted; a short-lived connection grant was issued.' );
		wp_send_json_success(
			array(
				'connected'   => false,
				'connect_url' => add_query_arg( array( 'action' => 'dln_attendance_employee_google_start', 'grant' => $grant ), admin_url( 'admin-post.php' ) ),
			)
		);
	}

	/** Bounds expensive anonymous loopback validation to one attempt per IP every three seconds. */
	private static function employee_status_rate_limited(): bool {
		$ip = sanitize_text_field( (string) ( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ) );
		$key = 'dln_attendance_employee_rl_' . substr( hash( 'sha256', $ip ), 0, 32 );
		$now = time();
		$until = absint( get_option( $key, 0 ) );
		if ( $until > $now ) {
			return true;
		}
		if ( $until ) {
			delete_option( $key );
		}
		return ! add_option( $key, $now + 3, '', 'no' );
	}

	public static function employee_diagnostic(): array {
		$value = get_option( self::EMPLOYEE_DIAGNOSTIC_OPTION, array() );
		return is_array( $value ) ? $value : array();
	}

	private static function set_employee_diagnostic( string $state, int $employee_id, string $message ): void {
		update_option(
			self::EMPLOYEE_DIAGNOSTIC_OPTION,
			array(
				'state' => sanitize_key( $state ),
				'employee_id' => absint( $employee_id ),
				'message' => sanitize_text_field( $message ),
				'updated_at' => time(),
			),
			false
		);
	}

	public static function disconnect( string $email ): bool {
		$accounts = self::accounts();
		if ( ! isset( $accounts[ $email ] ) ) {
			return false;
		}
		unset( $accounts[ $email ] );
		self::save_accounts( $accounts );
		return true;
	}

	public static function accounts(): array {
		$accounts = get_option( self::ACCOUNTS_OPTION, array() );
		if ( ! is_array( $accounts ) ) {
			return array();
		}
		foreach ( $accounts as &$account ) {
			if ( is_array( $account ) ) {
				$account['access_token'] = DLN_Attendance_Secrets::unseal( (string) ( $account['access_token'] ?? '' ) );
				$account['refresh_token'] = DLN_Attendance_Secrets::unseal( (string) ( $account['refresh_token'] ?? '' ) );
			}
		}
		unset( $account );
		return $accounts;
	}

	public static function account_count(): int {
		$accounts = get_option( self::ACCOUNTS_OPTION, array() );
		return is_array( $accounts ) ? count( $accounts ) : 0;
	}

	private static function save_accounts( array $accounts ): void {
		foreach ( $accounts as &$account ) {
			if ( is_array( $account ) ) {
				$account['access_token'] = DLN_Attendance_Secrets::seal( (string) ( $account['access_token'] ?? '' ) );
				$account['refresh_token'] = DLN_Attendance_Secrets::seal( (string) ( $account['refresh_token'] ?? '' ) );
			}
		}
		unset( $account );
		update_option( self::ACCOUNTS_OPTION, $accounts, false );
	}

	public static function store_amelia_credentials( string $employee_email, array $credentials, int $employee_id ) {
		$employee_email = sanitize_email( $employee_email );
		$access_token = sanitize_text_field( (string) ( $credentials['access_token'] ?? '' ) );
		if ( ! $employee_email || ! $access_token ) {
			return new WP_Error( 'invalid_amelia_token', 'Amelia did not provide a usable Google access token.' );
		}

		$token_info = self::json_response(
			wp_remote_get(
				add_query_arg( 'access_token', $access_token, 'https://oauth2.googleapis.com/tokeninfo' ),
				array( 'timeout' => 15 )
			)
		);
		if ( is_wp_error( $token_info ) ) {
			return new WP_Error( 'token_validation_failed', 'Google could not validate the token supplied through Amelia.' );
		}
		$scopes = preg_split( '/\s+/', trim( (string) ( $token_info['scope'] ?? '' ) ) ) ?: array();
		if ( ! in_array( self::MEET_SCOPE, $scopes, true ) ) {
			return new WP_Error( 'meet_scope_missing', 'The Amelia Google token does not include the Meet attendance scope.' );
		}
		$token_email = sanitize_email( (string) ( $token_info['email'] ?? '' ) );
		if ( ! $token_email ) {
			return new WP_Error( 'google_email_missing', 'Google did not identify the account authorised through Amelia.' );
		}
		if ( ! hash_equals( strtolower( $employee_email ), strtolower( $token_email ) ) ) {
			return new WP_Error( 'google_email_mismatch', 'The Google account email must exactly match the verified Amelia teacher email.' );
		}

		$accounts      = self::accounts();
		$existing      = $accounts[ $token_email ] ?? array();
		$refresh_token = sanitize_text_field( (string) ( $credentials['refresh_token'] ?? ( $existing['refresh_token'] ?? '' ) ) );
		if ( ! $refresh_token ) {
			return new WP_Error( 'refresh_token_not_exposed', 'Amelia exposed the granted scope, but not a renewable refresh token.' );
		}

		$accounts[ $token_email ] = array(
			'email'                 => $token_email,
			'name'                  => $token_email,
			'access_token'       => $access_token,
			'refresh_token'      => $refresh_token,
			'expires_at'         => time() + max( 60, absint( $token_info['expires_in'] ?? $credentials['expires_in'] ?? 3600 ) ) - 60,
			'connected_at'       => time(),
			'source'             => 'amelia',
			'oauth_client_id'    => sanitize_text_field( (string) ( DLN_Attendance_Plugin::settings()['amelia_google_client_id'] ?? '' ) ),
			'amelia_employee_id' => absint( $employee_id ),
			'amelia_employee_email' => $employee_email,
		);
		self::save_accounts( $accounts );
		return true;
	}

	public static function reconcile_pending( int $limit = 20 ): array {
		$sync   = DLN_Attendance_WhatsApp::sync_attendance_records();
		$result = array( 'checked' => 0, 'attended' => 0, 'review' => 0, 'errors' => array(), 'synced' => absint( $sync['synced'] ?? 0 ) );
		if ( ! empty( $sync['error'] ) ) {
			$result['errors'][] = (string) $sync['error'];
		}
		foreach ( DLN_Attendance_Database::records_for_reconciliation( $limit ) as $record ) {
			++$result['checked'];
			$outcome = self::reconcile_record( $record );
			if ( is_wp_error( $outcome ) ) {
				$result['errors'][] = $outcome->get_error_message();
			} elseif ( 'attended' === $outcome ) {
				++$result['attended'];
			} elseif ( 'review_needed' === $outcome ) {
				++$result['review'];
			}
		}
		return $result;
	}

	public static function reconcile_record( array $record ) {
		if ( empty( $record['meet_code'] ) ) {
			return new WP_Error( 'no_meet_code', 'Booking ' . absint( $record['booking_id'] ) . ' has no Google Meet code.' );
		}
		if ( ! self::accounts() ) {
			return new WP_Error( 'no_google_account', 'No Google organizer account is connected.' );
		}

		$conference = self::find_conference( (string) $record['meet_code'], (string) ( $record['booking_start'] ?? '' ) );
		if ( is_wp_error( $conference ) ) {
			return $conference;
		}
		if ( ! $conference ) {
			return new WP_Error( 'conference_not_found', 'No accessible conference record found for Meet code ' . $record['meet_code'] . '.' );
		}

		$participants = self::paged_get( $conference['account'], $conference['name'] . '/participants', 'participants' );
		if ( is_wp_error( $participants ) ) {
			return $participants;
		}
		$wanted_student = self::normalize_name( (string) $record['customer_name'] );
		$wanted_teacher = self::normalize_name( (string) $record['employee_name'] );
		$students       = array();
		$teachers       = array();
		foreach ( $participants as $participant ) {
			$name = self::participant_name( $participant );
			$normalized = self::normalize_name( $name );
			if ( ! $normalized || empty( $participant['name'] ) ) {
				continue;
			}
			if ( $wanted_student && $normalized === $wanted_student ) {
				$students[] = self::participant_evidence( $conference['account'], $participant, $name );
			}
			if ( $wanted_teacher && $normalized === $wanted_teacher ) {
				$teachers[] = self::participant_evidence( $conference['account'], $participant, $name );
			}
		}

		$settings = DLN_Attendance_Plugin::settings();
		$minimum  = max( 1, absint( $settings['minimum_attendance_minutes'] ?? 20 ) ) * MINUTE_IN_SECONDS;
		$best     = null;
		foreach ( $students as $student ) {
			foreach ( $teachers as $teacher ) {
				if ( $student['resource'] === $teacher['resource'] ) {
					continue;
				}
				$overlap = self::overlap_duration( $student['intervals'], $teacher['intervals'] );
				if ( null === $best || $overlap > $best['overlap'] ) {
					$best = array( 'student' => $student, 'teacher' => $teacher, 'overlap' => $overlap );
				}
			}
		}
		if ( $best && $best['overlap'] >= $minimum ) {
				DLN_Attendance_Database::update_status(
					(int) $record['id'],
					'attended',
					array(
						'attended_at'               => current_time( 'mysql', true ),
						'meet_participant_name'     => sanitize_text_field( $best['student']['name'] ),
						'meet_duration_seconds'     => absint( $best['student']['duration'] ),
						'student_participant_name'  => sanitize_text_field( $best['student']['name'] ),
						'student_duration_seconds'  => absint( $best['student']['duration'] ),
						'teacher_participant_name'  => sanitize_text_field( $best['teacher']['name'] ),
						'teacher_duration_seconds'  => absint( $best['teacher']['duration'] ),
						'overlap_seconds'           => absint( $best['overlap'] ),
						'verification_note'         => 'Verified: assigned teacher and booked student overlapped in Google Meet for at least ' . absint( $settings['minimum_attendance_minutes'] ?? 20 ) . ' minutes.',
					)
				);
				do_action( 'dln_attendance_status_changed', $record, 'attended' );
				return 'attended';
		}

		if ( ! $teachers && ! $students ) {
			$note = 'Anomaly: neither the assigned teacher nor the booked student could be matched by Google Meet display name.';
		} elseif ( ! $teachers ) {
			$note = 'Anomaly: the assigned teacher could not be matched by Google Meet display name.';
		} elseif ( ! $students ) {
			$note = 'Anomaly: the booked student could not be matched by Google Meet display name.';
		} else {
			$note = 'Anomaly: teacher and student overlap was shorter than ' . absint( $settings['minimum_attendance_minutes'] ?? 20 ) . ' minutes.';
		}
		$extra = array( 'verification_note' => $note );
		if ( $best ) {
			$extra['student_participant_name'] = sanitize_text_field( $best['student']['name'] );
			$extra['student_duration_seconds'] = absint( $best['student']['duration'] );
			$extra['teacher_participant_name'] = sanitize_text_field( $best['teacher']['name'] );
			$extra['teacher_duration_seconds'] = absint( $best['teacher']['duration'] );
			$extra['overlap_seconds'] = absint( $best['overlap'] );
		}
		DLN_Attendance_Database::update_status( (int) $record['id'], 'review_needed', $extra );
		return 'review_needed';
	}

	private static function find_conference( string $meeting_code, string $booking_start ) {
		$last_error = null;
		$candidates = array();
		$target     = strtotime( $booking_start . ' UTC' ) ?: 0;
		foreach ( array_keys( self::accounts() ) as $email ) {
			$page_token = '';
			$page_count = 0;
			do {
				$query = 'conferenceRecords?filter=' . rawurlencode( 'space.meeting_code="' . $meeting_code . '"' ) . '&pageSize=100';
				if ( $page_token ) {
					$query .= '&pageToken=' . rawurlencode( $page_token );
				}
				$data = self::api_get( $email, $query );
				if ( is_wp_error( $data ) ) {
					$last_error = $data;
					break;
				}
				foreach ( (array) ( $data['conferenceRecords'] ?? array() ) as $record ) {
					if ( empty( $record['name'] ) ) {
						continue;
					}
					$start        = strtotime( (string) ( $record['startTime'] ?? '' ) ) ?: 0;
					$candidates[] = array(
						'name'     => (string) $record['name'],
						'account'  => $email,
						'distance' => $target && $start ? abs( $target - $start ) : PHP_INT_MAX,
					);
				}
				$page_token = sanitize_text_field( (string) ( $data['nextPageToken'] ?? '' ) );
				++$page_count;
			} while ( $page_token && $page_count < 10 );
		}
		if ( $candidates ) {
			usort( $candidates, static fn( $a, $b ) => $a['distance'] <=> $b['distance'] );
			return $candidates[0];
		}
		return $last_error ?: null;
	}

	private static function participant_evidence( string $email, array $participant, string $name ): array {
		$intervals = self::participant_intervals( $email, (string) $participant['name'] );
		return array(
			'resource'  => (string) $participant['name'],
			'name'      => $name,
			'intervals' => $intervals,
			'duration'  => self::interval_duration( $intervals ),
		);
	}

	private static function participant_intervals( string $email, string $participant_name ): array {
		$sessions = self::paged_get( $email, $participant_name . '/participantSessions', 'participantSessions' );
		if ( is_wp_error( $sessions ) ) {
			return array();
		}
		$intervals = array();
		foreach ( $sessions as $session ) {
			$start = strtotime( (string) ( $session['startTime'] ?? '' ) );
			$end   = strtotime( (string) ( $session['endTime'] ?? '' ) );
			if ( $start && $end && $end > $start ) {
				$intervals[] = array( $start, $end );
			}
		}
		return self::merge_intervals( $intervals );
	}

	private static function merge_intervals( array $intervals ): array {
		usort( $intervals, static fn( $a, $b ) => $a[0] <=> $b[0] );
		$merged = array();
		foreach ( $intervals as $interval ) {
			if ( ! $merged || $interval[0] > $merged[ count( $merged ) - 1 ][1] ) {
				$merged[] = $interval;
			} else {
				$merged[ count( $merged ) - 1 ][1] = max( $merged[ count( $merged ) - 1 ][1], $interval[1] );
			}
		}
		return $merged;
	}

	private static function interval_duration( array $intervals ): int {
		return array_sum( array_map( static fn( $interval ) => max( 0, $interval[1] - $interval[0] ), $intervals ) );
	}

	private static function overlap_duration( array $left, array $right ): int {
		$total = 0;
		foreach ( $left as $a ) {
			foreach ( $right as $b ) {
				$total += max( 0, min( $a[1], $b[1] ) - max( $a[0], $b[0] ) );
			}
		}
		return $total;
	}

	private static function participant_name( array $participant ): string {
		foreach ( array( 'signedinUser', 'anonymousUser', 'phoneUser' ) as $kind ) {
			if ( ! empty( $participant[ $kind ]['displayName'] ) ) {
				return sanitize_text_field( (string) $participant[ $kind ]['displayName'] );
			}
		}
		return '';
	}

	private static function normalize_name( string $name ): string {
		$name = trim( preg_replace( '/\s+/u', ' ', $name ) );
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $name, 'UTF-8' ) : strtolower( $name );
	}

	private static function paged_get( string $email, string $path, string $key ) {
		$items = array();
		$token = '';
		do {
			$query = $path . '?pageSize=100';
			if ( $token ) {
				$query .= '&pageToken=' . rawurlencode( $token );
			}
			$data = self::api_get( $email, $query );
			if ( is_wp_error( $data ) ) {
				return $data;
			}
			$items = array_merge( $items, is_array( $data[ $key ] ?? null ) ? $data[ $key ] : array() );
			$token = sanitize_text_field( (string) ( $data['nextPageToken'] ?? '' ) );
		} while ( $token );
		return $items;
	}

	private static function api_get( string $email, string $path ) {
		$token = self::access_token( $email );
		if ( is_wp_error( $token ) ) {
			return $token;
		}
		return self::json_response(
			wp_remote_get(
				self::API_BASE . $path,
				array(
					'timeout' => 20,
					'headers' => array( 'Authorization' => 'Bearer ' . $token ),
				)
			)
		);
	}

	private static function access_token( string $email ) {
		$accounts = self::accounts();
		if ( empty( $accounts[ $email ] ) ) {
			return new WP_Error( 'account_missing', 'Google account is no longer connected.' );
		}
		$account = $accounts[ $email ];
		if ( ! empty( $account['access_token'] ) && absint( $account['expires_at'] ?? 0 ) > time() ) {
			return (string) $account['access_token'];
		}
		if ( empty( $account['refresh_token'] ) ) {
			return new WP_Error( 'reauthorize_google', 'Google account ' . $email . ' must be reauthorized.' );
		}
		$settings = DLN_Attendance_Plugin::settings();
		$is_amelia = 'amelia' === (string) ( $account['source'] ?? '' );
		$client_id = $is_amelia ? (string) ( $settings['amelia_google_client_id'] ?? '' ) : (string) ( $settings['google_client_id'] ?? '' );
		$client_secret = $is_amelia ? (string) ( $settings['amelia_google_client_secret'] ?? '' ) : (string) ( $settings['google_client_secret'] ?? '' );
		if ( ! $client_id || ! $client_secret ) {
			return new WP_Error( 'oauth_client_missing', 'The OAuth client credentials required to refresh Google account ' . $email . ' are missing.' );
		}
		$response = wp_remote_post(
			self::TOKEN_ENDPOINT,
			array(
				'timeout' => 20,
				'body'    => array(
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
					'refresh_token' => (string) $account['refresh_token'],
					'grant_type'    => 'refresh_token',
				),
			)
		);
		$data = self::json_response( $response );
		if ( is_wp_error( $data ) || empty( $data['access_token'] ) ) {
			$accounts[ $email ]['connection_error'] = is_wp_error( $data ) ? $data->get_error_message() : 'Google refresh token was rejected.';
			$accounts[ $email ]['connection_error_at'] = time();
			self::save_accounts( $accounts );
			return is_wp_error( $data ) ? $data : new WP_Error( 'refresh_failed', 'Google token refresh failed.' );
		}
		$accounts[ $email ]['access_token'] = (string) $data['access_token'];
		$accounts[ $email ]['expires_at']   = time() + absint( $data['expires_in'] ?? 3600 ) - 60;
		unset( $accounts[ $email ]['connection_error'], $accounts[ $email ]['connection_error_at'] );
		self::save_accounts( $accounts );
		return (string) $data['access_token'];
	}

	private static function json_response( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $code < 200 || $code >= 300 ) {
			$message = is_array( $data ) ? ( $data['error']['message'] ?? $data['error_description'] ?? 'Google API request failed.' ) : 'Google API request failed.';
			return new WP_Error( 'google_api_error', sanitize_text_field( (string) $message ), array( 'status' => $code ) );
		}
		return is_array( $data ) ? $data : array();
	}

	private static function redirect_notice( string $message, string $type ): void {
		set_transient( 'dln_attendance_notice_' . get_current_user_id(), array( $type, $message ), MINUTE_IN_SECONDS );
		wp_safe_redirect( admin_url( 'admin.php?page=dln-attendance-settings' ) );
		exit;
	}

	private static function employee_id_by_email( string $email ): int {
		global $wpdb;
		$table = $wpdb->prefix . 'amelia_users';
		return absint( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE email = %s AND type = %s LIMIT 1", $email, 'provider' ) ) );
	}

	/** Accepts a signed-in WordPress teacher only when its email is an Amelia provider. */
	private static function wordpress_employee_identity(): array {
		if ( ! is_user_logged_in() ) {
			return array();
		}
		$user = wp_get_current_user();
		$email = sanitize_email( (string) $user->user_email );
		$id = $email ? self::employee_id_by_email( $email ) : 0;
		return $id ? array( 'id' => $id, 'email' => $email ) : array();
	}

	private static function verified_employee( int $employee_id, string $email ): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'amelia_users';
		$found = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE id = %d AND email = %s AND type = %s LIMIT 1",
				$employee_id,
				$email,
				'provider'
			)
		);
		return $employee_id === absint( $found );
	}

	private static function employee_account_connected( int $employee_id ): bool {
		foreach ( self::accounts() as $account ) {
			if ( $employee_id === absint( $account['amelia_employee_id'] ?? 0 ) && ! empty( $account['refresh_token'] ) && empty( $account['connection_error'] ) ) {
				return true;
			}
		}
		return false;
	}

	private static function employee_redirect( array $flow, string $message, string $status ): void {
		$return = wp_validate_redirect( (string) ( $flow['return_url'] ?? '' ), home_url( '/' ) );
		$return = add_query_arg( array( 'attendance_google' => 'success' === $status ? 'success' : 'error' ), $return );
		wp_safe_redirect( $return );
		exit;
	}
}

