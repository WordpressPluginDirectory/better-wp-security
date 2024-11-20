<?php

namespace iThemesSecurity\Lib\Site_Types\Question;

use iThemesSecurity\Lib\Site_Types\Answer_Handler;
use iThemesSecurity\Lib\Site_Types\Has_Prerequisites;
use iThemesSecurity\Lib\Site_Types\Question;
use iThemesSecurity\Lib\Site_Types\Questions_Provider;
use iThemesSecurity\Lib\Site_Types\Responds;
use iThemesSecurity\Lib\Site_Types\Site_Type;
use iThemesSecurity\Lib\Site_Types\Templated_Question;
use iThemesSecurity\Lib\Site_Types\Templating_Site_Type;

final class Global_Question_Pack implements Questions_Provider {

	/** @var Site_Type|null */
	private $site_type;

	public function __construct( Site_Type $site_type = null ) { $this->site_type = $site_type; }

	public function get_questions(): array {
		$questions = [];

		$questions[] = new class( $this->site_type instanceof Templating_Site_Type ? $this->site_type : null ) extends Templated_Question {
			public function get_id(): string {
				return self::SCAN_SITE;
			}

			protected function get_prompt_fallback(): string {
				return __( 'Before we configure Solid Security, let’s scan your site for vulnerabilities…', 'better-wp-security' );
			}

			public function get_description(): string {
				return __( 'Scanning your website helps you better understand your security situation. Having more context around your site’s security is helpful to know for your initial setup.', 'better-wp-security' );
			}

			public function get_answer_schema(): array {
				return [
					'type' => ['boolean', 'integer'],
				];
			}
		};

		if ( ! \ITSEC_Modules::is_active( 'security-check-pro' ) ) {
			$questions[] = new class implements Question, Responds {
				public function get_id(): string {
					return self::SECURITY_CHECK_PRO;
				}

				public function get_prompt(): string {
					return __( 'Enable Security Check Pro?', 'better-wp-security' );
				}

				public function get_description(): string {
					return __( 'For certain firewall features to function, Solid Security must be properly configured to identify the IP addresses of your site’s visitors and users. Security Check Pro automatically configures the correct IP detection method based upon your server configuration.<br><br>Enabling Security Check Pro allows your site to periodically make an API request to SolidWP servers to discover your current server configuration and set/update the IP detection method. No user information is sent to SolidWP.', 'better-wp-security' );
				}

				public function get_answer_schema(): array {
					return [
						'type'        => 'boolean',
						'title'       => __( 'Enable Security Check Pro', 'better-wp-security' ),
						'description' => '<a href="https://go.solidwp.com/solid-privacy-policy">' . __( 'Read our Privacy Policy', 'better-wp-security' ) . '</a>',
						'default'     => false,
						'uiSchema'    => [
							'ui:widget' => 'ToggleWidget',
						],
					];
				}

				public function respond( Answer_Handler $handler ) {
					if ( $handler->get_answer() ) {
						$handler->enable_module( 'security-check-pro' );
						$handler->set_setting( 'global', 'proxy', 'security-check' );
					}
				}
			};

			$questions[] = new class implements Question, Responds, Has_Prerequisites {
				public function get_id(): string {
					return self::IP_DETECTION;
				}

				public function get_prompt(): string {
					return __( 'Configure IP Detection', 'better-wp-security' );
				}

				public function get_description(): string {
					return __( 'Solid Security relies on accurately identifying the IP addresses of your site’s visitors and users to block and/or authorize them.', 'better-wp-security' )
					       . ' <a href="https://go.solidwp.com/about-ip-detection" target="_blank">'
					       . __( 'Read more about IP detection.', 'better-wp-security' )
					       . '</a>';
				}

				public function get_prerequisites(): array {
					return [
						self::SECURITY_CHECK_PRO => [
							'type' => 'boolean',
							'enum' => [ false ],
						],
					];
				}

				public function get_answer_schema(): array {
					$schema = \ITSEC_Modules::get_settings_obj( 'global' )->get_settings_schema();
					array_unshift( $schema['properties']['proxy_header']['enum'], '' );
					array_unshift( $schema['properties']['proxy_header']['enumNames'], '' );

					return [
						'title'      => __( 'Select IP Source', 'better-wp-security' ),
						'type'       => 'object',
						'properties' => [
							'proxy'        => $schema['properties']['proxy'],
							'proxy_header' => $schema['properties']['proxy_header'],
						],
					];
				}

				public function respond( Answer_Handler $handler ) {
					$handler->set_setting( 'global', 'proxy', $handler->get_answer()['proxy'] );

					if ( $handler->get_answer()['proxy_header'] ) {
						$handler->set_setting( 'global', 'proxy_header', $handler->get_answer()['proxy_header'] );
					}
				}
			};
		}

		return $questions;
	}
}
