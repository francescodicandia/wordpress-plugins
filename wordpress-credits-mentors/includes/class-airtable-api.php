<?php
/**
 * Airtable API wrapper for fetching mentor records.
 *
 * @package WordPressCredits
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPCM_Airtable_API {

    private $token;
    private $base_id;
    private $table_id;

    public function __construct() {
        $this->token    = get_option( 'wpcm_airtable_token', '' );
        $this->base_id  = get_option( 'wpcm_airtable_base_id', '' );
        $this->table_id = get_option( 'wpcm_airtable_table_id', '' );
    }

    public function fetch_mentors() {
        $config_error = $this->validate_configuration();

        if ( is_wp_error( $config_error ) ) {
            return $config_error;
        }

        $mentors = array();
        $offset  = '';
        $url     = "https://api.airtable.com/v0/{$this->base_id}/{$this->table_id}";

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
                $fields = $record['fields'];

                $status = isset( $fields['Status'] ) ? sanitize_text_field( $fields['Status'] ) : '';

                $valid_statuses = get_option( 'wpcm_mentor_statuses', 'Active' );
                $valid_statuses = array_map( 'trim', explode( ',', $valid_statuses ) );

                if ( ! in_array( $status, $valid_statuses, true ) ) {
                    continue;
                }

                $sanitized = array();
                foreach ( $fields as $key => $value ) {
                    $sanitized[ sanitize_text_field( $key ) ] = $this->sanitize_field_value( $value );
                }

                $mentors[] = array(
                    'raw_fields'             => $sanitized,
                    'id'                     => sanitize_text_field( $record['id'] ),
                    'full_name'              => isset( $fields['Full Name'] ) ? sanitize_text_field( $fields['Full Name'] ) : '',
                    'wordpress_profile'      => isset( $fields['WordPress profile'] ) ? esc_url_raw( $fields['WordPress profile'] ) : '',
                    'email'                  => isset( $fields['Email'] ) ? sanitize_email( $fields['Email'] ) : '',
                    'sponsored'              => isset( $fields['Sponsored'] ) && 'Yes' === $fields['Sponsored'],
                    'sponsor_company'        => isset( $fields['Sponsor company'] ) ? sanitize_text_field( $fields['Sponsor company'] ) : '',
                    'expertise'              => isset( $fields['Contribution Area - Expertise'] ) ? array_map( 'sanitize_text_field', (array) $fields['Contribution Area - Expertise'] ) : array(),
                    'available_hours'        => isset( $fields['Available hours per week'] ) ? intval( $fields['Available hours per week'] ) : 0,
                );
            }

            $offset = isset( $data['offset'] ) ? $data['offset'] : '';

        } while ( ! empty( $offset ) );

        return $mentors;
    }

    public function get_all_statuses() {
        $config_error = $this->validate_configuration();

        if ( is_wp_error( $config_error ) ) {
            return $config_error;
        }

        $statuses = array();
        $offset   = '';
        $url      = "https://api.airtable.com/v0/{$this->base_id}/{$this->table_id}";

        do {
            $request_url = add_query_arg(
                array( 'pageSize' => 100, 'fields' => 'Status' ),
                $url
            );
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
                if ( isset( $record['fields']['Status'] ) ) {
                    $statuses[ sanitize_text_field( $record['fields']['Status'] ) ] = true;
                }
            }

            $offset = isset( $data['offset'] ) ? $data['offset'] : '';

        } while ( ! empty( $offset ) );

        return array_keys( $statuses );
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
    public function get_table_fields() {
        $config_error = $this->validate_configuration();

        if ( is_wp_error( $config_error ) ) {
            return $config_error;
        }

        $transient_key = 'wpcm_table_fields_' . md5( $this->base_id . '|' . $this->table_id );
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
            return new WP_Error( 'airtable_schema_error', __( 'Airtable schema response did not include any tables.', 'wpcredits-mentors' ) );
        }

        $fields = array();
        foreach ( $body['tables'] as $table ) {
            if ( $table['id'] === $this->table_id || $table['name'] === $this->table_id ) {
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
                    __( 'The configured Airtable table %s was not found in the base schema.', 'wpcredits-mentors' ),
                    $this->table_id
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
    private function validate_configuration() {
        if ( empty( $this->token ) ) {
            return new WP_Error( 'missing_token', __( 'Airtable token is not configured.', 'wpcredits-mentors' ) );
        }

        if ( empty( $this->base_id ) ) {
            return new WP_Error( 'missing_base_id', __( 'Airtable Base ID is not configured.', 'wpcredits-mentors' ) );
        }

        if ( empty( $this->table_id ) ) {
            return new WP_Error( 'missing_table_id', __( 'Airtable Table ID is not configured.', 'wpcredits-mentors' ) );
        }

        return true;
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
                    __( 'Airtable request failed with HTTP %1$d: %2$s', 'wpcredits-mentors' ),
                    $code,
                    $this->get_error_message_from_response( $data )
                )
            );
        }

        if ( ! is_array( $data ) ) {
            return new WP_Error( 'airtable_invalid_json', __( 'Airtable returned an invalid JSON response.', 'wpcredits-mentors' ) );
        }

        if ( isset( $data['error'] ) ) {
            return new WP_Error(
                'airtable_api_error',
                sprintf(
                    __( 'Airtable returned an API error: %s', 'wpcredits-mentors' ),
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

        return __( 'Unknown Airtable error.', 'wpcredits-mentors' );
    }
}
