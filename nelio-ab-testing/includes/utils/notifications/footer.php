<?php
defined( 'ABSPATH' ) || exit;
?>
		<tr>
			<td>
				<table id="footer" width="100%" cellpadding="0" cellspacing="0"
					border="0"
					style="margin-top: 1rem; background: white; color: #989ea6;">
					<tr>
						<td valign="top" align="center" style="padding: 16px 8px 24px;">
							<div style="max-width: 600px; margin: 0 auto;">
								<p class="footer_address"
									style="margin-top: 16px; font-size: 12px; line-height: 20px;">
									<?php
										printf(
											/* translators: %1$s: Nelio Software. */
											esc_html_x( 'Sent by %1$s', 'text', 'nelio-ab-testing' ),
											sprintf(
												'<a href="%1$s" style="%2$s">Nelio Software</a>',
												esc_url( 'https://neliosoftware.com' ),
												'font-weight: bold; color: #439fe0;'
											),
										);
										?>
									<br />
									Pomaret 83 &nbsp;&bull;&nbsp; 08017 Barcelona
								</p>
								<p class="footer_address"
									style="margin-top: 16px; font-size: 12px; line-height: 24px;">
									<img
										src="https://neliosoftware.com/wp-content/uploads/2018/05/nelio-footer-small.png" alt="Nelio Software Logo"
										height="11px" style="vertical-align: text-top;" />
								</p>
							</div>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>
