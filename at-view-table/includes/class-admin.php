<?php
/**
 * Admin settings page for the plugin.
 *
 * @package ATViewTable
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ATVT_Admin {

    private $capability = 'activate_plugins';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_filter(
            'plugin_action_links_' . plugin_basename( ATVT_PLUGIN_DIR . 'at-view-table.php' ),
            array( $this, 'add_settings_link' )
        );
    }

    public function add_admin_menu() {
        add_submenu_page(
            'options-general.php',
            __( 'AT View Table', 'at-view-table' ),
            __( 'AT View Table', 'at-view-table' ),
            $this->capability,
            'at-view-table',
            array( $this, 'render_settings_page' )
        );
    }

    public function register_settings() {
        register_setting(
            'atvt_settings',
            'atvt_airtable_base_id',
            array(
                'sanitize_callback' => array( $this, 'sanitize_airtable_base_id' ),
            )
        );

        register_setting(
            'atvt_settings',
            'atvt_airtable_token',
            array(
                'sanitize_callback' => array( $this, 'sanitize_airtable_token' ),
            )
        );

        register_setting(
            'atvt_settings',
            'atvt_default_limit',
            array(
                'sanitize_callback' => array( $this, 'sanitize_default_limit' ),
            )
        );

        register_setting(
            'atvt_settings',
            'atvt_cache_ttl',
            array(
                'sanitize_callback' => array( $this, 'sanitize_cache_ttl' ),
            )
        );
    }

    public function sanitize_airtable_base_id( $value ) {
        $this->clear_plugin_caches();

        return sanitize_text_field( $value );
    }

    public function sanitize_airtable_token( $value ) {
        $this->clear_plugin_caches();

        return sanitize_text_field( $value );
    }

    public function sanitize_default_limit( $value ) {
        $limit = max( 1, intval( $value ) );

        return $limit;
    }

    public function sanitize_cache_ttl( $value ) {
        $this->clear_plugin_caches();

        return max( 1, intval( $value ) );
    }

    private function clear_plugin_caches() {
        delete_transient( 'atvt_valid_field_names' );
    }

    public function add_settings_link( $links ) {
        $url = admin_url( 'options-general.php?page=at-view-table' );
        $links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'at-view-table' ) . '</a>';
        return $links;
    }

    public function render_settings_page() {
        if ( ! current_user_can( $this->capability ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'at-view-table' ) );
        }

        $base_id       = get_option( 'atvt_airtable_base_id', '' );
        $token         = get_option( 'atvt_airtable_token' );
        $default_limit = max( 1, intval( get_option( 'atvt_default_limit', 25 ) ) );
        $cache_ttl     = max( 1, intval( get_option( 'atvt_cache_ttl', 60 ) ) );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'AT View Table Settings', 'at-view-table' ); ?></h1>

            <p class="description">
                <?php echo esc_html__( 'Configure the Airtable connection used by AT View Table from this screen.', 'at-view-table' ); ?>
            </p>

            <form method="post" action="options.php">
                <?php settings_fields( 'atvt_settings' ); ?>
                <h2><?php echo esc_html__( 'Airtable Connection', 'at-view-table' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="atvt_airtable_base_id"><?php echo esc_html__( 'Airtable Base ID', 'at-view-table' ); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                id="atvt_airtable_base_id"
                                name="atvt_airtable_base_id"
                                value="<?php echo esc_attr( $base_id ); ?>"
                                class="regular-text"
                            />
                            <p class="description">
                                <?php echo esc_html__( 'Enter the Airtable Base ID (for example: appXXXXXXXXXXXXXX).', 'at-view-table' ); ?>
                            </p>
                            <p class="description">
                                <a href="https://support.airtable.com/finding-airtable-ids" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Where do I find the Base ID?', 'at-view-table' ); ?></a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="atvt_airtable_token"><?php echo esc_html__( 'Airtable API Token', 'at-view-table' ); ?></label>
                        </th>
                        <td>
                            <input
                                type="password"
                                id="atvt_airtable_token"
                                name="atvt_airtable_token"
                                value="<?php echo esc_attr( $token ); ?>"
                                class="regular-text"
                            />
                            <p class="description">
                                <?php echo esc_html__( 'Enter your Airtable personal access token.', 'at-view-table' ); ?>
                            </p>
                            <p class="description">
                                <a href="https://airtable.com/create/tokens" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Create or manage Airtable personal access tokens', 'at-view-table' ); ?></a>
                            </p>
                        </td>
                    </tr>
                </table>

                <h2><?php echo esc_html__( 'Default Plugin Behavior', 'at-view-table' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="atvt_default_limit"><?php echo esc_html__( 'Default Limit', 'at-view-table' ); ?></label>
                        </th>
                        <td>
                            <input
                                type="number"
                                min="1"
                                step="1"
                                id="atvt_default_limit"
                                name="atvt_default_limit"
                                value="<?php echo esc_attr( $default_limit ); ?>"
                                class="small-text"
                            />
                            <p class="description">
                                <?php echo esc_html__( 'Default maximum number of rows to show when the shortcode does not define a limit. Default: 25.', 'at-view-table' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="atvt_cache_ttl"><?php echo esc_html__( 'Cache TTL (minutes)', 'at-view-table' ); ?></label>
                        </th>
                        <td>
                            <input
                                type="number"
                                min="1"
                                step="1"
                                id="atvt_cache_ttl"
                                name="atvt_cache_ttl"
                                value="<?php echo esc_attr( $cache_ttl ); ?>"
                                class="small-text"
                            />
                            <p class="description">
                                <?php echo esc_html__( 'How long to cache Airtable API responses locally (in minutes). Lower values show fresher data but increase API calls. Default: 60.', 'at-view-table' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <?php if ( ! empty( $base_id ) && ! empty( $token ) ) : ?>
                <hr />
                <h2><?php echo esc_html__( 'Inspect Table', 'at-view-table' ); ?></h2>
                <p class="description">
                    <?php echo esc_html__( 'Enter an Airtable Table ID to inspect its fields and copy the exact column names into the shortcode fields attribute.', 'at-view-table' ); ?>
                </p>
                <p>
                    <label for="atvt-inspect-table-id" class="screen-reader-text"><?php echo esc_html__( 'Table ID to inspect', 'at-view-table' ); ?></label>
                    <input
                        type="text"
                        id="atvt-inspect-table-id"
                        value=""
                        class="regular-text"
                        placeholder="tblXXXXXXXXXXXXXX"
                    />
                    <button
                        type="button"
                        class="button"
                        id="atvt-inspect-table"
                        data-nonce="<?php echo esc_attr( wp_create_nonce( 'atvt_inspect_table' ) ); ?>"
                    >
                        <?php echo esc_html__( 'Inspect Table', 'at-view-table' ); ?>
                    </button>
                    <span id="atvt-inspect-result" style="margin-left: 10px;"></span>
                </p>
                <div id="atvt-inspect-output"></div>

                <script>
                (function() {
                    var btn = document.getElementById('atvt-inspect-table');
                    var input = document.getElementById('atvt-inspect-table-id');
                    var result = document.getElementById('atvt-inspect-result');
                    var output = document.getElementById('atvt-inspect-output');

                    btn.addEventListener('click', function() {
                        var tableId = input.value.trim();

                        if (!tableId) {
                            result.textContent = '<?php echo esc_js( __( 'Enter a Table ID first.', 'at-view-table' ) ); ?>';
                            result.style.color = '#dc3232';
                            output.innerHTML = '';
                            return;
                        }

                        result.textContent = '<?php echo esc_js( __( 'Inspecting...', 'at-view-table' ) ); ?>';
                        result.style.color = '#666';
                        output.innerHTML = '';

                        fetch(ajaxurl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({
                                action: 'atvt_inspect_table',
                                _ajax_nonce: btn.getAttribute('data-nonce'),
                                table_id: tableId
                            })
                        })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            result.textContent = data.data.message;
                            result.style.color = data.success ? '#46b450' : '#dc3232';
                            output.innerHTML = data.success && data.data.html ? data.data.html : '';
                        })
                        .catch(function() {
                            result.textContent = '<?php echo esc_js( __( 'Request failed.', 'at-view-table' ) ); ?>';
                            result.style.color = '#dc3232';
                            output.innerHTML = '';
                        });
                    });
                })();
                </script>
            <?php endif; ?>

            <?php if ( ! empty( $token ) ) : ?>
                <hr />
                <h2><?php echo esc_html__( 'Test Connection', 'at-view-table' ); ?></h2>
                <p>
                    <button
                        type="button"
                        class="button"
                        id="atvt-test-connection"
                        data-nonce="<?php echo esc_attr( wp_create_nonce( 'atvt_test_connection' ) ); ?>"
                    >
                        <?php echo esc_html__( 'Test Airtable Connection', 'at-view-table' ); ?>
                    </button>
                    <span id="atvt-test-result" style="margin-left: 10px;"></span>
                </p>

                <script>
                (function() {
                    var btn = document.getElementById('atvt-test-connection');
                    var result = document.getElementById('atvt-test-result');

                    btn.addEventListener('click', function() {
                        result.textContent = '<?php echo esc_js( __( 'Testing...', 'at-view-table' ) ); ?>';
                        result.style.color = '#666';

                        fetch(ajaxurl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({
                                action: 'atvt_test_airtable',
                                _ajax_nonce: btn.getAttribute('data-nonce')
                            })
                        })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            result.textContent = data.data;
                            result.style.color = data.success ? '#46b450' : '#dc3232';
                        })
                        .catch(function() {
                            result.textContent = '<?php echo esc_js( __( 'Request failed.', 'at-view-table' ) ); ?>';
                            result.style.color = '#dc3232';
                        });
                    });
                })();
                </script>
            <?php endif; ?>

            <hr />
            <h2><?php echo esc_html__( 'How To Use', 'at-view-table' ); ?></h2>
            <p><?php echo esc_html__( 'Use the following shortcode to display Airtable data on any page or post:', 'at-view-table' ); ?></p>
            <p><code>[at_view_table table_id="tblXXXXXXXXXXXXXX" fields="Name,Status"]</code></p>
            <p><?php echo esc_html__( 'Available shortcode parameters:', 'at-view-table' ); ?></p>
            <ul style="list-style: disc; padding-left: 24px;">
                <li>
                    <strong><code>table_id</code></strong>
                    &mdash;
                    <?php echo esc_html__( 'Required. Airtable Table ID to query.', 'at-view-table' ); ?>
                </li>
                <li>
                    <strong><code>fields</code></strong>
                    &mdash;
                    <?php echo esc_html__( 'Required. Comma-separated list of Airtable field names to show in the table.', 'at-view-table' ); ?>
                    <br /><code>[at_view_table table_id="tblXXXXXXXXXXXXXX" fields="Name,Email,Status"]</code>
                </li>
                <li>
                    <strong><code>filter_field</code></strong> / <strong><code>filter_value</code></strong>
                    &mdash;
                    <?php echo esc_html__( 'Optional. Show only rows where the selected field matches the selected value.', 'at-view-table' ); ?>
                    <br /><code>[at_view_table table_id="tblXXXXXXXXXXXXXX" fields="Name,Status" filter_field="Status" filter_value="Active"]</code>
                </li>
                <li>
                    <strong><code>sort_field</code></strong> / <strong><code>sort_direction</code></strong>
                    &mdash;
                    <?php echo esc_html__( 'Optional. Sort rows by one field using asc or desc.', 'at-view-table' ); ?>
                    <br /><code>[at_view_table table_id="tblXXXXXXXXXXXXXX" fields="Name,Status" sort_field="Name" sort_direction="asc"]</code>
                </li>
                <li>
                    <strong><code>limit</code></strong>
                    &mdash;
                    <?php echo esc_html__( 'Optional. Maximum number of rows to fetch from Airtable. If omitted, the global default limit is used.', 'at-view-table' ); ?>
                    <br /><code>[at_view_table table_id="tblXXXXXXXXXXXXXX" fields="Name,Status" limit="100"]</code>
                </li>
                <li>
                    <strong><code>page_size</code></strong>
                    &mdash;
                    <?php echo esc_html__( 'Optional. Rows per page for client-side pagination. If omitted, all rows are shown on one page.', 'at-view-table' ); ?>
                    <br /><code>[at_view_table table_id="tblXXXXXXXXXXXXXX" fields="Name,Status" limit="100" page_size="25"]</code>
                </li>
            </ul>
        </div>
        <?php
    }
}

add_action( 'wp_ajax_atvt_test_airtable', 'atvt_ajax_test_airtable' );
function atvt_ajax_test_airtable() {
    check_ajax_referer( 'atvt_test_connection' );

    if ( ! current_user_can( 'activate_plugins' ) ) {
        wp_send_json_error( __( 'Permission denied.', 'at-view-table' ) );
    }

    $api = new ATVT_Airtable_API();
    $result = $api->test_connection();

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message() );
    }

    wp_send_json_success(
        sprintf(
            /* translators: %d: number of Airtable tables returned by the base schema. */
            __( 'Connection successful. The Airtable base is reachable and returned %d table definitions.', 'at-view-table' ),
            isset( $result['tables'] ) && is_array( $result['tables'] ) ? count( $result['tables'] ) : 0
        )
    );
}

