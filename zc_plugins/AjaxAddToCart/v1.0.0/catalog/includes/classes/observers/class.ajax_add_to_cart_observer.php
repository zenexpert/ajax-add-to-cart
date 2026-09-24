<?php
declare(strict_types=1);

use Zencart\DbRepositories\PluginControlRepository;
use Zencart\DbRepositories\PluginControlVersionRepository;
use Zencart\PluginManager\PluginManager;

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

class ajax_add_to_cart_observer extends base
{
    protected string $zcPluginDir;
    protected string $logFile;

    public function __construct()
    {
        if (!defined('AJAX_ADD_TO_CART_STATUS') || AJAX_ADD_TO_CART_STATUS !== 'true' || IS_ADMIN_FLAG === true) {
            return;
        }

        $this->logFile = DIR_FS_LOGS . '/ajax_add_to_cart_debug.log';

        global $db;
        $plugin_manager = new PluginManager(new PluginControlRepository($db), new PluginControlVersionRepository($db));
        $this->zcPluginDir = str_replace(
            DIR_FS_CATALOG,
            '',
            $plugin_manager->getPluginVersionDirectory('AjaxAddToCart', $plugin_manager->getInstalledPlugins()) . 'catalog/'
        );

        $this->attach($this, [
            'NOTIFY_HTML_HEAD_END',
            'NOTIFY_ZEN_REDIRECT'
        ]);
    }

    public function update(&$class, string $eventID, $p1 = null, $p2 = null, $p3 = null, $p4 = null): void
    {
        if ($eventID === 'NOTIFY_HTML_HEAD_END') {
            global $template, $current_page_base;

            $stylesheet = 'ajax_add_to_cart.css';
            $css_base_file = $this->getZcPluginDir() . DIR_WS_TEMPLATES . "default/css/$stylesheet";
            if (file_exists(DIR_FS_CATALOG . $css_base_file)) {
                echo '<link rel="stylesheet" href="' . $css_base_file . '">' . "\n";
            }

            if (isset($template) && is_object($template)) {
                $stylesheet_dir = $template->get_template_dir($stylesheet, DIR_WS_TEMPLATE, $current_page_base, 'css') . '/' . $stylesheet;
                if (!str_contains($stylesheet_dir, $this->getZcPluginDir()) && file_exists(DIR_FS_CATALOG . $stylesheet_dir)) {
                    echo '<link rel="stylesheet" href="' . $stylesheet_dir . '">' . "\n";
                }
            }

            $js_file = DIR_FS_CATALOG . $this->getZcPluginDir() . DIR_WS_TEMPLATES . 'default/jscript/ajax_add_to_cart_jscript.php';
            if (file_exists($js_file)) {
                require $js_file;
            }
        }

        if ($eventID === 'NOTIFY_ZEN_REDIRECT') {
            $isAjax = (!empty($_POST['ajax_add_to_cart']) || !empty($_GET['ajax_add_to_cart']));

            if ($isAjax) {
                global $currencies;

                $success = true;
                $messages = [];

                if (isset($_SESSION['messageToStack']) && is_array($_SESSION['messageToStack'])) {
                    foreach ($_SESSION['messageToStack'] as $key => $message) {
                        if (isset($message['type']) && in_array($message['type'], ['error', 'warning', 'caution'], true)) {
                            $success = false;
                        }
                        if (isset($message['text']) && $message['type'] !== 'success') {
                            $messages[] = strip_tags($message['text']);
                        }
                        unset($_SESSION['messageToStack'][$key]);
                    }
                    $_SESSION['messageToStack'] = array_values($_SESSION['messageToStack']);
                }

                if ($success) {
                    $added_products = [];

                    if (!empty($_POST['products_id']) && is_array($_POST['products_id'])) {
                        foreach ($_POST['products_id'] as $k => $v) {
                            // Multiple Products via Quantity Boxes (Key = Product ID, Value = Quantity)
                            if (is_numeric($k) && (int)$k > 0) {
                                if (is_numeric($v) && (float)$v > 0) {
                                    $added_products[(int)$k] = (int)$k;
                                }
                            }
                            // Multiple Products via Checkboxes (Key = sequential array index, Value = Product ID)
                            elseif (is_numeric($v) && (int)$v > 0) {
                                $qty = 1;
                                if (isset($_POST['cart_quantity']) && is_array($_POST['cart_quantity']) && isset($_POST['cart_quantity'][$v])) {
                                    $qty = (float)$_POST['cart_quantity'][$v];
                                }
                                if ($qty > 0) {
                                    $added_products[(int)$v] = (int)$v;
                                }
                            }
                        }
                    }
                    // Standard Single Product Forms
                    elseif (!empty($_POST['products_id'])) {
                        $added_products[(int)$_POST['products_id']] = (int)$_POST['products_id'];
                    }
                    // Direct "Buy Now" links
                    elseif (!empty($_GET['products_id'])) {
                        $added_products[(int)$_GET['products_id']] = (int)$_GET['products_id'];
                    }

                    $productNames = [];
                    foreach ($added_products as $pID) {
                        $name = zen_get_products_name($pID);
                        if (!empty($name)) {
                            $productNames[] = zen_output_string_protected($name);
                        }
                    }

                    if (!empty($productNames)) {
                        $productNamesList = '<b>' . implode('</b>, <b>', $productNames) . '</b>';
                        $messages[] = sprintf(AJAX_ADD_TO_CART_SUCCESS_ADDED, $productNamesList);
                    } else {
                        $messages[] = AJAX_ADD_TO_CART_SUCCESS_MESSAGE;
                    }
                }

                // Generate the exact native HTML for the Quantity in Cart text
                $productQtyHtml = '';
                if (isset($_SESSION['cart'])) {
                    $target_pID = 0;
                    if (!empty($_POST['products_id'])) {
                        if (is_array($_POST['products_id'])) {
                            reset($_POST['products_id']);
                            $target_pID = (int)key($_POST['products_id']);
                        } else {
                            $target_pID = (int)$_POST['products_id'];
                        }
                    } elseif (!empty($_GET['products_id'])) {
                        $target_pID = (int)$_GET['products_id'];
                    }

                    if ($target_pID > 0) {
                        // Check switch for showing QTY on this specific product
                        $show_qty = function_exists('zen_get_show_product_switch') ? zen_get_show_product_switch($target_pID, 'in_cart_qty') : 1;

                        if ($show_qty == 1) {
                            // Sums up all attributes natively
                            $qty = $_SESSION['cart']->in_cart_mixed($target_pID);

                            if ($qty > 0) {
                                $prefix = defined('PRODUCTS_ORDER_QTY_TEXT_IN_CART') ? PRODUCTS_ORDER_QTY_TEXT_IN_CART : 'Quantity in Cart: ';
                                // Wrap it in the exact markup native templates use, but add a class so we can reliably target it
                                $productQtyHtml = '<p class="cartInCart"><strong>' . $prefix . $qty . '</strong></p>';
                            }
                        }
                    }
                }

                $sideboxData = $this->renderShoppingCartSidebox();

                $response = [
                    'success' => $success,
                    'messages' => $messages,
                    'cartCount' => isset($_SESSION['cart']) ? $_SESSION['cart']->count_contents() : 0,
                    'cartTotal' => (isset($_SESSION['cart']) && is_object($currencies)) ? strip_tags($currencies->format($_SESSION['cart']->show_total())) : 0,
                    'sideboxHtml' => $sideboxData['html'],
                    'sideboxColumn' => $sideboxData['column'],
                    'columnBoxes' => $sideboxData['columnBoxes'],
                    'productQtyHtml' => $productQtyHtml
                ];

                $p1 = null;
                $this->notify('NOTIFY_AJAX_ADD_TO_CART_FILTER_RESPONSE', $p1, $response);

                while (ob_get_level() > 0) {
                    if (!@ob_end_clean()) break;
                }

                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($response, JSON_INVALID_UTF8_SUBSTITUTE);
                exit;
            }
        }
    }

