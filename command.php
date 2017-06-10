<?php

namespace WeDevs\CLI;

use WP_CLI;
use WP_CLI_Command;

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * The exporter class
 */
class FB_User_Export extends WP_CLI_Command {

    /**
     * Export users for facebook ad audience
     *
     * ## OPTIONS
     *
     * [--type=<type>]
     * : Use fields from either WooCommerce or Easy Digital Downloads
     * ---
     * options:
     *   - woo
     *   - edd
     * ---
     *
     * [--role=<role>]
     * : Only export users with a certain role.
     *
     * ## EXAMPLES
     *
     *     wp user export_csv --role=subscriber
     *     wp user export_csv --field=woo --role=customer
     *
     * @when after_wp_load
     */
    public function fb_export_csv( $args, $assoc_args ) {
        $file_name = 'user_export-' . date( 'Y-m-d' ) . '.csv';
        $type     = WP_CLI\Utils\get_flag_value( $assoc_args, 'type', '' );
        $role      = WP_CLI\Utils\get_flag_value( $assoc_args, 'role', '' );

        $woo       = false;
        $edd       = false;
        $items     = [];
        $user_args = [
            // 'number' => 10
        ];

        if ( !empty( $role )) {
            $user_args['role'] = $role;
        }

        $users     = get_users( $user_args );
        $headers   = [
            'email', 'fn', 'ln'
        ];

        if ( 'woo' == $type ) {
            $woo     = true;
            $headers = array_merge( $headers, [ 'phone', 'ct', 'st', 'country', 'zip' ] );
        }

        if ( 'edd' == $type ) {
            $edd     = true;
            $headers = array_merge( $headers, [ 'phone', 'ct', 'st', 'country', 'zip' ] );
        }

        if ( $users ) {
            foreach ($users as $user) {
                $row = [];

                foreach ($headers as $key) {

                    switch ( $key ) {
                        case 'email':
                            $row[ $key ] = $user->user_email;
                            break;

                        case 'fn':
                            $row[ $key ] = $user->first_name;
                            break;

                        case 'ln':
                            $row[ $key ] = $user->last_name;
                            break;

                        case 'phone':
                            $row[ $key ] = '';

                            if ( $woo ) {
                                $row[ $key ] = $user->billing_phone;
                            }

                            break;

                        case 'ct':
                            $row[ $key ] = '';

                            if ( $woo ) {
                                $row[ $key ] = $user->billing_city;
                            }

                            if ( $edd ) {
                                $row[ $key ] = $this->get_edd_value( $user, 'city' );
                            }

                            break;

                        case 'st':
                            $row[ $key ] = '';

                            if ( $woo ) {
                                $row[ $key ] = $user->billing_state;
                            }

                            if ( $edd ) {
                                $row[ $key ] = $this->get_edd_value( $user, 'state' );
                            }

                            break;

                        case 'country':
                            $row[ $key ] = '';

                            if ( $woo ) {
                                $row[ $key ] = $user->billing_country;
                            }

                            if ( $edd ) {
                                $row[ $key ] = $this->get_edd_value( $user, 'country' );
                            }

                            break;

                        case 'zip':
                            $row[ $key ] = '';

                            if ( $woo ) {
                                $row[ $key ] = $user->billing_postcode;
                            }

                            if ( $edd ) {
                                $row[ $key ] = $this->get_edd_value( $user, 'zip' );
                            }

                            break;

                        default:
                            # code...
                            break;
                    }
                }

                $items[] = $row;
            }
        }

        // print_r( $items );

        // WP_CLI\Utils\format_items( 'table', $items, $headers );

        $file = fopen( $file_name, 'w' );
        WP_CLI\Utils\write_csv( $file, $items, $headers );

        WP_CLI::success( "Exported users to {$file_name}!" );
    }

    private function get_edd_value( $user, $field ) {
        $meta = get_user_meta( $user->ID, '_edd_user_address', true );

        if ( is_array( $meta ) && isset( $meta[ $field ] ) ) {
            return $meta[ $field ];
        }
    }
}

WP_CLI::add_command( 'user fb-user-export', [ 'WeDevs\CLI\FB_User_Export', 'fb_export_csv' ]  );