add_action( 'wp_ajax_atvt_inspect_table', 'atvt_ajax_inspect_table' );
function atvt_ajax_inspect_table() {
    check_ajax_referer( 'atvt_inspect_table' );

    if ( ! current_user_can( 'activate_plugins' ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Permission denied.', 'at-view-table' ),
            )
        );
    }

    $table_id = isset( $_POST['table_id'] ) ? sanitize_text_field( wp_unslash( $_POST['table_id'] ) ) : '';

    if ( '' === $table_id ) {
        wp_send_json_error(
            array(
                'message' => __( 'The table_id parameter is required.', 'at-view-table' ),
            )
        );
    }

    $api    = new ATVT_Airtable_API();
    $fields = $api->get_table_fields( $table_id );

    if ( is_wp_error( $fields ) ) {
        wp_send_json_error(
            array(
                'message' => $fields->get_error_message(),
            )
        );
    }

    $field_names = wp_list_pluck( $fields, 'name' );

    ob_start();
    ?>
    <table class="widefat striped" style="max-width: 720px; margin-top: 12px;">
        <thead>
            <tr>
                <th><?php echo esc_html__( 'Field Name', 'at-view-table' ); ?></th>
                <th><?php echo esc_html__( 'Type', 'at-view-table' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $fields as $field ) : ?>
                <tr>
                    <td><code><?php echo esc_html( $field['name'] ); ?></code></td>
                    <td><?php echo esc_html( $field['type'] ); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p><strong><?php echo esc_html__( 'Copy for fields:', 'at-view-table' ); ?></strong> <code><?php echo esc_html( implode( ',', $field_names ) ); ?></code></p>
    <?php
    $html = ob_get_clean();

    wp_send_json_success(
        array(
            'message' => __( 'Table schema loaded successfully.', 'at-view-table' ),
            'html'    => $html,
        )
    );
}
