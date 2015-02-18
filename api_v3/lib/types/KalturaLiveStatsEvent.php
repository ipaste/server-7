<?php
/**
 * Will hold data from the Kaltura Player components to be passed on to the live analytics system
 * @package api
 * @subpackage objects
 */
class KalturaLiveStatsEvent extends KalturaObject 
{
	
	/**
	 * @var int
	 */
	public $partnerId;
	
	/**
	 * @var string
	 */
	public $entryId;
	
    /**
     * an integer representing the type of event being sent from the player
     * @var KalturaLiveStatsEventType
     */
    public $eventType;
	
	/**
	 * a unique string generated by the client that will represent the client-side session: the primary component will pass it on to other components that sprout from it
	 * @var string
	 */
	public $sessionId;	
	
	/**
	 * incremental sequence of the event
	 * @var int
	 */
	public $eventIndex;
	
	/**
	 * buffer time in seconds from the last 10 seconds
	 * @var int
	 */
	public $bufferTime;
	
	/**
	 * bitrate used in the last 10 seconds
	 * @var int
	 */
	public $bitrate;
	
	/**
	 * the referrer of the client
	 * @var string
	 */
	public $referrer;	
	
	/**
	 * @var bool 
	 */
	public $isLive;
		
	/**
	 * delivery type used for this stream
	 * @var KalturaPlaybackProtocol 
	 */
	public $deliveryType;
		
}