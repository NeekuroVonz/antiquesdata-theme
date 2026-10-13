<?php
/**
 * AI market brief and chat for the Trend Dashboard.
 *
 * Uses only compacted dashboard metrics — never queries the catalog DB.
 *
 * @package AntiquesMarketplace
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Allowed dashboard range keys.
 *
 * @return string[]
 */
function antiques_marketplace_dashboard_ai_ranges() {
	return array( '1m', '6m', '12m' );
}

/**
 * Sanitize a dashboard range key.
 *
 * @param string $range Raw range.
 * @return string
 */
function antiques_marketplace_dashboard_ai_sanitize_range( $range ) {
	$range = sanitize_key( (string) $range );
	return in_array( $range, antiques_marketplace_dashboard_ai_ranges(), true ) ? $range : '12m';
}

/**
 * Whether the current user may use dashboard AI endpoints.
 *
 * @return bool
 */
function antiques_marketplace_dashboard_ai_user_can_access() {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	if ( current_user_can( 'manage_options' ) || antiques_marketplace_bypass_membership() ) {
		return true;
	}
	return antiques_marketplace_user_has_membership();
}

/**
 * Compact metrics for LLM prompts (small JSON, display currency numbers).
 *
 * @param array<string, mixed> $metrics Full dashboard metrics.
 * @param string               $range   1m|6m|12m.
 * @return array<string, mixed>
 */
function antiques_marketplace_dashboard_ai_context( array $metrics, $range ) {
	$range  = antiques_marketplace_dashboard_ai_sanitize_range( $range );
	$period = isset( $metrics['periods'][ $range ] ) && is_array( $metrics['periods'][ $range ] )
		? $metrics['periods'][ $range ]
		: array();

	$currency = antiques_marketplace_display_currency();
	$median   = antiques_marketplace_format_currency_amount( (float) ( $period['median_eur'] ?? 0 ), $currency );
	$lots     = (int) ( $period['lots'] ?? 0 );

	$price  = array();
	$volume = array();
	foreach ( array_slice( (array) ( $period['price'] ?? array() ), -12 ) as $point ) {
		if ( ! is_array( $point ) ) {
			continue;
		}
		$price[] = array(
			't' => (string) ( $point['t'] ?? '' ),
			'v' => $point['v'],
		);
	}
	foreach ( array_slice( (array) ( $period['volume'] ?? array() ), -12 ) as $point ) {
		if ( ! is_array( $point ) ) {
			continue;
		}
		$volume[] = array(
			't' => (string) ( $point['t'] ?? '' ),
			'v' => isset( $point['v'] ) ? (int) $point['v'] : 0,
		);
	}

	$countries = array();
	foreach ( array_slice( (array) ( $period['countries'] ?? array() ), 0, 5 ) as $country ) {
		if ( ! is_array( $country ) ) {
			continue;
		}
		$cats = array();
		foreach ( array_slice( (array) ( $country['categories'] ?? array() ), 0, 3 ) as $category ) {
			if ( ! is_array( $category ) ) {
				continue;
			}
			$cats[] = (string) ( $category['label'] ?? $category['slug'] ?? '' );
		}
		$countries[] = array(
			'name'       => (string) ( $country['name'] ?? '' ),
			'lots'       => (int) ( $country['lots'] ?? 0 ),
			'median'     => antiques_marketplace_format_currency_amount( (float) ( $country['median_eur'] ?? 0 ), $currency ),
			'categories' => array_values( array_filter( $cats ) ),
		);
	}

	return array(
		'range'      => $range,
		'currency'   => $currency,
		'median'     => $median,
		'lots'       => $lots,
		'first_end'  => substr( (string) ( $period['first_end'] ?? '' ), 0, 10 ),
		'last_end'   => substr( (string) ( $period['last_end'] ?? '' ), 0, 10 ),
		'price'      => $price,
		'volume'     => $volume,
		'countries'  => $countries,
		'fx_caption' => antiques_marketplace_exchange_rate_caption(),
	);
}

/**
 * Hash of context used for insight cache keys.
 *
 * @param array<string, mixed> $context Compact context.
 * @return string
 */
function antiques_marketplace_dashboard_ai_context_hash( array $context ) {
	$encoded = wp_json_encode( $context );
	return md5( is_string( $encoded ) ? $encoded : '' );
}

/**
 * PHP fallback brief when OpenAI is unavailable.
 *
 * @param array<string, mixed> $context Compact context.
 * @param string               $lang    ja|en.
 * @return string
 */
