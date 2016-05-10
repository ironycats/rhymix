<?php

namespace Rhymix\Framework;

/**
 * The mail class.
 */
class Mail
{
	/**
	 * Instance properties.
	 */
	public $message = null;
	public $driver = null;
	public $content_type = 'text/html';
	public $attachments = array();
	public $errors = array();
	
	/**
	 * Static properties.
	 */
	public static $default_driver = null;
	public static $custom_drivers = array();
	
	/**
	 * Set the default driver.
	 * 
	 * @param object $driver
	 * @return void
	 */
	public static function setDefaultDriver(Drivers\MailInterface $driver)
	{
		self::$default_driver = $driver;
	}
	
	/**
	 * Get the default driver.
	 * 
	 * @return object
	 */
	public static function getDefaultDriver()
	{
		if (!self::$default_driver)
		{
			self::$default_driver = Drivers\Mail\MailFunction::getInstance(array());
		}
		return self::$default_driver;
	}
	
	/**
	 * Add a custom mail driver.
	 */
	public static function addDriver(Drivers\MailInterface $driver)
	{
		self::$custom_drivers[] = $driver;
	}
	
	/**
	 * Get the list of supported mail drivers.
	 * 
	 * @return array
	 */
	public static function getSupportedDrivers()
	{
		$result = array();
		foreach (Storage::readDirectory(__DIR__ . '/drivers/mail', false) as $filename)
		{
			$driver_name = substr($filename, 0, -4);
			$class_name = '\Rhymix\Framework\Drivers\Mail\\' . $driver_name;
			if ($class_name::isSupported())
			{
				$result[] = $driver_name;
			}
		}
		foreach (self::$custom_drivers as $driver)
		{
			if ($driver->isSupported())
			{
				$result[] = strtolower(class_basename($driver));
			}
		}
		$result = array_unique($result);
		sort($result);
		return $result;
	}
	
	/**
	 * The constructor.
	 */
	public function __construct()
	{
		$this->message = \Swift_Message::newInstance();
		$this->driver = self::getDefaultDriver();
	}
	
	/**
	 * Set the sender (From:).
	 *
	 * @param string $email E-mail address
	 * @param string $name Name (optional)
	 * @return bool
	 */
	public function setFrom($email, $name = null)
	{
		try
		{
			$this->message->setFrom($name === null ? $email : array($email => $name));
			return true;
		}
		catch (\Exception $e)
		{
			$this->errors[] = array($e->getMessage());
			return false;
		}
	}
	
	/**
	 * Get the sender (From:).
	 *
	 * @return string|null
	 */
	public function getFrom()
	{
		$list = $this->formatAddresses($this->message->getFrom());
		return $list ? array_first($list) : null;
	}
	
	/**
	 * Add a recipient (To:).
	 *
	 * @param string $email E-mail address
	 * @param string $name Name (optional)
	 * @return bool
	 */
	public function addTo($email, $name = null)
	{
		try
		{
			$this->message->addTo($email, $name);
			return true;
		}
		catch (\Exception $e)
		{
			$this->errors[] = array($e->getMessage());
			return false;
		}
	}
	
	/**
	 * Add a recipient (CC:).
	 *
	 * @param string $email E-mail address
	 * @param string $name Name (optional)
	 * @return bool
	 */
	public function addCc($email, $name = null)
	{
		try
		{
			$this->message->addCc($email, $name);
			return true;
		}
		catch (\Exception $e)
		{
			$this->errors[] = array($e->getMessage());
			return false;
		}
	}
	
	/**
	 * Add a recipient (BCC:).
	 *
	 * @param string $email E-mail address
	 * @param string $name Name (optional)
	 * @return bool
	 */
	public function addBcc($email, $name = null)
	{
		try
		{
			$this->message->addBcc($email, $name);
			return true;
		}
		catch (\Exception $e)
		{
			$this->errors[] = array($e->getMessage());
			return false;
		}
	}
	
