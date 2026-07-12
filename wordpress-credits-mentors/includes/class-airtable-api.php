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
        $this->token   = get_option( 'wpcm_airtable_token', '' );
        $this->base_id = WPCM_AIRTABLE_BASE_ID;
        $this->table_id = WPCM_AIRTABLE_TABLE_ID;
    }

    public function fetch_mentors() {
        if ( empty( $this->token ) ) {
            return new WP_Error( 'missing_token', __( 'Airtable token is not configured.', 'wpcredits-mentors' ) );
        }

        $mentors = array();
        $offset  = '';
        $url     = "https://api.airtable.com/v0/{$this->base_id}/{$this->table_id}";

        do {
            $args = array(
                'headers' => array(
                    'Authorization' => "Bearer {$this->token}",
                ),
                'timeout' => 15,
            );

            $request_url = add_query_arg( array( 'pageSize' => 100 ), $url );
            if ( ! empty( $offset ) ) {
                $request_url = add_query_arg( array( 'offset' => $offset ), $request_url );
            }

            $response = wp_remote_get( esc_url_raw( $request_url ), $args );

            if ( is_wp_error( $response ) ) {
                return $response;
            }

            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body, true );

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
        if ( empty( $this->token ) ) {
            return new WP_Error( 'missing_token', __( 'Airtable token is not configured.', 'wpcredits-mentors' ) );
        }

        $statuses = array();
        $offset   = '';
        $url      = "https://api.airtable.com/v0/{$this->base_id}/{$this->table_id}";

        do {
            $args = array(
                'headers' => array(
                    'Authorization' => "Bearer {$this->token}",
                ),
                'timeout' => 15,
            );

            $request_url = add_query_arg(
                array( 'pageSize' => 100, 'fields' => 'Status' ),
                $url
            );
            if ( ! empty( $offset ) ) {
                $request_url = add_query_arg( array( 'offset' => $offset ), $request_url );
            }

            $response = wp_remote_get( esc_url_raw( $request_url ), $args );

            if ( is_wp_error( $response ) ) {
                return $response;
            }

            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body, true );

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
     * @return array List of field definitions with 'name' and 'type'.
     */
    public function get_table_fields() {
        if ( empty( $this->token ) ) {
            return array();
        }

        $transient_key = 'wpcm_table_fields_' . md5( $this->table_id );
        $cached = get_transient( $transient_key );
        if ( false !== $cached ) {
            return $cached;
        }

        $url  = "https://api.airtable.com/v0/meta/bases/{$this->base_id}/tables";
        $args = array(
            'headers' => array(
                'Authorization' => "Bearer {$this->token}",
            ),
            'timeout' => 30,
        );

        $response = wp_remote_get( esc_url_raw( $url ), $args );

        if ( is_wp_error( $response ) ) {
            return array();
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! isset( $body['tables'] ) ) {
            return array();
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

        set_transient( $transient_key, $fields, HOUR_IN_SECONDS );
        return $fields;
    }
}
