<?php

declare(strict_types=1);

namespace Valolink\GpWoo;

/**
 * Minimal self-contained GitHub release updater (no third-party library). Hooks WordPress's
 * own update flow so updates surface in wp-admin. Prefers a `.zip` release asset (built by
 * build.sh), falling back to GitHub's source zipball and renaming the unpacked folder to the slug.
 *
 * Inert until a real repo is passed (see VALOLINK_GP_WOO_GITHUB_REPO in the main plugin file).
 */
final class Updater
{
    private string $basename;
    private string $slug;
    private string $cache_key;
    private int $cache_ttl;

    public function __construct(
        private readonly string $plugin_file,
        private readonly string $repo,
        private readonly string $version,
    ) {
        $this->basename  = plugin_basename($plugin_file);
        $this->slug      = dirname($this->basename);
        $this->cache_key = 'valolink_gp_woo_updater_' . md5($repo);
        $this->cache_ttl = 6 * HOUR_IN_SECONDS;
    }

    public function register(): void
    {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
        add_filter('upgrader_source_selection', [$this, 'fix_source_dir'], 10, 4);
        add_action('upgrader_process_complete', [$this, 'clear_cache']);
    }

    private function get_release(): array
    {
        $cached = get_transient($this->cache_key);
        if ($cached !== false) {
            return is_array($cached) ? $cached : [];
        }

        $response = wp_remote_get("https://api.github.com/repos/{$this->repo}/releases/latest", [
            'timeout' => 10,
            'headers' => [
                'Accept'     => 'application/vnd.github+json',
                'User-Agent' => 'valolink-gp-woo-updater',
            ],
        ]);

        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            set_transient($this->cache_key, [], 15 * MINUTE_IN_SECONDS);
            return [];
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        $data = is_array($data) ? $data : [];
        set_transient($this->cache_key, $data, $this->cache_ttl);
        return $data;
    }

    private function release_version(array $release): string
    {
        return isset($release['tag_name']) ? ltrim((string) $release['tag_name'], 'vV') : '';
    }

    private function package_url(array $release): string
    {
        if (!empty($release['assets']) && is_array($release['assets'])) {
            foreach ($release['assets'] as $asset) {
                if (!empty($asset['browser_download_url']) && str_ends_with((string) $asset['name'], '.zip')) {
                    return (string) $asset['browser_download_url'];
                }
            }
        }
        return (string) ($release['zipball_url'] ?? '');
    }

    public function check_update($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        $release = $this->get_release();
        $new_version = $this->release_version($release);
        if ($new_version === '' || version_compare($new_version, $this->version, '<=')) {
            return $transient;
        }

        $package = $this->package_url($release);
        if ($package === '') {
            return $transient;
        }

        $transient->response[$this->basename] = (object) [
            'slug'        => $this->slug,
            'plugin'      => $this->basename,
            'new_version' => $new_version,
            'package'     => $package,
            'url'         => $release['html_url'] ?? '',
        ];
        return $transient;
    }

    public function plugin_info($result, $action, $args)
    {
        if ($action !== 'plugin_information' || empty($args->slug) || $args->slug !== $this->slug) {
            return $result;
        }

        $release = $this->get_release();
        if (empty($release)) {
            return $result;
        }

        return (object) [
            'name'          => 'Valolink GP Woo',
            'slug'          => $this->slug,
            'version'       => $this->release_version($release),
            'homepage'      => $release['html_url'] ?? '',
            'download_link' => $this->package_url($release),
            'sections'      => [
                'changelog' => !empty($release['body']) ? wpautop(esc_html((string) $release['body'])) : '',
            ],
        ];
    }

    public function fix_source_dir($source, $remote_source, $upgrader, $hook_extra = [])
    {
        if (empty($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->basename) {
            return $source;
        }

        global $wp_filesystem;
        $desired = trailingslashit($remote_source) . $this->slug . '/';
        if (untrailingslashit($source) === untrailingslashit($desired)) {
            return $source;
        }
        if ($wp_filesystem && $wp_filesystem->move($source, $desired)) {
            return $desired;
        }
        return $source;
    }

    public function clear_cache(): void
    {
        delete_transient($this->cache_key);
    }
}
