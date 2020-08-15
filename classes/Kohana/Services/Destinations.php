<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_Services_Destinations
{
	public $params;

	public function to($email, $name = NULL)
	{
		$this->params['Destination']['ToAddresses'][] = is_array($email) ? $email : array($email => $name);

		return $this;
	}

	public function cc($email, $name = NULL)
	{
		$this->params['Destination']['CcAddresses'][] = is_array($email) ? $email : array($email => $name);

		return $this;
	}

	public function bcc($email, $name = NULL)
	{
		$this->params['Destination']['BccAddresses'][] = is_array($email) ? $email : array($email => $name);

		return $this;
	}

	public function tag($name, $value)
	{
		$this->params['ReplacementTags'][] = array(
			'Name' => $name,
			'Value' => $value
		);

		return $this;
	}

	public function data($name, $value = NULL)
	{
		if (is_array($name))
			$this->params['ReplacementTemplateData'] = $name;
		else
			$this->params['ReplacementTemplateData'][$name] = $value;

		return $this;
	}
}