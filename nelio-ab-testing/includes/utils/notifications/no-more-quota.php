<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>${title}</title>

<style type="text/css">

/* Template styling */
body {
	font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
	width: 100%;
	max-width: 100%;
	font-size: 17px;
	line-height: 24px;
	color: #373737;
	background: #F9F9F9;
}

table {
	width: 100%;
	margin: 0 auto;
}

h1, h2, h3, h4 {
	color: #2ab27b;
	margin-bottom: 12px;
	line-height: 26px;
}

p, ul, ul li {
	font-size: 17px;
	margin: 0 0 16px;
	line-height: 24px;
}

ul {
	margin-bottom: 24px;
}

ul li {
	margin-bottom: 8px;
}

p.mini {
	font-size: 12px;
	line-height: 18px;
	color: #ABAFB4;
}

p.message {
	font-size: 16px;
	line-height: 20px;
	margin-bottom: 4px;
}

hr {
	margin: 2rem 0;
	width: 100%;
	border: none;
	border-bottom: 1px solid #ECECEC;
}

a, a:link, a:visited, a:active, a:hover {
	font-weight: bold;
	color: #439fe0;
	text-decoration: none;
	word-break: break-word;
}

a:active, a:hover {
	text-decoration: underline;
}

.time {
	font-size: 11px;
	color: #ABAFB4;
	padding-right: 6px;
}

.emoji {
	vertical-align: bottom;
}

.avatar {
	border-radius: 2px;
}

#footer p {
	margin-top: 16px;
	font-size: 12px;
}

/* Client-specific Styles */
#outlook a {
	padding: 0;
}

body {
	width: 100% !important;
	-webkit-text-size-adjust: 100%;
	-ms-text-size-adjust: 100%;
	margin: 0 auto;
	padding: 0;
}

.ExternalClass {
	width: 100%;
}

.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font,
	.ExternalClass td, .ExternalClass div {
	line-height: 100%;
}

#backgroundTable {
	margin: 0;
	padding: 0;
	width: 100%;
	line-height: 100% !important;
}

/* Some sensible defaults for images
		Bring inline: Yes. */
img {
	outline: none;
	text-decoration: none;
	-ms-interpolation-mode: bicubic;
}

a img {
	border: none;
	max-width: 100%;
}

.image_fix {
	display: block;
}

/* Outlook 07, 10 Padding issue fix
		Bring inline: No.*/
table td {
	border-collapse: collapse;
}

/* Fix spacing around Outlook 07, 10 tables
		Bring inline: Yes */
table {
	border-collapse: collapse;
	mso-table-lspace: 0pt;
	mso-table-rspace: 0pt;
}

/* Mobile */
@media only screen and (max-device-width: 480px) {
	/* Part one of controlling phone number linking for mobile. */
	a[href^="tel"], a[href^="sms"] {
		text-decoration: none;
		color: blue; /* or whatever your want */
		pointer-events: none;
		cursor: default;
	}
	.mobile_link a[href^="tel"], .mobile_link a[href^="sms"] {
		text-decoration: default;
		color: orange !important;
		pointer-events: auto;
		cursor: default;
	}
}

/* Not all email clients will obey these, but the important ones will */
@media only screen and (max-width: 480px) {
	.card {
		padding: 1rem 0.75rem !important;
	}
	.link_option {
		font-size: 14px;
	}
	hr {
		margin: 2rem -0.75rem !important;
		padding-right: 1.5rem !important;
	}
}

/* More Specific Targeting */
@media only screen and (min-device-width: 768px) and (max-device-width:
	1024px) {
	/* You guessed it, ipad (tablets, smaller screens, etc) */
	/* repeating for the ipad */
	a[href^="tel"], a[href^="sms"] {
		text-decoration: none;
		color: blue; /* or whatever your want */
		pointer-events: none;
		cursor: default;
	}
	.mobile_link a[href^="tel"], .mobile_link a[href^="sms"] {
		text-decoration: default;
		color: orange !important;
		pointer-events: auto;
		cursor: default;
	}
}

