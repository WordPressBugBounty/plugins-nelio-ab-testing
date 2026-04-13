<?php
/**
 * This file contains the setting for domain forwarding.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      6.1.0
 */

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * This class represents the setting for domain forwarding.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      6.1.0
 */
class Nelio_AB_Testing_Cloud_Proxy_Setting extends Nelio_AB_Testing_Abstract_React_Setting {

	public function __construct( $name ) {
		parent::__construct(
			$name,
			Z::object(
				array(
					'mode'             => Z::enum(
						array(
							'disabled',
							'domain-forwarding',
							'rest',
						)
					)->catch( 'disabled' ),
					'isCheckingStatus' => Z::boolean()->catch( false ),
					'value'            => Z::string()->trim()->catch( '' ),
					'domain'           => Z::string()->trim()->catch( '' ),
					'domainStatus'     => Z::enum(
						array(
							'disabled',
							'missing-forward',
							'cert-validation-pending',
							'cert-validation-success',
							'success',
						)
					)->catch( 'disabled' ),
					'dnsValidation'    => Z::object(
						array(
							'recordName'  => Z::string()->catch( '' ),
							'recordValue' => Z::string()->catch( '' ),
						)
					)
						->transform(
							function ( $value ) {
								$value = is_array( $value ) ? $value : array();
								if ( empty( $value['recordName'] ) ) {
									unset( $value['recordName'] );
								}
								if ( empty( $value['recordValue'] ) ) {
									unset( $value['recordValue'] );
								}
								return $value;
							}
						)
						->catch( array() ),
				)
			)->catch(
				array(
					'mode'             => 'disabled',
					'isCheckingStatus' => false,
					'value'            => '',
					'domain'           => '',
					'domainStatus'     => 'disabled',
				)
			),
			'CloudProxySetting'
		);
	}

	// @Overrides
	public function print_description() {
		// @codeCoverageIgnoreStart
		?>
		<div class="setting-help" style="display:none;">
			<p><span class="description">
				<?php
				echo esc_html_x(
					'Ad blocking tools have recently added Nelio A/B Testing domains to their block lists, which means you won’t be able to track any events triggered by visitors who use an ad blocker on their browsers.',
					'text',
					'nelio-ab-testing'
				);
				echo esc_html_x(
					'This setting allows you to bypass adblocking restrictions by setting a different domain to track split testing events:',
					'text',
					'nelio-ab-testing'
				);
				?>
				</span>
			</p>
			<ul style="list-style-type:disc;margin-left:3em;">
				<li>
					<span class="description">
						<strong><?php echo esc_html_x( 'Disabled.', 'text (proxy)', 'nelio-ab-testing' ); ?></strong> <?php echo esc_html_x( 'Keeps using Nelio’s default domain.', 'text', 'nelio-ab-testing' ); ?>
					</span>
				</li>
				<li>
					<span class="description">
						<strong><?php echo esc_html_x( 'REST API.', 'text (proxy)', 'nelio-ab-testing' ); ?></strong> <?php echo esc_html_x( 'Creates a new endpoint in your WordPress’ REST API.', 'text', 'nelio-ab-testing' ); ?>
						<?php
						esc_html_x(
							'Nelio A/B Testing will send all events to your WordPress, which is now responsible of forwarding them to Nelio’s cloud.',
							'text',
							'nelio-ab-testing'
						);
						?>
						<br />
						<?php
						echo esc_html_x(
							'Please notice this setting will increase the load of your WordPress server.',
							'text',
							'nelio-ab-testing'
						);
						?>
						<br />
						<?php
						echo esc_html_x(
							'If you’re using a cache plugin or a CDN, please make sure to exclude this endpoint from being cached.',
							'text',
							'nelio-ab-testing'
						);
						?>
					</span>
				</li>
				<li>
					<span class="description">
						<strong><?php echo esc_html_x( 'Domain Forwarding.', 'text (proxy)', 'nelio-ab-testing' ); ?></strong> <?php echo esc_html_x( 'Forwards a subdomain on your site to our servers.', 'text', 'nelio-ab-testing' ); ?>
						<?php
						echo esc_html_x(
							'Nelio A/B Testing will send all events through a subdomain of your WordPress, which is now responsible of forwarding them to Nelio’s cloud.',
							'text',
							'nelio-ab-testing'
						);
						?>
						<br />
						<?php
						echo esc_html_x(
							'Please notice this setting will not increase the load of your WordPress server.',
							'text',
							'nelio-ab-testing'
						);
						?>
						<br>
						<?php
						printf(
							/* translators: %1$s: URL (yourdomain.com). %2$s: URL (api.nelioabtesting.com). %3$s: URL (subdomain.yourdomain.com). */
							esc_html_x(
								'For example, if you have a website on %1$s, requests going to %2$s may get stopped. But by forwarding %3$s to our servers, everything belongs to your primary domain and nothing will be blocked.',
								'text',
								'nelio-ab-testing'
							),
							sprintf( '<code>%s</code>', esc_html_x( 'yourdomain.com', 'text', 'nelio-ab-testing' ) ),
							sprintf( '<code>%s</code>', 'api.nelioabtesting.com' ),
							sprintf( '<code>%s</code>', esc_html_x( 'subdomain.yourdomain.com', 'text', 'nelio-ab-testing' ) )
						);
						?>
					</span>
				</li>
			</ul>
		</div>
		<?php
		// @codeCoverageIgnoreEnd
	}
}
