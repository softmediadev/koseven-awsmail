# koseven-awsmail
Amazon Simple Email Service enables you to send and receive email using a reliable and scalable email platform.

Jump To:
* [Installation](_##installation_)

#### Requirements

Read the Amazon Simple Email Service documentation

* **Access Key**: https://console.aws.amazon.com/iam/home
* **Developer Guide**: https://docs.aws.amazon.com/ses/latest/DeveloperGuide/Welcome.html
* **Documentation**: https://docs.aws.amazon.com/ses/index.html
* **SDK API**: https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-email-2010-12-01.html

#### Installation

Place this module in your modules directory.

Copy `MODPATH.koseven-awsmail/config/awsmail.php` into `APPPATH/config/awsmail.php` and customize.

Activate the module in `bootstrap.php`.

```php
<?php
Kohana::modules(array(
	...
	'awsmail' => MODPATH.'awsmail',
));
```

## Configuration

* **Access Key**: Your access key (check link in Requirements). **REQUIRED**
* **Secret Key**: Your secret key (check link in Requirements). **REQUIRED**
* **Region**: Amazon SES is available in several AWS Regions around the world (check Developer Guide). **REQUIRED**
* **Version**: Version of the SDK library (you can use "latest" or "3.149.1"). **REQUIRED**
* **Source Email**: You can set a default source (from) for sending your emails.  **OPTIONAL (BLANK|NULL)**
* **Return Email**: You can set a default email address that bounces and complaints will be forwarded to when feedback forwarding is enabled. **OPTIONAL (BLANK|NULL)**
* **Charset**: Default charset. **REQUIRED**

`APPPATH/config/awsmail.php`
```php
<?php
return array(
  'access_key' => 'Your access key',
  'secret_key' => 'Your secret key',
  'region' => 'us-east-1',
  'version' => 'latest',
  'source_email' => array('noreply@domain.com' => 'My Project Name'),
  'return_email' => array('bounce@domain.com' => ''),
  'charset' => 'UTF-8'
);
```

## Message

### Example 1
```php
$result = AWSMail::message()
  ->from('from@domain.com', 'Name from')
  ->to('to@domain.com', 'Name to')
  ->subject('This is the subject')
  ->body('This is the content')
  ->send();
      
print_r($result);
```

### Example 2
```php
$result = AWSMail::message()
  ->from('from@domain.com', 'Name from')
  ->to('to1@domain.com', 'Name to 1')
  ->to('to2@domain.com', 'Name to 2')
  ->cc('cc@domain.com', 'Name cc')
  ->cc(array('cc2@domain.com', 'cc3@domain.com', 'cc4@domain.com' => 'Name cc4'))
  ->bcc('bcc@domain.com', 'Name bcc')
  ->reply_to('replyto@domain.com', 'Name reply to')
  ->subject('This is the subject')
  ->body('This is the content')
  ->send();

print_r($result);
```

### Example 3
```php
$file1 = 'dir/filename.ext';
$file2 = 'http://domain.com/page.html';
$file3 = 'This is the content of the attachment';

$result = AWSMail::message()
  ->from('from@domain.com', 'Name from')
  ->to('to1@domain.com', 'Name to 1')
  ->subject('This is the subject')
  ->body('<p>This is the Html content</p>', 'This is the Text content')
  ->attachment($file1, 'local_file.ext')
  ->attachment($file2, 'external_file.html')
  ->attachment($file3, 'text.txt')
  ->send();
      
print_r($result);
```

## Utility

### Create a template
```php
$html = '<h1>Hello {{name}},</h1><p>Your favorite animal is {{favoriteanimal}}.</p>';
$text = 'Dear {{name}},\r\nYour favorite animal is {{favoriteanimal}}.';

$result = AWSMail::utility()->create_template('template_name', 'Greetings {{name}}', $html, $text);

print_r($result);
```

### Update a template
```php
$html = '<h1>Hello {{name}},</h1><p>Your favorite color is {{favoritecolor}}.</p>';
$text = 'Dear {{name}},\r\nYour favorite color is {{favoritecolor}}.';

$result = AWSMail::utility()->update_template('template_name', 'Greetings {{name}}', $html, $text);

print_r($result);
```

### Get a template
```php
$result = AWSMail::utility()->get_template('template_name');

print_r($result);
```

### Delete a template
```php
$result = AWSMail::utility()->delete_template('template_name');

print_r($result);
```

## Destinations

### Create a destination object
```php
$destination1 = AWSMail::destinations()->to('to@domain.com')->data(array('name' => 'My Name', 'color' => 'Green'));
$destination2 = AWSMail::destinations()->to('to@domain.com')->cc('cc@domain.com')->data(array('name' => 'My Other Name', 'color' => 'Blue'));
```

## Template

You can send up to 50 destinations in a single call using a template
```php
$template = AWSMail::template();
$template->name('tempalte_name');
$template->destination($destination1);
$template->destination($destination2);
$template->default_data(array('name' => 'Unknown', 'color' => 'White'));
$result = $template->send();

print_r($result);
```

#### ABOUT AND LICENSE

Copyright (c) 2020, Soft Media Development. All right reserved.

This project is using the SDK from https://github.com/aws/aws-sdk-php created by [Amazon Web Services](https://github.com/aws).

This project is made under BSD license. See LICENSE file for more information.
