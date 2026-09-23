<?php
/**
 * The "Community" post type: customer testimonials shown in the homepage
 * "Community Rotations" section (each one is a short quote from a
 * customer with a star rating).
 *
 * Each entry is a plain post in the admin: the quote is the post content
 * (classic editor — it's a couple of sentences, not a page), and a
 * "Testimonial details" box below it holds the rating (1–5 stars), first
 * name, surname, position, company and address. There is no title field:
 * the title is filled in from the name on save so the list stays readable.
 *
 * It's registered here, not in the theme, because it's content data:
 * switching themes must not make the testimonials disappear from the
 * admin. The theme only reads it (see inc/community.php). The post type
 * is not public — no single or archive pages, not in search.
 *
 * @package RX\Core
 */

declare(strict_types=1);

namespace RX\Core\Community;

use RX\Core\Service;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the post type, its details box, saving, and admin columns.
 */
final class CommunityPostType implements Service {

	/**
	 * Post type key. The theme queries by this name.
	 */
	public const POST_TYPE = 'rx_community';

	/**
	 * Meta keys (the theme reads these by key, like the product-card meta).
	 */
	public const RATING     = '_rx_community_rating';
	public const FIRST_NAME = '_rx_community_first_name';
	public const SURNAME    = '_rx_community_surname';
	public const POSITION   = '_rx_community_position';
	public const COMPANY    = '_rx_community_company';
	public const ADDRESS    = '_rx_community_address';

	/**
	 * Nonce action / field for the details box.
	 */
	private const NONCE_ACTION = 'rx_community_save';
	private const NONCE_FIELD  = 'rx_community_nonce';

