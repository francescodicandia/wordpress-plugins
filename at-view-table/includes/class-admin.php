<?php
/**
 * Admin settings page for the plugin.
 *
 * @package WordPressCredits
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPCM_Admin {

    private $capability = 'activate_plugins';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_filter(
            'plugin_action_links_' . plugin_basename( WPCM_PLUGIN_DIR . 'at-view-table.php' ),
            array( $this, 'add_settings_link' )
        );
    }

    public function add_admin_menu() {
        add_submenu_page(
            'options-general.php',
            __( 'WordPress Credits Mentors', 'wpcredits-mentors' ),
            __( 'Credits Mentors', 'wpcredits-mentors' ),
            $this->capability,
            'wpcredits-mentors',
            array( $this, 'render_settings_page' )
        );
    }

    public function register_settings() {
        register_setting(
            'wpcm_settings',
            'wpcm_airtable_base_id',
            array(
                'sanitize_callback' => array( $this, 'sanitize_airtable_base_id' ),
            )
        );

        register_setting(
            'wpcm_settings',
            'wpcm_airtable_table_id',
            array(
                'sanitize_callback' => array( $this, 'sanitize_airtable_table_id' ),
            )
        );

        register_setting(
            'wpcm_settings',
            'wpcm_airtable_token',
            array(
                'sanitize_callback' => array( $this, 'sanitize_airtable_token' ),
            )
        );

        register_setting(
            'wpcm_settings',
            'wpcm_mentor_statuses',
            array(
                'sanitize_callback' => array( $this, 'sanitize_mentor_statuses' ),
            )
        );
    }

    public function sanitize_airtable_base_id( $value ) {
        $this->clear_plugin_caches(
            get_option( 'wpcm_airtable_base_id', '' ),
            get_option( 'wpcm_airtable_table_id', '' ),
            sanitize_text_field( $value ),
            isset( $_POST['wpcm_airtable_table_id'] ) ? sanitize_text_field( wp_unslash( $_POST['wpcm_airtable_table_id'] ) ) : get_option( 'wpcm_airtable_table_id', '' )
        );

        return sanitize_text_field( $value );
    }

    public function sanitize_airtable_table_id( $value ) {
        $this->clear_plugin_caches(
            get_option( 'wpcm_airtable_base_id', '' ),
            get_option( 'wpcm_airtable_table_id', '' ),
            isset( $_POST['wpcm_airtable_base_id'] ) ? sanitize_text_field( wp_unslash( $_POST['wpcm_airtable_base_id'] ) ) : get_option( 'wpcm_airtable_base_id', '' ),
            sanitize_text_field( $value )
        );

        return sanitize_text_field( $value );
    }

    public function sanitize_airtable_token( $value ) {
        $this->clear_plugin_caches();

        return sanitize_text_field( $value );
    }

    public function sanitize_mentor_statuses( $value ) {
        delete_transient( 'wpcm_mentors' );

        return sanitize_text_field( $value );
    }

    private function clear_plugin_caches( $old_base_id = '', $old_table_id = '', $new_base_id = '', $new_table_id = '' ) {
        delete_transient( 'wpcm_mentors' );
        delete_transient( 'wpcm_all_statuses' );
        delete_transient( 'wpcm_valid_field_names' );

        if ( ! empty( $old_base_id ) && ! empty( $old_table_id ) ) {
            delete_transient( 'wpcm_table_fields_' . md5( $old_base_id . '|' . $old_table_id ) );
        }

        if ( ! empty( $new_base_id ) && ! empty( $new_table_id ) ) {
            delete_transient( 'wpcm_table_fields_' . md5( $new_base_id . '|' . $new_table_id ) );
        }
    }

    public function add_settings_link( $links ) {
        $url = admin_url( 'options-general.php?page=wpcredits-mentors' );
        $links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'wpcredits-mentors' ) . '</a>';
        return $links;
    }

    public function render_settings_page() {
        if ( ! current_user_can( $this->capability ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wpcredits-mentors' ) );
        }

        $base_id  = get_option( 'wpcm_airtable_base_id', '' );
        $table_id = get_option( 'wpcm_airtable_table_id', '' );
        $token    = get_option( 'wpcm_airtable_token' );
        $statuses = get_option( 'wpcm_mentor_statuses', 'Active' );

        $all_statuses = get_transient( 'wpcm_all_statuses' );
        if ( false === $all_statuses && ! empty( $token ) ) {
            $api = new WPCM_Airtable_API();
            $result = $api->get_all_statuses();
            if ( is_array( $result ) ) {
                $all_statuses = $result;
                set_transient( 'wpcm_all_statuses', $all_statuses, DAY_IN_SECONDS );
            }
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'WordPress Credits Mentors Settings', 'wpcredits-mentors' ); ?></h1>

            <p class="description">
                <?php echo esc_html__( 'The Airtable API token was already configured from this screen. Base ID and Table ID are now also configured here instead of being stored in code.', 'wpcredits-mentors' ); ?>
            </p>

            <form method="post" action="options.php">
                <?php settings_fields( 'wpcm_settings' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wpcm_airtable_base_id"><?php echo esc_html__( 'Airtable Base ID', 'wpcredits-mentors' ); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                id="wpcm_airtable_base_id"
                                name="wpcm_airtable_base_id"
                                value="<?php echo esc_attr( $base_id ); ?>"
                                class="regular-text"
                            />
                            <p class="description">
                                <?php echo esc_html__( 'Enter the Airtable Base ID (for example: appXXXXXXXXXXXXXX).', 'wpcredits-mentors' ); ?>
                            </p>
                            <p class="description">
                                <a href="https://support.airtable.com/finding-airtable-ids" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Where do I find the Base ID?', 'wpcredits-mentors' ); ?></a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wpcm_airtable_table_id"><?php echo esc_html__( 'Airtable Table ID', 'wpcredits-mentors' ); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                id="wpcm_airtable_table_id"
                                name="wpcm_airtable_table_id"
                                value="<?php echo esc_attr( $table_id ); ?>"
                                class="regular-text"
                            />
                            <p class="description">
                                <?php echo esc_html__( 'Enter the Airtable Table ID (for example: tblXXXXXXXXXXXXXX).', 'wpcredits-mentors' ); ?>
                            </p>
                            <p class="description">
                                <a href="https://support.airtable.com/finding-airtable-ids" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Where do I find the Table ID?', 'wpcredits-mentors' ); ?></a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wpcm_airtable_token"><?php echo esc_html__( 'Airtable API Token', 'wpcredits-mentors' ); ?></label>
                        </th>
                        <td>
                            <input
                                type="password"
                                id="wpcm_airtable_token"
                                name="wpcm_airtable_token"
                                value="<?php echo esc_attr( $token ); ?>"
                                class="regular-text"
                            />
                            <p class="description">
                                <?php echo esc_html__( 'Enter your Airtable personal access token.', 'wpcredits-mentors' ); ?>
                            </p>
                            <p class="description">
                                <a href="https://airtable.com/create/tokens" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Create or manage Airtable personal access tokens', 'wpcredits-mentors' ); ?></a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wpcm_mentor_statuses"><?php echo esc_html__( 'Allowed Statuses', 'wpcredits-mentors' ); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                id="wpcm_mentor_statuses"
                                name="wpcm_mentor_statuses"
                                value="<?php echo esc_attr( $statuses ); ?>"
                                class="regular-text"
                            />
                            <p class="description">
                                <?php echo esc_html__( 'Comma-separated list of Status values to include. Any mentor whose Status field does not match is excluded.', 'wpcredits-mentors' ); ?>
                            </p>
                            <?php if ( ! empty( $all_statuses ) ) : ?>
                                <p class="description">
                                    <?php echo esc_html__( 'Available statuses in the Airtable database:', 'wpcredits-mentors' ); ?>
                                    <strong><?php echo esc_html( implode( ', ', $all_statuses ) ); ?></strong>
                                    <button
                                        type="button"
                                        class="button button-small"
                                        id="wpcm-refresh-statuses"
                                        data-nonce="<?php echo esc_attr( wp_create_nonce( 'wpcm_refresh_statuses' ) ); ?>"
                                        style="margin-left: 8px;"
                                    ><?php echo esc_html__( 'Refresh', 'wpcredits-mentors' ); ?></button>
                                    <span id="wpcm-statuses-result" style="margin-left: 6px;"></span>
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
                $api = new WPCM_Airtable_API();
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
                <h2><?php echo esc_html__( 'Available Airtable Fields', 'wpcredits-mentors' ); ?></h2>
                <p><?php echo esc_html__( 'These text/URL fields from your Airtable table can be used in the shortcode:', 'wpcredits-mentors' ); ?></p>
                <table class="widefat striped" style="max-width: 600px;">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__( 'Field Name', 'wpcredits-mentors' ); ?></th>
                            <th><?php echo esc_html__( 'Type', 'wpcredits-mentors' ); ?></th>
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
                <h2><?php echo esc_html__( 'Test Connection', 'wpcredits-mentors' ); ?></h2>
                <p>
                    <button
                        type="button"
                        class="button"
                        id="wpcm-test-connection"
                        data-nonce="<?php echo esc_attr( wp_create_nonce( 'wpcm_test_connection' ) ); ?>"
                    >
                        <?php echo esc_html__( 'Test Airtable Connection', 'wpcredits-mentors' ); ?>
                    </button>
                    <span id="wpcm-test-result" style="margin-left: 10px;"></span>
                </p>

                <script>
                (function() {
                    var btn = document.getElementById('wpcm-test-connection');
                    var result = document.getElementById('wpcm-test-result');

                    btn.addEventListener('click', function() {
                        result.textContent = '<?php echo esc_js( __( 'Testing...', 'wpcredits-mentors' ) ); ?>';
                        result.style.color = '#666';

                        fetch(ajaxurl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({
                                action: 'wpcm_test_airtable',
                                _ajax_nonce: btn.getAttribute('data-nonce')
                            })
                        })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            result.textContent = data.data;
                            result.style.color = data.success ? '#46b450' : '#dc3232';
                        })
                        .catch(function() {
                            result.textContent = '<?php echo esc_js( __( 'Request failed.', 'wpcredits-mentors' ) ); ?>';
                            result.style.color = '#dc3232';
                        });
                    });
                })();
                </script>
            <?php endif; ?>

            <script>
            (function() {
                var btn = document.getElementById('wpcm-refresh-statuses');
                if ( ! btn ) return;
                var result = document.getElementById('wpcm-statuses-result');

                btn.addEventListener('click', function() {
                    result.textContent = '<?php echo esc_js( __( 'Fetching...', 'wpcredits-mentors' ) ); ?>';
                    result.style.color = '#666';

                    fetch(ajaxurl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'wpcm_refresh_statuses',
                            _ajax_nonce: btn.getAttribute('data-nonce')
                        })
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if ( data.success ) {
                            var strong = btn.parentNode.querySelector('strong');
                            if ( strong ) strong.textContent = data.data;
                            result.textContent = '<?php echo esc_js( __( 'Updated.', 'wpcredits-mentors' ) ); ?>';
                            result.style.color = '#46b450';
                        } else {
                            result.textContent = data.data;
                            result.style.color = '#dc3232';
                        }
                    })
                    .catch(function() {
                        result.textContent = '<?php echo esc_js( __( 'Request failed.', 'wpcredits-mentors' ) ); ?>';
                        result.style.color = '#dc3232';
                    });
                });
            })();
            </script>

            <hr />
            <h2><?php echo esc_html__( 'Shortcode Usage', 'wpcredits-mentors' ); ?></h2>
            <p><?php echo esc_html__( 'Use the following shortcode to display mentors on any page or post:', 'wpcredits-mentors' ); ?></p>
            <p><code>[wpcredits_mentors]</code></p>
            <p><?php echo esc_html__( 'Optional parameters:', 'wpcredits-mentors' ); ?></p>
            <ul style="list-style: disc; padding-left: 24px;">
                    <li>
                        <strong><code>columns</code></strong>
                        &mdash;
                        <?php echo esc_html__( 'Number of columns in grid view (1 to 4). Default: 3.', 'wpcredits-mentors' ); ?>
                        <br /><code>[wpcredits_mentors columns=2]</code>
                    </li>
                    <li>
                        <strong><code>view</code></strong>
                        &mdash;
                        <?php echo esc_html__( 'Display layout: grid (cards) or table (one row per mentor). Default: grid.', 'wpcredits-mentors' ); ?>
                        <br /><code>[wpcredits_mentors view=table]</code>
                    </li>
                    <li>
                        <strong><code>fields</code></strong>
                        &mdash;
                        <?php echo esc_html__( 'Columns to show in table view. Comma-separated list of Airtable field names (see table above). Backward-compatible aliases: name, email, expertise, hours, sponsor, profile, status, company. Default: Full Name, Email, WordPress profile, Contribution Area - Expertise, Available hours per week, Sponsor company.', 'wpcredits-mentors' ); ?>
                        <br /><code>[wpcredits_mentors view=table fields="Full Name,Email,Sponsor company"]</code>
                    </li>
            </ul>
        </div>
        <?php
    }
}

add_action( 'wp_ajax_wpcm_test_airtable', 'wpcm_ajax_test_airtable' );
function wpcm_ajax_test_airtable() {
    check_ajax_referer( 'wpcm_test_connection' );

    if ( ! current_user_can( 'activate_plugins' ) ) {
        wp_send_json_error( __( 'Permission denied.', 'wpcredits-mentors' ) );
    }

    $api = new WPCM_Airtable_API();
    $result = $api->fetch_mentors();

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message() );
    }

    $count = count( $result );

    if ( 0 === $count ) {
        $allowed_statuses = get_option( 'wpcm_mentor_statuses', 'Active' );
        $available_statuses = $api->get_all_statuses();

        if ( is_array( $available_statuses ) && ! empty( $available_statuses ) ) {
            wp_send_json_success(
                sprintf(
                    __( 'Connection successful, but 0 mentors matched Allowed Statuses (%1$s). Available statuses in this table: %2$s', 'wpcredits-mentors' ),
                    $allowed_statuses,
                    implode( ', ', $available_statuses )
                )
            );
        }
    }

    wp_send_json_success(
        sprintf(
            __( 'Connection successful. %d active mentors found.', 'wpcredits-mentors' ),
            $count
        )
    );
}

add_action( 'wp_ajax_wpcm_refresh_statuses', 'wpcm_ajax_refresh_statuses' );
function wpcm_ajax_refresh_statuses() {
    check_ajax_referer( 'wpcm_refresh_statuses' );

    if ( ! current_user_can( 'activate_plugins' ) ) {
        wp_send_json_error( __( 'Permission denied.', 'wpcredits-mentors' ) );
    }

    $api   = new WPCM_Airtable_API();
    $result = $api->get_all_statuses();

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message() );
    }

    set_transient( 'wpcm_all_statuses', $result, DAY_IN_SECONDS );

    wp_send_json_success( implode( ', ', $result ) );
}
