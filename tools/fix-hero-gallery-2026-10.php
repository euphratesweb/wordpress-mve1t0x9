<?php
/**
 * One-off fixes for xclusivewash.com (Oct 2026):
 *  - Service page heroes: replace the em-based background offset (which pushes the logo out of view
 *    at some text sizes) with a percentage position that keeps the logo in view at any size.
 *  - Homepage gallery: turn off the filter bar (every photo sits in one "Gallery Filter" category).
 *  - Booking page: replace the "Book-detail-package" heading text.
 *
 * Run from the web service shell:
 *   php fix-hero-gallery-2026-10.php            dry run: prints current and new values
 *   php fix-hero-gallery-2026-10.php --apply    writes changes (originals backed up first)
 *   php fix-hero-gallery-2026-10.php --restore  puts the backed-up values back
 */

$_SERVER['HTTP_HOST'] = 'xclusivewash.com';
$_SERVER['HTTPS']     = 'on';
define( 'WP_USE_THEMES', false );
require '/var/www/html/wp-load.php';

$mode = in_array( '--apply', $argv, true ) ? 'apply' : ( in_array( '--restore', $argv, true ) ? 'restore' : 'dry' );

const BACKUP_KEY = '_elementor_data_backup_fix_2026_10';

// Service page => hero container id.
$heroes = [
	2018 => 'fa56c1b', // interior & exterior
	2014 => '549400d', // ceramic coating
	2022 => '5364c9d', // paint correction
	2016 => 'f6b93fe', // engine bay
	2020 => '7f6969e', // ozone
	2249 => '00dd248', // add-ons
];
const HOME_ID    = 2509;
const BOOKING_ID = 2755;

/** Logo sits at roughly 67-89% of the banner's height; an 85% vertical position keeps it in view. */
function xw_hero_position( &$settings ) {
	$pct = function ( $n ) {
		return [ 'unit' => '%', 'size' => $n, 'sizes' => [] ];
	};
	foreach ( [ '', '_tablet', '_mobile' ] as $device ) {
		$settings[ 'background_position' . $device ] = 'initial';
		$settings[ 'background_xpos' . $device ]     = $pct( 50 );
		$settings[ 'background_ypos' . $device ]     = $pct( 85 );
	}
}

function &xw_find( &$elements, $id ) {
	foreach ( $elements as &$el ) {
		if ( ( $el['id'] ?? '' ) === $id ) {
			return $el;
		}
		if ( ! empty( $el['elements'] ) ) {
			$found = &xw_find( $el['elements'], $id );
			if ( null !== $found ) {
				return $found;
			}
		}
	}
	$none = null;
	return $none;
}

function &xw_find_widget( &$elements, $type ) {
	foreach ( $elements as &$el ) {
		if ( ( $el['widgetType'] ?? '' ) === $type ) {
			return $el;
		}
		if ( ! empty( $el['elements'] ) ) {
			$found = &xw_find_widget( $el['elements'], $type );
			if ( null !== $found ) {
				return $found;
			}
		}
	}
	$none = null;
	return $none;
}

function xw_show( $settings, $prefix ) {
	$out = [];
	foreach ( $settings as $k => $v ) {
		if ( 0 === strpos( $k, $prefix ) ) {
			$out[] = "      $k = " . ( is_array( $v ) ? wp_json_encode( $v ) : var_export( $v, true ) );
		}
	}
	return $out ? implode( "\n", $out ) : '      (none set)';
}

function xw_save( $id, $raw, $data, $mode ) {
	if ( 'apply' !== $mode ) {
		return;
	}
	if ( ! get_post_meta( $id, BACKUP_KEY, true ) ) {
		add_post_meta( $id, BACKUP_KEY, wp_slash( $raw ), true );
	}
	update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
	delete_post_meta( $id, '_elementor_element_cache' );
	delete_post_meta( $id, '_elementor_css' );
}

echo "Mode: $mode\n\n";

if ( 'restore' === $mode ) {
	foreach ( array_merge( array_keys( $heroes ), [ HOME_ID ] ) as $id ) {
		$backup = get_post_meta( $id, BACKUP_KEY, true );
		if ( $backup ) {
			update_post_meta( $id, '_elementor_data', wp_slash( $backup ) );
			delete_post_meta( $id, '_elementor_element_cache' );
			delete_post_meta( $id, '_elementor_css' );
			echo "$id: restored\n";
		}
	}
	$post = get_post( BOOKING_ID );
	if ( $post && false !== strpos( $post->post_content, 'Book Your Detail' ) ) {
		wp_update_post( [ 'ID' => BOOKING_ID, 'post_content' => str_replace( 'Book Your Detail', 'Book-detail-package', $post->post_content ) ] );
		echo BOOKING_ID . ": heading restored\n";
	}
} else {
	// 1. Hero backgrounds.
	foreach ( $heroes as $id => $hero_id ) {
		$raw  = get_post_meta( $id, '_elementor_data', true );
		$data = json_decode( $raw, true );
		$hero = &xw_find( $data, $hero_id );
		if ( null === $hero ) {
			echo "$id: hero $hero_id NOT FOUND, skipped\n";
			unset( $hero );
			continue;
		}
		echo "$id: hero $hero_id background position before:\n" . xw_show( $hero['settings'], 'background_position' ) . "\n" . xw_show( $hero['settings'], 'background_ypos' ) . "\n";
		xw_hero_position( $hero['settings'] );
		echo "    after: background_position* = 'initial', xpos 50%, ypos 85% (desktop, tablet, phone)\n\n";
		unset( $hero );
		xw_save( $id, $raw, $data, $mode );
	}

	// 2. Gallery filter bar.
	$raw     = get_post_meta( HOME_ID, '_elementor_data', true );
	$data    = json_decode( $raw, true );
	$gallery = &xw_find_widget( $data, 'eael-filterable-gallery' );
	if ( null === $gallery ) {
		echo "home: filterable gallery NOT FOUND\n";
	} else {
		$s = $gallery['settings'];
		echo "home: gallery {$gallery['id']} filter settings before:\n" . xw_show( $s, 'filter' ) . "\n" . xw_show( $s, 'eael_fg_all' ) . "\n";
		$gallery['settings']['filter_enable'] = '';
		echo "    after: filter_enable = '' (filter bar hidden)\n\n";
		xw_save( HOME_ID, $raw, $data, $mode );
	}
	unset( $gallery );

	// 3. Booking page heading.
	$post  = get_post( BOOKING_ID );
	$count = substr_count( $post->post_content, 'Book-detail-package' );
	echo BOOKING_ID . ": 'Book-detail-package' found $count time(s) in page content -> 'Book Your Detail'\n";
	if ( 'apply' === $mode && $count ) {
		wp_update_post( [ 'ID' => BOOKING_ID, 'post_content' => str_replace( 'Book-detail-package', 'Book Your Detail', $post->post_content ) ] );
	}
}

if ( 'dry' !== $mode && class_exists( '\Elementor\Plugin' ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
	echo "\nElementor cache cleared.\n";
}