	/**
	 * Hook registration.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes_' . self::POST_TYPE, array( $this, 'add_details_box' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_details' ), 10, 2 );
		add_filter( 'use_block_editor_for_post_type', array( $this, 'use_classic_editor' ), 10, 2 );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
	}

	/**
	 * Register the post type.
	 */
	public function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => _x( 'Community', 'post type general name', 'rx-core' ),
					'singular_name'      => _x( 'Community testimonial', 'post type singular name', 'rx-core' ),
					'menu_name'          => __( 'Community', 'rx-core' ),
					'all_items'          => __( 'All testimonials', 'rx-core' ),
					'add_new'            => __( 'Add testimonial', 'rx-core' ),
					'add_new_item'       => __( 'Add community testimonial', 'rx-core' ),
					'edit_item'          => __( 'Edit community testimonial', 'rx-core' ),
					'new_item'           => __( 'New community testimonial', 'rx-core' ),
					'view_item'          => __( 'View testimonial', 'rx-core' ),
					'search_items'       => __( 'Search testimonials', 'rx-core' ),
					'not_found'          => __( 'No testimonials found.', 'rx-core' ),
					'not_found_in_trash' => __( 'No testimonials found in the bin.', 'rx-core' ),
				),
				'description'         => __( 'Customer testimonials shown in the homepage "Community Rotations" section.', 'rx-core' ),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => true,
				'menu_position'       => 26,
				'menu_icon'           => 'dashicons-format-quote',
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'editor', 'page-attributes' ),
			)
		);
	}

	/**
	 * A testimonial is a couple of sentences: use the classic editor
	 * (a plain text box) rather than the block editor.
	 *
	 * @param bool   $use_block_editor Whether to use the block editor.
	 * @param string $post_type        Post type being edited.
	 */
	public function use_classic_editor( bool $use_block_editor, string $post_type ): bool {
		return self::POST_TYPE === $post_type ? false : $use_block_editor;
	}

	/**
	 * Add the details box below the editor.
	 */
	public function add_details_box(): void {
		add_meta_box(
			'rx-community-details',
			__( 'Testimonial details', 'rx-core' ),
			array( $this, 'render_details_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render the details box.
	 *
	 * @param WP_Post $post Post being edited.
	 */
	public function render_details_box( WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$rating = $this->rating( $post->ID );
		$fields = array(
			self::FIRST_NAME => array( __( 'First name', 'rx-core' ), __( 'e.g. Marcus', 'rx-core' ) ),
			self::SURNAME    => array( __( 'Surname', 'rx-core' ), __( 'e.g. Larsen (the homepage shows the initial only)', 'rx-core' ) ),
			self::POSITION   => array( __( 'Position', 'rx-core' ), __( 'e.g. Strength Coach', 'rx-core' ) ),
			self::COMPANY    => array( __( 'Company', 'rx-core' ), __( 'e.g. CrossFit Torian', 'rx-core' ) ),
			self::ADDRESS    => array( __( 'Address', 'rx-core' ), __( 'e.g. Brisbane, QLD', 'rx-core' ) ),
		);
		?>
		<p class="description">
			<?php esc_html_e( 'Write the customer\'s quote in the box above. Position, company and address are optional and show on the card separated by bullets.', 'rx-core' ); ?>
		</p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="<?php echo esc_attr( self::RATING ); ?>"><?php esc_html_e( 'Rating', 'rx-core' ); ?></label></th>
				<td>
					<select name="<?php echo esc_attr( self::RATING ); ?>" id="<?php echo esc_attr( self::RATING ); ?>">
						<?php for ( $stars = 5; $stars >= 1; $stars-- ) : ?>
							<option value="<?php echo (int) $stars; ?>" <?php selected( $rating, $stars ); ?>>
								<?php
								echo esc_html(
									str_repeat( '★', $stars ) . str_repeat( '☆', 5 - $stars ) . ' — ' .
									/* translators: %d: number of stars, 1-5. */
									sprintf( _n( '%d star', '%d stars', $stars, 'rx-core' ), $stars )
								);
								?>
							</option>
						<?php endfor; ?>
					</select>
				</td>
			</tr>
			<?php foreach ( $fields as $key => $field ) : ?>
				<tr>
					<th scope="row"><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field[0] ); ?></label></th>
					<td>
						<input type="text" class="regular-text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, $key, true ) ); ?>" placeholder="<?php echo esc_attr( $field[1] ); ?>">
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php
	}

	/**
	 * Save the details, then keep the post title in step with the name.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post being saved.
	 */
	public function save_details( int $post_id, WP_Post $post ): void {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified above.
		$rating = isset( $_POST[ self::RATING ] ) ? absint( $_POST[ self::RATING ] ) : 5;
		update_post_meta( $post_id, self::RATING, (string) min( 5, max( 1, $rating ) ) );

		foreach ( array( self::FIRST_NAME, self::SURNAME, self::POSITION, self::COMPANY, self::ADDRESS ) as $key ) {
			$value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
			update_post_meta( $post_id, $key, $value );
		}
		// phpcs:enable

		$name = trim( (string) get_post_meta( $post_id, self::FIRST_NAME, true ) . ' ' . (string) get_post_meta( $post_id, self::SURNAME, true ) );
		$name = '' !== $name ? $name : __( '(No name)', 'rx-core' );

		if ( $post->post_title !== $name ) {
			// wp_update_post() fires this hook again; step aside for it.
			remove_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_details' ), 10 );
			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => $name,
					'post_name'  => sanitize_title( $name . '-' . $post_id ),
				)
			);
			add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_details' ), 10, 2 );
		}
	}

	/**
	 * A testimonial's rating, 1–5 (defaults to 5 when unset).
	 *
	 * @param int $post_id Post ID.
	 */
	private function rating( int $post_id ): int {
		$stored = get_post_meta( $post_id, self::RATING, true );

		return '' === $stored ? 5 : min( 5, max( 1, (int) $stored ) );
	}

	/**
	 * List-table columns: name, rating, role, address, order, date.
	 *
	 * @param array<string,string> $columns Default columns.
	 * @return array<string,string>
	 */
	public function columns( array $columns ): array {
		return array(
			'cb'         => $columns['cb'] ?? '',
			'title'      => __( 'Name', 'rx-core' ),
			'rx_rating'  => __( 'Rating', 'rx-core' ),
			'rx_role'    => __( 'Position / company', 'rx-core' ),
			'rx_address' => __( 'Address', 'rx-core' ),
			'rx_order'   => __( 'Order', 'rx-core' ),
			'date'       => $columns['date'] ?? __( 'Date', 'rx-core' ),
		);
	}

	/**
	 * Fill the custom columns.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public function render_column( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'rx_rating':
				$rating = $this->rating( $post_id );
				echo '<span style="color:#f59e0b" aria-label="' . esc_attr( sprintf( /* translators: %d: stars. */ _n( '%d star', '%d stars', $rating, 'rx-core' ), $rating ) ) . '">' . esc_html( str_repeat( '★', $rating ) ) . '</span>' . esc_html( str_repeat( '☆', 5 - $rating ) );
				break;
			case 'rx_role':
				$parts = array_filter(
					array(
						(string) get_post_meta( $post_id, self::POSITION, true ),
						(string) get_post_meta( $post_id, self::COMPANY, true ),
					)
				);
				echo esc_html( implode( ' · ', $parts ) );
				break;
			case 'rx_address':
				echo esc_html( (string) get_post_meta( $post_id, self::ADDRESS, true ) );
				break;
			case 'rx_order':
				echo (int) get_post_field( 'menu_order', $post_id );
				break;
		}
	}
}
