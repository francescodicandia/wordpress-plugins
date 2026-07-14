<?php
/**
 * Generic Airtable table display shortcode.
 *
 * @package ATViewTable
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ATVT_Table_Display {

    public function __construct() {
        add_shortcode( 'at_view_table', array( $this, 'render_shortcode' ) );
    }

    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'fields' => '',
        ), $atts, 'at_view_table' );

        $records = $this->get_records_cached();

        if ( empty( $records ) ) {
            return '<p>' . esc_html__( 'No Airtable rows found.', 'at-view-table' ) . '</p>';
        }

        $fields = $this->parse_fields( $atts['fields'] );

        if ( is_wp_error( $fields ) ) {
            return '<p>' . esc_html( $fields->get_error_message() ) . '</p>';
        }

        if ( empty( $fields ) ) {
            return '<p>' . esc_html__( 'No Airtable fields are available for table rendering.', 'at-view-table' ) . '</p>';
        }

        return $this->render_table( $records, $fields );
    }

    private function parse_fields( $raw ) {
        $valid = $this->get_valid_field_names_cached();

        if ( is_wp_error( $valid ) ) {
            return $valid;
        }

        if ( empty( $raw ) ) {
            return $valid;
        }

        $requested = array_map( 'trim', explode( ',', $raw ) );
        $resolved  = array();
        foreach ( $requested as $r ) {
            if ( in_array( $r, $valid, true ) ) {
                $resolved[] = $r;
            }
        }

        if ( ! empty( $resolved ) ) {
            return array_values( array_unique( $resolved ) );
        }

        return new WP_Error( 'atvt_invalid_fields', __( 'No valid Airtable fields were requested for table view.', 'at-view-table' ) );
    }

    /**
     * Fetch valid Airtable field names, cached from the schema API.
     */
    private function get_valid_field_names_cached() {
        $cache_key = 'atvt_valid_field_names';
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        $api   = new ATVT_Airtable_API();
        $fields = $api->get_table_fields();

        if ( is_wp_error( $fields ) ) {
            return $fields;
        }

        if ( ! is_array( $fields ) ) {
            return new WP_Error( 'atvt_invalid_schema_fields', __( 'Airtable schema fields could not be loaded.', 'at-view-table' ) );
        }

        $names  = wp_list_pluck( $fields, 'name' );

        set_transient( $cache_key, $names, HOUR_IN_SECONDS );
        return $names;
    }

    private function render_table( $records, $fields ) {
        ob_start();
        ?>
        <div class="atvt-table-wrap">
            <table class="atvt-table">
                <thead>
                    <tr>
                        <?php foreach ( $fields as $field_name ) : ?>
                            <th><?php echo esc_html( $field_name ); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $records as $record ) : ?>
                        <tr>
                            <?php foreach ( $fields as $field_name ) : ?>
                                <td data-label="<?php echo esc_attr( $field_name ); ?>">
                                    <?php $this->render_table_cell( $record, $field_name ); ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_table_cell( $record, $field_name ) {
        $value = isset( $record['fields'][ $field_name ] ) ? $record['fields'][ $field_name ] : '';

        if ( '' === $value && '0' !== $value ) {
            echo '<span class="atvt-table-na">&mdash;</span>';
        } elseif ( is_array( $value ) ) {
            foreach ( $value as $item ) {
                echo '<span class="atvt-tag">' . esc_html( $item ) . '</span>';
            }
        } elseif ( is_string( $value ) && filter_var( $value, FILTER_VALIDATE_URL ) ) {
            echo '<a href="' . esc_url( $value ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $value ) . '</a>';
        } else {
            echo esc_html( (string) $value );
        }
    }

    private function get_records_cached() {
        $cache_key = 'atvt_records';
        $records   = get_transient( $cache_key );

        if ( false !== $records ) {
            return $records;
        }

        $api     = new ATVT_Airtable_API();
        $records = $api->fetch_records();

        if ( ! is_array( $records ) || is_wp_error( $records ) ) {
            return array();
        }

        set_transient( $cache_key, $records, ATVT_CACHE_TTL );

        return $records;
    }
}
