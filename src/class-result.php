<?php
/**
 * Health result value object.
 *
 * @package StoreCheckup
 */

namespace StoreCheckup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Result {
	public const CRITICAL = 'critical';
	public const WARNING  = 'warning';
	public const PASSED   = 'passed';
	public const INFO     = 'info';

	public $id;
	public $area;
	public $status;
	public $title;
	public $message;
	public $count;
	public $action_url;
	public $action_label;

	public function __construct( $id, $area, $status, $title, $message, $count = 0, $action_url = '', $action_label = '' ) {
		$this->id           = sanitize_key( $id );
		$this->area         = sanitize_key( $area );
		$this->status       = in_array( $status, array( self::CRITICAL, self::WARNING, self::PASSED, self::INFO ), true ) ? $status : self::INFO;
		$this->title        = (string) $title;
		$this->message      = (string) $message;
		$this->count        = max( 0, (int) $count );
		$this->action_url   = (string) $action_url;
		$this->action_label = (string) $action_label;
	}

	public function to_array() {
		return array(
			'id'           => $this->id,
			'area'         => $this->area,
			'status'       => $this->status,
			'title'        => $this->title,
			'message'      => $this->message,
			'count'        => $this->count,
			'action_url'   => $this->action_url,
			'action_label' => $this->action_label,
		);
	}
}
