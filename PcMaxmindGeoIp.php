<?php
/**
 * Created on 01 06 2012 (11:57 AM)
 *
 *
 * @license:
 * Copyright (c) 2012, Boaz Rymland
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 * - Redistributions of source code must retain the above copyright notice, this list of conditions and the following
 *      disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following
 *      disclaimer in the documentation and/or other materials provided with the distribution.
 * - The names of the contributors may not be used to endorse or promote products derived from this software without
 *      specific prior written permission.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
 * TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
 * EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

class PcMaxmindGeoIp extends CApplicationComponent {
	const DEFAULT_DB_FILENAME = "GeoLiteCity.dat";
	const CLIENT_DIRNAME = "geoip_maxmind_pure_php_API__php-1.11";
	const CLIENT_FILENAME_A = "geoip.inc";
	const CLIENT_FILENAME_B = "geoipcity.inc";

	/* @var string $dbFilename */
	public $dbFilename;

	/* @var GeoIP $_geoDbResource */
	private $_geoDbResource;

	/**
	 * init...
	 *
	 * @throws CException
	 */
	public function init() {
		// initialize dbFileName if none provided
		/*if ((!is_string($this->dbFilename)) || (strlen($this->dbFilename) == 0)) {
			throw new CException("Bummer - no GeoIP DB file. I cannot continue...");
		}*/
		if (!is_string($this->dbFilename)) {
			$this->dbFilename = dirname(__FILE__) . "/maxmind/" . self::DEFAULT_DB_FILENAME;
		}
		// make sure the now-initialized db file exists and readable
		if (!is_readable($this->dbFilename)) {
			throw new CException("Bummer - cannot read GeoIP DB file=" . $this->dbFilename);
		}
		// verify the client class can be accessed now, and avoid exceptions later down the road:
		if (!is_readable(dirname(__FILE__) . "/maxmind/" . self::CLIENT_DIRNAME . "/" . self::CLIENT_FILENAME_A)) {
			throw new CException("Bummer - cannot read Maxmind client file=" . dirname(__FILE__) . "/" . self::CLIENT_DIRNAME . "/" . self::CLIENT_FILENAME_A);
		}

		parent::init();
	}

	/**
	 * This method will 'include' the client class file and create an internal, private resource for it.
	 *
	 * We put it here and on in init() so it will be loaded only when needed and not in every init() to this extension, which is typically
	 * on each request. Note that this requires calling this method in the beginning of every other method here that needs the services of
	 * the maxmind information.
	 */
	private function _loadClientFile() {
		require_once(dirname(__FILE__) . "/maxmind/" . self::CLIENT_DIRNAME . "/" . self::CLIENT_FILENAME_A);
		require_once(dirname(__FILE__) . "/maxmind/" . self::CLIENT_DIRNAME . "/" . self::CLIENT_FILENAME_B);
		$this->_geoDbResource = geoip_open($this->dbFilename, GEOIP_STANDARD);
	}

	/**
	 * @param string $ip_address
	 * @return array of geo information (array values might be false if partial information retrieved or all false if geo IP querying failed)
	 */
	public function getCityInfoForIp($ip_address) {
		// load client library
		$this->_loadClientFile();
		$geoiprecord = geoip_record_by_addr($this->_geoDbResource, $ip_address);
		geoip_close($this->_geoDbResource);

		// build data to be returned as an array. for failures to get data $geoiprecord will be null itself.
		$data['country_code'] = isset($geoiprecord->country_code) ? $geoiprecord->country_code : false;
		$data['country_code3'] = isset($geoiprecord->country_code3) ? $geoiprecord->country_code3 : false;
		$data['country_name'] = isset($geoiprecord->country_name) ? $geoiprecord->country_name : false;
		$data['region'] = isset($geoiprecord->region) ? $geoiprecord->region : false;
		$data['city'] = isset($geoiprecord->city) ? $geoiprecord->city : false;
		$data['postal_code'] = isset($geoiprecord->postal_code) ? $geoiprecord->postal_code : false;
		$data['latitude'] = isset($geoiprecord->latitude) ? $geoiprecord->latitude : false;
		$data['longitude'] = isset($geoiprecord->longitude) ? $geoiprecord->longitude : false;
		$data['area_code'] = isset($geoiprecord->area_code) ? $geoiprecord->area_code : false;
		$data['dma_code'] = isset($geoiprecord->dma_code) ? $geoiprecord->dma_code : false;
		$data['metro_code'] = isset($geoiprecord->metro_code) ? $geoiprecord->metro_code : false;
		$data['continent_code'] = isset($geoiprecord->continent_code) ? $geoiprecord->continent_code : false;

		return $data;
	}

	/**
	 * Returns the valid IP address of the current user.
	 * Supports IPv6 addresses (at least, supposed to :-)
	 * 
	 * IMPORTANT SECURITY NOTICE: since the http headers used by this function can forged with little effort never trust the answer
	 * returned by this method for security decisions. Even when used for statistics always remember - INFORMATION RETURNED BY THIS 
	 * METHOD IS INACCRUTATE AND FORGE-ABLE. **NEVER TRUST IT**. 
	 *
	 * @return string
	 */
	public function getRemoteIpAddress() {
		foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
			if (array_key_exists($key, $_SERVER) === true) {
				foreach (explode(',', $_SERVER[$key]) as $ip) {
					if (filter_var($ip, FILTER_VALIDATE_IP, array('flags' => array(FILTER_FLAG_IPV4, FILTER_FLAG_IPV6))) !== false) {
						return $ip;
					}
				}
			}
		}
		// we we always return something from the loop above. at "worst", that will be $_SERVER['REMOTE_ADDR']. But, hmm... haven't checked CLI mode... .
	}

	/**
	 * Tells whether the given IP address is a 'public' IP and is routable, meaning not internal network (10.0.0.0/8...) or belonging to some reserved range.
	 * Supports IPv6 addresses (at least, supposed to :-)
	 *
	 * @param string $ip_address the tested IP address
	 * @return bool
	 */
	public function isPubliclyRoutableIpAddress($ip_address) {
		// check private networks:
		//if (filter_var($ip_address, FILTER_VALIDATE_IP, array('flags' => array(FILTER_FLAG_IPV4, FILTER_FLAG_IPV6, FILTER_FLAG_NO_PRIV_RANGE))) === false) {
		if (filter_var($ip_address, FILTER_VALIDATE_IP, array('flags' => array(FILTER_FLAG_NO_PRIV_RANGE))) === false) {
			// private network
			return false;
		}

		/* Since we use PHP's (now commonly found) "Filter" extension, and since its flag for reserved addresses does not apply to IPv6 addresses, the
		following will pass always for IPv6 addresses */
		if (filter_var($ip_address, FILTER_VALIDATE_IP, array('flags' => array(FILTER_FLAG_NO_RES_RANGE))) === false) {
			return false;
		}

		/* for some reason the 2 checks above fail to flag 127.0.0.1 as FALSE. There were a few bugs on this on PHP but they're closed for two years.
			no time to debug PHP. do a check here instead. 127.* is considered non-publicly-routable (see http://en.wikipedia.org/wiki/Loopback) */
		if (strpos($ip_address, "127.") === 0) {
			return false;
		}

		// all passed
		return true;
	}

	/**
	 * Tells whether the given IP address is a valid IPv4 or IPv6 address.
	 *
	 * @param string $ip_address
	 * @return bool
	 */
	public function isValidIpAddress($ip_address) {
		if (filter_var($ip_address, FILTER_VALIDATE_IP, array('flags' => array(FILTER_FLAG_IPV4, FILTER_FLAG_IPV6))) === false) {
			return false;
		}
		return true;
	}
}