function antiques_marketplace_dashboard_ai_fallback_insight( array $context, $lang ) {
	$range_labels = array(
		'1m'  => 'ja' === $lang ? '1か月' : '1 month',
		'6m'  => 'ja' === $lang ? '6か月' : '6 months',
		'12m' => 'ja' === $lang ? '12か月' : '12 months',
	);
	$range   = (string) ( $context['range'] ?? '12m' );
	$label   = isset( $range_labels[ $range ] ) ? $range_labels[ $range ] : $range;
	$median  = (string) ( $context['median'] ?? '—' );
	$lots    = number_format_i18n( (int) ( $context['lots'] ?? 0 ) );
	$span    = trim( (string) ( $context['first_end'] ?? '' ) . ' – ' . (string) ( $context['last_end'] ?? '' ), ' –' );
	$top     = isset( $context['countries'][0]['name'] ) ? (string) $context['countries'][0]['name'] : '';
	$top_cat = '';
	if ( ! empty( $context['countries'][0]['categories'][0] ) ) {
		$top_cat = (string) $context['countries'][0]['categories'][0];
	}

	if ( 'ja' === $lang ) {
		$lines   = array();
		$lines[] = '・期間: ' . $label . ( $span ? '（' . $span . '）' : '' );
		$lines[] = '・中央値（P50）落札価格: ' . $median;
		$lines[] = '・落札ロット数: ' . $lots;
		if ( '' !== $top ) {
			$lines[] = '・主な落札国: ' . $top . ( '' !== $top_cat ? '（#' . $top_cat . '）' : '' );
		}
		$lines[] = '';
		$lines[] = 'OpenAIキーが未設定のため、集計数値からの簡易サマリーです。ディーラーが相場判断の出発点として使えるよう、中央値と流通量・落札国をまとめています。';
		return implode( "\n", $lines );
	}

	$lines   = array();
	$lines[] = '• Window: ' . $label . ( $span ? ' (' . $span . ')' : '' );
	$lines[] = '• Median (P50) hammer: ' . $median;
	$lines[] = '• Lots sold: ' . $lots;
	if ( '' !== $top ) {
		$lines[] = '• Top buyer country: ' . $top . ( '' !== $top_cat ? ' (#' . $top_cat . ')' : '' );
	}
	$lines[] = '';
	$lines[] = 'OpenAI is not configured, so this is a numbers-only brief from the cached dashboard metrics—median, volume, and buyer geography for dealer pricing context.';
	return implode( "\n", $lines );
}

/**
 * Call OpenAI chat completions; return assistant text or empty string.
 *
 * @param array<int, array<string, string>> $messages Chat messages.
 * @param float                             $temperature Sampling temperature.
 * @param int                               $timeout Request timeout seconds.
 * @return string
 */
function antiques_marketplace_dashboard_ai_openai_chat( array $messages, $temperature = 0.3, $timeout = 20 ) {
	$api_key = antiques_marketplace_env( 'OPENAI_API_KEY' );
	if ( '' === $api_key ) {
		return '';
	}

	$payload = wp_json_encode(
		array(
			'model'       => 'gpt-4o-mini',
			'temperature' => (float) $temperature,
			'messages'    => $messages,
		)
	);

	$response = wp_remote_post(
		'https://api.openai.com/v1/chat/completions',
		array(
			'timeout' => max( 5, (int) $timeout ),
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => $payload ? $payload : '{}',
		)
	);

	if ( is_wp_error( $response ) ) {
		return '';
	}

	$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	$text = $body['choices'][0]['message']['content'] ?? '';
	return is_string( $text ) ? trim( $text ) : '';
}

/**
 * Build or fetch cached AI market brief for a range.
 *
 * @param string                    $range   1m|6m|12m.
 * @param string                    $lang    ja|en.
 * @param array<string, mixed>|null $metrics Optional preloaded metrics.
 * @return array{text:string,source:string,range:string}
 */
