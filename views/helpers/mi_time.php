<?php
App::import('Helper', 'Time');

class MiTimeHelper extends TimeHelper {

	public $name = 'MiHtml';

	public $settings = array(
		'relativeTime' => array(
			'enabled' => true,
			'jsPacket' => null,
			'tag'  => '<time class="timeago" datetime=":date">:date</time>',
			'tag'  => '<abbr class="timeago" title=":date">:date</abbr>',
		)
	);

	protected $_jsAdded = false;

	public function __construct($settings = array()) {
		if (App::import('Helper', 'MiAsset.Asset')) {
			$this->helpers[] = 'MiAsset.Asset';
		}
		$this->settings = Set::merge($this->settings, $settings);
		parent::__construct($settings);
	}

	function timeAgoInWords($dateTime, $options = array()) {
		if (!isset($this->Asset) || !$this->settings['relativeTime']['enabled'] || isset($options['useCore'])) {
			unset($options['useCore']);
			return parent::timeAgoInWords($dateTime, $options);
		}

		if (!$this->_jsAdded) {
			if ($this->settings['relativeTime']['jsPacket'] !== false) {
				$this->Asset->js(array('jquery.timeago', 'jquery.mi.relativeTime'), $this->settings['relativeTime']['jsPacket']);
			}
			$this->_jsAdded = true;
		}

		$date = date('Y-m-d H:i O', $this->fromString($dateTime));
		return String::insert($this->settings['relativeTime']['tag'], array_merge($options, compact('date')));
	}
}