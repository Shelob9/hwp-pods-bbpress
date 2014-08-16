<?php

/**
 * Class pods_bbpress
 *
 * Adds Pods fields to bbpress topics.
 *
 * @see http://wp-dreams.com/articles/2013/06/adding-custom-fields-bbpress-topic-form/
 */
class pods_bbpress {

	static public $cache_group = 'hwp_bpp';

	function __construct() {
		add_action( 'bbp_theme_before_topic_form_content', array( $this, 'extra_fields' ), 99 );
		//add_filter( 'bbp_new_topic',  array( $this, 'save_extra_fields' ), 21 );
		add_action( 'bbp_template_before_replies_loop', array( $this,  'show_extra_fields' ) );
		add_action( 'bbp_post_request', array( $this, 'validate' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
		add_filter( 'pods_api_pre_save_pod_item_topic', array( $this, 'pre_save' ) );

	}

	/**
	 * Add extra fields to form
	 *
	 * @uses 'bbp_theme_before_topic_form_content'
	 *
	 * @since 0.0.1
	 */
	function extra_fields() {
		/**
		 * Set message to output before the Pods fields.
		 *
		 * @since 0.0.1
		 *
		 * @param bool|string $message The message to output. Leave at false to output no message.
		 *
		 * @return The message or false to prevent messages.
		 */
		$message = apply_filters( 'hwp_bpp_pre_pods_fields_message', false );

		if ( $message ) {
			echo '<div class="forums-extra-fields-message">' . $message . '</div>';
		}

		$fields = array_keys( $this->fields( $this->pod() ) );

		/**
		 * Override which fields are outputted
		 *
		 * @since 0.0.1
		 *
		 * @param array $fields A flat array of field names.
		 *
		 * @return bool|array Field names or false, which will prevent output.
		 *
		 */
		$fields = apply_filters( 'hwp_bpp_fields_to_output', $fields );

		if ( is_array( $fields ) ) {
			$params = array ( 'fields' => $fields, 'fields_only' => true );
			echo $this->pod()->form( $params );
		}

	}

	/**
	 * Save extra fields
	 *
	 * @TODO Cut this?
	 *
	 * @uses 'bbp_new_topic'
	 *
	 * @since 0.0.1
	 *
	 * @param $topic_id
	 */
	function save_extra_fields ( $id ) {

		$pod = $this->pod( $id );
		$data = false;
		$fields = $this->fields( $pod );

		foreach ( $fields as $field => $info  ) {
			$value = pods_v( $field, 'post' );
			if (  $value ) {
				$data[ $field ] = $value;
			}

		}

		if ( is_array( $data ) ) {
			$pod->save( $data );
		}


	}

	/**
	 * Show extra fields before replies, only if user is allowed to
	 *
	 * @todo make this variable who can see.
	 * @todo pretty up the output
	 */
	function show_extra_fields() {
		$capability = apply_filters( 'hwp_bbp_extra_fields_capability', 'moderate' );
		if ( current_user_can( $capability ) ) {
			global $post;
			$pods = $this->pod( $post->ID );
			if ( $pods ) {
				foreach( $this->fields( $pods ) as $field => $info ) {
					$value = $pods->display( $field );
					if ( $value ) {
						$label = $info[ 'label' ];

						$out[] = '<div class="pods-bbpress-extra-field" id="pods-bbpress-extra-field-' . $field . '"><span class="pods-bbpress-extra-field-label">'.$label . ':  </span>' . $value . '</div>';
					}

				}

			}

			$extra = apply_filters( 'hwp_bbp_show_fields_extra', false );
			if ( ! empty( $out ) || is_string( $extra ) ) {
				$out = implode( ' ', $out );
				if ( is_string( $extra ) ) {
					$out = $out.$extra;
				}
				$out = '<div id="pods-bbpress-extra-fields">'.$out.'</div>';
				echo $out;

			}
		}

	}


	/**
	 * Get Pods object for topics
	 *
	 * @param bool|int $id
	 *
	 * @return bool|Pods
	 */
	function pod( $id = false ) {

		$params ['expires' ] = 3567;

		if ( $id ) {
			$params[ 'where' ] = 't.ID = "'.$id.'"';
		}

		$pods = pods( 'topic', $id );

		return $pods;

	}

	/**
	 * The fields for topics pod.
	 *
	 * @param $pods
	 *
	 * @return mixed
	 */
	function fields( $pods ) {
		$key = 'hwp_bpp_fields_array';
		if ( HWP_BPP_DEV || false == ( $fields = pods_transient_get( $key ) )  ) {
			$fields = $pods->fields();

			pods_transient_set( $key, $fields );
		}

		if ( is_array( $fields ) ) {

			return $fields;
		}

	}

	function required_fields() {
		$key = 'hwp_pbb_required_fields';
		if ( HWP_BPP_DEV || false == ( $required_fields = pods_transient_get( $key ) ) ) {
			$fields = $this->fields( $this->pod() );
			if ( $fields ) {
				foreach ( $fields as $field => $info ) {
					if ( $info[ 'options' ][ 'required' ] ) {
						$required_fields[ $field ] = $info[ 'label' ];
					}

				}
			}

			if ( $required_fields && is_array( $required_fields ) ) {
				pods_transient_set( $key, $required_fields );
			}

		}

		if ( $required_fields && is_array( $required_fields ) ) {
			return $required_fields;
		}

	}



	/**
	 * Validate required fields
	 *
	 */
	function validate() {

		$required_fields = $this->required_fields();

			foreach ( $_POST as $field => $value ) {
				if ( in_array( $field, array_keys( $required_fields ) ) && empty( $value ) ) {
					bbp_add_error( "bbp_new_topic_{$field}", sprintf( __( '<strong>ERROR</strong>: The field %1s is required.', 'hwp_bpp' ), $required_fields[ $field ] ) );

				}

			}



	}

	/**
	 * Add Scripts and styles
	 *
	 * @since 0.0.1
	 *
	 * @uses wp_enqueue_scripts
	 */
	function scripts() {
		wp_enqueue_style( 'hwp-bpp', HWP_BPP_ASSETS_URL.'/css/hwp_pods_bbpress.min.css' );
	}

	/**
	 * Add the Pods fields being saved from $_POST to the Pods API's updating activities.
	 *
	 * @since 0.0.1
	 *
	 * @uses pods_api_pre_save_pod_item_topic
	 * @param $pieces
	 *
	 * @return mixed
	 */
	function pre_save( $pieces  ) {

		$fields = $this->fields( $this->pod( ) );
		if ( is_array( $fields ) ) {
			foreach ( $fields as $field => $info ) {
				$value = pods_v( $field, 'post', false, true );
				if ( $value ) {
					$pieces[ 'fields' ][ $field ][ 'value' ] = $value;
				}

			}
		}

		return $pieces;
	}

}
