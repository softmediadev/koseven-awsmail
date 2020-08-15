<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_Services_Utility extends AWSMail_Main
{
	public function create_template($name, $subject, $html, $text = NULL)
	{
		$html = $this->get_content($html);
		$text = $this->get_content($text);

		$result = $this->client->createTemplate([
			'Template' => [
				'TemplateName' => $name,
				'SubjectPart' => $subject,
				'HtmlPart' => $html,
				'TextPart' => $text
			],
		]);

		return $result->toArray();
	}

	public function update_template($name, $subject, $html, $text = NULL)
	{
		$html = $this->get_content($html);
		$text = $this->get_content($text);

		$result = $this->client->updateTemplate([
			'Template' => [
				'TemplateName' => $name,
				'SubjectPart' => $subject,
				'HtmlPart' => $html,
				'TextPart' => $text
			],
		]);

		return $result->toArray();
	}

	public function delete_template($name)
	{
		$result = $this->client->deleteTemplate([
			'TemplateName' => $name
		]);

		return $result->toArray();
	}

	public function get_template($name)
	{
		$result = $this->client->getTemplate([
			'TemplateName' => $name
		]);

		return $result->toArray();
	}
}