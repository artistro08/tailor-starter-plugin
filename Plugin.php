<?php
namespace Artistro08\TailorStarterCompanion;

use Backend;
use Event;
use Log;
use Validator;
use Mail;
use System\Classes\PluginBase;
use Tailor\Models\EntryRecord;
use Tailor\Models\GlobalRecord;
use Exception;
use View;
use Illuminate\Support\Facades\Config;

/**
 * Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Tailor Starter',
            'description' => 'A companion plugin to go with the Tailor Starter Theme.',
            'author'      => 'Artistro08',
            'icon'        => 'icon-archive'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {
        $this->app->resolving('validator', function ($validator) {
            Validator::extend('recaptcha', 'Artistro08\TailorStarterCompanion\Classes\ReCaptchaValidator@validateReCaptcha', 'Recaptcha validation failed. Please refresh and try again.');
        });
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {
        EntryRecord::extend(function ($model) {
            $model->bindEvent('model.afterSave', function () use ($model) {
                try {
                    if ($model->inSection('Content\Orders')) {

                        // Get the settings from the site
                        $settings = GlobalRecord::findForGlobal('Content\Settings');

                        // Get the order statuses
                        $order_status     = $model->order_status;
                        $sent_receipt     = $model->sent_email_receipt;
                        $sent_in_progress = $model->sent_in_progress;
                        $sent_cancelled   = $model->sent_cancelled;
                        $sent_tracking    = $model->sent_tracking_receipt;
                        $resend_email     = $model->resend_email;

                        // Remove Tailor ID from metadata
                        $unwanted_words = 'tailor_id';
                        $replace_match  = '/^.*' . $unwanted_words . '.*$(?:\r\n|\n)?/m';
                        $order_contents = preg_replace($replace_match, '', $model->order_contents);

                        // Set Mail Order Data
                        View::share('site_name', $settings->website_name);
                        $mail_data = [
                            'customer_name'        => $model->customer_name,
                            'customer_email'       => $model->customer_email,
                            'shipping_method'      => $model->shipping_method,
                            'customer_address'     => $model->customer_address,
                            'tracking_number'      => $model->tracking_number,
                            'tracking_url'         => $model->tracking_url,
                            'cancellation_message' => $model->cancellation_message,
                            'total'                => $model->total,
                            'order_contents'       => $order_contents,
                        ];

                        // New Order
                        if ($order_status == 'new' && ! $sent_receipt) {

                            // Send Customer Email
                            Mail::send('artistro08.tailorstartercompanion::mail.new_order', $mail_data, function ($message) use ($model) {
                                $message->to($model->customer_email, $model->customer_name);
                            });

                            // Send Admin Email
                            Mail::send('artistro08.tailorstartercompanion::mail.new_order_admin', $mail_data, function ($message) use ($settings, $model) {
                                $message->to($settings->notification_email, $model->notification_email_recipient_name);
                            });

                            $model->sent_email_receipt = true;
                            $model->save();
                        }

                        // Order in Progress
                        if ($order_status == 'in_progress' && ! $sent_in_progress) {

                            // Send Customer Email
                            Mail::send('artistro08.tailorstartercompanion::mail.order_in_progress', $mail_data, function ($message) use ($model) {
                                $message->to($model->customer_email, $model->customer_name);
                            });

                            $model->sent_in_progress = true;
                            $model->save();
                        }

                        // Shipped Order
                        if ($order_status == 'shipped' && ! $sent_tracking) {

                            // Send Customer Email
                            Mail::send('artistro08.tailorstartercompanion::mail.order_shipped', $mail_data, function ($message) use ($model) {
                                $message->to($model->customer_email, $model->customer_name);
                            });

                            $model->sent_tracking_receipt = true;
                            $model->save();
                        }

                        // Cancelled Order
                        if ($order_status == 'cancelled' && ! $sent_cancelled) {

                            // Send Customer Email
                            Mail::send('artistro08.tailorstartercompanion::mail.order_cancelled', $mail_data, function ($message) use ($model) {
                                $message->to($model->customer_email, $model->customer_name);
                            });

                            $model->sent_cancelled = true;
                            $model->save();
                        }

                        // Check if we need to resend any emails
                        if ($resend_email) {

                            // New Order
                            if ($order_status == 'new') {

                                // Send Customer Email
                                Mail::send('artistro08.tailorstartercompanion::mail.new_order', $mail_data, function ($message) use ($model) {
                                    $message->to($model->customer_email, $model->customer_name);
                                });

                                $model->resend_email = false;
                                $model->save();
                            }

                            // Order in Progress
                            if ($order_status == 'in_progress') {

                                // Send Customer Email
                                Mail::send('artistro08.tailorstartercompanion::mail.order_in_progress', $mail_data, function ($message) use ($model) {
                                    $message->to($model->customer_email, $model->customer_name);
                                });

                                $model->resend_email = false;
                                $model->save();
                            }

                            // Shipped Order
                            if ($order_status == 'shipped') {

                                // Send Customer Email
                                Mail::send('artistro08.tailorstartercompanion::mail.order_shipped', $mail_data, function ($message) use ($model) {
                                    $message->to($model->customer_email, $model->customer_name);
                                });

                                $model->resend_email = false;
                                $model->save();
                            }

                            // Cancelled Order
                            if ($order_status == 'cancelled') {

                                // Send Customer Email
                                Mail::send('artistro08.tailorstartercompanion::mail.order_cancelled', $mail_data, function ($message) use ($model) {
                                    $message->to($model->customer_email, $model->customer_name);
                                });

                                $model->resend_email = false;
                                $model->save();
                            }

                        }
                    }
                } catch (Exception $e) {
                    if (Config::get('app.debug') == '')
                        Log::info('Orders Model does not exist, skipping orders code');
                }
            });
        });

        // Hide Blocks if Shop, Events, or blog are disabled
        try {
            $settings      = GlobalRecord::findForGlobal('Content\Settings');
            $enable_shop   = $settings->enable_shop;
            $enable_events = $settings->enable_events;
            $enable_blog   = $settings->enable_blog;
            $enable_search = $settings->enable_search;

            Event::listen('backend.page.beforeDisplay', function ($controller, $action, $params) {
                if ($controller instanceof \Tailor\Controllers\Globals) {
                    $controller->addJs('/plugins/artistro08/tailorstartercompanion/assets/js/companion.js');
                }
            });

            if (! $enable_events)
                Event::listen('backend.page.beforeDisplay', function ($controller, $action, $params) {
                    $controller->addCss('/plugins/artistro08/tailorstartercompanion/assets/css/disable_events.css');
                    $controller->addJs('/plugins/artistro08/tailorstartercompanion/assets/js/disable_events.js');
                });

            if (! $enable_shop)
                Event::listen('backend.page.beforeDisplay', function ($controller, $action, $params) {
                    $controller->addCss('/plugins/artistro08/tailorstartercompanion/assets/css/disable_shop.css');
                    $controller->addJs('/plugins/artistro08/tailorstartercompanion/assets/js/disable_shop.js');
                });

            if (! $enable_blog)
                Event::listen('backend.page.beforeDisplay', function ($controller, $action, $params) {
                    $controller->addCss('/plugins/artistro08/tailorstartercompanion/assets/css/disable_blog.css');
                    $controller->addJs('/plugins/artistro08/tailorstartercompanion/assets/js/disable_blog.js');
                });

            if (! $enable_search)
                Event::listen('backend.page.beforeDisplay', function ($controller, $action, $params) {
                    $controller->addCss('/plugins/artistro08/tailorstartercompanion/assets/css/disable_search.css');
                    $controller->addJs('/plugins/artistro08/tailorstartercompanion/assets/js/disable_search.js');
                });

        } catch (Exception $e) {
            return null;
        }

    }

    public function registerMailTemplates()
    {
        return [
            'artistro08.tailorstartercompanion::mail.new_order',
            'artistro08.tailorstartercompanion::mail.new_order_admin',
            'artistro08.tailorstartercompanion::mail.order_cancelled',
            'artistro08.tailorstartercompanion::mail.order_in_progress',
            'artistro08.tailorstartercompanion::mail.order_shipped',
            'artistro08.tailorstartercompanion::mail.form_submission'
        ];
    }

    public function registerMarkupTags()
    {
        $filters = [
            'format_money' => function ($value) {
                // Get the settings from the site
                $settings = GlobalRecord::findForGlobal('Content\Settings');

                $currency_symbol           = $settings->currency_symbol ?? '$';
                $currency_symbol_placement = $settings->currency_symbol_placement ?? true;

                return ($currency_symbol_placement == "before" ? $currency_symbol : '') . number_format($value, 2, ".", ",") . ($currency_symbol_placement == "after" ? $currency_symbol : '');
            },
        ];

        return [
            'filters' => $filters,
        ];
    }
}
