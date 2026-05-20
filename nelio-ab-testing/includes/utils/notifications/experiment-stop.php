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
										<?php echo esc_html_x( 'Congratulations!', 'text', 'nelio-ab-testing' ); ?>
									</h2>

									<p style="font-size: 18px; line-height: 24px;">
										<?php
											echo esc_html(
												sprintf(
													/* translators: %1$s: User name (user email). %2$s: Test name. %3$s: Date. %4$s: Time. */
													_x( '%1$s stopped test “%2$s” on %3$s at %4$s local time.', 'text', 'nelio-ab-testing' ),
													isset( $finalizer ) && is_string( $finalizer ) ? $finalizer : '—',
													isset( $experiment_name ) && is_string( $experiment_name ) ? $experiment_name : '—',
													isset( $experiment_end_date ) && is_string( $experiment_end_date ) ? $experiment_end_date : '—',
													isset( $experiment_end_time ) && is_string( $experiment_end_time ) ? $experiment_end_time : '—'
												)
											);
											?>
									</p>

									<p style="font-size: 18px; line-height: 24px;">
										<?php
											echo esc_html_x( 'To see the results of the test, click the following button:', 'text', 'nelio-ab-testing' );
										?>
									</p>

									<p style="text-align:center;margin:2rem 0 2rem">
										<a href="<?php echo esc_url( isset( $experiment_url ) && is_string( $experiment_url ) ? $experiment_url : '#' ); ?>" style="display:inline-block;padding:14px 32px;background:#ff7e00;border-radius:4px;font-weight:normal;letter-spacing:1px;font-size:20px;line-height:26px;color:white;text-decoration:none" target="_blank"><?php echo esc_html_x( 'See Results', 'text', 'nelio-ab-testing' ); ?></a>
									</p>

									<?php if ( isset( $end_mode ) && 'manual' !== $end_mode && isset( $end_value ) && isset( $stopper_user_id ) && 0 === $stopper_user_id ) { ?>
									<p style="font-size: 18px; line-height: 24px;">
										<?php
										switch ( $end_mode ) {
											case 'pageviews':
												echo esc_html(
													sprintf(
														/* translators: %s: Positive number of pageviews. */
														_x( 'Note that the test ended automatically after consuming %s page views.', 'text', 'nelio-ab-testing' ),
														is_int( $end_value ) ? number_format_i18n( $end_value ) : '—'
													)
												);
												break;
											case 'duration':
												echo esc_html(
													sprintf(
														/* translators: %d: Positive number of days. */
														_x( 'Note that the test ended automatically after %d days running.', 'text', 'nelio-ab-testing' ),
														absint( $end_value )
													)
												);
												break;
											case 'confidence':
												echo esc_html(
													sprintf(
														/* translators: %d: Percentage. */
														_x( 'Note that the test ended automatically after reaching a confidence of %d%% in the results.', 'text', 'nelio-ab-testing' ),
														absint( $end_value )
													)
												);
												break;
										}
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
