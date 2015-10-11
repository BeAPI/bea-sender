<?php
namespace BEA\Sender\Core;

class Content {

	private $id = 0;
	private $content_html = '';
	private $content_text = '';

	/**
	 * @param $id
	 */
	function __construct( $id ) {
		$this->id = (int) $id;
		$this->init();
	}

	/**
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @param $content_html
	 * @param string $content_text
	 *
	 * @return Content|bool
	 * @author Alexandre Sadowski
	 */
	public static function create( $content_html, $content_text = '' ) {
		/**
		 * @var $wpdb \wpdb
		 */
		global $wpdb;

		if ( ! isset( $content_html ) || empty( $content_html ) ) {
			return false;
		}

		$content_text = ! empty( $content_text ) ? strip_tags( $content_text ) : '';

		$inserted = $wpdb->insert( $wpdb->bea_s_contents, array(
			'html' => $content_html,
			'text' => $content_text,
		) );

		if ( false === $inserted ) {
			return false;
		}

		//Return inserted element
		return new self( $wpdb->insert_id );
	}

	/**
	 * @return string
	 * @author Nicolas Juen
	 */
	public function get_html() {
		return $this->content_html;
	}

	/**
	 * @return string
	 * @author Nicolas Juen
	 */
	public function get_text() {
		return $this->content_text;
	}

	/**
	 * @return bool
	 * @author Alexandre Sadowski
	 */
	private function init() {
		/**
		 * @var $wpdb \wpdb
		 */
		global $wpdb;

		$data = $wpdb->get_row( $wpdb->prepare( "SELECT html, text FROM $wpdb->bea_s_contents WHERE id = %d ", $this->id ) );
		if ( empty( $data ) ) {
			return false;
		}

		$this->content_html = $data->html;
		$this->content_text = $data->text;
	}

	/**
	 * @param $content_html
	 * @param string $content_text
	 *
	 * @return bool|false|int|WP_Error
	 * @author Alexandre Sadowski
	 */
	public function update( $content_html, $content_text = '' ) {
		/**
		 * @var $wpdb \wpdb
		 */
		global $wpdb;
		if ( 0 === $this->id ) {
			return new WP_Error( 'not-found', __( 'Content not found in database', 'bea-sender' ) );
		}

		if ( ! isset( $content_html ) || empty( $content_html ) ) {
			return false;
		}

		$content_text = ! empty( $content_text ) ? strip_tags( $content_text ) : '';

		$updated = $wpdb->update( $wpdb->bea_s_contents, array(
			'html' => $content_html,
			'text' => $content_text,
		), array( 'id' => $this->id ) );
		$this->init();

		return $updated;
	}
}