function antiques_marketplace_dashboard_ai_insight( $range, $lang, $metrics = null ) {
	$range = antiques_marketplace_dashboard_ai_sanitize_range( $range );
	$lang  = 'ja' === $lang ? 'ja' : 'en';

	if ( ! is_array( $metrics ) ) {
		$metrics = antiques_marketplace_get_dashboard_metrics();
	}
	if ( ! empty( $metrics['error'] ) ) {
		return array(
			'text'   => (string) $metrics['error'],
			'source' => 'error',
			'range'  => $range,
		);
	}

	$context = antiques_marketplace_dashboard_ai_context( $metrics, $range );
	$hash    = antiques_marketplace_dashboard_ai_context_hash( $context );
	$cache_key = 'ant_dash_ai_insight_v1_' . $lang . '_' . $range . '_' . $hash;
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) && ! empty( $cached['text'] ) ) {
		return array(
			'text'   => (string) $cached['text'],
			'source' => (string) ( $cached['source'] ?? 'cache' ),
			'range'  => $range,
		);
	}

	$api_key = antiques_marketplace_env( 'OPENAI_API_KEY' );
	if ( '' === $api_key ) {
		$text = antiques_marketplace_dashboard_ai_fallback_insight( $context, $lang );
		$out  = array(
			'text'   => $text,
			'source' => 'fallback',
			'range'  => $range,
		);
		set_transient( $cache_key, $out, 30 * MINUTE_IN_SECONDS );
		return $out;
	}

	$context_json = wp_json_encode( $context, JSON_UNESCAPED_UNICODE );
	$lang_name    = 'ja' === $lang ? 'Japanese' : 'English';
	$system       = 'You are a market analyst for Japanese antique dealers using Antique Data Japan. '
		. 'Use ONLY the JSON metrics provided. Do not invent lot counts, prices, countries, or dates. '
		. 'If a field is missing, say so briefly. Write in ' . $lang_name . '. '
		. 'Return 3–5 short bullets plus one short closing paragraph for dealers. No markdown tables. No GMV.';

	$text = antiques_marketplace_dashboard_ai_openai_chat(
		array(
			array(
				'role'    => 'system',
				'content' => $system,
			),
			array(
				'role'    => 'user',
				'content' => "Write the market brief for range {$range}. Metrics JSON:\n" . ( $context_json ? $context_json : '{}' ),
			),
		),
		0.35,
		22
	);

	if ( '' === $text ) {
		$text   = antiques_marketplace_dashboard_ai_fallback_insight( $context, $lang );
		$source = 'fallback';
	} else {
		$source = 'openai';
	}

	$out = array(
		'text'   => $text,
		'source' => $source,
		'range'  => $range,
	);
	set_transient( $cache_key, $out, 30 * MINUTE_IN_SECONDS );
	return $out;
}

/**
 * Answer a chat question using dashboard metrics context.
 *
 * @param string                           $question User question.
 * @param string                           $range    1m|6m|12m.
 * @param string                           $lang     ja|en.
 * @param array<int, array<string,string>> $history  Prior turns (role/content), max few.
 * @return array{answer:string,source:string,error?:string}
 */
function antiques_marketplace_dashboard_ai_chat_answer( $question, $range, $lang, array $history = array() ) {
	$question = trim( wp_strip_all_tags( (string) $question ) );
	if ( '' === $question ) {
		return array(
			'answer' => '',
			'source' => 'error',
			'error'  => __( 'Please enter a question.', 'antiques-marketplace' ),
		);
	}
	if ( function_exists( 'mb_strlen' ) && mb_strlen( $question ) > 500 ) {
		$question = mb_substr( $question, 0, 500 );
	} elseif ( strlen( $question ) > 500 ) {
		$question = substr( $question, 0, 500 );
	}

	$range   = antiques_marketplace_dashboard_ai_sanitize_range( $range );
	$lang    = 'ja' === $lang ? 'ja' : 'en';
	$metrics = antiques_marketplace_get_dashboard_metrics();
	if ( ! empty( $metrics['error'] ) ) {
		return array(
			'answer' => (string) $metrics['error'],
			'source' => 'error',
		);
	}

	$context = antiques_marketplace_dashboard_ai_context( $metrics, $range );

	if ( '' === antiques_marketplace_env( 'OPENAI_API_KEY' ) ) {
		return array(
			'answer' => __( 'AI chat is unavailable because OPENAI_API_KEY is not configured. The charts and median figures above still reflect the live aggregated data.', 'antiques-marketplace' ),
			'source' => 'fallback',
		);
	}

	$context_json = wp_json_encode( $context, JSON_UNESCAPED_UNICODE );
	$lang_name    = 'ja' === $lang ? 'Japanese' : 'English';
	$system       = 'You answer short questions about Antique Data Japan Trend Dashboard metrics. '
		. 'Use ONLY the JSON metrics. Do not invent numbers. If the answer is not in the data, say you cannot tell from this window. '
		. 'Reply in ' . $lang_name . ' in at most 4 short sentences. Dealer-facing tone.';

	$messages = array(
		array(
			'role'    => 'system',
			'content' => $system,
		),
		array(
			'role'    => 'user',
			'content' => "Metrics JSON for range {$range}:\n" . ( $context_json ? $context_json : '{}' ),
		),
	);

	$turns = 0;
	foreach ( $history as $turn ) {
		if ( $turns >= 4 ) {
			break;
		}
		if ( ! is_array( $turn ) ) {
			continue;
		}
		$role = isset( $turn['role'] ) ? (string) $turn['role'] : '';
		$text = isset( $turn['content'] ) ? trim( wp_strip_all_tags( (string) $turn['content'] ) ) : '';
		if ( ( 'user' !== $role && 'assistant' !== $role ) || '' === $text ) {
			continue;
		}
		if ( function_exists( 'mb_strlen' ) && mb_strlen( $text ) > 800 ) {
			$text = mb_substr( $text, 0, 800 );
		} elseif ( strlen( $text ) > 800 ) {
			$text = substr( $text, 0, 800 );
		}
		$messages[] = array(
			'role'    => $role,
			'content' => $text,
		);
		$turns++;
	}

	$messages[] = array(
		'role'    => 'user',
		'content' => $question,
	);

	$answer = antiques_marketplace_dashboard_ai_openai_chat( $messages, 0.25, 20 );
	if ( '' === $answer ) {
		return array(
			'answer' => __( 'Could not get an AI answer right now. Try again in a moment.', 'antiques-marketplace' ),
			'source' => 'error',
		);
	}

	return array(
		'answer' => $answer,
		'source' => 'openai',
	);
}

