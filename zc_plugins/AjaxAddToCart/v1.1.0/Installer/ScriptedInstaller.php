<?php
declare(strict_types=1);

use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

class ScriptedInstaller extends ScriptedInstallBase
{
    protected function executeInstall()
    {
        $cgi = $this->getConfigGroupId('Ajax Add To Cart', 'Configuration settings for the Ajax Add To Cart plugin');
        $this->installConfigurationKeys($cgi);

        if (function_exists('zen_register_admin_page')) {
            zen_deregister_admin_pages(['configAjaxAddToCart']);
            zen_register_admin_page('configAjaxAddToCart', 'BOX_CONFIGURATION_AJAX_ADD_TO_CART', 'FILENAME_CONFIGURATION', "gID=$cgi", 'configuration', 'Y');
        }

        parent::executeInstall();
        return true;
    }

    protected function executeUpgrade($oldVersion)
    {
        $cgi = $this->getConfigGroupId('Ajax Add To Cart', 'Configuration settings for the Ajax Add To Cart plugin');
        $this->installConfigurationKeys($cgi);

        return true;
    }

    protected function executeUninstall()
    {
        if (function_exists('zen_deregister_admin_pages')) {
            zen_deregister_admin_pages(['configAjaxAddToCart']);
        }

        $cgi = $this->getConfigGroupId('Ajax Add To Cart', 'Configuration settings for the Ajax Add To Cart plugin');

        $sql = "DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_group_id = $cgi";
        $this->executeInstallerSql($sql);

        $sql = "DELETE FROM " . TABLE_CONFIGURATION_GROUP . " WHERE configuration_group_id = $cgi LIMIT 1";
        $this->executeInstallerSql($sql);

        parent::executeUninstall();
    }

    protected function installConfigurationKeys(int $cgi): void
    {
        $sql = "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function)
         VALUES
            ('Enable Ajax Add To Cart', 'AJAX_ADD_TO_CART_STATUS', 'true', 'Enable or disable the Ajax Add To Cart functionality?', $cgi, 1, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),
            ('Form Selector', 'AJAX_ADD_TO_CART_FORM_SELECTOR', 'form[action*=\'action=add_product\'], form[action*=\'action=buy_now\'], form[action*=\'action=multiple_products_add_product\']', 'jQuery selector for the add to cart forms.', $cgi, 2, now(), NULL, NULL),
            ('Button Selector', 'AJAX_ADD_TO_CART_BUTTON_SELECTOR', 'button[type=\"submit\"], input[type=\"submit\"], input[type=\"image\"]', 'jQuery selector for the submit buttons inside the forms.', $cgi, 3, now(), NULL, NULL),
            ('Link Selector', 'AJAX_ADD_TO_CART_LINK_SELECTOR', 'a[href*=\'action=buy_now\']', 'jQuery selector for direct buy now links.', $cgi, 4, now(), NULL, NULL),
            ('Cart Count Selector', 'AJAX_ADD_TO_CART_CART_COUNT_SELECTOR', '.cart-count', 'jQuery selector for elements displaying the cart item count (leave empty if none).', $cgi, 5, now(), NULL, NULL),
            ('Cart Total Selector', 'AJAX_ADD_TO_CART_CART_TOTAL_SELECTOR', '.cart-total', 'jQuery selector for elements displaying the cart total amount (leave empty if none).', $cgi, 6, now(), NULL, NULL),
            ('Sidebox Selector', 'AJAX_ADD_TO_CART_SIDEBOX_SELECTOR', '#shopping-cart, #shoppingcart', 'jQuery selector for the shopping cart sidebox wrapper.', $cgi, 8, now(), NULL, NULL),
            ('In-Cart Selector', 'AJAX_ADD_TO_CART_IN_CART_SELECTOR', '.cartInCart, #cartAdd p', 'jQuery selector for the Quantity in Cart element on the product info page.', $cgi, 9, now(), NULL, NULL),
            ('In-Cart Parent', 'AJAX_ADD_TO_CART_IN_CART_PARENT', '#cartAdd', 'jQuery selector for the parent wrapper where the Quantity in Cart element should be injected if it does not already exist.', $cgi, 10, now(), NULL, NULL),
            ('Inject Header Cart Links', 'AJAX_ADD_TO_CART_INJECT_HEADER_LINKS', 'true', 'When the cart was empty, inject the active template\'s native Shopping Cart and Checkout header links after an Ajax add. After a full page reload the template renders them itself.', $cgi, 11, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),
            ('Header Nav Selector', 'AJAX_ADD_TO_CART_HEADER_NAV_SELECTOR', '#navMain ul, ul.navbar-nav', 'jQuery selector for the header navigation list where Shopping Cart and Checkout links should be injected if they are not already present.', $cgi, 12, now(), NULL, NULL),
            ('Header Cart Link Selector', 'AJAX_ADD_TO_CART_HEADER_CART_SELECTOR', 'a[href*=\'shopping_cart\'], a.navCartContentsIndicator', 'jQuery selector used to detect whether the header already contains a Shopping Cart link. If a match is found, nothing is injected.', $cgi, 13, now(), NULL, NULL)";

        $this->executeInstallerSql($sql);
    }

    protected function getConfigGroupId(string $config_group_title, string $config_group_description): int
    {
        $sql = "SELECT configuration_group_id
                FROM " . TABLE_CONFIGURATION_GROUP . "
                WHERE configuration_group_title = '" . $this->dbConn->prepare_input($config_group_title) . "'
                LIMIT 1";

        $check = $this->executeInstallerSelectSql($sql);
        if (!$check->EOF) {
            return (int)$check->fields['configuration_group_id'];
        }

        $sql = "INSERT INTO " . TABLE_CONFIGURATION_GROUP . "
                (configuration_group_title, configuration_group_description, sort_order, visible)
                VALUES ('" . $this->dbConn->prepare_input($config_group_title) . "', '" . $this->dbConn->prepare_input($config_group_description) . "', 1, 1)";
        $this->executeInstallerSql($sql);

        $sql = "SELECT configuration_group_id FROM " . TABLE_CONFIGURATION_GROUP . " WHERE configuration_group_title = '" . $this->dbConn->prepare_input($config_group_title) . "' LIMIT 1";
        $config_group = $this->executeInstallerSelectSql($sql);
        $cgi = (int)$config_group->fields['configuration_group_id'];

        $sql = "UPDATE " . TABLE_CONFIGURATION_GROUP . " SET sort_order = $cgi WHERE configuration_group_id = $cgi LIMIT 1";
        $this->executeInstallerSql($sql);

        return $cgi;
    }

    protected function executeInstallerSelectSql(string $sql)
    {
        $this->dbConn->dieOnErrors = false;
        $result = $this->dbConn->Execute($sql);
        if ($this->dbConn->error_number !== 0) {
            if (isset($this->errorContainer)) {
                $this->errorContainer->addError(0, $this->dbConn->error_text, true, PLUGIN_INSTALL_SQL_FAILURE);
            }
            return false;
        }
        $this->dbConn->dieOnErrors = true;
        return $result;
    }
}
