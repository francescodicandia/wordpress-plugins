<?php
/**
 * Table display handler and shortcode rendering logic.
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

    /**
     * Default Airtable field names shown in table view when no fields attribute given.
     */
    private $default_fields = array(
        'Full Name',
        'Email',
        'WordPress profile',
        'Contribution Area - Expertise',
        'Available hours per week',
        'Sponsor company',
    );

    /**
     * Map short legacy keys to Airtable field names for backward compatibility.
     */
    private $field_aliases = array(
        'name'      => 'Full Name',
        'email'     => 'Email',
        'profile'   => 'WordPress profile',
        'expertise' => 'Contribution Area - Expertise',
        'hours'     => 'Available hours per week',
        'sponsor'   => 'Sponsor company',
        'company'   => 'Sponsor company',
        'status'    => 'Status',
    );

    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'columns' => 3,
            'view'    => 'grid',
            'fields'  => '',
        ), $atts, 'at_view_table' );

        $columns = max( 1, min( 4, intval( $atts['columns'] ) ) );
        $view    = in_array( $atts['view'], array( 'grid', 'table' ), true ) ? $atts['view'] : 'grid';

        $mentors = $this->get_mentors_cached();

        if ( empty( $mentors ) ) {
            return '<p>' . esc_html__( 'No mentors found.', 'at-view-table' ) . '</p>';
        }

        if ( 'table' === $view ) {
            $fields = $this->parse_fields( $atts['fields'] );

            if ( is_wp_error( $fields ) ) {
                return '<p>' . esc_html( $fields->get_error_message() ) . '</p>';
            }

            if ( empty( $fields ) ) {
                return '<p>' . esc_html__( 'Table view is unavailable until Airtable schema access is configured correctly.', 'at-view-table' ) . '</p>';
            }

            return $this->render_table( $mentors, $fields );
        }

        return $this->render_grid( $mentors, $columns );
    }

    private function parse_fields( $raw ) {
        $valid = $this->get_valid_field_names_cached();

        if ( is_wp_error( $valid ) ) {
            return $valid;
        }

        if ( empty( $raw ) ) {
            $defaults = array();
            foreach ( $this->default_fields as $f ) {
                if ( in_array( $f, $valid, true ) ) {
                    $defaults[] = $f;
                }
            }
            return ! empty( $defaults ) ? $defaults : $valid;
        }

        $requested = array_map( 'trim', explode( ',', $raw ) );
        $resolved  = array();
        foreach ( $requested as $r ) {
            $lower = strtolower( $r );
            if ( in_array( $r, $valid, true ) ) {
                $resolved[] = $r;
            } elseif ( isset( $this->field_aliases[ $lower ] ) && in_array( $this->field_aliases[ $lower ], $valid, true ) ) {
                $resolved[] = $this->field_aliases[ $lower ];
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

    private function render_grid( $mentors, $columns ) {
        ob_start();
        ?>
        <div class="atvt-grid" style="--atvt-columns: <?php echo esc_attr( $columns ); ?>;">
            <?php foreach ( $mentors as $mentor ) : ?>
                <div class="atvt-card">
                    <div class="atvt-card-inner">
                        <div class="atvt-avatar">
                            <?php if ( ! empty( $mentor['email'] ) ) : ?>
                                <?php echo get_avatar( $mentor['email'], 120, '', $mentor['full_name'] ); ?>
                            <?php else : ?>
                                <div class="atvt-avatar-placeholder">
                                    <?php echo esc_html( strtoupper( substr( $mentor['full_name'], 0, 1 ) ) ); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <h3 class="atvt-name">
                            <?php if ( ! empty( $mentor['wordpress_profile'] ) ) : ?>
                                <a href="<?php echo esc_url( $mentor['wordpress_profile'] ); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php echo esc_html( $mentor['full_name'] ); ?>
                                </a>
                            <?php else : ?>
                                <?php echo esc_html( $mentor['full_name'] ); ?>
                            <?php endif; ?>
                        </h3>

                        <?php if ( ! empty( $mentor['expertise'] ) ) : ?>
                            <div class="atvt-expertise">
                                <?php foreach ( $mentor['expertise'] as $area ) : ?>
                                    <span class="atvt-tag"><?php echo esc_html( $area ); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( $mentor['sponsored'] && ! empty( $mentor['sponsor_company'] ) ) : ?>
                            <div class="atvt-sponsored">
                                <span class="atvt-sponsored-label">
                                    <?php
                                    printf(
                                        esc_html__( 'Sponsored by %s', 'at-view-table' ),
                                        esc_html( $mentor['sponsor_company'] )
                                    );
                                    ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_table( $mentors, $fields ) {
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
                    <?php foreach ( $mentors as $mentor ) : ?>
                        <tr>
                            <?php foreach ( $fields as $field_name ) : ?>
                                <td class="<?php echo 'Full Name' === $field_name ? 'atvt-table-name-cell' : ''; ?>" data-label="<?php echo esc_attr( $field_name ); ?>">
                                    <?php $this->render_table_cell( $mentor, $field_name ); ?>
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

    private function render_table_cell( $mentor, $field_name ) {
        if ( 'Full Name' === $field_name ) {
            if ( ! empty( $mentor['email'] ) ) {
                echo get_avatar( $mentor['email'], 32, '', $mentor['full_name'], array( 'class' => 'atvt-table-avatar' ) );
            }
            if ( ! empty( $mentor['wordpress_profile'] ) ) {
                echo '<a href="' . esc_url( $mentor['wordpress_profile'] ) . '" target="_blank" rel="noopener noreferrer">'
                    . esc_html( $mentor['full_name'] ) . '</a>';
            } else {
                echo esc_html( $mentor['full_name'] );
            }
            return;
        }

        $value = isset( $mentor['raw_fields'][ $field_name ] ) ? $mentor['raw_fields'][ $field_name ] : '';

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

    private function get_mentors_cached() {
        $cache_key = 'atvt_mentors';
        $mentors   = get_transient( $cache_key );

        if ( false !== $mentors ) {
            return $mentors;
        }

        $api     = new ATVT_Airtable_API();
        $mentors = $api->fetch_mentors();

        if ( ! is_array( $mentors ) || is_wp_error( $mentors ) ) {
            return array();
        }

        set_transient( $cache_key, $mentors, ATVT_CACHE_TTL );

        return $mentors;
    }
}
