<?php
/*
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Contactform extends Module implements WidgetInterface
{
    protected $contact;
    protected $customer_thread;

    public function __construct()
    {
        $this->name = 'contactform';
        $this->author = 'PrestaShop';
        $this->tab = 'front_office_features';
        $this->version = '3.0.0';

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Contact form', array(), 'Modules.Contactform.Admin');
        $this->description = $this->trans('Adds a contact form to the "Contact us" page.', array(), 'Modules.Contactform.Admin');
        $this->ps_versions_compliancy = array('min' => '1.7.2.0', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        return parent::install()
        && $this->registerHook('displayBeforeBodyClosingTag')
        && $this->registerHook('displayHeader');
    }

    public function hookDisplayHeader($hook_args)
    {
        if ($this->context->controller->php_self === 'contact') {
            $this->context->controller->registerJavascript('captcha.js', 'https://www.google.com/recaptcha/api.js?onload=cbRecaptcha&render=explicit', array('server' => 'remote'));
            $this->context->controller->registerJavascript('modules-recaptcha', 'modules/'.$this->name.'/views/js/recaptcha.js', ['position' => 'bottom', 'priority' => 150]);
        }
    }

    public function getContent()
    {
        if(!$this->isRegisteredInHook('displayHeader')) {
            $this->registerHook('displayHeader');
        }

        if(!$this->isRegisteredInHook('displayBeforeBodyClosingTag')) {
            $this->registerHook('displayBeforeBodyClosingTag');
        }

        
        $output = null;
        if (Tools::isSubmit('submit'.$this->name)) {
            $conv_value = strval(Tools::getValue('PR_CAPTCHA_SECRET'));
            if (!$conv_value || empty($conv_value)) {
                $output .= $this->displayError($this->trans('Invalid Configuration value', array(), 'Modules.Contactform.Shop'));
            } else {
                Configuration::updateValue('PR_CAPTCHA_SECRET', $conv_value);
                $output .= $this->displayConfirmation($this->trans('Settings updated', array(), 'Modules.Contactform.Shop'));
            }

            $conv_value = strval(Tools::getValue('PR_CAPTCHA_PUBLIC'));
            if (!$conv_value || empty($conv_value)) {
                $output .= $this->displayError($this->trans('Invalid Configuration value', array(), 'Modules.Contactform.Shop'));
            } else {
                Configuration::updateValue('PR_CAPTCHA_PUBLIC', $conv_value);
                $output .= $this->displayConfirmation($this->trans('Settings updated', array(), 'Modules.Contactform.Shop'));
            }
        }

        $output .= '<div style="font-size: 1.3em; padding: 15px;">';
        $output .= '<p>'.$this->trans('This module adds Google reCaptcha scripts to your site. Your site must have google API access to use ReCaptcha', array(), 'Modules.Contactform.Shop').'</p>';
        $output .= '<p>'.$this->trans('You can obtain them from this URL', array(), 'Modules.Contactform.Shop').' <a href="https://www.google.com/recaptcha/admin">https://www.google.com/recaptcha/admin</a></p>';
        $output .= '</div>';

        return $this->renderForm().$output;
    }

    public function renderForm()
    {

        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->trans('Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->trans('Site Key', array(), 'Modules.Contactform.Shop'),
                    'name' => 'PR_CAPTCHA_PUBLIC',
                    'size' => 60,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->trans('Secret Key', array(), 'Modules.Contactform.Shop'),
                    'name' => 'PR_CAPTCHA_SECRET',
                    'size' => 60,
                    'required' => true
                )
            ),
            'submit' => array(
                'title' => $this->trans('Save'),
                'class' => 'button btn btn-default pull-right'
            )
        );
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' => array(
                'desc' => $this->trans('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->trans('Back to list')
            )
        );
        $helper->fields_value['PR_CAPTCHA_SECRET'] = Configuration::get('PR_CAPTCHA_SECRET');
        $helper->fields_value['PR_CAPTCHA_PUBLIC'] = Configuration::get('PR_CAPTCHA_PUBLIC');

        return $helper->generateForm($fields_form);
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        if($hookName == 'displayBeforeBodyClosingTag'){
            $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
            return $this->display(__FILE__, 'views/templates/widget/recaptchafield.tpl');

        }else{

        $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        return $this->display(__FILE__, 'views/templates/widget/contactform.tpl');
        }
    }

    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        $notifications = false;
        if (Tools::isSubmit('submitMessage')) {
            $this->sendMessage();

            if (!empty($this->context->controller->errors)) {
                $notifications['messages'] = $this->context->controller->errors;
                $notifications['nw_error'] = true;
            } elseif (!empty($this->context->controller->success)) {
                $notifications['messages'] = $this->context->controller->success;
                $notifications['nw_error'] = false;
            }
        }

        if (($id_customer_thread = (int)Tools::getValue('id_customer_thread')) && $token = Tools::getValue('token')) {
            $cm = new CustomerThread($id_customer_thread);
            if ($cm->token == $token) {
                $this->customer_thread = $this->context->controller->objectPresenter->present($cm);
                $order = new Order((int)$this->customer_thread['id_order']);
                if (Validate::isLoadedObject($order)) {
                    $customer_thread['reference'] = $order->getUniqReference();
                }
            }
        }

        $this->contact['contacts'] = $this->getTemplateVarContact();
        $this->contact['message'] = html_entity_decode(Tools::getValue('message'));
        $this->contact['allow_file_upload'] = (bool) Configuration::get('PS_CUSTOMER_SERVICE_FILE_UPLOAD');

        if (!(bool)Configuration::isCatalogMode()) {
            $this->contact['orders'] = $this->getTemplateVarOrders();
        } else {
            $this->contact['orders'] = array();
        }

        if ($this->customer_thread['email']) {
            $this->contact['email'] = $this->customer_thread['email'];
        } else {
            $this->contact['email'] = Tools::safeOutput(Tools::getValue('from', ((isset($this->context->cookie) && isset($this->context->cookie->email) && Validate::isEmail($this->context->cookie->email)) ? $this->context->cookie->email : '')));
        }

        return [
            'contact' => $this->contact,
            'notifications' => $notifications,
            'recap_public' => Configuration::get('PR_CAPTCHA_PUBLIC')
        ];
    }

    public function getTemplateVarContact()
    {
        $contacts = array();
        $all_contacts = Contact::getContacts($this->context->language->id);

        foreach ($all_contacts as $one_contact_id => $one_contact) {
            $contacts[$one_contact['id_contact']] = $one_contact;
        }

        if ($this->customer_thread['id_contact']) {
            return [$contacts[$this->customer_thread['id_contact']]];
        }

        return $contacts;
    }

    public function getTemplateVarOrders()
    {
        $orders = array();

        if (!isset($this->customer_thread['id_order']) && $this->context->customer->isLogged()) {
            $customer_orders = Order::getCustomerOrders($this->context->customer->id);
            foreach ($customer_orders as $customer_order) {
                $myOrder = new Order((int)$customer_order['id_order']);
                if (Validate::isLoadedObject($myOrder)) {
                    $orders[$customer_order['id_order']] = $customer_order;
                    $orders[$customer_order['id_order']]['products'] = $myOrder->getProducts();
                }
            }
        } elseif ((int)$this->customer_thread['id_order'] > 0) {
            $myOrder = new Order($this->customer_thread['id_order']);
            if (Validate::isLoadedObject($myOrder)) {
                $orders[$myOrder->id] = $this->context->controller->objectPresenter->present($myOrder);
                $orders[$myOrder->id]['id_order'] = $myOrder->id;
                $orders[$myOrder->id]['products'] = $myOrder->getProducts();
            }
        }

        if ($this->customer_thread['id_product']) {
            $id_order = 0;
            if (isset($this->customer_thread['id_order'])) {
                $id_order = (int)$this->customer_thread['id_order'];
            }
            $orders[$id_order]['products'][(int)$this->customer_thread['id_product']] = $this->context->controller->objectPresenter->present(new Product((int)$this->customer_thread['id_product']));
        }

        return $orders;
    }

    public function verifyReCaptcha($param)
    {
        /**
         * Taken from: https://github.com/google/recaptcha/blob/master/src/ReCaptcha/RequestMethod/Post.php
         * PHP 5.6.0 changed the way you specify the peer name for SSL context options.
         * Using "CN_name" will still work, but it will raise deprecated errors.
         */
        $peer_key = version_compare(PHP_VERSION, '5.6.0', '<') ? 'CN_name' : 'peer_name';
        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($param),
                // Force the peer to validate (not needed in 5.6.0+, but still works)
                'verify_peer' => true,
                // Force the peer validation to use www.google.com
                $peer_key => 'www.google.com',
            ),
        );
        $context = stream_context_create($options);
        return json_decode(file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context), true);
    }

    public function reCapchaErrorTrnslate($error_code)
    {
        $r = array(
            'invalid-input-secret' => $this->trans('The secret parameter is missing.', array(), 'Modules.Contactform.Shop'),
            'missing-input-response' => $this->trans('The secret parameter is invalid or malformed.', array(), 'Modules.Contactform.Shop'),
            'invalid-input-response' => $this->trans('The response parameter is missing.', array(), 'Modules.Contactform.Shop'),
            'bad-request' => $this->trans('The response parameter is invalid or malformed.', array(), 'Modules.Contactform.Shop'),
            'missing-input-secret' => $this->trans('The request is invalid or malformed', array(), 'Modules.Contactform.Shop'),
            'timeout-or-duplicate' => $this->trans('You have already submited this form or waited too long to submit it. Refresh page first', array(), 'Modules.Contactform.Shop')
        );
        return $r[$error_code];
    }

    public function sendMessage()
    {
        $extension = array('.txt', '.rtf', '.doc', '.docx', '.pdf', '.zip', '.png', '.jpeg', '.gif', '.jpg');
        $file_attachment = Tools::fileAttachment('fileUpload');
        $message = Tools::getValue('message');

        if (Configuration::get('PR_CAPTCHA_PUBLIC') && Configuration::get('PR_CAPTCHA_SECRET')) {
            $response = $this->verifyReCaptcha(array(
                'secret' => Configuration::get('PR_CAPTCHA_SECRET'),
                'response' => Tools::getValue('g-recaptcha-response'),
                'remoteip' => $_SERVER["REMOTE_ADDR"],
            ));
        }

        if (isset($response) && isset($response['success']) && !$response['success']) {
            $this->context->controller->errors[] = Tools::displayError($this->trans('Le captcha n\'est pas validé', array(), 'Modules.Contactform.Shop'));
            // foreach ($response['error-codes'] as $erc) {
            //     $this->context->controller->errors[] = $this->reCapchaErrorTrnslate($erc);
            // }
        } elseif (!($from = trim(Tools::getValue('from'))) || !Validate::isEmail($from)) {
            $this->context->controller->errors[] = $this->trans('Invalid email address.', array(), 'Shop.Notifications.Error');
        } elseif (!$message) {
            $this->context->controller->errors[] = $this->trans('The message cannot be blank.', array(), 'Shop.Notifications.Error');
        } elseif (!Validate::isCleanHtml($message)) {
            $this->context->controller->errors[] = $this->trans('Invalid message', array(), 'Shop.Notifications.Error');
        } elseif (!($id_contact = (int)Tools::getValue('id_contact')) || !(Validate::isLoadedObject($contact = new Contact($id_contact, $this->context->language->id)))) {
            $this->context->controller->errors[] = $this->trans('Please select a subject from the list provided. ', array(), 'Modules.Contactform.Shop');
        } elseif (!empty($file_attachment['name']) && $file_attachment['error'] != 0) {
            $this->context->controller->errors[] = $this->trans('An error occurred during the file-upload process.', array(), 'Modules.Contactform.Shop');
        } elseif (!empty($file_attachment['name']) && !in_array(Tools::strtolower(substr($file_attachment['name'], -4)), $extension) && !in_array(Tools::strtolower(substr($file_attachment['name'], -5)), $extension)) {
            $this->context->controller->errors[] = $this->trans('Bad file extension', array(), 'Modules.Contactform.Shop');
        } else {
            $customer = $this->context->customer;
            if (!$customer->id) {
                $customer->getByEmail($from);
            }

            $id_order = (int)Tools::getValue('id_order');

            $id_customer_thread = CustomerThread::getIdCustomerThreadByEmailAndIdOrder($from, $id_order);

            if ($contact->customer_service) {
                if ((int)$id_customer_thread) {
                    $ct = new CustomerThread($id_customer_thread);
                    $ct->status = 'open';
                    $ct->id_lang = (int)$this->context->language->id;
                    $ct->id_contact = (int)$id_contact;
                    $ct->id_order = (int)$id_order;
                    if ($id_product = (int)Tools::getValue('id_product')) {
                        $ct->id_product = $id_product;
                    }
                    $ct->update();
                } else {
                    $ct = new CustomerThread();
                    if (isset($customer->id)) {
                        $ct->id_customer = (int)$customer->id;
                    }
                    $ct->id_shop = (int)$this->context->shop->id;
                    $ct->id_order = (int)$id_order;
                    if ($id_product = (int)Tools::getValue('id_product')) {
                        $ct->id_product = $id_product;
                    }
                    $ct->id_contact = (int)$id_contact;
                    $ct->id_lang = (int)$this->context->language->id;
                    $ct->email = $from;
                    $ct->status = 'open';
                    $ct->token = Tools::passwdGen(12);
                    $ct->add();
                }

                if ($ct->id) {

                    $lastMessage = CustomerMessage::getLastMessageForCustomerThread($ct->id);
                    $testFileUpload = (isset($file_attachment['rename']) && !empty($file_attachment['rename']));

                    // if last message is the same as new message (and no file upload), do not consider this contact
                    if ($lastMessage != $message || $testFileUpload) {
                        $cm = new CustomerMessage();
                        $cm->id_customer_thread = $ct->id;
                        $cm->message = $message;
                        if ($testFileUpload && rename($file_attachment['tmp_name'], _PS_UPLOAD_DIR_ . basename($file_attachment['rename']))) {
                            $cm->file_name = $file_attachment['rename'];
                            @chmod(_PS_UPLOAD_DIR_ . basename($file_attachment['rename']), 0664);
                        }
                        $cm->ip_address = (int)ip2long(Tools::getRemoteAddr());
                        $cm->user_agent = $_SERVER['HTTP_USER_AGENT'];
                        if (!$cm->add()) {
                            $this->context->controller->errors[] = $this->trans('An error occurred while sending the message.', array(), 'Modules.Contactform.Shop');
                        }
                    } else {
                        $mailAlreadySend = true;
                    }
                } else {
                    $this->context->controller->errors[] = $this->trans('An error occurred while sending the message.', array(), 'Modules.Contactform.Shop');
                }
            }

            if (!count($this->context->controller->errors) && empty($mailAlreadySend)) {
                $var_list = [
                    '{order_name}' => '-',
                    '{attached_file}' => '-',
                    '{message}' => Tools::nl2br(stripslashes($message)),
                    '{email}' =>  $from,
                    '{product_name}' => '',
                ];

                if (isset($file_attachment['name'])) {
                    $var_list['{attached_file}'] = $file_attachment['name'];
                }

                $id_product = (int)Tools::getValue('id_product');

                if (isset($ct) && Validate::isLoadedObject($ct) && $ct->id_order) {
                    $order = new Order((int)$ct->id_order);
                    $var_list['{order_name}'] = $order->getUniqReference();
                    $var_list['{id_order}'] = (int)$order->id;
                }

                if ($id_product) {
                    $product = new Product((int)$id_product);
                    if (Validate::isLoadedObject($product) && isset($product->name[Context::getContext()->language->id])) {
                        $var_list['{product_name}'] = $product->name[Context::getContext()->language->id];
                    }
                }

                if (empty($contact->email)) {
                    Mail::Send(
                        $this->context->language->id,
                        'contact_form',
                        ((isset($ct) && Validate::isLoadedObject($ct)) ? $this->trans('Your message has been correctly sent #ct%thread_id% #tc%thread_token%', array('%thread_id%' => $ct->id, '%thread_token%' => $ct->token), 'Emails.Subject') : $this->trans('Your message has been correctly sent', array(), 'Emails.Subject')),
                        $var_list,
                        $from,
                        null,
                        null,
                        null,
                        $file_attachment
                    );
                } else {
                    if (!Mail::Send(
                        $this->context->language->id,
                        'contact',
                        $this->trans('Message from contact form', array(), 'Emails.Subject').' [no_sync]',
                        $var_list,
                        $contact->email,
                        $contact->name,
                        null,
                        null,
                        $file_attachment,
                        null,
                        _PS_MAIL_DIR_,
                        false,
                        null,
                        null,
                        $from
                    ) || !Mail::Send(
                        $this->context->language->id,
                        'contact_form',
                        ((isset($ct) && Validate::isLoadedObject($ct)) ? $this->trans('Your message has been correctly sent #ct%thread_id% #tc%thread_token%', array('%thread_id%' => $ct->id, '%thread_token%' => $ct->token), 'Emails.Subject') : $this->trans('Your message has been correctly sent', array(), 'Emails.Subject')),
                        $var_list,
                        $from,
                        null,
                        null,
                        null,
                        $file_attachment,
                        null,
                        _PS_MAIL_DIR_,
                        false,
                        null,
                        null,
                        $contact->email
                    )) {
                        $this->context->controller->errors[] = $this->trans('An error occurred while sending the message.', array(), 'Modules.Contactform.Shop');
                    }
                }
            }

            if (!count($this->context->controller->errors)) {
                $this->context->controller->success[] = $this->trans('Your message has been successfully sent to our team.', array(), 'Modules.Contactform.Shop');
            }
        }
    }
}