/* iPhone Retina */
@media only screen and (-webkit-min-device-pixel-ratio: 2) and
	(max-device-width: 640px) {
	/* Must include !important on each style to override inline styles */
	#footer p {
		font-size: 9px;
	}
}

/* Android targeting */
@media only screen and (-webkit-device-pixel-ratio:.75) {
	/* Put CSS for low density (ldpi) Android layouts in here */
	img {
		max-width: 100%;
		height: auto;
	}
}

@media only screen and (-webkit-device-pixel-ratio:1) {
	/* Put CSS for medium density (mdpi) Android layouts in here */
	img {
		max-width: 100%;
		height: auto;
	}
}

@media only screen and (-webkit-device-pixel-ratio:1.5) {
	/* Put CSS for high density (hdpi) Android layouts in here */
	img {
		max-width: 100%;
		height: auto;
	}
}
/* Galaxy Nexus */
@media only screen and (min-device-width : 720px) and (max-device-width
	: 1280px) {
	img {
		max-width: 100%;
		height: auto;
	}
	body {
		font-size: 16px;
	}
}
/* end Android targeting */
</style>

</head>
<body>
	<table width="100%" cellpadding="0" cellspacing="0" border="0"
		id="backgroundTable"
		style="font-size: 17px; line-height: 24px; color: #373737; background: #F9F9F9;">
		<tr>
			<td valign="top">
				<table id="header" width="100%" cellpadding="0" cellspacing="0"
					border="0">
					<tr>
						<td valign="bottom" style="padding: 20px 16px 12px;">
							<div
								style="max-width: 600px; margin: 0 auto; text-align: center;">
								<a href="https://neliosoftware.com/testing/"> <img
									src="https://neliosoftware.com/wp-content/uploads/2018/05/nelio-ab-testing-colored-email-header-logo.png" alt="Nelio A/B Testing Logo"
									style="width: 307px; height: 100px; margin-left: -1.5rem" />
								</a>
							</div>
						</td>
					</tr>
				</table>
			</td>
		</tr>
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
											<a href="<?php echo esc_url( $account_url ); ?>" style="display:inline-block;padding:14px 32px;background:#ff7e00;border-radius:4px;font-weight:normal;letter-spacing:1px;font-size:20px;line-height:26px;color:white;text-decoration:none" target="_blank"><?php echo esc_html_x( 'Buy More Quota', 'command', 'nelio-ab-testing' ); ?></a>
										</p>

										<p style="font-size: 18px; line-height: 24px;">
											<?php
												echo esc_html_x( 'Please let us know if you think that you’ll need permanently more quota and we’ll provide you with a new fixed pricing to avoid any other inconvenience.', 'text', 'nelio-ab-testing' );
											?>
										</p>
									<?php }//end if ?>

									<p style="font-size: 18px; line-height: 24px;">
										<?php
											printf(
												/* translators: A mailto link */
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
		<tr>
			<td>
				<table id="footer" width="100%" cellpadding="0" cellspacing="0"
					border="0"
					style="margin-top: 1rem; background: white; color: #989EA6;">
					<tr>
						<td valign="top" align="center" style="padding: 16px 8px 24px;">
							<div style="max-width: 600px; margin: 0 auto;">
								<p class="footer_address"
									style="margin-top: 16px; font-size: 12px; line-height: 20px;">
									<?php
										printf(
											/* translators: 1 -> an URL, 2 -> CSS styles */
											wp_kses_data( _x( 'Sent by <a href="%1$s" style="%2$s">Nelio Software</a>.', 'text', 'nelio-ab-testing' ) ),
											esc_url( 'https://neliosoftware.com' ),
											'font-weight: bold; color: #439fe0;'
										);
										?>
									<br />
									Pomaret 83, &nbsp;&bull;&nbsp; 08017 Barcelona
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
