<?php

/**
 * @file Drupal\popup_message\EventSubscriber\PopupMessageSubscriber
 */
namespace Drupal\popup_message\EventSubscriber;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\AttachmentsInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class PopupMessageSubscriber
 * @package Drupal\popup_message\EventSubscriber
 */
class PopupMessageSubscriber implements EventSubscriberInterface
{

    /**
     * @var \Drupal\Core\Config\ImmutableConfig
     */
    protected $config;

    /**
     * @var
     */
    protected $path;

    /**
     * PopupMessageSubscriber constructor.
     */
    public function __construct()
    {
        $this->config = \Drupal::configFactory()->get('popup_message.settings');
        $this->path = ltrim(\Drupal::service('path.current')->getPath(), '/');
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
     */
    public function showPopupMessage(FilterResponseEvent $event)
    {
        // Check permissions to display message.

        $response = $event->getResponse();

        if (!$response instanceof AttachmentsInterface) {
            return;
        }

        // Check module has enable popup
        $status = $this->config->get('enable');

        //Ommit system path.
        $current_url = \Drupal::request()->getRequestUri();
        $decline_system_path = ('/editor/*');
        $system_path = \Drupal::service('path.matcher')
            ->matchPath($current_url, $decline_system_path);


        /**
         * Check module has enable popup, permission, exclude denied url
         * Set session with true or false.
         * If all requirements are ok session PopupMessageStatus is set to true.
         *
         */

        if ($status && !$system_path) {
            $permission = \Drupal::currentUser()
                ->hasPermission('display popup message');
            // Get status: enabled/disabled.

            // Allow other modules to modiffy permissions.
            \Drupal::moduleHandler()->alter('popup_message_permission', $permission);

            $message_title = Xss::filter($this->config->get('title'));
            $message_body_variable = $this->config->get('body');
            $message_url = $this->config->get('url');
            $message_body = check_markup(
                $message_body_variable['value'],
                ($message_body_variable['format'] ? $message_body_variable['format'] : filter_default_format()),
                FALSE
            );

            $popup_message_parameters = array(
                'title' => $message_title,
                'body' => $message_body,
                'url' => $message_url,
                'target' => $this->config->get('target') ? $this->config->get('target') : '_blank',
                'check_cookie' => $this->config->get('check_cookie') ? $this->config->get('check_cookie') : 0,
                'width' => $this->config->get('width') ? $this->config->get('width') : 300,
                'height' => $this->config->get('height') ? $this->config->get('height') : 300,
                'delay' => $this->config->get('delay') ? $this->config->get('delay') : 0,
            );
            // Allow other modules to modiffy message parameters.
            \Drupal::moduleHandler()
                ->alter('popup_message_perameters', $popup_message_parameters);

            if ($message_title && $message_body) {
                $attachments = $response->getAttachments();
                $attachments['library'][] = 'popup_message/popup_message_style';
                $attachments['drupalSettings']['popupMessage'] = $popup_message_parameters;
                $response->setAttachments($attachments);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $events[KernelEvents::RESPONSE][] = array('showPopupMessage', 20);

        return $events;
    }
}