    private function renderShoppingCartSidebox(): array
    {
        global $db, $template, $currencies, $cart, $current_page_base, $request_type, $PHP_SELF, $language_page_directory, $template_dir, $tplSetting;

        $sideboxHtml = '';
        $sideboxColumn = 'left';
        $columnBoxes = [];

        if (isset($template) && is_object($template)) {
            $sidebox_module = DIR_FS_CATALOG . DIR_WS_MODULES . zen_get_module_directory('sideboxes/shopping_cart.php');

            if (file_exists($sidebox_module)) {
                $column_box_default = 'tpl_box_default_left.php';
                $column_width = defined('BOX_WIDTH_LEFT') ? BOX_WIDTH_LEFT : '150px';

                $tDir = isset($template_dir) ? $template_dir : 'default';

                $layout_sql = "SELECT layout_box_location FROM " . TABLE_LAYOUT_BOXES . "
                               WHERE layout_box_name = 'shopping_cart.php' AND layout_template = '" . zen_db_input($tDir) . "' LIMIT 1";
                $layout_check = $db->Execute($layout_sql);

                if ($layout_check->EOF) {
                    $layout_sql = "SELECT layout_box_location FROM " . TABLE_LAYOUT_BOXES . "
                                   WHERE layout_box_name = 'shopping_cart.php' AND layout_template = 'default' LIMIT 1";
                    $layout_check = $db->Execute($layout_sql);
                    $tDir = 'default';
                }

                $my_location = 0;
                if (!$layout_check->EOF) {
                    $my_location = (int)$layout_check->fields['layout_box_location'];
                    if ($my_location === 1) {
                        $column_box_default = 'tpl_box_default_right.php';
                        $column_width = defined('BOX_WIDTH_RIGHT') ? BOX_WIDTH_RIGHT : '150px';
                        $sideboxColumn = 'right';
                    }
                }

                $all_boxes_sql = "SELECT layout_box_name FROM " . TABLE_LAYOUT_BOXES . "
                                  WHERE layout_template = '" . zen_db_input($tDir) . " '
                                  AND layout_box_location = " . $my_location . "
                                  AND layout_box_status = 1
                                  ORDER BY layout_box_sort_order ASC";
                $all_boxes = $db->Execute($all_boxes_sql);
                foreach ($all_boxes as $box) {
                    $columnBoxes[] = '#' . str_replace(['_', '.php'], '', $box['layout_box_name']);
                }

                $box_id = str_replace(['_', '.php'], '', 'shopping_cart.php');

                ob_start();
                try {
                    require($sidebox_module);
                } catch (\Throwable $e) {
                }
                $sideboxHtml = ob_get_clean();
            }
        }

        return [
            'html' => $sideboxHtml,
            'column' => $sideboxColumn,
            'columnBoxes' => $columnBoxes
        ];
    }

    public function getZcPluginDir(): string
    {
        return $this->zcPluginDir;
    }
}