	/**
	 * Get the list of recipients.
	 *
	 * @return array();
	 */
	public function getRecipients()
	{
		$result = array();
		
		foreach ($this->formatAddresses($this->message->getTo()) as $address)
		{
			$result[] = $address;
		}
		foreach ($this->formatAddresses($this->message->getCc()) as $address)
		{
			$result[] = $address;
		}
		foreach ($this->formatAddresses($this->message->getBcc()) as $address)
		{
			$result[] = $address;
		}
		
		return array_unique($result);
	}
	
	/**
	 * Set the Reply-To: address.
	 *
	 * @param string $replyTo
	 * @return bool
	 */
	public function setReplyTo($replyTo)
	{
		try
		{
			$this->message->setReplyTo(array($replyTo));
			return true;
		}
		catch (\Exception $e)
		{
			$this->errors[] = array($e->getMessage());
			return false;
		}
	}
	
	/**
	 * Set the Return-Path: address.
	 *
	 * @param string $returnPath
	 * @return bool
	 */
	public function setReturnPath($returnPath)
	{
		try
		{
			$this->message->setReturnPath($returnPath);
			return true;
		}
		catch (\Exception $e)
		{
			$this->errors[] = array($e->getMessage());
			return false;
		}
	}
	
	/**
	 * Set the Message ID.
	 *
	 * @param string $messageId
	 * @return bool
	 */
	public function setMessageID($messageId)
	{
		try
		{
			$headers = $this->message->getHeaders();
			$headers->get('Message-ID')->setId($messageId);
			return true;
		}
		catch (\Exception $e)
		{
			$this->errors[] = array($e->getMessage());
			return false;
		}
	}
	
	/**
	 * Set the In-Reply-To: header.
	 *
	 * @param string $inReplyTo
	 * @return bool
	 */
	public function setInReplyTo($inReplyTo)
	{
		try
		{
			$headers = $this->message->getHeaders();
			$headers->addTextHeader('In-Reply-To', $inReplyTo);
			return true;
		}
		catch (\Exception $e)
		{
			$this->errors[] = array($e->getMessage());
			return false;
		}
	}
	
	/**
	 * Set the References: header.
	 *
	 * @param string $references
	 * @return bool
	 */
	public function setReferences($references)
	{
		try
		{
			$headers = $this->message->getHeaders();
			$headers->addTextHeader('References', $references);
			return true;
		}
		catch (\Exception $e)
		{
			$this->errors[] = array($e->getMessage());
			return false;
		}
	}
	
	/**
	 * Set the subject.
	 *
	 * @param string $subject
	 * @return bool
	 */
	public function setSubject($subject)
	{
		try
		{
			$this->message->setSubject(strval($subject));
			return true;
		}
		catch (\Exception $e)
		{
			$this->errors[] = array($e->getMessage());
			return false;
		}
	}
	
	/**
	 * Get the subject.
	 *
	 * @return string
	 */
	public function getSubject()
	{
		return $this->message->getSubject();
	}
	
	/**
	 * Set the subject (alias to setSubject).
	 *
	 * @param string $subject
	 * @return bool
	 */
	public function setTitle($subject)
	{
		return $this->setSubject($subject);
	}
	
	/**
	 * Get the subject (alias to getSubject).
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return $this->getSubject();
	}
	
	/**
	 * Set the body content.
	 *
	 * @param string $content
	 * @param string $content_type (optional)
	 * @return void
	 */
	public function setBody($content, $content_type = null)
	{
		if ($content_type !== null)
		{
			$this->setContentType($content_type);
		}
		
		if (strpos($this->content_type, 'html') !== false)
		{
			$content = preg_replace_callback('/<img([^>]+)>/i', array($this, 'convertImageURLs'), $content);
		}
		
		$this->message->setBody($content, $this->content_type);
	}
	
	/**
	 * Get the body content.
	 * 
	 * @return string
	 */
	public function getBody()
	{
		return $this->message->getBody();
	}
	
