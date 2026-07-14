<?php
/**
 * Airtable API wrapper for fetching generic Airtable records.
 *
 * @package ATViewTable
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ATVT_Airtable_API {

    private $token;
    private $base_id;
    private $table_id;

    public function __construct() {
        $this->token    = get_option( 'atvt_airtable_token', '' );
        $this->base_id  = get_option( 'atvt_airtable_base_id', '' );
        $this->table_id = get_option( 'atvt_airtable_table_id', '' );
    }

    public function fetch_records( $args = array() ) {
        $table_id = isset( $args['table_id'] ) ? sanitize_text_field( $args['table_id'] ) : '';
        $config_error = $this->validate_configuration( $table_id );

        if ( is_wp_error( $config_error ) ) {
            return $config_error;
        }

        $resolved_table_id = $this->get_resolved_table_id( $table_id );
        $limit             = isset( $args['limit'] ) ? max( 1, intval( $args['limit'] ) ) : 0;
        $records = array();
        $offset  = '';
        $url     = "https://api.airtable.com/v0/{$this->base_id}/{$resolved_table_id}";

        do {
            $request_url = add_query_arg( array( 'pageSize' => 100 ), $url );
            if ( ! empty( $offset ) ) {
                $request_url = add_query_arg( array( 'offset' => $offset ), $request_url );
            }

            $data = $this->request_json( $request_url, 15 );

            if ( is_wp_error( $data ) ) {
                return $data;
            }

            if ( empty( $data['records'] ) ) {
                break;
            }

            foreach ( $data['records'] as $record ) {
                $fields = isset( $record['fields'] ) && is_array( $record['fields'] ) ? $record['fields'] : array();
                $sanitized = array();
                foreach ( $fields as $key => $value ) {
                    $sanitized[ sanitize_text_field( $key ) ] = $this->sanitize_field_value( $value );
                }

                $records[] = array(
                    'id'     => sanitize_text_field( $record['id'] ),
                    'fields' => $sanitized,
                );
            }

            $offset = isset( $data['offset'] ) ? $data['offset'] : '';

        } while ( ! empty( $offset ) );

        $records = $this->filter_records( $records, $args );
        $records = $this->sort_records( $records, $args );

        if ( $limit > 0 ) {
            $records = array_slice( $records, 0, $limit );
        }

        return $records;
    }

    /**
     * Validate the Airtable connection using base ID and token only.
     *
     * @return array|WP_Error
     */
    public function test_connection() {
        if ( empty( $this->token ) ) {
            return new WP_Error( 'missing_token', __( 'Airtable token is not configured.', 'at-view-table' ) );
        }

        if ( empty( $this->base_id ) ) {
            return new WP_Error( 'missing_base_id', __( 'Airtable Base ID is not configured.', 'at-view-table' ) );
        }

        return $this->request_json( "https://api.airtable.com/v0/meta/bases/{$this->base_id}/tables", 30 );
    }

    /**
     * Sanitize a field value from Airtable.
     */
    private function sanitize_field_value( $value ) {
        if ( is_array( $value ) ) {
            return array_map( array( $this, 'sanitize_field_value' ), $value );
        }
        if ( is_string( $value ) ) {
            return sanitize_text_field( $value );
        }
        return $value;
    }

    /**
     * Fetch table schema (field names and types) via the Airtable meta API.
     *
     * @return array|WP_Error List of field definitions with 'name' and 'type'.
     */
    public function get_table_fields( $table_id = '' ) {
        $table_id     = sanitize_text_field( $table_id );
        $config_error = $this->validate_configuration( $table_id );

        if ( is_wp_error( $config_error ) ) {
            return $config_error;
        }

        $resolved_table_id = $this->get_resolved_table_id( $table_id );
        $transient_key     = 'atvt_table_fields_' . md5( $this->base_id . '|' . $resolved_table_id );
        $cached = get_transient( $transient_key );
        if ( false !== $cached ) {
            return $cached;
        }

        $url  = "https://api.airtable.com/v0/meta/bases/{$this->base_id}/tables";
        $body = $this->request_json( $url, 30 );

        if ( is_wp_error( $body ) ) {
            return $body;
        }

        if ( ! isset( $body['tables'] ) || ! is_array( $body['tables'] ) ) {
            return new WP_Error( 'airtable_schema_error', __( 'Airtable schema response did not include any tables.', 'at-view-table' ) );
        }

        $fields = array();
        foreach ( $body['tables'] as $table ) {
            if ( $table['id'] === $resolved_table_id || $table['name'] === $resolved_table_id ) {
                foreach ( $table['fields'] as $field ) {
                    $fields[] = array(
                        'name' => $field['name'],
                        'type' => $field['type'],
                    );
                }
                break;
            }
        }

        if ( empty( $fields ) ) {
            return new WP_Error(
                'airtable_table_not_found',
                sprintf(
                    __( 'The configured Airtable table %s was not found in the base schema.', 'at-view-table' ),
                    $resolved_table_id
                )
            );
        }

        set_transient( $transient_key, $fields, HOUR_IN_SECONDS );
        return $fields;
    }

    /**
     * Validate the Airtable configuration required for requests.
     *
     * @return true|WP_Error
     */
    private function validate_configuration( $table_id = '' ) {
        if ( empty( $this->token ) ) {
            return new WP_Error( 'missing_token', __( 'Airtable token is not configured.', 'at-view-table' ) );
        }

        if ( empty( $this->base_id ) ) {
            return new WP_Error( 'missing_base_id', __( 'Airtable Base ID is not configured.', 'at-view-table' ) );
        }

        if ( empty( $this->get_resolved_table_id( $table_id ) ) ) {
            return new WP_Error( 'missing_table_id', __( 'Airtable Table ID is not configured.', 'at-view-table' ) );
        }

        return true;
    }

    private function get_resolved_table_id( $table_id = '' ) {
        return ! empty( $table_id ) ? $table_id : $this->table_id;
    }

    private function filter_records( $records, $args ) {
        $filter_field = isset( $args['filter_field'] ) ? sanitize_text_field( $args['filter_field'] ) : '';
        $filter_value = isset( $args['filter_value'] ) ? sanitize_text_field( $args['filter_value'] ) : '';

        if ( '' === $filter_field || '' === $filter_value ) {
            return $records;
        }

        return array_values(
            array_filter(
                $records,
                function ( $record ) use ( $filter_field, $filter_value ) {
                    $value = isset( $record['fields'][ $filter_field ] ) ? $record['fields'][ $filter_field ] : null;

                    if ( is_array( $value ) ) {
                        return in_array( $filter_value, array_map( 'strval', $value ), true );
                    }

                    return (string) $value === $filter_value;
                }
            )
        );
    }

    private function sort_records( $records, $args ) {
        $sort_field     = isset( $args['sort_field'] ) ? sanitize_text_field( $args['sort_field'] ) : '';
        $sort_direction = isset( $args['sort_direction'] ) ? strtolower( sanitize_text_field( $args['sort_direction'] ) ) : 'asc';

        if ( '' === $sort_field ) {
            return $records;
        }

        usort(
            $records,
            function ( $left, $right ) use ( $sort_field, $sort_direction ) {
                $left_value  = $this->normalize_sort_value( isset( $left['fields'][ $sort_field ] ) ? $left['fields'][ $sort_field ] : '' );
                $right_value = $this->normalize_sort_value( isset( $right['fields'][ $sort_field ] ) ? $right['fields'][ $sort_field ] : '' );
                $result      = strcmp( $left_value, $right_value );

                return 'desc' === $sort_direction ? -$result : $result;
            }
        );

        return $records;
    }

    private function normalize_sort_value( $value ) {
        if ( is_array( $value ) ) {
            return implode( ', ', array_map( 'strval', $value ) );
        }

        return (string) $value;
    }

    /**
     * Perform an Airtable GET request and decode JSON.
     *
     * @param string $url Request URL.
     * @param int    $timeout Request timeout in seconds.
     * @return array|WP_Error
     */
    private function request_json( $url, $timeout ) {
        $response = wp_remote_get(
            esc_url_raw( $url ),
            array(
                'headers' => array(
                    'Authorization' => "Bearer {$this->token}",
                ),
                'timeout' => $timeout,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $code < 200 || $code >= 300 ) {
            return new WP_Error(
                'airtable_http_error',
                sprintf(
                    __( 'Airtable request failed with HTTP %1$d: %2$s', 'at-view-table' ),
                    $code,
                    $this->get_error_message_from_response( $data )
                )
            );
        }

        if ( ! is_array( $data ) ) {
            return new WP_Error( 'airtable_invalid_json', __( 'Airtable returned an invalid JSON response.', 'at-view-table' ) );
        }

        if ( isset( $data['error'] ) ) {
            return new WP_Error(
                'airtable_api_error',
                sprintf(
                    __( 'Airtable returned an API error: %s', 'at-view-table' ),
                    $this->get_error_message_from_response( $data )
                )
            );
        }

        return $data;
    }

    /**
     * Extract a readable Airtable error message from the response body.
     *
     * @param array $data Decoded response body.
     * @return string
     */
    private function get_error_message_from_response( $data ) {
        if ( isset( $data['error'] ) && is_string( $data['error'] ) ) {
            return $data['error'];
        }

        if ( isset( $data['error'] ) && is_array( $data['error'] ) ) {
            if ( ! empty( $data['error']['message'] ) ) {
                return $data['error']['message'];
            }

            if ( ! empty( $data['error']['type'] ) ) {
                return $data['error']['type'];
            }
        }

        return __( 'Unknown Airtable error.', 'at-view-table' );
    }
}
