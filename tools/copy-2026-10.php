<?php
/**
 * One-off copy update for xclusivewash.com (Oct 2026 rewrite + quote-button removal).
 *
 * Run from the web service shell:
 *   php copy-2026-10.php            dry run: prints every match, changes nothing
 *   php copy-2026-10.php --apply    writes changes (original Elementor data is backed up first)
 *   php copy-2026-10.php --restore  puts the backed-up Elementor data back
 */

$_SERVER['HTTP_HOST'] = 'xclusivewash.com';
$_SERVER['HTTPS']     = 'on';
define( 'WP_USE_THEMES', false );
require '/var/www/html/wp-load.php';

$mode = in_array( '--apply', $argv, true ) ? 'apply' : ( in_array( '--restore', $argv, true ) ? 'restore' : 'dry' );

const BACKUP_KEY  = '_elementor_data_backup_copy_2026_10';
const BOOKING_URL = 'https://xclusivewash.com/book-detail-package/';

$pages = [
	'home'     => 2509,
	'about'    => 1944,
	'contact'  => 1946,
	'interior' => 2018,
	'ceramic'  => 2014,
	'paint'    => 2022,
	'engine'   => 2016,
	'ozone'    => 2020,
	'addons'   => 2249,
];

/** Plain text used for matching: no tags or entities, straight quotes, single spaces, no space before punctuation. */
function xw_norm( $s ) {
	$s = html_entity_decode( wp_strip_all_tags( (string) $s ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$s = str_replace( [ "\u{2019}", "\u{2018}", "\u{201C}", "\u{201D}", "\u{00A0}" ], [ "'", "'", '"', '"', ' ' ], $s );
	$s = preg_replace( '/\s+/u', ' ', $s );
	$s = preg_replace( '/\s+([,.;:!?])/u', '$1', $s );
	return mb_strtolower( trim( $s ) );
}

/*
 * Rules: [ pages ('*' or list of keys), match type ('exact' or 'prefix'), old text, new text, extra condition ].
 * 'exact' compares the whole field; 'prefix' compares the start of the field. Extra condition may hold
 * 'contains' or 'excludes' to tell apart fields that start the same way.
 */
$rules = [
	// Shared across pages.
	[ '*', 'exact', 'Ready to Transform Your Ride?', 'Ready to Book?' ],
	[ '*', 'prefix', 'Book your detailing appointment today', 'Pick a package and choose a time that works for you.' ],
	[ '*', 'exact', 'Book an Exclusive Detail Package', 'Book a Detail Package' ],
	[ [ 'about', 'contact' ], 'prefix', 'Our team brings top-tier expertise', 'Our team has years of experience with professional detailing techniques.' ],
	[ [ 'about', 'contact' ], 'prefix', 'Watch your vehicle come to life', 'Deep detailing and paint enhancement bring back a bold, glossy finish.' ],
	[ [ 'about', 'contact' ], 'prefix', 'Defend your investment with services designed', 'Our protection services help your car hold up against UV rays, grime, and weather.' ],

	// Home.
	[ [ 'home' ], 'prefix', 'Providing unmatched attention to detail', 'Xclusive Wash is a car detailing shop in New Castle, Delaware. We do interior and exterior detailing, paint correction, ceramic coating, and more.' ],
	[ [ 'home' ], 'exact', 'Book a Detail With Us Today!', 'Book a Detail' ],
	[ [ 'home' ], 'exact', 'We only use top-quality detailing and ceramic products.', 'We use top-quality detailing and ceramic coating products.' ],
	[ [ 'home' ], 'exact', 'Easy scheduling, quick response times.', 'Book online, and we respond quickly.' ],
	[ [ 'home' ], 'exact', "Delaware's go-to auto spa for premium detailing.", 'Drivers across Delaware bring their cars to us for detailing.' ],
	[ [ 'home' ], 'prefix', 'At Xclusive Wash, we provide more than just a simple car wash', "Xclusive Wash does professional car detailing in New Castle, DE. We clean, restore, and protect your car inside and out, from a deep interior clean to a polished exterior. Whether you need a full detail or longer-lasting paint protection, we'll get your car looking fresh again." ],
	[ [ 'home' ], 'exact', 'Choose the level of care that fits your car best', 'Pick the package that fits your car.' ],
	[ [ 'home' ], 'exact', 'Everthing in Silver plus,', 'Everything in Silver, plus' ],
	[ [ 'home' ], 'prefix', 'In addition to our xclusive detailing packages', 'Besides our packages, we offer these services on their own.' ],
	[ [ 'home' ], 'prefix', 'Deep clean for carpets, seats', 'A deep clean for carpets, seats, and every interior surface.' ],
	[ [ 'home' ], 'prefix', 'Durable protection that keeps your vehicle glossy', 'Long-lasting protection that keeps your paint glossy for years.' ],
	[ [ 'home' ], 'prefix', 'Removes swirls, scratches, and oxidation to restore', 'Removes swirls, scratches, and oxidation from your paint.' ],
	[ [ 'home' ], 'prefix', 'Thorough cleaning and protection to keep your engine', 'Cleans and protects your engine bay.' ],
	[ [ 'home' ], 'prefix', 'Eliminates smoke, pet, and food odors at the source', 'Removes smoke, pet, and food odors at the source.' ],

	// About.
	[ [ 'about' ], 'prefix', "At Xclusive Wash, we're more than just a detailing shop", 'Xclusive Wash is an auto detailing shop in New Castle, Delaware, and we take pride in every car we work on. We aim for quality work, premium products, and protection that lasts.' ],
	[ [ 'about' ], 'prefix', 'Xclusive Wash is proudly owned and operated', "Xclusive Wash is owned and run by a lifelong car enthusiast who turned that passion into a detailing business in New Castle, DE. Our team has years of hands-on experience in paint correction, ceramic coating, and interior detailing, and many of our technicians have worked with major automakers and industry leaders. We treat every car the way we'd treat our own, whether that means correcting paint defects or bringing a worn interior back to life.", [ 'contains' => 'from correcting paint imperfections' ] ],
	[ [ 'about' ], 'prefix', 'Xclusive Wash is proudly owned and operated', "Our technicians focus on the work that takes the most skill: paint correction, ceramic coating, and interior restoration. They work on your car the same way they'd work on their own.", [ 'excludes' => 'from correcting paint imperfections' ] ],
	[ [ 'about' ], 'prefix', 'At Xclusive Wash, our mission is to provide', "We want every car we work on to look better, stay protected, and last longer. That comes down to careful cleaning, polishing, and restoration, and making sure you're happy with the result." ],
	[ [ 'about' ], 'prefix', 'When you trust us with your vehicle', "Here's what you get when you bring your car to us." ],

	// Contact.
	[ [ 'contact' ], 'prefix', 'Xclusive Auto Wash is standing by', "Questions about a detail or a booking? Call, email, or send us a message below, and we'll get back to you." ],

	// Interior & exterior detailing.
	[ [ 'interior' ], 'prefix', 'Professional car detailing services that restore', 'Professional detailing that cleans, protects, and restores your car inside and out.' ],
	[ [ 'interior' ], 'exact', 'Deep Clean. Total Shine. Long-Lasting Care', 'Detailing Inside and Out' ],
	[ [ 'interior' ], 'prefix', 'At Xclusive Auto Wash, we deliver professional interior and exterior', 'Xclusive Wash offers interior and exterior detailing for drivers in New Castle, Wilmington, Newark, and nearby areas. We clean the interior thoroughly and polish the exterior so your car feels close to new again, which also helps it hold its value.' ],
	[ [ 'interior' ], 'prefix', 'Our expert team uses premium detailing products', "We use premium detailing products and proven techniques, and we adjust the work to the car, whether it's a luxury sedan, an SUV, or a daily commuter. The result looks good and stays protected." ],
	[ [ 'interior' ], 'prefix', 'Every package is designed to give your vehicle complete care', "Here's what's included when you book interior and exterior detailing." ],
	[ [ 'interior' ], 'prefix', 'At Xclusive Wash in New Castle, DE, interior detailing is more than', 'Our interior detailing covers the whole cabin: carpets, upholstery, the dashboard, and the vents. We remove stubborn stains, ground-in dirt, and lingering odors, so a worn or dusty interior comes back clean and fresh. Most clients say the car feels new again.' ],

	// Ceramic coating.
	[ [ 'ceramic' ], 'exact', 'Ceramic Coating in New Castle Delaware', 'Ceramic Coating in New Castle, Delaware' ],
	[ [ 'ceramic' ], 'prefix', 'Professional ceramic coating services in New Castle', 'Ceramic coating for drivers in New Castle, Wilmington, Newark, and nearby areas. It protects your paint and keeps it glossy.' ],
	[ [ 'ceramic' ], 'exact', 'Lock In the Shine. Defend Your Paint.', 'Lock In the Shine and Protect Your Paint' ],
	[ [ 'ceramic' ], 'prefix', 'Ceramic coating is the ultimate solution', 'Ceramic coating bonds to your paint and forms a hard, water-repellent layer that lasts far longer than wax. It protects against UV rays, water spots, dirt, bird droppings, and road grime, and it gives the paint a deep gloss. Our technicians use professional-grade products and apply them carefully, whether you drive a luxury car, an SUV, or a daily commuter. A coated car is easier to keep clean and holds its resale value better.' ],
	[ [ 'ceramic' ], 'prefix', 'With ceramic coating, your vehicle stays cleaner', 'A coated car stays clean longer and needs less upkeep. The coating also guards against fading and oxidation, so the paint keeps looking good year after year.' ],
	[ [ 'ceramic' ], 'exact', 'Ultra-Glossy Finish', 'Glossy Finish' ],
	[ [ 'ceramic' ], 'prefix', 'Achieve a wet-look shine', 'A deep, wet-look shine.' ],
	[ [ 'ceramic' ], 'exact', 'Long-Term Paint Protection', 'Long-Term Protection' ],
	[ [ 'ceramic' ], 'prefix', 'Strong barrier against scratches', 'Helps guard against scratches and fading.' ],
	[ [ 'ceramic' ], 'exact', 'Hydrophobic Layer', 'Water-Repellent' ],
	[ [ 'ceramic' ], 'prefix', 'Water beads & dirt slide right off', 'Water beads up, and dirt rinses off easily.' ],
	[ [ 'ceramic' ], 'prefix', "Every ceramic coating service is designed to maximize your car's shine", "Ceramic coating starts at $300, depending on your vehicle's size, the paint's condition, and any add-ons." ],
	[ [ 'ceramic' ], 'prefix', 'Our clients trust us to keep their vehicles looking brand new', 'Clients bring us everything from sports cars to family SUVs for ceramic coating. They get deep gloss and protection that lasts.' ],

	// Paint correction.
	[ [ 'paint' ], 'exact', 'Paint Correction in New Castle Delaware', 'Paint Correction in New Castle, Delaware' ],
	[ [ 'paint' ], 'prefix', "Restore your vehicle's paint to a flawless", 'Professional paint correction for drivers in New Castle, Wilmington, Newark, and nearby areas.' ],
	[ [ 'paint' ], 'prefix', "Over time, your vehicle's paint can lose its shine", "Over time, swirls, light scratches, oxidation, and etching dull your car's paint. Paint correction refines the surface to bring back gloss, clarity, and depth, for much less than a repaint. We use machine polishers, pads, and compounds to remove defects one layer at a time. It's a good choice before selling the car, before a show, or just because you want it to look its best." ],
	[ [ 'paint' ], 'prefix', "Paint correction isn't just cosmetic", 'Removing surface damage protects the paint as well as improving how it looks, which helps the car hold its value.' ],
	[ [ 'paint' ], 'exact', 'Brings back a flawless, mirror-like finish.', 'Brings back a mirror-like finish.' ],
	[ [ 'paint' ], 'exact', 'Protects Investment', 'Protects Your Investment' ],
	[ [ 'paint' ], 'exact', 'Better-than-New Look', 'Looks Better Than New' ],
	[ [ 'paint' ], 'exact', 'Perfect for resale, shows, or everyday pride.', 'Great for resale, shows, or everyday driving.' ],
	[ [ 'paint' ], 'prefix', 'Paint correction services start at $200', 'Paint correction starts at $200. You can pair it with ceramic coating to protect the corrected paint.' ],
	[ [ 'paint' ], 'prefix', 'Our clients trust us to restore the original brilliance', 'Clients bring us daily drivers, luxury cars, and show cars for paint correction. We remove swirls, haze, oxidation, and scratches, leaving deep gloss and real clarity.' ],

	// Engine bay detailing.
	[ [ 'engine' ], 'exact', 'Engine Bay Detailing in New Castle DE', 'Engine Bay Detailing in New Castle, DE' ],
	[ [ 'engine' ], 'prefix', 'Give your engine the care it deserves', 'We remove built-up dirt and grime from your engine bay and protect the components underneath.' ],
	[ [ 'engine' ], 'prefix', 'Your engine bay is one of the most overlooked', 'The engine bay is one of the most overlooked parts of a car. A clean one makes inspections and servicing easier, and it makes a good impression when you sell the car or show it. We clean, degrease, and dress every surface using safe, non-abrasive methods, and we protect the electrical components while we work. Plastics, hoses, and exposed metal come out with an even finish.' ],
	[ [ 'engine' ], 'prefix', 'A detailed engine bay improves appearance', 'A detailed engine bay looks better, gets extra protection, and helps the car hold its value.' ],
	[ [ 'engine' ], 'exact', 'What Engine Bay Detailing Includes:', 'What Engine Bay Detailing Includes' ],
	[ [ 'engine' ], 'prefix', 'Every ceramic coating service is designed', "Engine bay detailing is a $40 add-on to any package, and it's included in the Silver and Gold packages." ],
	[ [ 'engine' ], 'exact', 'Transformations That Speak for Themselves', 'Before and After' ],
	[ [ 'engine' ], 'prefix', 'Our clients trust us for expert engine bay detailing', 'From daily commuters to high-performance cars, we bring even the dirtiest engine bays back to a clean, even finish.' ],

	// Ozone odor treatment.
	[ [ 'ozone' ], 'exact', 'Ozone Odor Treatment in New Castle Delaware', 'Ozone Odor Treatment in New Castle, Delaware' ],
	[ [ 'ozone' ], 'prefix', 'Eliminate stubborn odors at the source', 'Ozone treatment breaks down smoke, mildew, bacteria, and pet odors so your car smells fresh again.' ],
	[ [ 'ozone' ], 'prefix', 'Bad odors can ruin even the most detailed', 'Smoke, mildew, pets, and food leave odors that hide in fabric, vents, and tight spaces, even in a freshly cleaned car. Ozone reaches those places and breaks down the odor itself, so nothing is covered up with fragrance.' ],
	[ [ 'ozone' ], 'prefix', 'Best paired with interior detailing', 'It works best alongside an interior detail or steam clean. Clients in New Castle, Wilmington, and Newark come to us for smoke and pet odor removal.' ],
	[ [ 'ozone' ], 'exact', 'Industrial-Grade Equipment', 'Professional Equipment' ],
	[ [ 'ozone' ], 'prefix', 'Professional ozone generators for maximum', 'We use commercial ozone generators.' ],
	[ [ 'ozone' ], 'exact', 'Safe & Effective', 'No Harsh Chemicals' ],
	[ [ 'ozone' ], 'prefix', 'Targets odors at the molecular level without harsh', 'Ozone breaks down odors without masking sprays.' ],
	[ [ 'ozone' ], 'exact', 'Long-Lasting Freshness', 'Lasting Freshness' ],
	[ [ 'ozone' ], 'exact', 'Keeps your cabin clean, sanitized, and odor-free.', 'Your cabin stays clean and odor-free.' ],
	[ [ 'ozone' ], 'prefix', 'Every ceramic coating service is designed', 'Ozone odor treatment is an add-on to any interior detail.' ],
	[ [ 'ozone' ], 'exact', 'Fresh Cabin. Happy Drivers.', 'Fresh Cabin, Happy Drivers' ],
	[ [ 'ozone' ], 'prefix', 'Our clients trust us for professional ozone odor removal', 'We\'ve removed odors from smoke-filled interiors and cars that carry pets every day. The ozone reaches fabric, vents, and tight spaces, so the whole cabin smells clean when you pick it up.' ],

	// Add-ons.
	[ [ 'addons' ], 'exact', 'Premium Auto Detailing Add-Ons in New Castle Delaware', 'Detailing Add-Ons in New Castle, Delaware' ],
	[ [ 'addons' ], 'prefix', 'Take your detailing package to the next level', 'Add extra treatments to any interior or exterior detail, from engine bay cleaning to odor removal.' ],
	[ [ 'addons' ], 'exact', 'Tailored Extras. Maximum Results.', 'Tailored Extras' ],
	[ [ 'addons' ], 'prefix', 'Every vehicle is different, which is why', "Every car needs something different. Add only what yours needs, whether that's odor removal, an engine bay clean, or restored plastic trim." ],
	[ [ 'addons' ], 'prefix', 'Each add-on can be paired with any interior', 'Each add-on works with any interior or exterior detailing package.' ],
	[ [ 'addons' ], 'prefix', 'Plastic Restoration', 'Plastic Restoration ($40)', [ 'max_len' => 40 ] ],
	[ [ 'addons' ], 'prefix', 'Odor Treatment', 'Odor Treatment ($40)', [ 'max_len' => 40 ] ],
	[ [ 'addons' ], 'prefix', 'Engine Bay Detail', 'Engine Bay Detail ($40)', [ 'max_len' => 40 ] ],
	[ [ 'addons' ], 'prefix', 'Water Spot Remove', 'Water Spot Removal ($50)', [ 'max_len' => 40 ] ],
	[ [ 'addons' ], 'prefix', 'Restores faded plastic trim', 'Brings faded plastic trim back to a deep color and protects it.' ],
	[ [ 'addons' ], 'prefix', 'Clean, degrease, and dress engine components', 'Cleans, degreases, and dresses the engine bay.' ],
	[ [ 'addons' ], 'prefix', 'Eliminates stubborn water spots', 'Removes stubborn water spots without damaging the paint.' ],
	[ [ 'addons' ], 'prefix', 'Every ceramic coating service is designed', 'Add-ons cost $40 to $50. Here are a few combinations that work well:' ],
	[ [ 'addons' ], 'prefix', 'Our clients love the flexibility', 'Clients like being able to build their own detail. A few add-ons make a big difference, from deep-cleaned seats to a spotless engine bay.' ],
];

$hits = array_fill( 0, count( $rules ), 0 );
$log  = [];

/** Apply text rules to every string inside a settings array (including repeaters). */
function xw_apply_rules( &$value, $page_key, $where, $rules, &$hits, &$log ) {
	if ( is_array( $value ) ) {
		foreach ( $value as $k => &$v ) {
			xw_apply_rules( $v, $page_key, $where . '.' . $k, $rules, $hits, $log );
		}
		return;
	}
	if ( ! is_string( $value ) || '' === trim( $value ) ) {
		return;
	}
	$plain = xw_norm( $value );
	foreach ( $rules as $i => $r ) {
		list( $scope, $type, $old, $new ) = $r;
		$opt = $r[4] ?? [];
		if ( '*' !== $scope && ! in_array( $page_key, $scope, true ) ) {
			continue;
		}
		$o  = xw_norm( $old );
		$ok = 'exact' === $type ? ( $plain === $o ) : ( 0 === strpos( $plain, $o ) );
		if ( $ok && isset( $opt['contains'] ) && false === strpos( $plain, xw_norm( $opt['contains'] ) ) ) {
			$ok = false;
		}
		if ( $ok && isset( $opt['excludes'] ) && false !== strpos( $plain, xw_norm( $opt['excludes'] ) ) ) {
			$ok = false;
		}
		if ( $ok && isset( $opt['max_len'] ) && mb_strlen( $plain ) > $opt['max_len'] ) {
			$ok = false;
		}
		if ( ! $ok ) {
			continue;
		}
		$replacement = esc_html( $new );
		if ( preg_match( '/<p[\s>]/i', $value ) ) {
			$replacement = '<p>' . $replacement . '</p>';
		}
		$log[] = sprintf( "  [%s] %s\n      OLD: %s\n      NEW: %s", $page_key, $where, mb_substr( $plain, 0, 90 ), $new );
		$value = $replacement;
		$hits[ $i ]++;
		return;
	}
}

/** Remove or convert "Contact Us For a Quote" buttons, then apply text rules, recursively. */
function xw_walk( &$elements, $page_key, $rules, &$hits, &$log, &$quote ) {
	$is_quote = function ( $el ) {
		return 'widget' === ( $el['elType'] ?? '' ) && 'button' === ( $el['widgetType'] ?? '' )
			&& 'contact us for a quote' === xw_norm( $el['settings']['text'] ?? '' );
	};
	$is_book = function ( $el ) {
		return 'widget' === ( $el['elType'] ?? '' ) && 'button' === ( $el['widgetType'] ?? '' )
			&& false !== strpos( xw_norm( $el['settings']['text'] ?? '' ), 'book' );
	};
	$has_book_sibling = (bool) array_filter( $elements, $is_book );

	foreach ( $elements as $idx => &$el ) {
		if ( $is_quote( $el ) ) {
			if ( $has_book_sibling ) {
				$log[] = "  [$page_key] quote button {$el['id']}: REMOVED (a Book button sits next to it)";
				unset( $elements[ $idx ] );
				$quote['removed']++;
				continue;
			}
			$el['settings']['text']        = 'Book a Detail';
			$el['settings']['link']        = array_merge( (array) ( $el['settings']['link'] ?? [] ), [ 'url' => BOOKING_URL, 'is_external' => '', 'nofollow' => '' ] );
			$log[]                         = "  [$page_key] quote button {$el['id']}: now 'Book a Detail' -> booking page";
			$quote['converted']++;
		}
		if ( isset( $el['settings'] ) && is_array( $el['settings'] ) ) {
			xw_apply_rules( $el['settings'], $page_key, ( $el['widgetType'] ?? $el['elType'] ) . '#' . $el['id'], $rules, $hits, $log );
		}
		if ( ! empty( $el['elements'] ) ) {
			xw_walk( $el['elements'], $page_key, $rules, $hits, $log, $quote );
		}
	}
	unset( $el );
	$elements = array_values( $elements );
}

echo "Mode: $mode\n\n";

foreach ( $pages as $key => $id ) {
	if ( 'restore' === $mode ) {
		$backup = get_post_meta( $id, BACKUP_KEY, true );
		if ( $backup ) {
			update_post_meta( $id, '_elementor_data', wp_slash( $backup ) );
			delete_post_meta( $id, '_elementor_element_cache' );
			echo "$key ($id): restored\n";
		} else {
			echo "$key ($id): no backup found\n";
		}
		continue;
	}

	$raw  = get_post_meta( $id, '_elementor_data', true );
	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) ) {
		echo "$key ($id): could not read Elementor data, skipped\n";
		continue;
	}
	$quote = [ 'removed' => 0, 'converted' => 0 ];
	$before = count( $log );
	xw_walk( $data, $key, $rules, $hits, $log, $quote );
	$changes = count( $log ) - $before;
	echo "$key ($id): $changes change(s), quote buttons removed {$quote['removed']}, converted {$quote['converted']}\n";

	if ( 'apply' === $mode && $changes > 0 ) {
		if ( ! get_post_meta( $id, BACKUP_KEY, true ) ) {
			add_post_meta( $id, BACKUP_KEY, wp_slash( $raw ), true );
		}
		update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
		delete_post_meta( $id, '_elementor_element_cache' );
		delete_post_meta( $id, '_elementor_css' );
	}
}

if ( 'restore' !== $mode ) {
	// Header menu item.
	$items = get_posts( [ 'post_type' => 'nav_menu_item', 'numberposts' => -1, 'post_status' => 'any' ] );
	foreach ( $items as $item ) {
		if ( 'contact us for quote' === xw_norm( $item->post_title ) ) {
			$log[] = "  [menu] item {$item->ID}: '{$item->post_title}' -> 'CONTACT US'";
			if ( 'apply' === $mode ) {
				wp_update_post( [ 'ID' => $item->ID, 'post_title' => 'CONTACT US' ] );
			}
		}
	}

	echo "\nDetails:\n" . implode( "\n", $log ) . "\n";

	$missed = [];
	foreach ( $rules as $i => $r ) {
		if ( 0 === $hits[ $i ] ) {
			$missed[] = '  ' . ( is_array( $r[0] ) ? implode( ',', $r[0] ) : $r[0] ) . ': ' . $r[2];
		}
	}
	echo "\nRules with no match (" . count( $missed ) . "):\n" . ( $missed ? implode( "\n", $missed ) : '  none' ) . "\n";
}

if ( 'dry' !== $mode && class_exists( '\Elementor\Plugin' ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
	echo "\nElementor cache cleared.\n";
}
