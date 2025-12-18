<?php
/**
 * SSL Helper Class
 * 
 * Helps detect SSL certificate issues and guides users to solutions.
 *
 * @package Custom_PWA
 * @since 1.0.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom_PWA_SSL_Helper class.
 */
class Custom_PWA_SSL_Helper {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_ssl_helper_page' ), 99 );
		add_action( 'admin_notices', array( $this, 'ssl_warning_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Add SSL Helper submenu page.
	 */
	public function add_ssl_helper_page() {
		add_submenu_page(
			'custom-pwa',
			__( 'SSL Setup Helper', 'custom-pwa' ),
			__( 'SSL Helper', 'custom-pwa' ),
			'manage_options',
			'custom_pwa_ssl_helper',
			array( $this, 'render_ssl_helper_page' )
		);
	}

	/**
	 * Check if SSL certificate is valid for the current domain.
	 *
	 * @return bool
	 */
	public function is_ssl_valid() {
		// If not using HTTPS, return false
		if ( ! is_ssl() ) {
			return false;
		}

		// Check if it's a local development domain
		$host = $_SERVER['HTTP_HOST'] ?? '';
		$is_local = $this->is_local_domain( $host );

		// For local domains, check if mkcert is installed
		if ( $is_local ) {
			return $this->is_mkcert_installed();
		}

		// For production domains, assume SSL is valid if HTTPS is enabled
		return true;
	}

	/**
	 * Check if domain is a local development domain.
	 *
	 * @param string $host Hostname.
	 * @return bool
	 */
	public function is_local_domain( $host ) {
		$local_domains = array( 'localhost', '127.0.0.1' );
		$local_tlds = array( '.local', '.test', '.dev', '.localhost' );

		if ( in_array( $host, $local_domains, true ) ) {
			return true;
		}

		foreach ( $local_tlds as $tld ) {
			if ( strpos( $host, $tld ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if mkcert is installed.
	 *
	 * @return bool
	 */
	public function is_mkcert_installed() {
		$output = array();
		$return_var = 0;
		exec( 'which mkcert 2>/dev/null', $output, $return_var );
		return $return_var === 0;
	}

	/**
	 * Display SSL warning notice in admin.
	 */
	public function ssl_warning_notice() {
		// Only show on Custom PWA pages
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'custom_pwa' ) === false ) {
			return;
		}

		// Don't show if SSL is valid
		if ( $this->is_ssl_valid() ) {
			return;
		}

		// Only show if Push is enabled
		$config = get_option( 'custom_pwa_config', array() );
		if ( empty( $config['enable_push'] ) ) {
			return;
		}

		// Check if user dismissed the notice
		if ( get_user_meta( get_current_user_id(), 'custom_pwa_ssl_notice_dismissed', true ) ) {
			return;
		}

		$host = $_SERVER['HTTP_HOST'] ?? '';
		$is_local = $this->is_local_domain( $host );

		?>
		<div class="notice notice-warning is-dismissible custom-pwa-ssl-warning">
			<h3><?php esc_html_e( '🔒 SSL Certificate Warning', 'custom-pwa' ); ?></h3>
			<p>
				<?php
				if ( $is_local ) {
					esc_html_e( 'Service Workers require a valid SSL certificate. Your local development environment uses a self-signed certificate that browsers will block.', 'custom-pwa' );
				} else {
					esc_html_e( 'Service Workers require HTTPS. Your site is not using a secure connection.', 'custom-pwa' );
				}
				?>
			</p>
			<?php if ( $is_local ) : ?>
				<p>
					<strong><?php esc_html_e( 'Quick Fix:', 'custom-pwa' ); ?></strong>
					<?php esc_html_e( 'Use mkcert to generate a valid local SSL certificate.', 'custom-pwa' ); ?>
				</p>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=custom_pwa_ssl_helper' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Open SSL Setup Helper', 'custom-pwa' ); ?>
					</a>
					<button type="button" class="button custom-pwa-copy-command" data-command="cd <?php echo esc_attr( CUSTOM_PWA_PLUGIN_DIR ); ?> && sudo bash install-mkcert.sh <?php echo esc_attr( $host ); ?>">
						<?php esc_html_e( 'Copy Installation Command', 'custom-pwa' ); ?>
					</button>
				</p>
			<?php else : ?>
				<p>
					<a href="https://wordpress.org/support/article/https-for-wordpress/" target="_blank" class="button button-primary">
						<?php esc_html_e( 'Learn How to Enable HTTPS', 'custom-pwa' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Enqueue admin scripts.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( strpos( $hook, 'custom_pwa' ) === false ) {
			return;
		}

		?>
		<script>
		jQuery(document).ready(function($) {
			// Copy command to clipboard
			$('.custom-pwa-copy-command').on('click', function(e) {
				e.preventDefault();
				var command = $(this).data('command');
				
				// Create temporary textarea
				var $temp = $('<textarea>');
				$('body').append($temp);
				$temp.val(command).select();
				document.execCommand('copy');
				$temp.remove();
				
				// Show feedback
				var $btn = $(this);
				var originalText = $btn.text();
				$btn.text('✓ Copied!');
				setTimeout(function() {
					$btn.text(originalText);
				}, 2000);
			});
		});
		</script>
		<style>
		.custom-pwa-ssl-warning h3 {
			margin-top: 0.5em;
			margin-bottom: 0.5em;
		}
		.custom-pwa-ssl-warning .button {
			margin-right: 10px;
			margin-top: 5px;
		}
		</style>
		<?php
	}

	/**
	 * Render SSL Helper page.
	 */
	public function render_ssl_helper_page() {
		$host = $_SERVER['HTTP_HOST'] ?? '';
		$is_local = $this->is_local_domain( $host );
		$is_https = is_ssl();
		$mkcert_installed = $this->is_mkcert_installed();
		$ssl_valid = $this->is_ssl_valid();

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'SSL Setup Helper', 'custom-pwa' ); ?></h1>
			
			<div class="card">
				<h2><?php esc_html_e( 'Current Status', 'custom-pwa' ); ?></h2>
				<table class="widefat">
					<tbody>
						<tr>
							<td><strong><?php esc_html_e( 'Domain:', 'custom-pwa' ); ?></strong></td>
							<td><code><?php echo esc_html( $host ); ?></code></td>
							<td>
								<?php if ( $is_local ) : ?>
									<span class="dashicons dashicons-yes" style="color: orange;"></span>
									<?php esc_html_e( 'Local Development', 'custom-pwa' ); ?>
								<?php else : ?>
									<span class="dashicons dashicons-yes" style="color: green;"></span>
									<?php esc_html_e( 'Production', 'custom-pwa' ); ?>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'HTTPS Enabled:', 'custom-pwa' ); ?></strong></td>
							<td><code><?php echo $is_https ? 'Yes' : 'No'; ?></code></td>
							<td>
								<?php if ( $is_https ) : ?>
									<span class="dashicons dashicons-yes" style="color: green;"></span>
									<?php esc_html_e( 'Enabled', 'custom-pwa' ); ?>
								<?php else : ?>
									<span class="dashicons dashicons-no" style="color: red;"></span>
									<?php esc_html_e( 'Disabled', 'custom-pwa' ); ?>
								<?php endif; ?>
							</td>
						</tr>
						<?php if ( $is_local ) : ?>
						<tr>
							<td><strong><?php esc_html_e( 'mkcert Installed:', 'custom-pwa' ); ?></strong></td>
							<td><code><?php echo $mkcert_installed ? 'Yes' : 'No'; ?></code></td>
							<td>
								<?php if ( $mkcert_installed ) : ?>
									<span class="dashicons dashicons-yes" style="color: green;"></span>
									<?php esc_html_e( 'Installed', 'custom-pwa' ); ?>
								<?php else : ?>
									<span class="dashicons dashicons-no" style="color: orange;"></span>
									<?php esc_html_e( 'Not Installed', 'custom-pwa' ); ?>
								<?php endif; ?>
							</td>
						</tr>
						<?php endif; ?>
						<tr>
							<td><strong><?php esc_html_e( 'SSL Certificate:', 'custom-pwa' ); ?></strong></td>
							<td><code><?php echo $ssl_valid ? 'Valid' : 'Invalid/Self-signed'; ?></code></td>
							<td>
								<?php if ( $ssl_valid ) : ?>
									<span class="dashicons dashicons-yes" style="color: green;"></span>
									<?php esc_html_e( 'Valid - Service Workers will work', 'custom-pwa' ); ?>
								<?php else : ?>
									<span class="dashicons dashicons-warning" style="color: red;"></span>
									<?php esc_html_e( 'Invalid - Service Workers blocked', 'custom-pwa' ); ?>
								<?php endif; ?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<?php if ( ! $ssl_valid && $is_local ) : ?>
			<div class="card">
				<h2><?php esc_html_e( 'Installation Instructions', 'custom-pwa' ); ?></h2>
				
				<h3><?php esc_html_e( 'Option 1: Automatic Installation (Recommended)', 'custom-pwa' ); ?></h3>
				<p><?php esc_html_e( 'Copy and paste this command in your terminal:', 'custom-pwa' ); ?></p>
				<div style="background: #f0f0f1; padding: 15px; border-radius: 4px; margin: 10px 0;">
					<code style="font-size: 14px;">cd <?php echo esc_html( CUSTOM_PWA_PLUGIN_DIR ); ?> && sudo bash install-mkcert.sh <?php echo esc_html( $host ); ?></code>
					<button type="button" class="button button-small custom-pwa-copy-command" data-command="cd <?php echo esc_attr( CUSTOM_PWA_PLUGIN_DIR ); ?> && sudo bash install-mkcert.sh <?php echo esc_attr( $host ); ?>" style="margin-left: 10px;">
						<?php esc_html_e( 'Copy Command', 'custom-pwa' ); ?>
					</button>
				</div>
				<p><em><?php esc_html_e( 'This will install mkcert, generate a valid certificate, and configure nginx automatically.', 'custom-pwa' ); ?></em></p>

				<h3><?php esc_html_e( 'Option 2: Manual Steps', 'custom-pwa' ); ?></h3>
				<ol>
					<li>
						<strong><?php esc_html_e( 'Install mkcert:', 'custom-pwa' ); ?></strong>
						<pre style="background: #f0f0f1; padding: 10px; border-radius: 4px;">sudo apt install libnss3-tools
wget https://github.com/FiloSottile/mkcert/releases/download/v1.4.4/mkcert-v1.4.4-linux-amd64
sudo mv mkcert-v1.4.4-linux-amd64 /usr/local/bin/mkcert
sudo chmod +x /usr/local/bin/mkcert
mkcert -install</pre>
					</li>
					<li>
						<strong><?php esc_html_e( 'Generate certificate:', 'custom-pwa' ); ?></strong>
						<pre style="background: #f0f0f1; padding: 10px; border-radius: 4px;">cd /tmp
mkcert <?php echo esc_html( $host ); ?>
sudo mkdir -p /etc/nginx/ssl
sudo cp <?php echo esc_html( $host ); ?>*.pem /etc/nginx/ssl/</pre>
					</li>
					<li>
						<strong><?php esc_html_e( 'Update nginx config:', 'custom-pwa' ); ?></strong>
						<pre style="background: #f0f0f1; padding: 10px; border-radius: 4px;">ssl_certificate /etc/nginx/ssl/<?php echo esc_html( $host ); ?>.pem;
ssl_certificate_key /etc/nginx/ssl/<?php echo esc_html( $host ); ?>-key.pem;</pre>
					</li>
					<li>
						<strong><?php esc_html_e( 'Reload nginx:', 'custom-pwa' ); ?></strong>
						<pre style="background: #f0f0f1; padding: 10px; border-radius: 4px;">sudo nginx -t
sudo systemctl reload nginx</pre>
					</li>
				</ol>

				<h3><?php esc_html_e( 'Option 3: Chrome Development Flags (Temporary)', 'custom-pwa' ); ?></h3>
				<p><?php esc_html_e( 'For quick testing only (not recommended for daily use):', 'custom-pwa' ); ?></p>
				<pre style="background: #f0f0f1; padding: 10px; border-radius: 4px;">killall chrome
google-chrome --ignore-certificate-errors --unsafely-treat-insecure-origin-as-secure=https://<?php echo esc_html( $host ); ?> --user-data-dir=/tmp/chrome-dev &</pre>
				<p><strong style="color: red;"><?php esc_html_e( '⚠️ Warning: Only use this Chrome profile for local development. Never browse other websites with these flags!', 'custom-pwa' ); ?></strong></p>
			</div>

			<div class="card">
				<h2><?php esc_html_e( 'After Installation', 'custom-pwa' ); ?></h2>
				<ol>
					<li><?php esc_html_e( 'Close and restart your browser completely', 'custom-pwa' ); ?></li>
					<li><?php esc_html_e( 'Visit your site and open the browser console (F12)', 'custom-pwa' ); ?></li>
					<li><?php esc_html_e( 'You should see: "[Custom PWA] Service Worker registered successfully"', 'custom-pwa' ); ?></li>
					<li><?php esc_html_e( 'Test push notifications from the Push menu', 'custom-pwa' ); ?></li>
				</ol>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=custom_pwa_ssl_helper' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Refresh Status', 'custom-pwa' ); ?>
					</a>
				</p>
			</div>
			<?php elseif ( $ssl_valid ) : ?>
			<div class="notice notice-success inline">
				<p><strong><?php esc_html_e( '✓ Your SSL certificate is valid! Service Workers will work correctly.', 'custom-pwa' ); ?></strong></p>
			</div>
			<?php endif; ?>

			<div class="card">
				<h2><?php esc_html_e( 'Additional Resources', 'custom-pwa' ); ?></h2>
				<ul>
					<li><a href="<?php echo esc_url( CUSTOM_PWA_PLUGIN_URL . 'SSL-SETUP.md' ); ?>" target="_blank"><?php esc_html_e( 'Complete SSL Setup Documentation', 'custom-pwa' ); ?></a></li>
					<li><a href="https://github.com/FiloSottile/mkcert" target="_blank"><?php esc_html_e( 'mkcert GitHub Repository', 'custom-pwa' ); ?></a></li>
					<li><a href="https://web.dev/service-workers-cache-storage/" target="_blank"><?php esc_html_e( 'Service Workers Documentation', 'custom-pwa' ); ?></a></li>
				</ul>
			</div>
		</div>
		<?php
	}
}
