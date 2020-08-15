<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_Services_Message extends AWSMail_Main
{
	protected $attachments;

	public function to($email, $name = NULL)
	{
		$email = $this->format_email($email, $name);

		if ( ! empty($email))
			$this->params['Destination']['ToAddresses'][] = $email;

		return $this;
	}

	public function cc($email, $name = NULL)
	{
		$email = $this->format_email($email, $name);

		if( ! empty($email))
			$this->params['Destination']['CcAddresses'][] = $email;

		return $this;
	}

	public function bcc($email, $name = NULL)
	{
		$email = $this->format_email($email, $name);

		if( ! empty($email))
			$this->params['Destination']['BccAddresses'][] = $email;

		return $this;
	}

	public function subject($value, $charset = NULL)
	{
		$this->params['Message']['Subject']['Data'] = $value;
		$this->params['Message']['Subject']['Charset'] = empty($charset) ? $this->config->charset : $charset;

		return $this;
	}

	public function body($html, $text = NULL, $charset = NULL)
	{
		if ( ! empty($html))
		{
			$this->params['Message']['Body']['Html']['Data'] = $html;
			$this->params['Message']['Body']['Html']['Charset'] = empty($charset) ? $this->config->charset : $charset;
		}

		if( ! empty($text))
		{
			$this->params['Message']['Body']['Text']['Data'] = $text;
			$this->params['Message']['Body']['Text']['Charset'] = empty($charset) ? $this->config->charset : $charset;
		}

		return $this;
	}

	public function tag($name, $value)
	{
		$this->params['Tags'][] = array(
			'Name' => $name,
			'Value' => $value
		);

		return $this;
	}

	public function attachment($file_source, $filename = NULL, $type = 'attachment', $content_id = NULL)
	{
		$file_extension = pathinfo($file_source, PATHINFO_EXTENSION);

		if(empty($file_extension))
			$file_extension = pathinfo($filename, PATHINFO_EXTENSION);

		$file_type = File::mime_by_ext($file_extension);

		if(empty($filename))
		{
			$filename = strtolower(pathinfo($file_source, PATHINFO_BASENAME));
		}
		else
		{
			$filename_extension = pathinfo($filename, PATHINFO_EXTENSION);

			if(empty($filename_extension))
				$filename .= '.' . $file_extension;

			$filename = strtolower($filename);
		}

		if(empty($content_id) and ! empty($filename))
			$content_id = md5($filename);

		$data = $this->get_content($file_source, TRUE);

		$this->attachments[$filename] = array(
			'name' => $filename,
			'mimeType' => $file_type,
			'data' => $data,
			'contentId' => $content_id,
			'attachmentType' => ($type == 'inline' ? 'inline; filename="' . $filename . '"' : $type)
		);

		return $this;
	}

	public function send()
	{
		if($this->validate())
		{
			$result = new stdClass();
			$result->sent = FALSE;
			$result->code = NULL;
			$result->error = NULL;
			$result->message_id = NULL;

			try
			{
				if(empty($this->attachments))
				{
					$response = $this->client->sendEmail($this->params);
				}
				else
				{
					$this->raw_message();

					$response = $this->client->sendRawEmail($this->params);
				}

				$response = $response->toArray();

				$result->code = $response['@metadata']['statusCode'];

				if($result->code == 200)
				{
					$result->sent = TRUE;
					$result->message_id = $response['MessageId'];
				}
			}
			catch(Aws\Exception\AwsException $e)
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

	private function raw_message()
	{
		if (isset($this->params['Message']['Body']['Text']))
		{
			$message_text = $this->params['Message']['Body']['Text']['Data'];
			$message_text_charset = $this->params['Message']['Body']['Text']['Charset'];
		}
		else
		{
			$message_text = NULL;
			$message_text_charset = NULL;
		}

		if(isset($this->params['Message']['Body']['Html']))
		{
			$message_html = $this->params['Message']['Body']['Html']['Data'];
			$message_html_charset = $this->params['Message']['Body']['Html']['Charset'];
		}
		else
		{
			$message_html = NULL;
			$message_html_charset = NULL;
		}

		$boundary = uniqid(rand(), TRUE);

		$raw_message = '';
		$raw_message .= 'From: ' . $this->encode_recipients($this->params['Source']) . "\n";

		if(isset($this->params['Destination']))
		{
			foreach($this->params['Destination'] as $address_type => $address)
			{
				if($address_type == 'ToAddresses')
					$raw_message .= 'To: ' . $this->encode_recipients($address) . "\n";

				if($address_type == 'CcAddresses')
					$raw_message .= 'CC: ' . $this->encode_recipients($address) . "\n";

				if($address_type == 'BccAddresses')
					$raw_message .= 'BCC: ' . $this->encode_recipients($address) . "\n";
			}

			unset($this->params['Destination']);
		}

		if(isset($this->params['ReplyToAddresses']))
		{
			$raw_message .= 'Reply-To: ' . $this->encode_recipients($this->params['ReplyToAddresses']) . "\n";

			unset($this->params['ReplyToAddresses']);
		}

		$raw_message .= 'Subject: =?' . $this->params['Message']['Subject']['Charset'] . '?B?' . base64_encode($this->params['Message']['Subject']['Data']) . "?=\n";

		unset($this->params['Message']);

		$raw_message .= 'MIME-Version: 1.0' . "\n";
		$raw_message .= 'Content-type: ' . ($this->has_inline_attachments() ? 'multipart/related' : 'Multipart/Mixed') . '; boundary="' . $boundary . '"' . "\n";
		$raw_message .= "\n--{$boundary}\n";
		$raw_message .= 'Content-type: Multipart/Alternative; boundary="alt-' . $boundary . '"' . "\n";

		if( ! empty($message_text))
		{
			$charset = empty($message_text_charset) ? '' : "; charset=\"{$message_text_charset}\"";
			$raw_message .= "\n--alt-{$boundary}\n";
			$raw_message .= 'Content-Type: text/plain' . $charset . "\n\n";
			$raw_message .= $message_text . "\n";
		}

		if( ! empty($message_html))
		{
			$charset = empty($message_html_charset) ? '' : "; charset=\"{$message_html_charset}\"";
			$raw_message .= "\n--alt-{$boundary}\n";
			$raw_message .= 'Content-Type: text/html' . $charset . "\n\n";
			$raw_message .= $message_html . "\n";
		}
		$raw_message .= "\n--alt-{$boundary}--\n";

		foreach($this->attachments as $attachment)
		{
			$raw_message .= "\n--{$boundary}\n";
			$raw_message .= 'Content-Type: ' . $attachment['mimeType'] . '; name="' . $attachment['name'] . '"' . "\n";
			$raw_message .= 'Content-Disposition: ' . $attachment['attachmentType'] . "\n";

			if( ! empty($attachment['contentId']))
				$raw_message .= 'Content-ID: ' . $attachment['contentId'] . '' . "\n";

			$raw_message .= 'Content-Transfer-Encoding: base64' . "\n";
			$raw_message .= "\n" . chunk_split(base64_encode($attachment['data']), 76, "\n") . "\n";
		}

		$raw_message .= "\n--{$boundary}--\n";

		$this->params['RawMessage']['Data'] = $raw_message;
	}

	private function has_inline_attachments()
	{
		foreach($this->attachments as $attachment)
		{
			if($attachment['attachmentType'] != 'attachment')
				return TRUE;
		}

		return FALSE;
	}

	private function encode_recipients($recipient)
	{
		if(is_array($recipient))
			return join(', ', array_map(array($this, 'encode_recipients'), $recipient));

		if(preg_match("/(.*)<(.*)>/", $recipient, $regs))
			$recipient = '=?' . $this->config->charset . '?B?' . base64_encode($regs[1]) . '?= <' . $regs[2] . '>';

		return $recipient;
	}

	private function validate()
	{
		if( ! isset($this->params['Destination']))
			return FALSE;

		$total_addresses = 0;

		foreach($this->params['Destination'] as $addresses)
			$total_addresses += count($addresses);

		if ($total_addresses > 50)
			return FALSE;

		if(empty($this->params['Source']))
			return FALSE;

		if (isset($this->params['Message']))
		{
			$message = $this->params['Message'];

			if ( ! isset($message['Subject']))
				return FALSE;

			if(isset($message['Body']))
			{
				$body = $message['Body'];

				if( ! isset($body['Html']) AND ! isset($body['Text']))
					return FALSE;
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			return FALSE;
		}

		return TRUE;
	}
}