<?php
/**
 * Load key/value pairs from a theme-local `.env` file into the PHP environment.
 *
 * Does not override variables already set by the web server, systemd, or wp-config.
 *
 * @package AntiquesMarketplace
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Parse `.env` once per request.
 */
function antiques_marketplace_load_env() {
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;

	$path = trailingslashit(get_template_directory()) . '.env';
	if (!is_readable($path)) {
		return;
	}

	$raw = file_get_contents($path);
	if (false === $raw || '' === trim($raw)) {
		return;
	}

	$lines = preg_split('/\r\n|\r|\n/', $raw);
	if (!is_array($lines)) {
		return;
	}

	foreach ($lines as $line) {
		$line = trim((string) $line);
		if ('' === $line || '#' === $line[0]) {
			continue;
		}

		if (!preg_match('/^(?:export\s+)?([\w.]+)\s*=\s*(.*)$/', $line, $matches)) {
			continue;
		}

		$key = $matches[1];
		$val = $matches[2];

		if (false !== getenv($key)) {
			continue;
		}

		if (preg_match('/^"(.*)"$/s', $val, $q)) {
			$val = stripcslashes($q[1]);
		} elseif (preg_match("/^'(.*)'$/s", $val, $q)) {
			$val = $q[1];
		} else {
			$hash_pos = strpos($val, ' #');
			if (false !== $hash_pos) {
				$val = substr($val, 0, $hash_pos);
			}
			$val = trim((string) $val);
		}

		putenv($key . '=' . $val);
		$_ENV[$key]    = $val;
		$_SERVER[$key] = $val;
	}
}

/**
 * Read a configuration value from the environment (server first, then `.env`).
 *
 * @param string $key     Variable name.
 * @param string $default Fallback when unset.
 * @return string
 */
function antiques_marketplace_env( $key, $default = '' ) {
	$from_env = getenv($key);
	if (false !== $from_env) {
		return (string) $from_env;
	}
	if (isset($_ENV[$key])) {
		return (string) $_ENV[$key];
	}
	return $default;
}
