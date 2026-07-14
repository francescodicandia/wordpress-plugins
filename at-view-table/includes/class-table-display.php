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
            'table_id'       => '',
            'fields'         => '',
            'filter_field'   => '',
            'filter_value'   => '',
            'sort_field'     => '',
            'sort_direction' => '',
            'limit'          => '',
        ), $atts, 'at_view_table' );

        $query_args = $this->normalize_shortcode_args( $atts );

        if ( is_wp_error( $query_args ) ) {
            return '<p>' . esc_html( $query_args->get_error_message() ) . '</p>';
        }

        $fields = $this->parse_fields( $query_args['fields'], $query_args['table_id'] );

        if ( is_wp_error( $fields ) ) {
            return '<p>' . esc_html( $fields->get_error_message() ) . '</p>';
        }

        $query_args['fields'] = $fields;

        $records = $this->get_records_cached( $query_args );

        if ( is_wp_error( $records ) ) {
            return '<p>' . esc_html( $records->get_error_message() ) . '</p>';
        }

        if ( empty( $records ) ) {
            return '<p>' . esc_html__( 'No Airtable rows found.', 'at-view-table' ) . '</p>';
        }

        return $this->render_table( $records, $fields );
    }

    private function normalize_shortcode_args( $atts ) {
        $table_id = sanitize_text_field( $atts['table_id'] );

        if ( '' === $table_id ) {
            return new WP_Error( 'atvt_missing_table_id', __( 'The table_id attribute is required.', 'at-view-table' ) );
        }

        $fields = trim( $atts['fields'] );

        if ( '' === $fields ) {
            return new WP_Error( 'atvt_missing_fields', __( 'The fields attribute is required.', 'at-view-table' ) );
        }

        $filter_field = sanitize_text_field( $atts['filter_field'] );
        $filter_value = sanitize_text_field( $atts['filter_value'] );

        if ( ( '' === $filter_field && '' !== $filter_value ) || ( '' !== $filter_field && '' === $filter_value ) ) {
            return new WP_Error( 'atvt_incomplete_filter', __( 'Use filter_field and filter_value together.', 'at-view-table' ) );
        }

        $sort_direction = strtolower( sanitize_text_field( $atts['sort_direction'] ) );

        if ( '' !== $sort_direction && ! in_array( $sort_direction, array( 'asc', 'desc' ), true ) ) {
            return new WP_Error( 'atvt_invalid_sort_direction', __( 'sort_direction must be either asc or desc.', 'at-view-table' ) );
        }

        $limit = '' === $atts['limit'] ? intval( get_option( 'atvt_default_limit', 25 ) ) : intval( $atts['limit'] );
        $limit = max( 1, $limit );

        return array(
            'table_id'       => $table_id,
            'fields'         => $fields,
            'filter_field'   => $filter_field,
            'filter_value'   => $filter_value,
            'sort_field'     => sanitize_text_field( $atts['sort_field'] ),
            'sort_direction' => '' === $sort_direction ? 'asc' : $sort_direction,
            'limit'          => $limit,
        );
    }

    private function parse_fields( $raw, $table_id ) {
        $valid = $this->get_valid_field_names_cached( $table_id );

        if ( is_wp_error( $valid ) ) {
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
    private function get_valid_field_names_cached( $table_id ) {
        $cache_key = 'atvt_valid_field_names_' . md5( $table_id );
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        $api   = new ATVT_Airtable_API();
        $fields = $api->get_table_fields( $table_id );

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
                            <th class="atvt-sortable" data-field="<?php echo esc_attr( $field_name ); ?>" tabindex="0"><?php echo esc_html( $field_name ); ?></th>
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

    private function get_records_cached( $query_args ) {
        $cache_key = 'atvt_records_' . md5( wp_json_encode( $query_args ) );
        $records   = get_transient( $cache_key );

        if ( false !== $records ) {
            return $records;
        }

        $api     = new ATVT_Airtable_API();
        $records = $api->fetch_records( $query_args );

        if ( is_wp_error( $records ) ) {
            return $records;
        }

        if ( ! is_array( $records ) ) {
            return array();
        }

        set_transient( $cache_key, $records, ATVT_CACHE_TTL );

        return $records;
    }
}
