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
            'atvt_airtable_table_id',
            array(
                'sanitize_callback' => array( $this, 'sanitize_airtable_table_id' ),
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
            'atvt_mentor_statuses',
            array(
                'sanitize_callback' => array( $this, 'sanitize_mentor_statuses' ),
            )
        );
    }

    public function sanitize_airtable_base_id( $value ) {
        $this->clear_plugin_caches(
            get_option( 'atvt_airtable_base_id', '' ),
            get_option( 'atvt_airtable_table_id', '' ),
            sanitize_text_field( $value ),
            isset( $_POST['atvt_airtable_table_id'] ) ? sanitize_text_field( wp_unslash( $_POST['atvt_airtable_table_id'] ) ) : get_option( 'atvt_airtable_table_id', '' )
        );

        return sanitize_text_field( $value );
    }

    public function sanitize_airtable_table_id( $value ) {
        $this->clear_plugin_caches(
            get_option( 'atvt_airtable_base_id', '' ),
            get_option( 'atvt_airtable_table_id', '' ),
            isset( $_POST['atvt_airtable_base_id'] ) ? sanitize_text_field( wp_unslash( $_POST['atvt_airtable_base_id'] ) ) : get_option( 'atvt_airtable_base_id', '' ),
            sanitize_text_field( $value )
        );

        return sanitize_text_field( $value );
    }

    public function sanitize_airtable_token( $value ) {
        $this->clear_plugin_caches();

        return sanitize_text_field( $value );
    }

    public function sanitize_mentor_statuses( $value ) {
        delete_transient( 'atvt_mentors' );

        return sanitize_text_field( $value );
    }

    private function clear_plugin_caches( $old_base_id = '', $old_table_id = '', $new_base_id = '', $new_table_id = '' ) {
        delete_transient( 'atvt_mentors' );
        delete_transient( 'atvt_all_statuses' );
        delete_transient( 'atvt_valid_field_names' );

        if ( ! empty( $old_base_id ) && ! empty( $old_table_id ) ) {
            delete_transient( 'atvt_table_fields_' . md5( $old_base_id . '|' . $old_table_id ) );
        }

        if ( ! empty( $new_base_id ) && ! empty( $new_table_id ) ) {
            delete_transient( 'atvt_table_fields_' . md5( $new_base_id . '|' . $new_table_id ) );
        }
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

        $base_id  = get_option( 'atvt_airtable_base_id', '' );
        $table_id = get_option( 'atvt_airtable_table_id', '' );
        $token    = get_option( 'atvt_airtable_token' );
        $statuses = get_option( 'atvt_mentor_statuses', 'Active' );

        $all_statuses = get_transient( 'atvt_all_statuses' );
        if ( false === $all_statuses && ! empty( $token ) ) {
            $api = new ATVT_Airtable_API();
            $result = $api->get_all_statuses();
            if ( is_array( $result ) ) {
                $all_statuses = $result;
                set_transient( 'atvt_all_statuses', $all_statuses, DAY_IN_SECONDS );
            }
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'AT View Table Settings', 'at-view-table' ); ?></h1>

            <p class="description">
                <?php echo esc_html__( 'Configure the Airtable connection used by AT View Table from this screen.', 'at-view-table' ); ?>
            </p>

            <form method="post" action="options.php">
                <?php settings_fields( 'atvt_settings' ); ?>
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
                            <label for="atvt_airtable_table_id"><?php echo esc_html__( 'Airtable Table ID', 'at-view-table' ); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                id="atvt_airtable_table_id"
                                name="atvt_airtable_table_id"
                                value="<?php echo esc_attr( $table_id ); ?>"
                                class="regular-text"
                            />
                            <p class="description">
                                <?php echo esc_html__( 'Enter the Airtable Table ID (for example: tblXXXXXXXXXXXXXX).', 'at-view-table' ); ?>
                            </p>
                            <p class="description">
                                <a href="https://support.airtable.com/finding-airtable-ids" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Where do I find the Table ID?', 'at-view-table' ); ?></a>
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
                    <tr>
                        <th scope="row">
                            <label for="atvt_mentor_statuses"><?php echo esc_html__( 'Allowed Statuses', 'at-view-table' ); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                id="atvt_mentor_statuses"
                                name="atvt_mentor_statuses"
                                value="<?php echo esc_attr( $statuses ); ?>"
                                class="regular-text"
                            />
                            <p class="description">
                                <?php echo esc_html__( 'Comma-separated list of Status values to include. Any row whose Status field does not match is excluded.', 'at-view-table' ); ?>
                            </p>
                            <?php if ( ! empty( $all_statuses ) ) : ?>
                                <p class="description">
                                    <?php echo esc_html__( 'Available statuses in the Airtable database:', 'at-view-table' ); ?>
                                    <strong><?php echo esc_html( implode( ', ', $all_statuses ) ); ?></strong>
                                    <button
                                        type="button"
                                        class="button button-small"
                                        id="atvt-refresh-statuses"
                                        data-nonce="<?php echo esc_attr( wp_create_nonce( 'atvt_refresh_statuses' ) ); ?>"
                                        style="margin-left: 8px;"
                                    ><?php echo esc_html__( 'Refresh', 'at-view-table' ); ?></button>
                                    <span id="atvt-statuses-result" style="margin-left: 6px;"></span>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <?php
            $display_field_types = array( 'singleLineText', 'multilineText', 'richText', 'email', 'url', 'phoneNumber', 'singleSelect', 'multipleSelects' );
            $available_fields = array();
            $fields_error = null;
            if ( ! empty( $token ) ) {
                $api = new ATVT_Airtable_API();
                $all_fields = $api->get_table_fields();
                if ( is_wp_error( $all_fields ) ) {
                    $fields_error = $all_fields->get_error_message();
                } elseif ( is_array( $all_fields ) ) {
                    foreach ( $all_fields as $f ) {
                        if ( in_array( $f['type'], $display_field_types, true ) ) {
                            $available_fields[] = $f;
                        }
                    }
                }
            }
            ?>

            <?php if ( ! empty( $fields_error ) ) : ?>
                <p class="notice notice-error inline"><span><?php echo esc_html( $fields_error ); ?></span></p>
            <?php endif; ?>

            <?php if ( ! empty( $available_fields ) ) : ?>
                <hr />
                <h2><?php echo esc_html__( 'Available Airtable Fields', 'at-view-table' ); ?></h2>
                <p><?php echo esc_html__( 'These text/URL fields from your Airtable table can be used in the shortcode:', 'at-view-table' ); ?></p>
                <table class="widefat striped" style="max-width: 600px;">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__( 'Field Name', 'at-view-table' ); ?></th>
                            <th><?php echo esc_html__( 'Type', 'at-view-table' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $available_fields as $field ) : ?>
                            <tr>
                                <td><code><?php echo esc_html( $field['name'] ); ?></code></td>
                                <td><?php echo esc_html( $field['type'] ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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

            <script>
            (function() {
                var btn = document.getElementById('atvt-refresh-statuses');
                if ( ! btn ) return;
                var result = document.getElementById('atvt-statuses-result');

                btn.addEventListener('click', function() {
                    result.textContent = '<?php echo esc_js( __( 'Fetching...', 'at-view-table' ) ); ?>';
                    result.style.color = '#666';

                    fetch(ajaxurl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'atvt_refresh_statuses',
                            _ajax_nonce: btn.getAttribute('data-nonce')
                        })
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if ( data.success ) {
                            var strong = btn.parentNode.querySelector('strong');
                            if ( strong ) strong.textContent = data.data;
                            result.textContent = '<?php echo esc_js( __( 'Updated.', 'at-view-table' ) ); ?>';
                            result.style.color = '#46b450';
                        } else {
                            result.textContent = data.data;
                            result.style.color = '#dc3232';
                        }
                    })
                    .catch(function() {
                        result.textContent = '<?php echo esc_js( __( 'Request failed.', 'at-view-table' ) ); ?>';
                        result.style.color = '#dc3232';
                    });
                });
            })();
            </script>

            <hr />
            <h2><?php echo esc_html__( 'Shortcode Usage', 'at-view-table' ); ?></h2>
            <p><?php echo esc_html__( 'Use the following shortcode to display Airtable data on any page or post:', 'at-view-table' ); ?></p>
            <p><code>[at_view_table table_id="tblXXXXXXXXXXXXXX" fields="Name,Status"]</code></p>
            <p><?php echo esc_html__( 'Optional parameters:', 'at-view-table' ); ?></p>
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
                    <?php echo esc_html__( 'Optional. Maximum number of rows to show. If omitted, the global default limit is used.', 'at-view-table' ); ?>
                    <br /><code>[at_view_table table_id="tblXXXXXXXXXXXXXX" fields="Name,Status" limit="25"]</code>
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
    $result = $api->fetch_records();

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message() );
    }

    wp_send_json_success(
        sprintf(
            __( 'Connection successful. %d rows found.', 'at-view-table' ),
            count( $result )
        )
    );
}

add_action( 'wp_ajax_atvt_refresh_statuses', 'atvt_ajax_refresh_statuses' );
function atvt_ajax_refresh_statuses() {
    check_ajax_referer( 'atvt_refresh_statuses' );

    if ( ! current_user_can( 'activate_plugins' ) ) {
        wp_send_json_error( __( 'Permission denied.', 'at-view-table' ) );
    }

    $api   = new ATVT_Airtable_API();
    $result = $api->get_all_statuses();

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message() );
    }

    set_transient( 'atvt_all_statuses', $result, DAY_IN_SECONDS );

    wp_send_json_success( implode( ', ', $result ) );
}