/**
 * Simple per-user hourly rate limit for chat.
 *
 * @param int $user_id User ID.
 * @param int $limit   Max requests per hour.
 * @return bool True if allowed.
 */
function antiques_marketplace_dashboard_ai_chat_rate_allow( $user_id, $limit = 10 ) {
	$user_id = absint( $user_id );
	if ( $user_id <= 0 ) {
		return false;
	}
	$key   = 'ant_dash_ai_chat_rl_' . $user_id;
	$count = (int) get_transient( $key );
	if ( $count >= $limit ) {
		return false;
	}
	set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	return true;
}

/**
 * AJAX: market brief for selected range.
 */
function antiques_marketplace_ajax_dashboard_ai_insight() {
	if ( ! check_ajax_referer( 'antiques_dashboard_ai', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid request.', 'antiques-marketplace' ) ), 403 );
	}
	if ( ! antiques_marketplace_dashboard_ai_user_can_access() ) {
		wp_send_json_error( array( 'message' => __( 'Membership required.', 'antiques-marketplace' ) ), 403 );
	}

	$range  = isset( $_REQUEST['range'] ) ? antiques_marketplace_dashboard_ai_sanitize_range( wp_unslash( (string) $_REQUEST['range'] ) ) : '12m';
	$lang   = antiques_marketplace_get_lang();
	$result = antiques_marketplace_dashboard_ai_insight( $range, $lang );
	wp_send_json_success( $result );
}
add_action( 'wp_ajax_antiques_dashboard_ai_insight', 'antiques_marketplace_ajax_dashboard_ai_insight' );

/**
 * AJAX: ask-the-data chat.
 */
function antiques_marketplace_ajax_dashboard_ai_chat() {
	if ( ! check_ajax_referer( 'antiques_dashboard_ai', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid request.', 'antiques-marketplace' ) ), 403 );
	}
	if ( ! antiques_marketplace_dashboard_ai_user_can_access() ) {
		wp_send_json_error( array( 'message' => __( 'Membership required.', 'antiques-marketplace' ) ), 403 );
	}

	$user_id = get_current_user_id();
	if ( ! antiques_marketplace_dashboard_ai_chat_rate_allow( $user_id, 10 ) ) {
		wp_send_json_error( array( 'message' => __( 'Chat rate limit reached. Try again later.', 'antiques-marketplace' ) ), 429 );
	}

	$range    = isset( $_POST['range'] ) ? antiques_marketplace_dashboard_ai_sanitize_range( wp_unslash( (string) $_POST['range'] ) ) : '12m';
	$question = isset( $_POST['question'] ) ? wp_unslash( (string) $_POST['question'] ) : '';
	$history  = array();
	if ( ! empty( $_POST['history'] ) ) {
		$raw = json_decode( wp_unslash( (string) $_POST['history'] ), true );
		if ( is_array( $raw ) ) {
			$history = $raw;
		}
	}

	$result = antiques_marketplace_dashboard_ai_chat_answer( $question, $range, antiques_marketplace_get_lang(), $history );
	if ( ! empty( $result['error'] ) ) {
		wp_send_json_error( array( 'message' => $result['error'] ), 400 );
	}
	wp_send_json_success( $result );
}
add_action( 'wp_ajax_antiques_dashboard_ai_chat', 'antiques_marketplace_ajax_dashboard_ai_chat' );
