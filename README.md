# Yii PcMaxmindGeoIp extension

## General

This extension provides the Maxmind GeoLite City package to Yii users.

## Features

- Latest Maxmind Geo City Lite is included. No need to install anything else aside from this Yii extension.
- Information retrieved includes the following properties of an IP address (all, some or none - depending on the info found by Maxmind):
  - country_code
  - country_code3
  - country_name
  - region
  - city
  - postal_code
  - latitude
  - longitude
  - area_code
  - dma_code
  - metro_code
  - continent_code
- Provides a modern method to determine the current remote IP address ($_SERVER['REMOTE_ADDR'] is hardly the only check you'll need. Visit getRemoteIpAddress() method for more details).
- Provides a method to determine if IP address is "publicly route-able", meaning not an internal network address or a reserved address (IPv6 supported).
- Provides a method to tell if a given IP address is valid or not (IPv6 supported).
- Easy procedure for future update of the binary DB file.


## Requirements

- This extension uses PHP's _Filter_ extension. Your PHP environment should include this extension. If you're using a PHP of version v5.2 or newer than Filter was built into PHP from that version on, so you're safe. To verify, run a simple phpinfo() on your server. 
- Tested on Yii v1.1.10, but should work with older versions.

## Installation

- Recommended usage as a 'Yii component'. Note that altough loaded as a component, only for Maxmind involving activity the Maxmind client and DB will be used (better for performance reasons). 
- To setup as a component, add the following code to your main.php config file:

~~~php
'components' => array(
//...
  'geoip' => array(
    'class' => 'ext.PcMaxmindGeoIp.PcMaxmindGeoIp',
  ),
//...
~~~

You can use another binary DB file by supplying the following parameter to the configuration above (right below the 'class' element): 'dbFilename' => '[db filename]'. Be sure to use relative path only and put the actual file under extensions/PcMaxmindGeoIp/maxmind/ directory.

## Usage

- Typically, at some point in your code you'll do:
- 
~~~php
$ip = Yii::app()->geoip->getRemoteIpAddress();
$address_information = Yii::app()->geoip->getCityInfoForIp($ip);
// at this stage $address_information is an array with element keys as noted 
// in the "Features" section above, with values that fetched from Maxmind or false 
// if none found.
~~~

- A few other auxiliary method are provided to complete the functionality:
  - **getRemoteIpAddress()**: Returns the valid IP address of the current user.
  - **isPubliclyRoutableIpAddress()**: Tells whether the given IP address is a 'public' IP and is routable, meaning not internal network (10.0.0.0/8...) or belonging to some reserved range.
  - **isValidIpAddress()**: Tells whether the given IP address is a valid IPv4 or IPv6 address.


## Maintenance

#### Update of Maxmind DB

- Maxmind provides monthly updates for their GeoLite City DB. 
- This update can be fetched automatically and efficiently using a cron job. See the wget example on [this page](http://www.maxmind.com/app/installation?city=1)

## Resources

- [Maxmind GeoLite City](http://www.maxmind.com/app/geolite)
