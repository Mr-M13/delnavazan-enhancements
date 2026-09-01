<?php

defined( 'ABSPATH' ) || exit;

final class DLN_Attendance_Context {
	public static function extract( $data ): array {
		$normalized = self::normalize( $data );
		$flat       = array();
		self::flatten( $normalized, '', $flat );

		$context = array(
			'booking_id'     => self::find_id( $flat, array( 'customerbookingid', 'bookingid', 'booking.id', 'booking_id' ) ),
			'appointment_id' => self::find_id( $flat, array( 'appointmentid', 'appointment.id', 'reservation.id', 'appointment_id' ) ),
			'customer_id'    => self::find_id( $flat, array( 'customerid', 'customer.id', 'customer_id' ) ),
			'customer_name'  => self::find_string( $flat, array( 'customer.fullname', 'customerfullname', 'customer.name', 'bookedcustomer.name' ) ),
			'customer_email' => self::find_email( $flat, array( 'customer.email', 'customeremail', 'bookedcustomer.email' ) ),
			'employee_name'  => self::find_string( $flat, array( 'employee.fullname', 'employeefullname', 'employee.name', 'provider.name' ) ),
			'employee_email' => self::find_email( $flat, array( 'employee.email', 'employeeemail', 'provider.email' ) ),
			'employee_phone' => self::find_string( $flat, array( 'employee.phone', 'employeephone', 'provider.phone' ) ),
			'customer_timezone' => self::find_string( $flat, array( 'customer.timezone', 'customertimezone', 'customer.time_zone', 'timezone' ) ),
			'service_name'   => self::find_string( $flat, array( 'service.name', 'servicename', 'service_name' ) ),
			'service_category_id' => self::find_id( $flat, array( 'service.categoryid', 'service.category.id', 'servicecategoryid', 'categoryid', 'service_category_id' ) ),
			'booking_start'  => self::find_string( $flat, array( 'bookingstart', 'appointment.bookingstart', 'startdatetime', 'appointmentdate', 'booking_start' ) ),
			'booking_end'    => self::find_string( $flat, array( 'bookingend', 'appointment.bookingend', 'enddatetime', 'booking_end' ) ),
			'meet_url'       => self::find_url( $flat, array( 'googlemeeturl', 'google_meet_url', 'meeturl', 'meet_url', 'lessonspace', 'meetingurl' ) ),
		);

		if ( empty( $context['customer_name'] ) ) {
			$first = self::find_string( $flat, array( 'customer.firstname', 'customerfirstname', 'first_name' ) );
			$last  = self::find_string( $flat, array( 'customer.lastname', 'customerlastname', 'last_name' ) );
			$context['customer_name'] = trim( $first . ' ' . $last );
		}

		$context['meet_code'] = self::meet_code( (string) ( $context['meet_url'] ?? '' ) );
		return apply_filters( 'dln_attendance_extracted_context', $context, $data );
	}

	public static function meet_code( string $value ): string {
		$value = trim( $value );
		if ( preg_match( '~meet\.google\.com/([a-z]{3}-[a-z]{4}-[a-z]{3}|[A-Za-z0-9_-]{8,20})~i', $value, $match ) ) {
			return sanitize_text_field( $match[1] );
		}
		if ( preg_match( '/^[A-Za-z0-9_-]{8,20}$/', $value ) ) {
			return sanitize_text_field( $value );
		}
		return '';
	}

	private static function normalize( $value ) {
		if ( is_object( $value ) ) {
			$value = get_object_vars( $value );
		}
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				$value[ $key ] = self::normalize( $item );
			}
		}
		return $value;
	}

	private static function flatten( $value, string $path, array &$flat ): void {
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				$next = '' === $path ? (string) $key : $path . '.' . $key;
				self::flatten( $item, $next, $flat );
			}
			return;
		}
		if ( is_scalar( $value ) || null === $value ) {
			$flat[ strtolower( preg_replace( '/[^a-zA-Z0-9_.]/', '', $path ) ) ] = $value;
		}
	}

	private static function find_id( array $flat, array $keys ): int {
		$value = self::find( $flat, $keys );
		return absint( $value );
	}

	private static function find_string( array $flat, array $keys ): string {
		$value = self::find( $flat, $keys );
		return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
	}

	private static function find_email( array $flat, array $keys ): string {
		return sanitize_email( self::find_string( $flat, $keys ) );
	}

	private static function find_url( array $flat, array $keys ): string {
		$value = self::find_string( $flat, $keys );
		return esc_url_raw( $value );
	}

	private static function find( array $flat, array $keys ) {
		foreach ( $keys as $wanted ) {
			$wanted = strtolower( preg_replace( '/[^a-zA-Z0-9_.]/', '', $wanted ) );
			foreach ( $flat as $path => $value ) {
				if ( $path === $wanted || str_ends_with( $path, '.' . $wanted ) || str_ends_with( str_replace( '.', '', $path ), str_replace( '.', '', $wanted ) ) ) {
					if ( null !== $value && '' !== $value ) {
						return $value;
					}
				}
			}
		}
		return null;
	}
}

