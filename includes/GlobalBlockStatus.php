<?php

namespace MediaWiki\Extension\GlobalBlocking;

use StatusValue;

/**
 * The status of a global block operation, optionally providing separate global
 * and local components.
 *
 * @template T
 * @inherits StatusValue<T>
 */
class GlobalBlockStatus extends StatusValue {
	private ?StatusValue $globalStatus = null;
	private ?StatusValue $localStatus = null;

	/**
	 * @internal Can be constructed with no parameters by the parent class. The parameters
	 *   are just for withLocalStatus().
	 *
	 * @param StatusValue<T>|null $globalStatus
	 * @param StatusValue<T>|null $localStatus
	 */
	public function __construct( ?StatusValue $globalStatus = null, ?StatusValue $localStatus = null ) {
		if ( $globalStatus ) {
			$this->globalStatus = $globalStatus;
			$this->merge( $globalStatus, true );
		}
		if ( $localStatus ) {
			$this->localStatus = $localStatus;
			$this->merge( $localStatus );
		}
	}

	/**
	 * Create a new GlobalBlockStatus, with the current value as the global
	 * status and the passed value as the local status.
	 *
	 * @param StatusValue $localStatus
	 * @return self
	 */
	public function withLocalStatus( StatusValue $localStatus ): self {
		return new self( $this, $localStatus );
	}

	/**
	 * Did the global block operation complete successfully?
	 *
	 * @return bool
	 */
	public function isGlobalBlockOK(): bool {
		return $this->globalStatus ? $this->globalStatus->isOK() : $this->isOK();
	}

	/**
	 * Did the local block operation complete successfully? This is false if a
	 * local block was not attempted or failed.
	 *
	 * @return bool
	 */
	public function isLocalBlockOK(): bool {
		return $this->localStatus ? $this->localStatus->isOK() : false;
	}

	/**
	 * Did the local block fail? This is false if the local block was not
	 * attempted.
	 *
	 * @return bool
	 */
	public function hasLocalBlockError(): bool {
		return $this->localStatus && !$this->localStatus->isOK();
	}

	/**
	 * Get the global component of the status. If there is no local component,
	 * $this is the global component.
	 */
	public function getGlobalStatus(): StatusValue {
		return $this->globalStatus ?? $this;
	}
}
