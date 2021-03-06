<?php

namespace macropage\xmltv\parser;


use DateTimeZone;
use SimpleXMLElement;
use XMLReader;

class parser {

	private $file;
	private $channels;
	private $epgdata;
	private $channelfilter = [];
	private $ignoreDescr = [];
	private $targetTimeZone;

	public function __construct() {
		$this->targetTimeZone = date_default_timezone_get();
	}

	/**
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	public function parse(): void {

		if (!$this->file) {
			throw new \RuntimeException('missing file: please use setFile before parse');
		}

		if (!file_exists($this->file)) {
			throw new \RuntimeException('file does not exists: ' . $this->file);
		}

		$xml = new XMLReader();
		//compress.zlib://'
		$xml->open($this->file);

		/** @noinspection PhpStatementHasEmptyBodyInspection */
		/** @noinspection LoopWhichDoesNotLoopInspection */
		/** @noinspection MissingOrEmptyGroupStatementInspection */
		while ($xml->read() && $xml->name !== 'channel') {
		}

		while ($xml->name === 'channel') {
			$element = new SimpleXMLElement($xml->readOuterXML());

			/** @noinspection PhpUndefinedFieldInspection */
			$this->channels[(string)$element->attributes()->id] = (string)$element->{'display-name'};

			$xml->next('channel');
			unset($element);
		}

		$xml->close();
		$xml->open($this->file);

		/** @noinspection PhpStatementHasEmptyBodyInspection */
		/** @noinspection LoopWhichDoesNotLoopInspection */
		/** @noinspection MissingOrEmptyGroupStatementInspection */
		while ($xml->read() && $xml->name !== 'programme') {
		}

		while ($xml->name === 'programme') {
			$element = new SimpleXMLElement($xml->readOuterXML());

			/** @noinspection PhpUndefinedFieldInspection */
			if (
				!\count($this->channelfilter)
				||
				(\count($this->channelfilter) && $this->channelMatchFilter((string)$element->attributes()->channel))
			) {

				/** @noinspection PhpUndefinedFieldInspection */
				$start         = \DateTime::createFromFormat('YmdHis P', (string)$element->attributes()->start,new DateTimeZone('UTC'));
				$start->setTimezone(new DateTimeZone($this->targetTimeZone));
				$startString = $start->format('Y-m-d H:i:s');

				/** @noinspection PhpUndefinedFieldInspection */
				$stop = \DateTime::createFromFormat('YmdHis P', (string)$element->attributes()->stop,new DateTimeZone('UTC'));
				$stop->setTimezone(new DateTimeZone($this->targetTimeZone));
				$stopString = $stop->format('Y-m-d H:i:s');

				/** @noinspection PhpUndefinedFieldInspection */
				$this->epgdata[(string)$element->attributes()->channel . ' ' . $startString] = [
					'start'       => $startString,
					'start_raw'   => (string)$element->attributes()->start,
					'channel'     => (string)$element->attributes()->channel,
					'stop'        => $stopString,
					'title'       => (string)$element->title,
					'sub-title'   => (string)$element->{'sub-title'},
					'desc'        => $this->filterDescr((string)$element->desc),
					'date'        => (int)(string)$element->date,
					'country'     => (string)$element->country,
					'episode-num' => (string)$element->{'episode-num'},
				];

			}

			$xml->next('programme');
			unset($element);
		}

		$xml->close();

	}

	/**
	 * @param $descr
	 *
	 * @return string
	 */
	private function filterDescr($descr): string {
		if (array_key_exists($descr,$this->ignoreDescr)) {
			return '';
		}
		return $descr;
	}

	private function channelMatchFilter(string $channel): bool {
		return array_key_exists($channel, $this->channelfilter);
	}

	/**
	 * @param mixed $file
	 */
	public function setFile($file): void {
		$this->file = $file;
	}

	/**
	 * @return mixed
	 */
	public function getChannels() {
		return $this->channels;
	}

	/**
	 * @return array
	 */
	public function getEpgdata() {
		return $this->epgdata;
	}

	/**
	 * @param mixed $channelfilter
	 */
	public function setChannelfilter($channelfilter): void {
		$this->channelfilter[$channelfilter] = 1;
	}

	public function resetChannelfilter(): void {
		$this->channelfilter = [];
	}

	/**
	 * @param string $descr
	 */
	public function setIgnoreDescr(string $descr): void {
		$this->ignoreDescr[$descr]=1;
	}

	/**
	 * @param mixed $targetTimeZone
	 */
	public function setTargetTimeZone($targetTimeZone): void {
		$this->targetTimeZone = $targetTimeZone;
	}


}