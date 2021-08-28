<?php

use Aws\Exception\AwsException;

class Kohana_Services_Template extends AWSMail_Main
{
	public function name($value, $arn = NULL): static
	{
		$this->params['Template'] = $value;

		if ( ! empty($arn))
			$this->params['TemplateArn'] = $arn;

		return $this;
	}

	public function destination(Kohana_Services_Destinations $object): static
	{
		$object = $object->params;

		$destinations = [];

		foreach($object['Destination'] as $type => $destination)
			$destinations[$type][] = $this->format_email($destination);

		$object['Destination'] = $destinations;
		
		if (isset($object['ReplacementTemplateData']))
			$object['ReplacementTemplateData'] = json_encode($object['ReplacementTemplateData']);

		$this->params['Destinations'][] = $object;

		return $this;
	}

	public function default_tags(array $tags): static
	{
		foreach($tags as $name => $value)
		{
			$this->params['DefaultTags'][] = array(
				'Name' => $name,
				'Value' => $value
			);
		}

		return $this;
	}

	public function default_data(array $data): static
	{
		$this->params['DefaultTemplateData'] = json_encode($data);

		return $this;
	}

	public function send(): stdClass
	{
		if($this->validate())
		{
			$result = new stdClass();
			$result->sent = FALSE;
			$result->code = NULL;
			$result->error = NULL;
			$result->status = NULL;

			try
			{
				$response = $this->client->sendBulkTemplatedEmail($this->params);
				$response = $response->toArray();

				$result->code = $response['@metadata']['statusCode'];

				if($result->code == 200)
				{
					$result->sent = TRUE;
					$result->status = $response['Status'];
				}
			}
			catch(AwsException $e)
			{
				$result->code = $e->getStatusCode();
				$result->error = $e->getAwsErrorMessage();
			}

			return $result;
		}
		else
		{
			throw new AWSMail_Exception('Message failed validation.');
		}
	}

	private function validate(): bool
	{
		if( ! isset($this->params['Destinations']))
			return FALSE;

		if (count($this->params['Destinations']) > 50)
			return FALSE;

		if(empty($this->params['Source']))
			return FALSE;

		if( ! isset($this->params['Template']))
			return FALSE;

		if( ! isset($this->params['DefaultTemplateData']))
			return FALSE;

		return TRUE;
	}
}
