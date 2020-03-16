<?php

/**
 * @Author Rex
 * SES email sender container
 */

namespace GlobalEmail\Container;

use GlobalEmail\driver;
use Aws\Ses\SesClient;
use Aws\Ses\Exception\SesException;

class SES implements driver
{
  private $to;
  private $from = null;
  private $bcc = null;
  private $replyTo = null;
  private $config_set;
  private $enable_tracking = false;

  const DEFAULT_CONFIG_SET = 'default-configuration';

  /** @var  S3Client */
  protected $client;

  public function __construct()
  {
    if (!$this->client) {
      $this->client = new SesClient([
        'version'     => 'latest',
        'region'      => AWS_REGION,
        'credentials' => [
          'key'    => AWS_KEY_ID,
          'secret' => AWS_KEY_SECRET,
        ],
      ]);
    }
  }

  public function setTo(array $to)
  {
    $this->to = $to;
    return $this;
  }
  /**
   * FROM Setter
   * If parameter is emtpy set the default sender to site email
   * @param array $from 
   * @return core_ses
   */
  public function setFrom(array $from = [])
  {
    if (empty($from)) {
      $from = []; //site default sender
    }

    $this->from = $this->formatFrom($from);
    return $this;
  }

  public function setBcc(array $bcc)
  {
    $this->bcc = $bcc;
    return $this;
  }
  public function setReplyTo($replyTo)
  {
    $this->replyTo = $replyTo;
    return $this;
  }
  public function setContent($content)
  {
    $this->content = $content;
    return $this;
  }
  public function setSubject($subject)
  {
    $this->subject = $subject;
    return $this;
  }

  /**
   * @param String $subject
   * @param String $content
   * @return Boolean
   */
  public function send()
  {
    try {
      $params = [
        'Destination' => [],
        'Message' => [
          'Body' => [
            'Html' => [
              'Charset' => 'UTF-8',
              'Data' => $this->content,
            ]
          ],
          'Subject' => [
            'Charset' => 'UTF-8',
            'Data' => $this->subject,
          ],
        ],
        'Source' =>  $this->from
      ];

      if (!is_null($this->to)) {
        $params['Destination']['ToAddresses'] = $this->to;
      }

      if (!is_null($this->bcc)) {
        $params['Destination']['BccAddresses'] = $this->bcc;
      }

      if (!is_null($this->cc)) {
        $params['Destination']['CcAddresses'] = $this->cc;
      }

      if (!is_null($this->replyTo)) {
        $params['ReplyToAddresses'] = $this->replyTo;
      }

      if (!is_null($this->config_set)) {
        $params['ConfigurationSetName'] = $this->config_set;
      }

      $this->client->sendEmail($params);

      return true;
    } catch (SesException $error) {
      error_log("The email was not sent. Error message: " . $error->getAwsErrorMessage() . "\n");
      return false;
    }
  }

  public function sendRaw($content, $additional_params = [])
  {
    try {
      $params = array_merge(['RawMessage' => [
        'Data' => $content
      ]], $additional_params);

      if (!is_null($this->config_set)) {
        $params['ConfigurationSetName'] = $this->config_set;
      }

      $result =  $this->client->sendRawEmail($params);
      return $result->get('MessageId');
    } catch (SesException $error) {
      error_log("The email was not sent. Error message: " . $error->getAwsErrorMessage() . "\n");
      return false;
    }
  }

  /**
   * Format SES standard FROM
   * @param array $from [email,name]
   * @return string email@email.com | Name <email@email.com>
   */
  private function formatFrom($from)
  {
    if (isset($from[1])) {
      return '"' . $from[1] . '"<' . $from[0] . '>';
    }
    return    $from[0];
  }

  public function enableTracking($enable)
  {
    $this->enable_tracking = $enable;
    if ($this->enable_tracking) {
      $this->setConfigSet();
    }
  }

  public function setConfigSet($value = null)
  {
    $this->config_set = $value ? $value : self::DEFAULT_CONFIG_SET;
  }

  public function initCustomVerification($name, $subject, $content, $failurl, $successurl)
  {
    if ($this->getCustomVerification($name) === false) {
      $this->createCustomEmailTemplate($name, $subject, $content, $failurl, $successurl);
    } else {
      $this->updateCustomVerification($name, [
        'FailureRedirectionURL' => $failurl,
        'SuccessRedirectionURL' => $successurl,
        'TemplateContent' => $content,
        'TemplateSubject' => $subject
      ]);
    }
  }
  /**
   * Delete Custom Email Verification Template
   * @param String $name Template Name
   * @return SesClient
   */
  public function deleteCustomVerification($name)
  {
    return $this->client->deleteCustomVerificationEmailTemplate([
      'TemplateName' => $name, // REQUIRED
    ]);
  }
  /**
   * Get Custom Verification Template List
   * @return Array
   */
  public function getCustomVerifications()
  {
    return $this->ses_client->listCustomVerificationEmailTemplates();
  }

  /**
   * Get Custom Verification Template by name
   * @param [type] $name
   * @param boolean $test
   * @return void
   */
  public function getCustomVerification($name, $test = false)
  {
    if ($test) {
      return $this->client->getCustomVerificationEmailTemplate([
        'TemplateName' => $name
      ]);
    }

    try {
      return $this->client->getCustomVerificationEmailTemplate([
        'TemplateName' => $name
      ]);
    } catch (SesException $error) {
      error_log("createCustomEmailTemplate Error: " . $error->getAwsErrorMessage() . "\n");
      return false;
    }
  }
  /**
   * Get Verified Emails from SES record
   * @return Array
   */
  public function getVerifiedEmails()
  {
    try {
      return $this->ses_client->listVerifiedEmailAddresses()->get('VerifiedEmailAddresses');
    } catch (SesException $error) {
      error_log("getVerifiedEmails Error: " . $error->getAwsErrorMessage() . "\n");
      return false;
    }
  }
  /**
   * Execute email verification
   * @param String $email
   * @param String $template
   * @return boolean 
   */
  public function verifyEmail($email, $template)
  {
    try {
      $this->ses_client->sendCustomVerificationEmail([
        'EmailAddress' => $email,
        'TemplateName' => $template
      ]);
      return true;
    } catch (SesException $error) {
      error_log("Verification Error: " . $error->getAwsErrorMessage() . "\n");
      return false;
    }
  }

  public function createCustomEmailTemplate($name, $subject, $content, $failurl, $successurl)
  {
    try {
      $this->client->createCustomVerificationEmailTemplate([
        'FailureRedirectionURL' => $failurl, // REQUIRED
        'FromEmailAddress' => 'test@example.com', //default email address get from config 
        'SuccessRedirectionURL' => $successurl, // REQUIRED
        'TemplateContent' => $content, // REQUIRED
        'TemplateName' => $name, // REQUIRED
        'TemplateSubject' => $subject, // REQUIRED
      ]);
      return true;
    } catch (SesException $error) {
      error_log("createCustomEmailTemplate Error: " . $error->getAwsErrorMessage() . "\n");
      return false;
    }
  }

  public function updateCustomVerification($name, $params = [])
  {
    try {

      $_params = [
        'TemplateName' => $name, // REQUIRED
      ];

      $this->ses_client->updateCustomVerificationEmailTemplate(
        array_merge($_params, $params)
      );

      return true;
    } catch (SesException $error) {
      error_log("updateCustomVerification Error: " . $error->getAwsErrorMessage() . "\n");
      return false;
    }
  }
}
