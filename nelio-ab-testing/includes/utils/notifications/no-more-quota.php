<?php require untrailingslashit( __DIR__ ) . '/header.php'; ?>
		<tr>
			<td valign="top">
				<table id="body" width="100%" cellpadding="0" cellspacing="0" border="0">
					<tr>
						<td valign="top">
							<div style="max-width: 600px; margin: 0 auto; padding: 0 12px;">
								<div class="card"
									style="background: white; border-radius: 0.5rem; padding: 2rem; margin-bottom: 1rem;">
									<h2 style="color: #ff7e00; margin: 0 0 12px; line-height: 30px;">
										<?php echo esc_html_x( 'Hello,', 'text', 'nelio-ab-testing' ); ?>
									</h2>

									<p style="font-size: 18px; line-height: 24px;">
										<?php
											echo esc_html_x( 'Your subscription has run out of quota. As a result, the service will not track any of your visitors until the end of your billing period, when the number of page views will be reset.', 'text', 'nelio-ab-testing' );
										?>
									</p>

									<?php if ( nab_are_subscription_controls_disabled() ) { ?>
										<p style="font-size: 18px; line-height: 24px;">
											<?php
												echo wp_kses_data( _x( 'If you want to ensure that the service won’t stop, you can buy more quota using the option available in your <strong>Account Details</strong> and get some additional page views.', 'text', 'nelio-ab-testing' ) );
											?>
										</p>

										<p style="text-align:center;margin:2rem 0 2rem">
											<a href="<?php echo esc_url( isset( $account_url ) && is_string( $account_url ) ? $account_url : '#' ); ?>" style="display:inline-block;padding:14px 32px;background:#ff7e00;border-radius:4px;font-weight:normal;letter-spacing:1px;font-size:20px;line-height:26px;color:white;text-decoration:none" target="_blank"><?php echo esc_html_x( 'Buy More Quota', 'command', 'nelio-ab-testing' ); ?></a>
										</p>

										<p style="font-size: 18px; line-height: 24px;">
											<?php
												echo esc_html_x( 'Please let us know if you think that you’ll need permanently more quota and we’ll provide you with a new fixed pricing to avoid any other inconvenience.', 'text', 'nelio-ab-testing' );
											?>
										</p>
									<?php } ?>

									<p style="font-size: 18px; line-height: 24px;">
										<?php
											printf(
												/* translators: %s: Mailto link. */
												wp_kses_data( _x( 'As always, if you need further assistance feel free to contact us directly by sending us an email to <a href="%s">Nelio Support</a>.', 'text', 'nelio-ab-testing' ) ),
												esc_attr( 'mailto:support@neliosoftware.com' )
											);
											?>
									</p>

									<p style="font-size: 18px; line-height: 24px;">
										<?php echo esc_html_x( 'Best,', 'text', 'nelio-ab-testing' ); ?>
										<br />&nbsp;&nbsp;&nbsp;
										<?php echo esc_html_x( 'David from Nelio', 'text', 'nelio-ab-testing' ); ?>
									</p>
								</div>
							</div>
						</td>
					</tr>
				</table>
			</td>
		</tr>
<?php require untrailingslashit( __DIR__ ) . '/footer.php'; ?>
