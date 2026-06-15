<?php

declare(strict_types=1);

namespace Valolink\GpWoo;

/**
 * Describes the current request so modules can skip loading where they're irrelevant.
 * Mirrors the Context used in the Valolink Plugin toolkit.
 */
final class Context
{
    public function __construct(
        public readonly bool $is_admin,
        public readonly bool $is_ajax,
        public readonly ?string $ajax_action,
        public readonly bool $is_rest,
        public readonly bool $is_cron,
        public readonly bool $is_cli,
        public readonly bool $is_login,
        public readonly bool $is_frontend,
    ) {}

    public static function detect(): self
    {
        $is_ajax = defined('DOING_AJAX') && DOING_AJAX;
        $ajax_action = null;
        if ($is_ajax && isset($_REQUEST['action']) && is_string($_REQUEST['action'])) {
            $ajax_action = sanitize_key(wp_unslash($_REQUEST['action']));
        }

        $is_rest = (defined('REST_REQUEST') && REST_REQUEST) || self::looks_like_rest_request();
        $is_cron = (defined('DOING_CRON') && DOING_CRON) || wp_doing_cron();
        $is_cli  = defined('WP_CLI') && WP_CLI;
        $is_admin = is_admin() && !$is_ajax;
        $is_login = self::is_login_request();
        $is_frontend = !$is_admin && !$is_ajax && !$is_rest && !$is_cron && !$is_cli && !$is_login;

        return new self(
            is_admin: $is_admin,
            is_ajax: $is_ajax,
            ajax_action: $ajax_action,
            is_rest: $is_rest,
            is_cron: $is_cron,
            is_cli: $is_cli,
            is_login: $is_login,
            is_frontend: $is_frontend,
        );
    }

    private static function looks_like_rest_request(): bool
    {
        $uri = isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])
            ? wp_unslash($_SERVER['REQUEST_URI'])
            : '';
        if ($uri === '') {
            return false;
        }
        $prefix = function_exists('rest_get_url_prefix') ? rest_get_url_prefix() : 'wp-json';
        return (bool) preg_match('#(^|/)' . preg_quote($prefix, '#') . '(/|\?|$)#', $uri);
    }

    private static function is_login_request(): bool
    {
        $script = isset($_SERVER['SCRIPT_FILENAME']) && is_string($_SERVER['SCRIPT_FILENAME'])
            ? $_SERVER['SCRIPT_FILENAME']
            : '';
        return str_ends_with($script, '/wp-login.php') || str_ends_with($script, '/wp-register.php');
    }
}