	/**
	 * Set the body content (alias to setBody).
	 *
	 * @param string $content
	 * @param string $content_type (optional)
	 * @return void
	 */
	public function setContent($content, $content_type = null)
	{
		return $this->setBody($content, $content_type);
	}
	
	/**
	 * Get the body content (alias to getBody).
	 * 
	 * @return string
	 */
	public function getContent()
	{
		return $this->getBody();
	}
	
	/**
	 * Set the content type.
	 * 
	 * @param string $mode The type
	 * @return void
	 */
	public function setContentType($type = 'text/html')
	{
		$this->content_type = (strpos($type, 'html') !== false) ? 'text/html' : ((strpos($type, '/') !== false) ? $type : 'text/plain');
	}
	
	/**
	 * Get the content type.
	 * 
	 * @return string
	 */
	public function getContentType()
	{
		return $this->content_type;
	}
	
	/**
	 * Attach a file.
	 *
	 * @param string $local_filename
	 * @param string $display_filename (optional)
	 * @return bool
	 */
	public function attach($local_filename, $display_filename = null)
	{
		if ($display_filename === null)
		{
			$display_filename = basename($local_filename);
		}
		if (!Storage::exists($local_filename))
		{
			return false;
		}
		
		$attachment = \Swift_Attachment::fromPath($local_filename);
		$attachment->setFilename($display_filename);
		$result = $this->message->attach($attachment);
		
		if ($result)
		{
			$this->attachments[] = (object)array(
				'type' => 'attach',
				'local_filename' => $local_filename,
				'display_filename' => $display_filename,
				'cid' => null,
			);
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Embed a file.
	 *
	 * @param string $local_filename
	 * @param string $cid (optional)
	 * @return string|false
	 */
	public function embed($local_filename, $cid = null)
	{
		if (!Storage::exists($local_filename))
		{
			return false;
		}
		
		$embedded = \Swift_EmbeddedFile::fromPath($local_filename);
		if ($cid !== null)
		{
			$embedded->setId(preg_replace('/^cid:/i', '', $cid));
		}
		$result = $this->message->embed($embedded);
		
		if ($result)
		{
			$this->attachments[] = (object)array(
				'type' => 'embed',
				'local_filename' => $local_filename,
				'display_filename' => null,
				'cid' => $result,
			);
			return $result;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Get the list of attachments to this message.
	 * 
	 * @return array
	 */
	public function getAttachments()
	{
		return $this->attachments;
	}
	
	/**
	 * Send the email.
	 * 
	 * @return bool
	 */
	public function send()
	{
		$output = \ModuleHandler::triggerCall('mail.send', 'before', $this);
		if(!$output->toBool())
		{
			$this->errors[] = $output->getMessage();
			return false;
		}
		
		try
		{
			$result = $this->driver->send($this);
		}
		catch(\Exception $e)
		{
			$this->errors[] = $e->getMessage();
			$result = false;
		}
		
		$output = \ModuleHandler::triggerCall('mail.send', 'after', $this);
		if(!$output->toBool())
		{
			$this->errors[] = $output->getMessage();
		}
		
		return $result;
	}
	
	/**
	 * Convert image paths to absolute URLs.
	 *
	 * @see Mail::setContent()
	 * @param array $matches Match info.
	 * @return string
	 */
	protected function convertImageURLs(array $matches)
	{
		return preg_replace('/src=(["\']?)files/i', 'src=$1' . URL::getCurrentDomainURL(\RX_BASEURL) . 'files', $matches[0]);
	}
	
	/**
	 * Format an array of addresses for display.
	 * 
	 * @param array $addresses
	 * @return array
	 */
	protected function formatAddresses(array $addresses)
	{
		$result = array();
		
		foreach($address as $email => $name)
		{
			if(strval($name) === '')
			{
				$result[] = $email;
			}
			else
			{
				$result[] = $name . ' <' . $email . '>';
			}
		}
		
		return $result;
	}
}
