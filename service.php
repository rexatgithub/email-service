<?php
/**
 * Author: Rex
 * Global Email service with injectable containers
 * So in the future using different email service container is possible without making a huge change from the code
 * 
 * Uses ses service as default container
 */

namespace GlobalEmail;

use GlobalEmail\Container\SES;

class Service
{
  private $container;

  public function __construct(driver $container = null)
  {
    $this->container = $container ? $container : new SES();
  }
  /**
   * Execute email send
   * @param array $to
   * @param string $subject
   * @param string $htmlContent
   * @param array $from [email,name(optional)]
   * @param array $bcc
   * @param string $replyTo
   * @return boolean
   */
  public function send(array $to, $subject, $htmlContent, $from = null, $bcc = null, $replyTo = null)
  {
    $to = $this->preSend($to);

    $this->container->setTo($to)
      ->setSubject($subject)
      ->setContent($htmlContent)
      ->setFrom();

    if ($from) {
      $this->container->setFrom($from);
    }

    if ($bcc) {
      $this->container->setBcc($bcc);
    }

    if ($replyTo) {
      $this->container->setReplyTo($replyTo);
    }
    return $this->container->send();
  }

  public function sendRaw($content)
  {
    return $this->container->sendRaw($content);
  }
  /**
   * Alter Email depends on 
   * @param [type] $recepients
   * @return array
   */
  public function preSend($recepients)
  {
    if (ENV == "production") {
      return $recepients;
    }
    return $this->alterEmail($recepients);
  }

  private function alterEmail($emails)
  {
    $new_emails = [];
    foreach ($emails as $email) {
      $new_emails[] = self::getTestEmailAddress($email);
    }
    return $new_emails;
  }

  public static function getTestEmailAddress($emailAddress)
  {
    if ($emailAddress == SITE_EMAIL) {
      return $emailAddress;
    }
    $siteEmail = explode('@', SITE_EMAIL);
    return $siteEmail[0] . '+' .
      preg_replace(
        '/[^0-9a-zA-Z\-_\.]/',
        '',
        str_replace('@', '-', $emailAddress)
      ) .
      '@' . $siteEmail[1];
  }

  public function enableTracking($value)
  {
    if (method_exists($this->container, 'enableTracking')) {
      $this->container->enableTracking($value);
    }
  }

  public function getContainer()
  {
    return $this->container;
  }
}
