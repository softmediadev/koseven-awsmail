<?php

use Aws\Ses\SesClient;
use Aws\Credentials\CredentialProvider;

abstract class Kohana_Main
{
	protected Config_Group $config;
	protected SesClient $client;
	protected array $params = [];
	
	public function __construct()
	{
		$this->config = Kohana::$config->load('awsmail');
		$credentials = NULL;
		
		if (isset($this->config->credentials['file']))
		{
			$path = $this->config->credentials['file']['path'];
			$profile = $this->config->credentials['file']['profile'];
			
			if ( ! empty($path) AND ! empty($profile))
			{
				$provider = CredentialProvider::ini($profile, $path);
				$credentials = CredentialProvider::memoize($provider);
			}
		}
		
		if (isset($this->config->credentials['access']) AND empty($credentials))
		{
			$key = $this->config->credentials['access']['key'];
			$secret = $this->config->credentials['access']['secret'];
			
			if ( ! empty($key) AND ! empty($secret))
			{
				$credentials['key'] = $key;
				$credentials['secret'] = $secret;
			}
		}
		
		if (empty($credentials))
			exit;
		
		$this->client = new SesClient([
			'version' => $this->config->version,
			'region' => $this->config->region,
			'credentials' => $credentials
		]);

		if(isset($this->config->source_email))
			$this->params['Source'] = $this->format_email($this->config->source_email);

		if(isset($this->config->return_email))
			$this->params['ReturnPath'] = $this->format_email($this->config->return_email);
	}

	public function configuration($name): static
	{
		$this->params['ConfigurationSetName'] = $name;

		return $this;
	}

	public function from($email, $name = NULL): static
	{
		$email = $this->format_email($email, $name);

		if( ! empty($email))
			$this->params['Source'] = $email;

		return $this;
	}

	public function reply_to($email, $name = NULL): static
	{
		$email = $this->format_email($email, $name);

		if( ! empty($email))
			$this->params['ReplyToAddresses'][] = $email;

		return $this;
	}

	public function return_path($email, $name = NULL, $arn = NULL): static
	{
		$email = $this->format_email($email, $name);

		if( ! empty($email))
		{
			$this->params['ReturnPath'] = $email;

			if( ! empty($arn))
				$this->params['ReturnPathArn'] = $arn;
		}

		return $this;
	}

	protected function format_email($email, $name = NULL)
	{
		if(is_array($email))
		{
			$result = [];

			foreach($email as $key => $value)
			{
				if(is_numeric($key))
				{
					$k = $value;
					$v = NULL;
				}
				else
				{
					$k = $key;
					$v = $value;
				}

				$value = $this->format_email($k, $v);

				if( ! empty($value))
					$result[] = $value;
			}

			if(count($result) == 1)
				return $result[0];
			else
				return $result;
		}
		else
		{
			if(Valid::email($email))
			{
				if( ! empty($name))
					$email = trim($name) . ' <' . trim($email) . '>';

				return $email;
			}
			else
			{
				return NULL;
			}
		}
	}

	protected function get_content($value, $valid_source = FALSE)
	{
		if(strpos($value, '//'))
		{
			$ch = curl_init($value);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_VERBOSE, 1);
			curl_setopt($ch, CURLOPT_HEADER, 1);

			$response = curl_exec($ch);

			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			if($http_code == 200)
				$value = substr($response, $header_size);
			else
				$value = NULL;
		}
		elseif(file_exists($value) AND is_file($value) AND is_readable($value))
		{
			$value = file_get_contents($value);
		}
		elseif($valid_source)
		{
			$value = NULL;
		}

		return $value;
	}
}
