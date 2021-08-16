<?php

class Kohana_AWSMail {

	public static function message(): AWSMail_Services_Message
	{
		return new AWSMail_Services_Message();
	}

	public static function template(): AWSMail_Services_Template
	{
		return new AWSMail_Services_Template();
	}

	public static function destinations(): Kohana_Services_Destinations
	{
		return new AWSMail_Services_Destinations();
	}

	public static function utility(): AWSMail_Services_Utility
	{
		return new AWSMail_Services_Utility();
	}
}
