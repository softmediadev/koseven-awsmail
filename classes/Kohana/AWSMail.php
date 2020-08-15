<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_AWSMail {

	public static function message()
	{
		return new AWSMail_Services_Message();
	}

	public static function template()
	{
		return new AWSMail_Services_Template();
	}

	public static function destinations()
	{
		return new AWSMail_Services_Destinations();
	}

	public static function utility()
	{
		return new AWSMail_Services_Utility();
	}
}
