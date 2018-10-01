<?php
// Parse This Global Functions


if ( ! function_exists( 'jf2_to_mf2' ) ) {
	function jf2_to_mf2( $entry ) {
		if ( ! $entry || ! is_array( $entry ) | isset( $entry['properties'] ) ) {
			return $entry;
		}
		$return               = array();
		$return['type']       = array( 'h-' . $entry['type'] );
		$return['properties'] = array();
		unset( $entry['type'] );
		foreach ( $entry as $key => $value ) {
			// Exclude  values
			if ( empty( $value ) || ( '_raw' === $key ) ) {
				continue;
			}
			if ( ! wp_is_numeric_array( $value ) && is_array( $value ) && array_key_exists( 'type', $value ) ) {
				$value = jf2_to_mf2( $value );
			} elseif ( wp_is_numeric_array( $value ) && is_array( $value[0] ) && array_key_exists( 'type', $value[0] ) ) {
				foreach ( $value as $item ) {
					$items[] = jf2_to_mf2( $item );
				}
				$value = $items;
			} elseif ( ! wp_is_numeric_array( $value ) ) {
				$value = array( $value );
			} else {
				continue;
			}
			$return['properties'][ $key ] = $value;
		}
		return $return;
	}
}

if ( ! function_exists( 'mf2_to_jf2' ) ) {

	function mf2_to_jf2( $entry ) {
		if ( wp_is_numeric_array( $entry ) || ! isset( $entry['properties'] ) ) {
			return $entry;
		}
		$jf2         = array();
		$type        = is_array( $entry['type'] ) ? array_pop( $entry['type'] ) : $entry['type'];
		$jf2['type'] = str_replace( 'h-', '', $type );
		if ( isset( $entry['properties'] ) && is_array( $entry['properties'] ) ) {
			foreach ( $entry['properties'] as $key => $value ) {
				if ( is_array( $value ) && 1 === count( $value ) ) {
					$value = array_pop( $value );
				}
				if ( ! wp_is_numeric_array( $value ) && isset( $value['type'] ) ) {
					$value = mf2_to_jf2( $value );
				}
				$jf2[ $key ] = $value;
			}
		} elseif ( isset( $entry['items'] ) ) {
			$jf2['children'] = array();
			foreach ( $entry['items'] as $item ) {
				$jf2['children'][] = mf2_to_jf2( $item );
			}
		}
		return $jf2;
	}
}

if ( ! function_exists( 'url_to_author' ) ) {
	/**
	 * Examine a url and try to determine the author ID it represents.
	 *
	 * @param string $url Permalink to check.
	 *
	 * @return WP_User, or null on failure.
	 */
	function url_to_author( $url ) {
		global $wp_rewrite;
		// check if url hase the same host
		if ( wp_parse_url( site_url(), PHP_URL_HOST ) !== wp_parse_url( $url, PHP_URL_HOST ) ) {
			return null;
		}
		// first, check to see if there is a 'author=N' to match against
		if ( preg_match( '/[?&]author=(\d+)/i', $url, $values ) ) {
			$id = absint( $values[1] );
			if ( $id ) {
				return get_user_by( 'id', $id );
			}
		}
		// check to see if we are using rewrite rules
		$rewrite = $wp_rewrite->wp_rewrite_rules();
		// not using rewrite rules, and 'author=N' method failed, so we're out of options
		if ( empty( $rewrite ) ) {
			return null;
		}
		// generate rewrite rule for the author url
		$author_rewrite = $wp_rewrite->get_author_permastruct();
		$author_regexp  = str_replace( '%author%', '', $author_rewrite );
		// match the rewrite rule with the passed url
		if ( preg_match( '/https?:\/\/(.+)' . preg_quote( $author_regexp, '/' ) . '([^\/]+)/i', $url, $match ) ) {
			$user = get_user_by( 'slug', $match[2] );
			if ( $user ) {
				return $user;
			}
		}
		return null;
	}
}

if ( ! function_exists( 'url_to_user' ) ) {
	/**
	 * Get the user associated with a URL.
	 *
	 * @param string $url url to match
	 * @return WP_User $user Associated user, or null if no associated user
	 */
	function url_to_user( $url ) {
		if ( empty( $url ) ) {
			return null;
		}
		// Ensure has trailing slash
		$url = trailingslashit( $url );
		if ( ( 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME ) ) && ( wp_parse_url( home_url(), PHP_URL_HOST ) === wp_parse_url( $url, PHP_URL_HOST ) ) ) {
			$url = set_url_scheme( $url, 'https' );
		}
		// Try to save the expense of a search query if the URL is the site URL
		if ( home_url( '/' ) === $url ) {
			// Use the Indieweb settings to set the default author
			if ( class_exists( 'Indieweb_Plugin' ) && ( get_option( 'iw_single_author' ) || ! is_multi_author() ) ) {
				return get_user_by( 'id', get_option( 'iw_default_author' ) );
			}
			$users = get_users( array( 'who' => 'authors' ) );
			if ( 1 === count( $users ) ) {
				return $users[0];
			}
			return null;
		}
		// Check if this is a author post URL
		$user = url_to_author( $url );
		if ( $user instanceof WP_User ) {
			return $user;
		}
		$args  = array(
			'search'         => $url,
			'search_columns' => array( 'user_url' ),
		);
		$users = get_users( $args );
		// check result
		if ( ! empty( $users ) ) {
			return $users[0];
		}
		return null;
	}
}

if ( ! function_exists( 'ifset' ) ) {
		/**
		 * If set, return otherwise false.
		 *
		 * @param type $var Check if set.
		 * @return $var|false Return either $var or $return.
		 */
	function ifset( &$var, $return = false ) {

			return isset( $var ) ? $var : $return;
	}
}



